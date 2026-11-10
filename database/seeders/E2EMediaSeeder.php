<?php

namespace Database\Seeders;

use App\Models\IncidentalCost;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class E2EMediaSeeder extends E2ESeeder
{
    public function run(): void
    {
        parent::run();
        $manager = User::where('email', 'manager@example.test')->sole();
        $group = $manager->userRoles()->firstOrFail()->complex->groups()->firstOrFail();
        for ($number = 1; $number <= 24; $number++) {
            Message::create(['group_id' => $group->id, 'user_id' => $manager->id,
                'text' => 'تصویر گالری '.$number, 'image_url' => 'e2e-attachment.png']);
        }
        Message::create(['group_id' => $group->id, 'user_id' => $manager->id,
            'text' => 'تصویر حذف‌شده', 'image_url' => 'e2e-attachment.png', 'deleted' => true]);
        Storage::disk('local')->copy(Message::ATTACHMENT_DIRECTORY.'/e2e-attachment.png', IncidentalCost::ATTACHMENT_DIRECTORY.'/e2e-invoice.png');
        IncidentalCost::where('complex_id', $group->complex_id)->update(['cost_invoice' => 'e2e-invoice.png']);
    }
}
