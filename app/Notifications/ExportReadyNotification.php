<?php

namespace App\Notifications;

use App\Models\ExportFile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ExportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $exportFileId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $exportFile = ExportFile::findOrFail($this->exportFileId);
        $expiresAt = $exportFile->expires_at ?? now()->addHours((int) config('exports.download_ttl_hours', 24));

        $downloadUrl = URL::temporarySignedRoute('app.export.download', $expiresAt, [
            'export' => $exportFile->id,
            'user' => $notifiable->getKey(),
        ]);

        return [
            'message' => "Export CSV selesai ({$exportFile->row_count} baris).",
            'download_url' => $downloadUrl,
            'export_id' => $exportFile->id,
            'row_count' => $exportFile->row_count,
            'expires_at' => $expiresAt->toIso8601String(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
