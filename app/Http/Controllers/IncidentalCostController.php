<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\IncidentalCostRequest;
use App\Http\Requests\IncidentalPaymentRequest;
use App\Models\Complex;
use App\Models\IncidentalCost;
use App\Services\FileUploadService;
use App\Services\PaymentService;

class IncidentalCostController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
        $this->middleware(AccessMiddleware::class.':manager')->only('incidentalCost');
    }

    public function incidentalCost(IncidentalCostRequest $request, Complex $complex, FileUploadService $uploads)
    {
        $data = $request->validated();

        $incidentalCost = [
            'complex_id' => $complex->id,
            'cost_invoice' => null,
            'cost_explanation' => $data['cost_explanation'] ?? null,
            'title' => $data['title'] ?? null,
            'total_amount' => $data['total_amount'] ?? null,
            'share_amount' => $data['share_amount'] ?? null,
        ];

        if ($data['cost_invoice'] ?? null) {
            $incidentalCost['cost_invoice'] = $uploads->store($data['cost_invoice'], 'public/incidentalCostFiles');
        }

        IncidentalCost::create($incidentalCost);

        $status = 'success';
        $message = 'هزینه جدید با موفقیت ثبت شد.';
        $page = 'cost';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function incidentalCostPayment(IncidentalPaymentRequest $request, PaymentService $payments)
    {
        return redirect()->back()->with($payments->incidentalCostPayment($request->validated(), auth()->id()));
    }
}
