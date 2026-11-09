<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\ChargePaymentRequest;
use App\Http\Requests\ChargeRequest;
use App\Models\Complex;
use App\Models\MonthlyCharge;
use App\Services\PaymentService;

class ChargeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
        $this->middleware(AccessMiddleware::class.':manager')->only('buildingCharge');
    }

    public function buildingCharge(ChargeRequest $request, Complex $complex)
    {
        $data = $request->validated();

        $startDate = ($data['yearStart'] ?? null).'-'.($data['monthStart'] ?? null).'-'.($data['dayStart'] ?? null);
        $endDate = ($data['yearEnd'] ?? null).'-'.($data['monthEnd'] ?? null).'-'.($data['dayEnd'] ?? null);

        if ($endDate < $startDate) {
            $status = 'error';
            $message = 'تاریخ شروع نمی تواند از تاریخ پایان بزرگتر باشد.';
            $page = 'charge';

            return redirect()->back()->with(compact('message', 'status', 'page'));
        }

        $monthlyCharges = [
            'complex_id' => $complex->id,
            'user_id' => auth()->id(),
            'amount' => ($data['chargeAmount'] ?? null),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        MonthlyCharge::create($monthlyCharges);

        $status = 'success';
        $message = 'شارژ جدید با موفقیت ثبت شد.';
        $page = 'charge';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function buildingChargePayment(ChargePaymentRequest $request, PaymentService $payments)
    {
        abort_if(config('payments.enabled'), 403);

        return redirect()->back()->with($payments->buildingChargePayment($request->validated(), auth()->id()));
    }
}
