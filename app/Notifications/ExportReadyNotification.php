<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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
        return [
            'message' => "Export CSV selesai ({$this->rowCount} baris).",
            'file_path' => $this->filePath,
            'row_count' => $this->rowCount,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
