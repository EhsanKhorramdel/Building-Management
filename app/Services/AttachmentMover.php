<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemException;

class AttachmentMover
{
    /** @return array{summary: string, warnings: list<string>, successful: bool} */
    public function move(string $public, string $private): array
    {
        $disk = Storage::disk('local');
        $warnings = [];
        $moved = $duplicates = $conflicts = $failed = 0;

        try {
            $files = $disk->allFiles($public);
        } catch (FilesystemException) {
            $files = [];
            $failed++;
            $warnings[] = "Could not list attachments: {$public}";
        }

        foreach ($files as $file) {
            $destination = $private.'/'.substr($file, strlen($public) + 1);

            try {
                if (! $disk->fileExists($destination)) {
                    if ($disk->move($file, $destination)) {
                        $moved++;

                        continue;
                    }
                } else {
                    $checksum = $disk->checksum($file, ['checksum_algo' => 'sha256']);
                    $existing = $disk->checksum($destination, ['checksum_algo' => 'sha256']);
                    if ($checksum && $existing && $checksum !== $existing) {
                        $warnings[] = "Conflicting attachment: {$file}";
                        $conflicts++;

                        continue;
                    }

                    if ($checksum && $checksum === $existing && $disk->delete($file)) {
                        $duplicates++;

                        continue;
                    }
                }
            } catch (FilesystemException) {
                // A filesystem error should leave the remaining files eligible to move.
            }

            $warnings[] = "Could not move attachment: {$file}";
            $failed++;
        }

        return [
            'summary' => sprintf('Found: %d. Moved: %d. Duplicate copies removed: %d. Conflicts: %d. Failed: %d.',
                count($files), $moved, $duplicates, $conflicts, $failed),
            'warnings' => $warnings,
            'successful' => $conflicts === 0 && $failed === 0,
        ];
    }
}
