<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Transaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class GatewayDriver implements PaymentGateway
{
    public function __construct(private array $config) {}

    public function initiate(Transaction $transaction): PaymentRedirect
    {
        $response = $this->post('initiate_path', [
            'amount' => $transaction->amount,
            'callback_url' => $this->config['callback_url'] ?: route('payments.callback', $transaction->gateway),
            'description' => $transaction->itemLabel(),
            'mobile' => $transaction->user->phone_number,
            'sandbox' => $this->config['sandbox'],
        ]);

        $authority = data_get($response, $this->field('initiate_authority'));
        if (! is_string($authority) || $authority === '' || strlen($authority) > 255) {
            throw new PaymentException('unavailable');
        }

        $checkout = $this->url('checkout_path');
        if (! str_contains($checkout, '{authority}')) {
            throw new PaymentException('unconfigured');
        }

        return new PaymentRedirect(str_replace('{authority}', rawurlencode($authority), $checkout), $authority);
    }

    public function verify(array $callbackParams): VerificationResult
    {
        $transaction = $callbackParams['transaction'];

        try {
            $response = $this->post('verify_path', [
                'verify_authority' => $transaction->authority,
                'amount' => $transaction->amount,
                'sandbox' => $this->config['sandbox'],
            ]);
        } catch (PaymentException $exception) {
            return new VerificationResult(false, failureReason: $exception->getMessage());
        }

        $status = data_get($response, $this->field('verified_status'));
        $reference = data_get($response, $this->field('verified_reference'));
        $amount = data_get($response, $this->field('verified_amount'));
        if (is_scalar($status) && (string) $status === (string) $this->config['cancel_status']) {
            return new VerificationResult(false, failureReason: 'cancelled');
        }
        if (! is_scalar($status) || (string) $status !== (string) $this->config['success_status']) {
            return new VerificationResult(false, failureReason: 'invalid_status');
        }
        if (! $transaction->matchesAmount($amount)) {
            return new VerificationResult(false, failureReason: 'amount_mismatch');
        }
        if ((! is_string($reference) && ! is_int($reference)) || (string) $reference === '' || strlen((string) $reference) > 255) {
            return new VerificationResult(false, failureReason: 'missing_reference');
        }

        return new VerificationResult(true, (string) $reference);
    }

    private function post(string $path, array $values): array
    {
        if (blank($this->config['merchant_id']) || blank($this->config['api_key'])) {
            throw new PaymentException('unconfigured');
        }

        $payload = [$this->field('merchant_id') => $this->config['merchant_id']];
        foreach ($values as $key => $value) {
            $payload[$this->field($key)] = $value;
        }

        try {
            $response = Http::acceptJson()->withToken($this->config['api_key'])
                ->withoutRedirecting()->timeout(10)->post($this->url($path), $payload);
        } catch (ConnectionException) {
            throw new PaymentException('unavailable');
        }

        if (! $response->successful() || ! is_array($response->json())) {
            throw new PaymentException('unavailable');
        }

        return $response->json();
    }

    private function url(string $path): string
    {
        $base = rtrim((string) $this->config['base_url'], '/');
        $endpoint = (string) $this->config[$path];
        if (! filter_var($base, FILTER_VALIDATE_URL) || parse_url($base, PHP_URL_SCHEME) !== 'https'
            || parse_url($base, PHP_URL_QUERY) !== null || parse_url($base, PHP_URL_FRAGMENT) !== null
            || ! str_starts_with($endpoint, '/') || str_starts_with($endpoint, '//')) {
            throw new PaymentException('unconfigured');
        }

        return $base.$endpoint;
    }

    private function field(string $key): string
    {
        return $this->config['fields'][$key];
    }
}
