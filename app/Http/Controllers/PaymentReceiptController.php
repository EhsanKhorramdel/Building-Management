<?php

namespace App\Http\Controllers;

use App\Models\ChargePayment;
use App\Models\Complex;
use App\Models\PaymentAdditionalCost;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Morilog\Jalali\Jalalian;

class PaymentReceiptController extends Controller
{
    public function show(Request $request, string $type, int $payment): Response
    {
        [$query, $relation] = $this->payments($type);
        $record = $query->findOrFail($payment);
        $item = $record->$relation;
        $complex = $item?->complex;
        $role = $record->role;
        abort_unless($complex && $role?->complex_id === $complex->id, 404);

        $transaction = $record->transaction;
        abort_if($transaction && ($transaction->role_id !== $role->id || $transaction->payable_id !== $item->id
            || ! $transaction->matchesAmount($record->amount)), 404);
        $payer = $transaction ? $transaction->user : $role->user;
        $viewer = $request->user();
        abort_unless($payer?->id === $viewer->id || $viewer->can('manage', $complex) || $viewer->isGlobalAdmin(), 403);

        $paidAt = Jalalian::fromCarbon($record->created_at);
        $period = $type === 'charge'
            ? sprintf('%04d/%02d', $record->period_year, $record->period_month)
            : $paidAt->format('Y/m');
        $method = $transaction ? 'آنلاین' : 'دستی';
        if (! $transaction && Transaction::query()->whereMorphedTo('payable', $item)->where('role_id', $role->id)
            ->where('status', Transaction::PAID)->where('amount', $record->amount)
            ->where('created_at', '<=', $record->created_at)->where('updated_at', '>=', $record->created_at)->exists()) {
            $method = 'نامشخص';
        }

        return response()->view('payments.receipt', [
            'number' => strtoupper($type).'-'.$record->id,
            'complex' => $complex->name,
            'unit' => $role->unit?->complex_id === $complex->id ? $role->unit->unit_number : '—',
            'payer' => $payer?->name ?? '—',
            'item' => $type === 'charge' ? __('integrations::payments.charge') : $item->title,
            'period' => $period,
            'date' => $paidAt->format('Y/m/d'),
            'amount' => $record->amount,
            'method' => $method,
            'reference' => $transaction?->reference_id,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function index(Request $request, ?Complex $complex = null): Response
    {
        $viewer = $request->user();
        $admin = $viewer->isGlobalAdmin();
        abort_unless($admin || ($complex && $viewer->can('view', $complex)), 403);
        $type = $request->validate(['type' => 'nullable|in:charge,cost'])['type'] ?? 'charge';
        [$query, $relation] = $this->payments($type);
        if ($complex) {
            $query->whereHas($relation, fn ($item) => $item->where('complex_id', $complex->id))
                ->whereHas('role', fn ($role) => $role->where('complex_id', $complex->id));
        }
        if (! $admin && ! $viewer->can('manage', $complex)) {
            $query->where(function ($query) use ($viewer) {
                $query->whereHas('transaction', fn ($transaction) => $transaction->where('user_id', $viewer->id))
                    ->orWhere(fn ($manual) => $manual->whereDoesntHave('transaction')
                        ->whereHas('role', fn ($role) => $role->where('user_id', $viewer->id)));
            });
        }
        $payments = $query->orderByDesc('id')->simplePaginate(20)->appends(['type' => $type]);

        return response()->view('payments.receipts', compact('payments', 'type', 'complex', 'relation'))
            ->header('Cache-Control', 'private, no-store');
    }

    /** @return array{Builder, string} */
    private function payments(string $type): array
    {
        $relation = $type === 'charge' ? 'MonthlyCharge' : 'incidentalCost';
        $query = $type === 'charge' ? ChargePayment::query() : PaymentAdditionalCost::query();

        return [$query->with([$relation.'.complex', 'role.user', 'role.unit', 'transaction.user']), $relation];
    }
}
