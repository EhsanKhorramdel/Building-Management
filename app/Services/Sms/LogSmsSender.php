<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSender
{
    public function send(string $to, string $message): array
    {
        Log::info('SMS recorded by the log driver.');

        return ['status' => SmsLog::LOGGED];
    }
}
