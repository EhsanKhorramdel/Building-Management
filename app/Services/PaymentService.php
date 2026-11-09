<?php

namespace App\Services;

use App\Models\ChargePayment;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\PaymentAdditionalCost;
use App\Models\Unit;
use App\Models\UserRole;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class PaymentService
{
    /**
     * @return array{message: string, status: 'success'|'error', page: 'cost'}
     */
    public function incidentalCostPayment(array $data, int $userId)
    {

        // The stored share determines the payment amount so clients cannot submit a different price.
        $payable = IncidentalCost::findOrFail($data['incidentalCostId'] ?? null);
        $complexId = $data['complexId'] ?? null;

        $unit = Unit::where('complex_id', $complexId)
            ->where('unit_number', $data['unitNumber'] ?? null)
            ->first();

        $role = null;

        if ($unit) {
            $role = UserRole::where('unit_id', $unit->id)
                ->where('complex_id', $complexId)
                ->where('user_id', $userId)
                ->first();
        } else {
            $status = 'error';
            $message = 'واحد وارد شده ثبت نشده است.';
            $page = 'cost';

            return compact('message', 'status', 'page');
        }

        if ($role) {
            $paymentExists = PaymentAdditionalCost::where('role_id', $role->id)
                ->where('incidental_costs_id', $data['incidentalCostId'] ?? null)
                ->exists();

            if (! $paymentExists) {
                $PaymentAdditionalCost = [
                    'role_id' => $role->id,
                    'incidental_costs_id' => $data['incidentalCostId'] ?? null,
                    'amount' => $payable->share_amount,
                ];

                PaymentAdditionalCost::create($PaymentAdditionalCost);

                $status = 'success';
                $message = 'پرداخت با موفقیت انجام شد.';
                $page = 'cost';
            } else {
                $status = 'error';
                $message = 'امکان پرداخت مجدد هزینه وجود ندارد.';
                $page = 'cost';
            }
        } else {
            $status = 'error';
            $message = 'واحد وارد شده متعلق به شما نیست.';
            $page = 'cost';
        }

        return compact('message', 'status', 'page');
    }

    /**
     * @return array{message: string, status: 'success'|'error', page: 'charge'}
     */
    public function buildingChargePayment(array $data, int $userId)
    {

        $currentDate = Carbon::now();
        $currentJalaliDate = Jalalian::fromCarbon($currentDate);

        $monthlyChargeStartDate = MonthlyCharge::findOrFail($data['monthlyChargeId'])->start_date;

        if ($monthlyChargeStartDate > $currentJalaliDate) {
            $status = 'error';
            $message = 'امکان پرداخت شارژ قبل از فرا رسیدن تاریخ شروع وجود ندارد.';
            $page = 'charge';

            return compact('message', 'status', 'page');
        }

        // The charge record supplies the price independently of the payment request.
        $payable = MonthlyCharge::findOrFail($data['monthlyChargeId'] ?? null);
        $complexId = $data['complexId'] ?? null;
        $unit = Unit::where('complex_id', $complexId)
            ->where('unit_number', $data['unitNumber'] ?? null)
            ->first();

        $role = null;

        if ($unit) {
            $role = UserRole::where('unit_id', $unit->id)
                ->where('complex_id', $complexId)
                ->where('user_id', $userId)
                ->first();
        } else {
            $status = 'error';
            $message = 'واحد وارد شده ثبت نشده است.';
            $page = 'charge';

            return compact('message', 'status', 'page');
        }

        if ($role) {

            // The same charge may be paid once per Jalali month. The unique index uses that same period.
            $payments = ChargePayment::where('role_id', $role->id)
                ->where('monthly_charge_id', $data['monthlyChargeId'] ?? null)
                ->get();

            $paymentExists = $payments->contains(function ($payment) use ($currentJalaliDate) {
                $paymentDate = Jalalian::fromCarbon($payment->created_at);

                return $paymentDate->getYear() === $currentJalaliDate->getYear()
                    && $paymentDate->getMonth() === $currentJalaliDate->getMonth();
            });

            if (! $paymentExists) {
                $ChargePayment = [
                    'role_id' => $role->id,
                    'monthly_charge_id' => $data['monthlyChargeId'] ?? null,
                    'amount' => $payable->amount,
                    'period_year' => $currentJalaliDate->getYear(),
                    'period_month' => $currentJalaliDate->getMonth(),
                ];

                ChargePayment::create($ChargePayment);

                $status = 'success';
                $message = 'پرداخت با موفقیت انجام شد.';
                $page = 'charge';
            } else {
                $status = 'error';
                $message = 'کاربر گرامی، شما شارژ خود را در ماه جاری پرداخت کرده اید.';
                $page = 'charge';
            }
        } else {
            $status = 'error';
            $message = 'واحد وارد شده متعلق به شما نیست.';
            $page = 'charge';
        }

        return compact('message', 'status', 'page');
    }
}
