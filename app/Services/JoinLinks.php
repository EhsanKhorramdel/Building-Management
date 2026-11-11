<?php

namespace App\Services;

use App\Models\Complex;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JoinLinks
{
    public function __construct(private AuditLogger $audit) {}

    public const LIFECYCLES = ['reusable', 'expiring', 'single'];

    public function rotate(Complex $complex, User $actor, ?string $lifecycle, ?int $days): void
    {
        DB::transaction(function () use ($complex, $actor, $lifecycle, $days) {
            $complex = Complex::lockForUpdate()->findOrFail($complex->id);
            $from = match (true) {
                $complex->join_link_expires_at !== null => 'expiring',
                $complex->join_link_uses_left !== null => 'single',
                default => 'reusable',
            };
            $to = $lifecycle ?? 'reusable';
            $complex->forceFill([
                'join_link' => Str::random(32),
                'join_link_uses_left' => $to === 'single' ? 1 : null,
                'join_link_expires_at' => $to === 'expiring' ? now()->addDays($days) : null,
                'join_link_expiry_recorded_at' => null,
                'join_link_issued_by' => $actor->id,
            ])->save();
            $meta = $lifecycle !== null || $from !== $to ? compact('from', 'to') : [];
            $this->audit->record('join_link.rotated', $actor->id, $complex->id, Complex::class, $complex->id, $meta);
        }, 3);
    }

    public function recordExpiries(): void
    {
        Complex::where('join_link_expires_at', '<=', now())->whereNull('join_link_expiry_recorded_at')
            ->whereNotNull('join_link_issued_by')->eachById(fn (Complex $complex) => $this->recordExpiry($complex));
    }

    private function recordExpiry(Complex $complex, ?int $fallbackActor = null): void
    {
        $actor = $complex->join_link_issued_by ?? $fallbackActor;
        if (! $actor) {
            return;
        }

        DB::transaction(function () use ($complex, $actor) {
            $marked = Complex::whereKey($complex->id)->where('join_link', $complex->join_link)
                ->where('join_link_expires_at', '<=', now())->whereNull('join_link_expiry_recorded_at')
                ->update(['join_link_expiry_recorded_at' => now()]);
            if ($marked) {
                $this->audit->record('join_link.expired', $actor, $complex->id, Complex::class, $complex->id);
            }
        });
    }

    public function redeem(string $link, User $user): array
    {
        return DB::transaction(function () use ($link, $user) {
            $complex = Complex::where('join_link', $link)->lockForUpdate()->first();
            if (! $complex || ! hash_equals($complex->join_link, $link)) {
                return ['status' => 'error', 'message' => 'خطا! لینک وارد شده نامعتبر است.'];
            }

            if ($complex->join_link_expires_at?->lte(now())) {
                $this->recordExpiry($complex, $user->id);

                return ['status' => 'error', 'message' => 'مهلت استفاده از لینک دعوت به پایان رسیده است.'];
            }

            if ($complex->join_link_uses_left === 0) {
                return ['status' => 'error', 'message' => 'این لینک دعوت قبلاً استفاده شده است.'];
            }

            if ($complex->userRoles()->where('user_id', $user->id)->where('active', true)->where('role', 'resident')->exists()) {
                return ['status' => 'error', 'message' => 'خطا! شما قبلاً این ساختمان را اضافه کرده‌اید.'];
            }

            if ($complex->join_link_uses_left !== null) {
                $claimed = Complex::whereKey($complex->id)->where('join_link', $link)
                    ->where('join_link_uses_left', 1)->update(['join_link_uses_left' => 0]);
                if (! $claimed) {
                    return ['status' => 'error', 'message' => 'این لینک دعوت قبلاً استفاده شده است.'];
                }
            }
            $complex->userRoles()->create(['user_id' => $user->id, 'role' => 'resident']);
            $this->audit->record('join_link.redeemed', $user->id, $complex->id, User::class, $user->id);

            return ['status' => 'success', 'message' => 'عملیات با موفقیت انجام شد.'];
        }, 3);
    }
}
