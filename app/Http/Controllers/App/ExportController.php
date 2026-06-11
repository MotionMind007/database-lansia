<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Jobs\ExportCsvJob;
use App\Models\ExportFile;
use App\Models\SurveyResponse;
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
     * Download a previously generated export file using signed URL.
     */
    public function download(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link download tidak valid atau sudah expired.');
        }

        $exportFile = ExportFile::findOrFail((int) $request->query('export'));
        $ownerId = (int) $request->query('user');

        $user = $request->user();
        if ($ownerId !== (int) $exportFile->user_id) {
            abort(403, 'Link download tidak sesuai dengan pemilik file.');
        }

        if (! $user->hasAnyRole(['administrator', 'super admin', 'super_admin']) && $user->id !== (int) $exportFile->user_id) {
            abort(403, 'Anda tidak memiliki akses ke file ini.');
        }

        if ($exportFile->status !== ExportFile::STATUS_READY || $exportFile->file_deleted_at || $exportFile->expires_at?->isPast()) {
            abort(410, 'File export sudah expired.');
        }

        if (! Storage::disk($exportFile->disk)->exists($exportFile->path)) {
            abort(404);
        }

        $exportFile->forceFill(['last_downloaded_at' => now()])->save();

        return Storage::disk($exportFile->disk)->download($exportFile->path, basename($exportFile->path), [
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
