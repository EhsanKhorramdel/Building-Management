<?php

namespace App\Contracts;

interface SmsSender
{
    /** @return array{status: string, meta?: array<string, int|string>} */
    public function send(string $to, string $message): array;
}
