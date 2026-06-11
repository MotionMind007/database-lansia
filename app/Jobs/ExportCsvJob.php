<?php

namespace App\Jobs;

use App\Http\Controllers\App\ExportController;
use App\Models\ExportFile;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Notifications\ExportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportCsvJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        private readonly int $userId,
        private readonly array $filters = [],
    ) {
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $query = SurveyResponse::query()
            ->with(['respondent', 'surveyor', 'region'])
            ->select('survey_responses.*');

        // Role scoping
        if ($user->hasRole('surveyor') && ! $user->hasAnyRole(['administrator', 'super admin', 'super_admin'])) {
            $query->where('surveyor_id', $user->id);
        }

        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['region_id'])) {
            $query->where('region_id', $this->filters['region_id']);
        }

        $filename = 'exports/data_lansia_'.$user->id.'_'.date('Ymd_His').'.csv';
        $disk = config('uploads.private_disk', 'local');

        $tempPath = tempnam(sys_get_temp_dir(), 'export_');
        $handle = fopen($tempPath, 'w');

        // BOM for Excel UTF-8
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'No', 'No. Kuesioner', 'Nama Lengkap', 'Jenis Kelamin', 'Umur',
            'Pendidikan', 'Pekerjaan', 'Alamat', 'No. Kontak', 'Agama', 'Suku',
            'Status OAP', 'Status RT', 'Wilayah', 'Surveyor', 'Tanggal Wawancara',
            'Status Verifikasi', 'Tanggal Submit', 'Tanggal Verifikasi',
        ]);

        $rowNumber = 1;

        foreach ($query->lazyById(1000) as $resp) {
            $r = $resp->respondent;
            fputcsv($handle, [
                $rowNumber++,
                ExportController::safeCsvValue($resp->questionnaire_number),
                ExportController::safeCsvValue($r?->full_name ?? '-'),
                ExportController::safeCsvValue($r?->gender === 'male' ? 'Laki-laki' : 'Perempuan'),
                $r?->age ?? '-',
                ExportController::safeCsvValue($r?->education ?? '-'),
                ExportController::safeCsvValue($r?->occupation ?? '-'),
                ExportController::safeCsvValue($r?->address ?? '-'),
                ExportController::safeCsvValue($r?->phone ?? '-'),
                ExportController::safeCsvValue($r?->religion ?? '-'),
                ExportController::safeCsvValue($r?->ethnicity ?? '-'),
                ExportController::safeCsvValue($r?->citizenship_status ?? '-'),
                ExportController::safeCsvValue($r?->household_status ?? '-'),
                ExportController::safeCsvValue($resp->region?->name ?? '-'),
                ExportController::safeCsvValue($resp->surveyor?->name ?? '-'),
                ExportController::safeCsvValue($resp->interview_date?->format('d/m/Y') ?? '-'),
                ExportController::safeCsvValue($resp->status_label),
                ExportController::safeCsvValue($resp->submitted_at?->format('d/m/Y H:i') ?? '-'),
                ExportController::safeCsvValue($resp->verified_at?->format('d/m/Y H:i') ?? '-'),
            ]);
        }

        fclose($handle);

        Storage::disk($disk)->put($filename, file_get_contents($tempPath));
        unlink($tempPath);

        $rowCount = $rowNumber - 1;
        $exportFile = ExportFile::create([
            'user_id' => $user->id,
            'disk' => $disk,
            'path' => $filename,
            'row_count' => $rowCount,
            'status' => ExportFile::STATUS_READY,
            'expires_at' => now()->addHours((int) config('exports.download_ttl_hours', 24)),
        ]);

        $user->notify(new ExportReadyNotification($exportFile->id));
    }
}
