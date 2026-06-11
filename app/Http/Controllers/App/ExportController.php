<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\ExportCsvJob;
use App\Models\SurveyResponse;
use App\Support\SurveyResponseAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Threshold above which export is dispatched to background queue.
     * Configurable via EXPORT_ASYNC_THRESHOLD env variable.
     */
    private function asyncThreshold(): int
    {
        return max(100, (int) config('dashboard.export_async_threshold', 2000));
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $user = auth()->user();

        $query = SurveyResponse::query()
            ->select('survey_responses.*');

        // Role scoping
        if ($user->hasRole('surveyor') && ! $user->hasAnyRole(['administrator', 'super admin', 'super_admin'])) {
            $query->where('surveyor_id', $user->id);
        }

        // Filters
        $filters = [];
        if ($request->filled('status')) {
            $query->where('status', $request->status);
            $filters['status'] = $request->status;
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
            $filters['region_id'] = $request->region_id;
        }

        $count = (clone $query)->count();

        activity('export')
            ->causedBy($user)
            ->event('export_csv')
            ->withProperties([
                'format' => 'csv',
                'filters' => $filters,
                'row_count' => $count,
                'async' => $count > $this->asyncThreshold(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ])
            ->log('User export data lansia.');

        // Large dataset → dispatch to background job
        if ($count > $this->asyncThreshold()) {
            ExportCsvJob::dispatch($user->id, $filters);

            return redirect()->back()
                ->with('info', "Export {$count} baris sedang diproses di background. Anda akan mendapat notifikasi saat file siap.");
        }

        // Small dataset → stream directly
        $query->with(['respondent', 'surveyor', 'region']);

        return $this->exportCsv($query);
    }

    /**
     * Download a previously generated export file.
     * Only the file owner or administrator can download.
     */
    public function download(Request $request)
    {
        $path = $request->query('file');
        $disk = config('uploads.private_disk', 'local');

        if (! $path || ! str_starts_with($path, 'exports/') || str_contains($path, '..')) {
            abort(404);
        }

        if (! Storage::disk($disk)->exists($path)) {
            abort(404);
        }

        // Ownership check: filename pattern is exports/data_lansia_{userId}_{timestamp}.csv
        $user = auth()->user();
        if (! $user->hasAnyRole(['administrator', 'super admin', 'super_admin'])) {
            $ownerPattern = 'exports/data_lansia_'.$user->id.'_';
            if (! str_starts_with($path, $ownerPattern)) {
                abort(403, 'Anda tidak memiliki akses ke file ini.');
            }
        }

        return Storage::disk($disk)->download($path, basename($path), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportCsv(Builder $query): StreamedResponse
    {
        $filename = 'data_lansia_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            // Header
            fputcsv($handle, [
                'No',
                'No. Kuesioner',
                'Nama Lengkap',
                'Jenis Kelamin',
                'Umur',
                'Pendidikan',
                'Pekerjaan',
                'Alamat',
                'No. Kontak',
                'Agama',
                'Suku',
                'Status OAP',
                'Status RT',
                'Wilayah',
                'Surveyor',
                'Tanggal Wawancara',
                'Status Verifikasi',
                'Tanggal Submit',
                'Tanggal Verifikasi',
            ]);

            $rowNumber = 1;

            foreach ((clone $query)->lazyById(1000) as $resp) {
                $r = $resp->respondent;
                fputcsv($handle, [
                    $rowNumber++,
                    self::safeCsvValue($resp->questionnaire_number),
                    self::safeCsvValue($r?->full_name ?? '-'),
                    self::safeCsvValue($r?->gender === 'male' ? 'Laki-laki' : 'Perempuan'),
                    $r?->age ?? '-',
                    self::safeCsvValue($r?->education ?? '-'),
                    self::safeCsvValue($r?->occupation ?? '-'),
                    self::safeCsvValue($r?->address ?? '-'),
                    self::safeCsvValue($r?->phone ?? '-'),
                    self::safeCsvValue($r?->religion ?? '-'),
                    self::safeCsvValue($r?->ethnicity ?? '-'),
                    self::safeCsvValue($r?->citizenship_status ?? '-'),
                    self::safeCsvValue($r?->household_status ?? '-'),
                    self::safeCsvValue($resp->region?->name ?? '-'),
                    self::safeCsvValue($resp->surveyor?->name ?? '-'),
                    self::safeCsvValue($resp->interview_date?->format('d/m/Y') ?? '-'),
                    self::safeCsvValue($resp->status_label),
                    self::safeCsvValue($resp->submitted_at?->format('d/m/Y H:i') ?? '-'),
                    self::safeCsvValue($resp->verified_at?->format('d/m/Y H:i') ?? '-'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function safeCsvValue(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
