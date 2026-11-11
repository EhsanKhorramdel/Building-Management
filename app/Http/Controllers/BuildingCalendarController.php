<?php

namespace App\Http\Controllers;

use App\Models\Complex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Morilog\Jalali\Jalalian;

class BuildingCalendarController extends Controller
{
    public function __invoke(Request $request, Complex $complex)
    {
        Gate::authorize('view', $complex);
        $data = $request->validate(['year' => 'sometimes|integer|between:1300,1499', 'month' => 'sometimes|integer|between:1,12']);
        $today = Jalalian::fromCarbon(now());
        $month = new Jalalian((int) ($data['year'] ?? $today->getYear()), (int) ($data['month'] ?? $today->getMonth()), 1);
        $next = $month->addMonths(1);
        $start = $month->toCarbon()->startOfDay();
        $end = $next->toCarbon()->startOfDay();
        $entries = [];

        foreach ($complex->monthlyCharges()->where('start_date', '<', $next->format('Y-m-d'))
            ->where('end_date', '>=', $month->format('Y-m-d'))->get() as $charge) {
            $firstDay = (int) substr(max($charge->start_date, $month->format('Y-m-d')), -2);
            $lastDay = (int) substr(min($charge->end_date, $next->subDays(1)->format('Y-m-d')), -2);
            for ($day = $firstDay; $day <= $lastDay; $day++) {
                $entries[$day][] = ['panel' => 'charge', 'label' => 'شارژ: '.$charge->amount];
            }
        }
        foreach ($complex->polls()->where('end_date', '>=', $start)->where('end_date', '<', $end)->get() as $poll) {
            $entries[Jalalian::fromCarbon($poll->end_date)->getDay()][] = ['panel' => 'poll', 'label' => 'مهلت رای‌گیری: '.($poll->title ?: $poll->question)];
        }
        foreach ($complex->incidentalCosts()->where('created_at', '>=', $start)->where('created_at', '<', $end)->get() as $cost) {
            $entries[Jalalian::fromCarbon($cost->created_at)->getDay()][] = ['panel' => 'cost', 'label' => 'هزینه جانبی: '.$cost->title];
        }

        return view('building.calendar', ['complex' => $complex, 'month' => $month, 'next' => $next, 'previous' => $month->subMonths(1), 'days' => $next->subDays(1)->getDay(), 'entries' => $entries]);
    }
}
