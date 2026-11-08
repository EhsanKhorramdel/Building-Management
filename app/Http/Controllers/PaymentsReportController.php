<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\PaymentsReportRequest;
use App\Models\Complex;
use App\Models\Unit;
use Morilog\Jalali\Jalalian;

class PaymentsReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
    }

    public function payments(PaymentsReportRequest $request)
    {
        $complex = Complex::findOrFail($request->validated()['complex_id']);
        $units = Unit::with(['userRoles.user', 'userRoles.chargePayments', 'userRoles.paymentAdditionalCosts'])
            ->where('complex_id', $complex->id)->get();
        $data = [];
        foreach ($units as $unit) {
            $role = $unit->userRoles->sortBy('id')->first();
            if (! $role) {
                continue;
            }
            foreach (['chargePayments' => 'شارژ', 'paymentAdditionalCosts' => 'جانبی'] as $relation => $type) {
                foreach ($role->$relation as $payment) {
                    $data[] = (object) [
                        'unit_number' => $unit->unit_number,
                        'type' => $type,
                        'amount' => $payment->amount,
                        'date' => Jalalian::fromDateTime($payment->created_at)->format('d-m-Y'),
                        'user_name' => $role->user->name,
                    ];
                }
            }
        }

        return response()->json($data);
    }
}
