<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class AccountNotifications
{
    public const LABELS = [
        'announcement' => 'اعلان جدید',
        'monthly_charge' => 'صدور شارژ',
        'poll_opened' => 'شروع رای‌گیری',
        'payment_receipt' => 'رسید پرداخت',
    ];

    public static function allows(User $user, string $event, string $channel): bool
    {
        return (bool) ($user->notification_preferences[$event][$channel] ?? true);
    }

    public function building(string $event, int $complexId, string $detail, string $panel, bool $assigned = false): void
    {
        User::whereHas('userRoles', function ($query) use ($complexId, $assigned) {
            $query->where('complex_id', $complexId)->where('active', true)->whereIn('role', ['resident', 'manager']);
            if ($assigned) {
                $query->whereNotNull('unit_id');
            }
        })->each(fn (User $user) => $this->record($user, $event, $complexId, $detail, $panel));
    }

    public function record(User $user, string $event, int $complexId, string $detail, string $panel): void
    {
        if (self::allows($user, $event, 'in_app')) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => $event,
                'data' => ['detail' => $detail, 'complex_id' => $complexId, 'panel' => $panel],
            ]);
        }
    }
}
