<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Sms\GatewaySmsSender;
use App\Services\Sms\LogSmsSender;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsService
{
    public function queue(string $template, int $subjectId): void
    {
        if (! config('sms.enabled')) {
            return;
        }

        try {
            // The app's default queue can be synchronous. SMS must wait for its own worker.
            Bus::dispatch((new SendSmsJob($template, $subjectId))->onConnection('database')->onQueue('sms'));
        } catch (Throwable) {
            rescue(fn () => Log::warning('SMS could not be queued.', ['template' => $template, 'subject_id' => $subjectId]), report: false);
        }
    }

    public function send(User $user, string $template, array $parameters): void
    {
        if (! config('sms.enabled')) {
            return;
        }

        $event = in_array($template, ['manual_charge_receipt', 'manual_cost_receipt'], true) ? 'payment_receipt' : $template;
        if (! AccountNotifications::allows($user, $event, 'sms')) {
            return;
        }

        if (blank($user->phone_number)) {
            Log::info('SMS skipped because the user has no phone number.', ['template' => $template, 'user_id' => $user->id]);

            return;
        }

        $driver = config('sms.driver');
        $message = __("integrations::sms.$template", $parameters, 'fa');
        $log = SmsLog::create([
            'recipient' => $user->phone_number,
            'template' => $template,
            'body' => $message,
            'driver' => $driver,
            'status' => SmsLog::PENDING,
        ]);

        try {
            $sender = match ($driver) {
                'log' => new LogSmsSender,
                'gateway' => new GatewaySmsSender(config('sms.drivers.gateway')),
                default => null,
            };
            $result = $sender?->send($user->phone_number, $message)
                ?? ['status' => SmsLog::FAILED, 'meta' => ['reason' => 'unconfigured']];
        } catch (Throwable) {
            $result = ['status' => SmsLog::FAILED, 'meta' => ['reason' => 'delivery_error']];
        }

        $log->update($result);
    }
}
