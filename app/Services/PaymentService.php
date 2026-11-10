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
    public function chargeHasStarted(MonthlyCharge $charge, Jalalian $period): bool
    {
        return ! ($charge->start_date > $period);
    }

    public function payableAmount(MonthlyCharge|IncidentalCost $payable): float|int|string
    {
        return $payable instanceof MonthlyCharge ? $payable->amount : $payable->share_amount;
    }

    public function alreadyPaid(MonthlyCharge|IncidentalCost $payable, UserRole $role, ?Jalalian $period = null): bool
    {
        if ($payable instanceof IncidentalCost) {
            return PaymentAdditionalCost::where('role_id', $role->id)->where('incidental_costs_id', $payable->id)->exists();
        }

        $period ??= Jalalian::fromCarbon(now());

        // Manual and online payments use the same Jalali period check.
        return ChargePayment::where('role_id', $role->id)->where('monthly_charge_id', $payable->id)
            ->get()->contains(function (ChargePayment $payment) use ($period) {
                $date = Jalalian::fromCarbon($payment->created_at);

                return $date->getYear() === $period->getYear() && $date->getMonth() === $period->getMonth();
            });
    }

    public function recordPayment(MonthlyCharge|IncidentalCost $payable, UserRole $role, ?Jalalian $period = null): void
    {
        $amount = $this->payableAmount($payable);
        if ($payable instanceof IncidentalCost) {
            PaymentAdditionalCost::create(['role_id' => $role->id, 'incidental_costs_id' => $payable->id, 'amount' => $amount]);

            return;
        }

        $period ??= Jalalian::fromCarbon(now());
        ChargePayment::create([
            'role_id' => $role->id,
            'monthly_charge_id' => $payable->id,
            'amount' => $amount,
            'period_year' => $period->getYear(),
            'period_month' => $period->getMonth(),
        ]);
    }

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
            if (! $this->alreadyPaid($payable, $role)) {
                $this->recordPayment($payable, $role);

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

        $payable = MonthlyCharge::findOrFail($data['monthlyChargeId']);

        if (! $this->chargeHasStarted($payable, $currentJalaliDate)) {
            $status = 'error';
            $message = 'امکان پرداخت شارژ قبل از فرا رسیدن تاریخ شروع وجود ندارد.';
            $page = 'charge';

            return compact('message', 'status', 'page');
        }

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

            if (! $this->alreadyPaid($payable, $role, $currentJalaliDate)) {
                $this->recordPayment($payable, $role, $currentJalaliDate);

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
