<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** Stores uploaded images/documents on the public disk and returns the path to keep in the database. */
class Uploads
{
    /** Path stored in the database, relative to public/ (e.g. "storage/uploads/guests/abc.jpg"). */
    public static function store(?UploadedFile $file, string $folder): ?string
    {
        if (! $file) {
            return null;
        }

        return 'storage/'.$file->store('uploads/'.trim($folder, '/'), 'public');
    }

    /** Delete a file previously saved by {@see store()}; paths that are not uploads (old assets) are left alone. */
    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'storage/uploads/')) {
            Storage::disk('public')->delete(substr($path, strlen('storage/')));
        }
    }
}
