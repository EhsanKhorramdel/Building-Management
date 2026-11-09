<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use App\Models\SmsLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GatewaySmsSender implements SmsSender
{
    public function __construct(private array $config) {}

    public function send(string $to, string $message): array
    {
        $url = (string) $this->config['base_url'];
        if (! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' || blank($this->config['api_key'])) {
            return ['status' => SmsLog::FAILED, 'meta' => ['reason' => 'unconfigured']];
        }

        $request = Http::acceptJson()->withToken($this->config['api_key'])->withoutRedirecting()->timeout(10);
        if (filled($this->config['sender'])) {
            $request->withHeaders(['X-Sender' => $this->config['sender']]);
        }

        try {
            $response = $request->post($url, ['to' => $to, 'message' => $message]);
        } catch (ConnectionException) {
            return ['status' => SmsLog::FAILED, 'meta' => ['reason' => 'unavailable']];
        }

        return [
            'status' => $response->successful() ? SmsLog::SENT : SmsLog::FAILED,
            'meta' => ['http_status' => $response->status()],
        ];
    }
}
