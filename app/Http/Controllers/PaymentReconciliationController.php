<?php

namespace App\Http\Controllers;

use App\Models\Complex;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentReconciliationController extends Controller
{
    public function index(Request $request, ?Complex $complex = null): Response
    {
        $viewer = $request->user();
        abort_unless($viewer->isGlobalAdmin() || ($complex && $viewer->can('manage', $complex)), 403);
        $cutoff = now()->subMinutes(max(1, config('payments.expiry_minutes')));
        $types = [MonthlyCharge::class, IncidentalCost::class];
        $query = Transaction::with(['payable.complex', 'role.complex', 'transitions', 'callbackIssues'])
            ->where(function ($query) use ($cutoff) {
                $query->whereIn('status', [Transaction::FAILED, Transaction::EXPIRED])
                    ->orWhere(fn ($pending) => $pending->where('status', Transaction::PENDING)->where('created_at', '<=', $cutoff))
                    ->orWhereHas('callbackIssues');
            });
        if ($complex) {
            $query->where(function ($query) use ($types, $complex) {
                $query->whereHasMorph('payable', $types, fn ($item) => $item->where('complex_id', $complex->id))
                    ->orWhere(fn ($missing) => $missing->where(fn ($items) => $items->whereNull('payable_type')->orWhereDoesntHaveMorph('payable', $types))
                        ->whereHas('role', fn ($role) => $role->where('complex_id', $complex->id)));
            });
        }
        $attempts = $query->orderByDesc('id')->simplePaginate(20);

        return response()->view('payments.reconciliation', compact('attempts', 'complex', 'cutoff'))
            ->header('Cache-Control', 'private, no-store');
    }
}
