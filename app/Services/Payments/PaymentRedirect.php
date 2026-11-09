<?php

namespace App\Services\Payments;

final readonly class PaymentRedirect
{
    public function __construct(public string $target, public string $authority) {}
}
