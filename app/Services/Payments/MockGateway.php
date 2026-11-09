<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Models\Transaction;
use Illuminate\Support\Str;

class MockGateway implements PaymentGateway
{
    public function initiate(Transaction $transaction): PaymentRedirect
    {
        return new PaymentRedirect(route('payments.mock', $transaction), Str::random(48));
    }

    public function verify(array $callbackParams): VerificationResult
    {
        $transaction = $callbackParams['transaction'];
        $payload = $callbackParams['payload'];

        if (! $transaction->matchesAmount($payload['amount'] ?? null)) {
            return new VerificationResult(false, failureReason: 'amount_mismatch');
        }

        return match ($payload['outcome'] ?? null) {
            'pay' => new VerificationResult(true, 'mock-'.$transaction->id),
            'cancel' => new VerificationResult(false, failureReason: 'cancelled'),
            default => new VerificationResult(false, failureReason: 'invalid_status'),
        };
    }
}
