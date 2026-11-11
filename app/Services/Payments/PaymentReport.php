<?php

namespace App\Services\Payments;

use App\Models\Complex;
use App\Models\Unit;
use Generator;
use Morilog\Jalali\Jalalian;

class PaymentReport
{
    /** @return Generator<int, array{unit_number: int, type: string, amount: int|float|string, date: string, user_name: string, receipt_url: string|null}> */
    public function rows(Complex $complex, array $filters, int $viewerId, bool $stream = false): Generator
    {
        $from = isset($filters['paid_from']) ? Jalalian::fromFormat('Y/m/d', $filters['paid_from'])->toCarbon()->startOfDay() : null;
        $to = isset($filters['paid_to']) ? Jalalian::fromFormat('Y/m/d', $filters['paid_to'])->toCarbon()->endOfDay() : null;
        $kinds = [
            'charge' => ['chargePayments', 'MonthlyCharge', 'شارژ'],
            'cost' => ['paymentAdditionalCosts', 'incidentalCost', 'جانبی'],
        ];
        if (isset($filters['type'])) {
            $kinds = array_intersect_key($kinds, [$filters['type'] => true]);
        }
        if (isset($filters['period_year'])) {
            unset($kinds['cost']);
        }
        if (! $kinds) {
            return;
        }

        $relations = [
            'userRoles' => fn ($query) => $query->where('complex_id', $complex->id)->orderBy('id'),
            'userRoles.user',
        ];
        foreach ($kinds as $kind => [$relation, $payable]) {
            $relations['userRoles.'.$relation] = function ($query) use ($payable, $complex, $from, $to, $kind, $filters) {
                $query->whereHas($payable, fn ($item) => $item->where('complex_id', $complex->id))->orderBy('id');
                if ($from) {
                    $query->where('created_at', '>=', $from);
                }
                if ($to) {
                    $query->where('created_at', '<=', $to);
                }
                if ($kind === 'charge' && isset($filters['period_year'])) {
                    $query->where('period_year', $filters['period_year'])->where('period_month', $filters['period_month']);
                }
            };
        }

        $units = Unit::with($relations)->where('complex_id', $complex->id)->orderBy('id');
        if (isset($filters['unit_number'])) {
            $units->where('unit_number', $filters['unit_number']);
        }
        foreach ($stream ? $units->lazyById(100) : $units->get() as $unit) {
            // Keep the report's existing first-role selection before applying payment filters.
            $role = $unit->userRoles->first();
            if (! $role) {
                continue;
            }
            foreach ($kinds as $kind => [$relation, , $label]) {
                foreach ($role->$relation as $payment) {
                    yield [
                        'unit_number' => $unit->unit_number,
                        'type' => $label,
                        'amount' => $payment->amount,
                        'date' => Jalalian::fromDateTime($payment->created_at)->format('d-m-Y'),
                        'user_name' => $role->user->name,
                        'receipt_url' => $complex->may_manage_receipts || $role->user_id === $viewerId
                            ? route('payments.receipt', [$kind, $payment->id]) : null,
                    ];
                }
            }
        }
    }
}
