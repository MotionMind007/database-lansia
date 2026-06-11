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
        // Bind the signed URL to the recipient so a forwarded link is rejected.
        $expiresAt = now()->addHours(24);
        $downloadUrl = URL::temporarySignedRoute('app.export.download', $expiresAt, [
            'file' => $this->filePath,
            'user' => $notifiable->getKey(),
        ]);

        return [
            'message' => "Export CSV selesai ({$this->rowCount} baris).",
            'download_url' => $downloadUrl,
            'row_count' => $this->rowCount,
            'expires_at' => $expiresAt->toIso8601String(),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
