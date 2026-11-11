<?php

namespace App\Http\Controllers;

use App\Http\Requests\OnlinePaymentRequest;
use App\Models\Complex;
use App\Models\Transaction;
use App\Services\Payments\GatewayManager;
use App\Services\Payments\OnlineHistory;
use App\Services\Payments\OnlinePaymentService;
use App\Services\Payments\PaymentException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnlinePaymentController extends Controller
{
    public function history(Request $request, Complex $complex, OnlineHistory $history)
    {
        abort_unless(config('payments.enabled'), 404);
        $this->authorize('view', $complex);
        $beforeId = $request->validate(['before_id' => 'nullable|integer|min:1'])['before_id'] ?? null;
        $page = $history->page($complex->id, $request->user()->id, $beforeId);

        return response()->json([
            'html' => view('building.online-payment-rows', $page)->render(),
            'cursor' => $page['nextHistoryId'],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function initiate(OnlinePaymentRequest $request, OnlinePaymentService $payments): RedirectResponse
    {
        try {
            return redirect()->away($payments->initiate($request->validated(), $request->user())->target);
        } catch (PaymentException $exception) {
            return back()->with([
                'status' => 'error',
                'message' => __('integrations::payments.'.$exception->getMessage()),
                'page' => $request->validated('type'),
            ]);
        }
    }

    public function mock(Request $request, Transaction $transaction, GatewayManager $gateways): View
    {
        abort_unless(config('payments.enabled') && $gateways->mockSelected() && $transaction->gateway === 'mock', 404);
        abort_unless($transaction->user_id === $request->user()->id, 403);
        abort_unless($transaction->authority && $transaction->status === Transaction::PENDING && $transaction->payable, 404);

        return view('payments.mock', ['transaction' => $transaction]);
    }

    public function callback(Request $request, string $driver, OnlinePaymentService $payments, GatewayManager $gateways): RedirectResponse
    {
        abort_unless(config('payments.enabled'), 404);

        if ($driver === 'mock') {
            abort_unless($gateways->mockSelected() && $request->isMethod('POST'), 404);
            $token = $request->input('_token');
            abort_unless(is_string($token) && hash_equals($request->session()->token(), $token), 419);
        }

        $transaction = $payments->callback($driver, $request->all(), $request->user()?->id);
        $paid = $transaction->status === Transaction::PAID;
        $target = $transaction->payable
            ? redirect()->route('building', $transaction->payable->complex_id)
            : redirect()->route('dashboard');

        return $target->with([
            'status' => $paid ? 'success' : 'error',
            'message' => __('integrations::payments.outcome.'.$transaction->status),
            'page' => 'paymentInfo',
        ]);
    }
}
