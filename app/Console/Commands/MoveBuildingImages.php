<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\IncidentalCost;
use App\Services\AttachmentMover;
use Illuminate\Console\Command;

class MoveBuildingImages extends Command
{
    protected $signature = 'attachments:move-building-images';

    protected $description = 'Move legacy announcement and incidental-cost images out of public storage';

    public function handle(AttachmentMover $mover): int
    {
        $successful = true;
        foreach ([
            'Announcements' => ['public/notificationFiles', Announcement::ATTACHMENT_DIRECTORY],
            'Incidental costs' => ['public/incidentalCostFiles', IncidentalCost::ATTACHMENT_DIRECTORY],
        ] as $type => [$public, $private]) {
            $result = $mover->move($public, $private);
            foreach ($result['warnings'] as $warning) {
                $this->warn($warning);
            }
            $this->info($type.' — '.$result['summary']);
            $successful = $result['successful'] && $successful;
        }

        return $successful ? self::SUCCESS : self::FAILURE;
    }
}
