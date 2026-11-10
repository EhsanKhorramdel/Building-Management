<?php

namespace App\Contracts;

use App\Models\Transaction;
use App\Services\Payments\PaymentRedirect;
use App\Services\Payments\VerificationResult;

interface PaymentGateway
{
    public function initiate(Transaction $transaction): PaymentRedirect;

    /** @param array{transaction: Transaction, payload: array<string, mixed>} $callbackParams */
    public function verify(array $callbackParams): VerificationResult;
}
