<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PaymentsReportController;
use App\Http\Controllers\UserController;
use App\Models\Complex;
use App\Models\IncidentalCost;
use App\Models\Message;
use App\Models\MonthlyCharge;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Unit;
use App\Support\TypedInput;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AccessMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if ($role === 'admin') {
            abort_unless($request->user()->userRoles()->where('role', 'admin')->where('active', true)->exists(), 403);

            return $next($request);
        }

        if ($request->route()->getActionMethod() === 'vote' && ! $request->filled('voteRadio')) {
            return $next($request);
        }

        $complex = $this->complex($request);
        Gate::authorize($role === 'manager' ? 'manage' : 'view', $complex);

        if ($request->route()->getActionMethod() === 'update'
            && $request->route()->getControllerClass() === UserController::class) {
            $unit = Unit::where('complex_id', $complex->id)
                ->where('unit_number', TypedInput::nullableInt($request->input('currentUnitNumber')))->first();
            if ($unit) {
                abort_unless(Gate::allows('manage', $complex) || $request->user()->userRoles()
                    ->where('complex_id', $complex->id)->where('unit_id', $unit->id)->where('active', true)->exists(), 403);
            }
        }

        return $next($request);
    }

    private function complex(Request $request): Complex
    {
        if ($request->route()->getControllerClass() === PaymentsReportController::class) {
            return Complex::findOrFail(TypedInput::nullableInt($request->input('complex_id')));
        }

        foreach (['complex', 'userRole', 'announcement', 'Message'] as $key) {
            $resource = $request->route($key);
            if ($resource instanceof Complex) {
                return $resource;
            }
            if ($resource instanceof Message) {
                return $resource->group->complex;
            }
            if (is_object($resource)) {
                return $resource->complex;
            }
        }

        $action = $request->route()->getActionMethod();
        $id = fn (string $field) => TypedInput::nullableInt($request->input($field));
        $resource = match ($action) {
            'vote' => PollOption::findOrFail($id('voteRadio'))->poll,
            'voteResult' => Poll::findOrFail($id('poll_id')),
            'incidentalCostPayment' => IncidentalCost::findOrFail($id('incidentalCostId')),
            'buildingChargePayment' => MonthlyCharge::findOrFail($id('monthlyChargeId')),
            'getMessageViewers' => Message::findOrFail($id('message_id'))->group,
            'edit', 'seen' => Message::findOrFail($id('messageId'))->group,
            default => null,
        };
        if ($resource) {
            if (in_array($action, ['incidentalCostPayment', 'buildingChargePayment'], true)) {
                // Unit lookup must use the same complex as the payable record.
                abort_unless($id('complexId') === $resource->complex_id, 403);
            }

            return $resource->complex;
        }

        return Complex::findOrFail($request->route('complexId') ?? $id('complexId') ?? $id('complex_id'));
    }
}
