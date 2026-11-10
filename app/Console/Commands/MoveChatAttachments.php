<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MoveChatAttachments extends Command
{
    protected $signature = 'chat:move-attachments';

    protected $description = 'Move legacy chat attachments out of the public disk';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $public = 'public/chats';
        $files = $disk->allFiles($public);
        $moved = $duplicates = $conflicts = $failed = 0;

        foreach ($files as $file) {
            $destination = Message::ATTACHMENT_DIRECTORY.'/'.substr($file, strlen($public) + 1);

            if ($disk->fileExists($destination)) {
                $checksum = $disk->checksum($file, ['checksum_algo' => 'sha256']);
                if (! $checksum || $checksum !== $disk->checksum($destination, ['checksum_algo' => 'sha256'])) {
                    $this->warn("Conflicting attachment: {$file}");
                    $conflicts++;

                    continue;
                }

                if ($disk->delete($file)) {
                    $duplicates++;
                } else {
                    $failed++;
                }

                continue;
            }

            if ($disk->move($file, $destination)) {
                $moved++;
            } else {
                $failed++;
            }
        }

        $this->info(sprintf('Found: %d. Moved: %d. Duplicate copies removed: %d. Conflicts: %d. Failed: %d.',
            count($files), $moved, $duplicates, $conflicts, $failed));

        return $conflicts || $failed ? self::FAILURE : self::SUCCESS;
    }
}
