<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        $query = SurveyResponse::query()
            ->with(['respondent', 'surveyor', 'region'])
            ->select('survey_responses.*');

        // Role scoping
        $user = auth()->user();
        if ($user->hasRole('surveyor') && ! $user->hasAnyRole(['administrator', 'super admin', 'super_admin'])) {
            $query->where('surveyor_id', $user->id);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        if ($format === 'csv') {
            activity('export')
                ->causedBy($user)
                ->event('export_csv')
                ->withProperties([
                    'format' => 'csv',
                    'filters' => $request->only(['status', 'region_id']),
                    'ip' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                ])
                ->log('User export data lansia.');

            return $this->exportCsv($query);
        }

        return $this->exportCsv($query); // default to CSV for now
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
