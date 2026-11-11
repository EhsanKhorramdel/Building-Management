<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\ChargePayment;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\Poll;
use App\Models\Transaction;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public string $template, public int $subjectId) {}

    public function handle(SmsService $sms): void
    {
        if (! config('sms.enabled')) {
            return;
        }

        match ($this->template) {
            'announcement' => $this->announcement($sms),
            'monthly_charge' => $this->monthlyCharge($sms),
            'payment_receipt' => $this->receipt($sms),
            'manual_charge_receipt' => $this->manualReceipt($sms, ChargePayment::find($this->subjectId)),
            'manual_cost_receipt' => $this->manualReceipt($sms, PaymentAdditionalCost::find($this->subjectId)),
            'poll_opened' => $this->poll($sms),
            'membership_approved' => $this->welcome($sms),
            default => null,
        };
    }

    private function announcement(SmsService $sms): void
    {
        $announcement = Announcement::find($this->subjectId);
        if ($announcement) {
            $this->members($sms, $announcement->complex_id, ['complex' => $announcement->complex->name]);
        }
    }

    private function monthlyCharge(SmsService $sms): void
    {
        $charge = MonthlyCharge::find($this->subjectId);
        if ($charge) {
            [$year, $month] = explode('-', $charge->start_date);
            $this->members($sms, $charge->complex_id, [
                'complex' => $charge->complex->name,
                'amount' => $charge->amount,
                'month' => sprintf('%04d/%02d', $year, $month),
            ], assigned: true);
        }
    }

    private function poll(SmsService $sms): void
    {
        $poll = Poll::find($this->subjectId);
        if ($poll) {
            $this->members($sms, $poll->complex_id, ['title' => $poll->title ?: $poll->question]);
        }
    }

    private function receipt(SmsService $sms): void
    {
        $transaction = Transaction::find($this->subjectId);
        if ($transaction?->status === Transaction::PAID && $transaction->user) {
            $sms->send($transaction->user, $this->template, [
                'amount' => $transaction->amount,
                'reference' => $transaction->reference_id,
            ]);
        }
    }

    private function manualReceipt(SmsService $sms, ChargePayment|PaymentAdditionalCost|null $payment): void
    {
        if ($payment?->role?->user) {
            $sms->send($payment->role->user, $this->template, ['amount' => $payment->amount]);
        }
    }

    private function welcome(SmsService $sms): void
    {
        $user = User::find($this->subjectId);
        if ($user) {
            $sms->send($user, $this->template, []);
        }
    }

    private function members(SmsService $sms, int $complexId, array $parameters, bool $assigned = false): void
    {
        User::whereHas('userRoles', function ($query) use ($complexId, $assigned) {
            $query->where('complex_id', $complexId)->where('active', true)->whereIn('role', ['resident', 'manager']);
            if ($assigned) {
                $query->whereNotNull('unit_id');
            }
        })->each(fn (User $user) => $sms->send($user, $this->template, $parameters));
    }
}
