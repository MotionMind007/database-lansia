<?php

namespace App\Support;

use App\Models\Respondent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecureUploadStorage
{
    public function storeProfilePhoto(UploadedFile $file, Respondent $respondent): string
    {
        return $file->storeAs(
            'photos/'.$respondent->id,
            $this->storedFilename($file),
            config('uploads.private_disk')
        );
    }

    public function storeDocument(UploadedFile $file, Respondent $respondent): array
    {
        $path = $file->storeAs(
            'documents/'.$respondent->id,
            $this->storedFilename($file),
            config('uploads.private_disk')
        );

        return [
            'file_path' => $path,
            'file_name' => $this->safeOriginalName($file),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public function validPrivatePath(?string $path, array $allowedPrefixes): bool
    {
        if (! $path || str_contains($path, '..') || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return false;
        }

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($path, rtrim($prefix, '/').'/')) {
                return true;
            }
        }

        return false;
    }

    public function exists(?string $path, array $allowedPrefixes, bool $includeLegacy = true): bool
    {
        if (! $this->validPrivatePath($path, $allowedPrefixes)) {
            return false;
        }

        if (Storage::disk(config('uploads.private_disk'))->exists($path)) {
            return true;
        }

        return $includeLegacy
            && Storage::disk(config('uploads.legacy_public_disk'))->exists($path);
    }

    public function delete(?string $path, array $allowedPrefixes, bool $includeLegacy = true): bool
    {
        if (! $this->validPrivatePath($path, $allowedPrefixes)) {
            return false;
        }

        $deleted = false;
        $disk = config('uploads.private_disk');

        if (Storage::disk($disk)->exists($path)) {
            $deleted = Storage::disk($disk)->delete($path) || $deleted;
        }

        if ($includeLegacy) {
            $legacyDisk = config('uploads.legacy_public_disk');

            if (Storage::disk($legacyDisk)->exists($path)) {
                $deleted = Storage::disk($legacyDisk)->delete($path) || $deleted;
            }
        }

        return $deleted;
    }

    public function response(string $path, string $filename, ?string $mimeType = null)
    {
        $disk = config('uploads.private_disk');

        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->response($path, $filename, [
                'Content-Type' => $mimeType ?: (Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream'),
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=300',
            ]);
        }

        $legacyDisk = config('uploads.legacy_public_disk');

        if (Storage::disk($legacyDisk)->exists($path)) {
            return Storage::disk($legacyDisk)->response($path, $filename, [
                'Content-Type' => $mimeType ?: (Storage::disk($legacyDisk)->mimeType($path) ?: 'application/octet-stream'),
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=300',
            ]);
        }

        abort(404);
    }

    private function storedFilename(UploadedFile $file): string
    {
        return Str::uuid().'.'.$this->extension($file);
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $this->extension($file);
        $name = Str::slug(Str::limit($original, 80, ''));

        return ($name ?: 'file').'.'.$extension;
    }

    private function extension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');

        return preg_replace('/[^a-z0-9]/', '', $extension) ?: 'bin';
    }
}
