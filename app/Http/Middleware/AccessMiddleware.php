<?php

namespace App\Http\Middleware;

use App\Http\Controllers\UserController;
use App\Models\Complex;
use App\Models\IncidentalCost;
use App\Models\Message;
use App\Models\MonthlyCharge;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Unit;
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
            $unit = Unit::where('complex_id', $complex->id)->where('unit_number', $request->currentUnitNumber)->first();
            if ($unit) {
                abort_unless(Gate::allows('manage', $complex) || $request->user()->userRoles()
                    ->where('complex_id', $complex->id)->where('unit_id', $unit->id)->where('active', true)->exists(), 403);
            }
        }

        return $next($request);
    }

    private function complex(Request $request): Complex
    {
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
        $resource = match ($action) {
            'vote' => PollOption::findOrFail($request->voteRadio)->poll,
            'voteResult' => Poll::findOrFail($request->poll_id),
            'incidentalCostPayment' => IncidentalCost::findOrFail($request->incidentalCostId),
            'buildingChargePayment' => MonthlyCharge::findOrFail($request->monthlyChargeId),
            'getMessageViewers' => Message::findOrFail($request->message_id)->group,
            'edit', 'seen' => Message::findOrFail($request->messageId)->group,
            default => null,
        };
        if ($resource) {
            if (in_array($action, ['incidentalCostPayment', 'buildingChargePayment'], true)) {
                abort_unless((int) $request->complexId === $resource->complex_id, 403);
            }

            return $resource->complex;
        }

        return Complex::findOrFail($request->route('complexId') ?? $request->input('complexId') ?? $request->input('complex_id'));
    }
}
