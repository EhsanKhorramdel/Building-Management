<?php

namespace App\Services\Payments;

final readonly class VerificationResult
{
    public function __construct(
        public bool $success,
        public ?string $referenceId = null,
        public ?string $failureReason = null,
    ) {}
}
