<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');

        $query = SurveyResponse::with(['respondent', 'surveyor', 'region', 'answers']);

        // Role scoping
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        if ($role === 'surveyor') {
            $query->where('surveyor_id', $user->id);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $responses = $query->orderByDesc('created_at')->get();

        if ($format === 'csv') {
            return $this->exportCsv($responses);
        }

        return $this->exportCsv($responses); // default to CSV for now
    }

    private function exportCsv($responses): StreamedResponse
    {
        $filename = 'data_lansia_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($responses) {
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

            foreach ($responses as $i => $resp) {
                $r = $resp->respondent;
                fputcsv($handle, [
                    $i + 1,
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
