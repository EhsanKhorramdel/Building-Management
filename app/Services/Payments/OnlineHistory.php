<?php

namespace App\Services\Payments;

use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class OnlineHistory
{
    /** @return array{onlineTransactions: Collection, nextHistoryId: int|null} */
    public function page(int $complexId, int $payerId, ?int $beforeId = null): array
    {
        $rows = Transaction::with('payable')->where('user_id', $payerId)
            ->whereHasMorph('payable', [MonthlyCharge::class, IncidentalCost::class], fn ($query) => $query->where('complex_id', $complexId))
            ->when($beforeId, fn ($query) => $query->where('id', '<', $beforeId))
            ->orderByDesc('id')->limit(16)->get();
        $page = $rows->take(15);

        return ['onlineTransactions' => $page, 'nextHistoryId' => $rows->count() > 15 ? $page->last()->id : null];
    }
}
