<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\AttachmentMover;
use Illuminate\Console\Command;

class MoveChatAttachments extends Command
{
    protected $signature = 'chat:move-attachments';

    protected $description = 'Move legacy chat attachments out of the public disk';

    public function handle(AttachmentMover $mover): int
    {
        $result = $mover->move('public/chats', Message::ATTACHMENT_DIRECTORY);
        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }
        $this->info($result['summary']);

        return $result['successful'] ? self::SUCCESS : self::FAILURE;
    }
}
