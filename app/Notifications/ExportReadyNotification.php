<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ExportReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $filePath,
        private readonly int $rowCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        // Signed URL expires in 24 hours — cannot be shared/guessed
        $downloadUrl = URL::signedRoute('app.export.download', [
            'file' => $this->filePath,
        ], now()->addHours(24));

        return [
            'message' => "Export CSV selesai ({$this->rowCount} baris).",
            'download_url' => $downloadUrl,
            'row_count' => $this->rowCount,
            'expires_at' => now()->addHours(24)->toIso8601String(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
