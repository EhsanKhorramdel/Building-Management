<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\PaymentsReportRequest;
use App\Models\Complex;
use App\Services\Payments\PaymentReport;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentsReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
    }

    /**
     * @return JsonResponse Body is the row list from PaymentReport::rows().
     */
    public function payments(PaymentsReportRequest $request)
    {
        return response()->json(iterator_to_array($this->rows($request)));
    }

    public function export(PaymentsReportRequest $request): StreamedResponse
    {
        $rows = $this->rows($request, stream: true);

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['نام', 'شماره واحد', 'نوع پرداخت', 'تاریخ پرداخت', 'مبلغ'], ',', '"', '');
            foreach ($rows as $row) {
                $name = $row['user_name'];
                if (preg_match('/^(?:[\t\r\n]|[\s\x{FEFF}]*[=+@-])/u', $name)) {
                    $name = "'".$name;
                }
                fputcsv($output, [$name, $row['unit_number'], $row['type'], $row['date'], $row['amount']], ',', '"', '');
            }
            fclose($output);
        }, 'payments.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    private function rows(PaymentsReportRequest $request, bool $stream = false): \Generator
    {
        $filters = $request->validated();
        $userId = auth()->id();
        $complex = Complex::withExists(['userRoles as may_manage_receipts' => fn ($query) => $query
            ->where('user_id', $userId)->where('role', 'manager')->where('active', true)])->findOrFail($filters['complex_id']);

        return app(PaymentReport::class)->rows($complex, $filters, $userId, $stream);
    }
}
