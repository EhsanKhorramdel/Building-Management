<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use LogicException;

final class QueryBudget
{
    private ?int $count = null;

    public function __construct()
    {
        DB::listen(function () {
            if ($this->count !== null) {
                $this->count++;
            }
        });
    }

    public function measure(callable $action): int
    {
        if ($this->count !== null) {
            throw new LogicException('A query measurement is already running.');
        }

        $this->count = 0;
        try {
            $action();

            return $this->count;
        } finally {
            $this->count = null;
        }
    }
}
