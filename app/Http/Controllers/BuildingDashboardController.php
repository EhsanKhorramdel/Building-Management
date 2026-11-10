<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Models\Complex;
use App\Models\Group;
use App\Models\IncidentalCost;
use App\Models\MonthlyCharge;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

class BuildingDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
    }

    public function index(Complex $complex)
    {
        $userRoles = UserRole::where('complex_id', $complex->id)
            ->where('user_id', auth()->id())
            ->get();

        $hasNullUnit = $userRoles->contains(function ($userRole) {
            return $userRole->unit_id === null;
        });

        if ($hasNullUnit) {
            $message = 'کاربر محترم، لطفاً ابتدا تمامی واحدهای خود در این ساختمان را ثبت کنید.';
            $status = 'error';
            $page = 'home';

            return redirect()->back()->with(compact('message', 'status', 'page'));
        }

        $userRole = $complex->userRoles;
        $units = $complex->units->keyBy('id');

        $userIds = $userRole->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $complex->users = $users;
        $complex->user_roles = $userRole;

        $managerCount = $userRole->where('role', 'manager')->pluck('user_id')->unique()->count();
        $residentCount = $userRole->where('role', 'resident')->pluck('user_id')->unique()->count();

        $userLogInId = auth()->id();
        $isManager = $userRole->contains(fn ($role) => $role->user_id === $userLogInId && $role->active && $role->role === 'manager');
        $announcements = $complex->announcements()->with('manager')->get();
        $incidentalCosts = $complex->incidentalCosts;
        foreach ($announcements as $announcement) {
            $announcement->managerName = $announcement->manager?->name ?? 'N/A';
        }
        $polls = $complex->polls()->with('pollOptions')->get();
        $polls = $polls->sortByDesc('end_date');
        $currentDate = Carbon::now();
        $expiredCount = 0;
        $notExpiredCount = 0;

        $monthlyCharges = MonthlyCharge::where('complex_id', $complex->id)
            ->where('end_date', '>', Jalalian::fromDateTime($currentDate)->format('Y-m-d'))
            ->get();

        foreach ($polls as $poll) {
            $endDate = Carbon::parse($poll['end_date']);

            if ($endDate->isPast()) {
                $expiredCount++;
            } else {
                $notExpiredCount++;
            }
        }

        foreach ($polls as $poll) {
            $poll->poll_options = $poll->pollOptions;
        }

        $group = Group::where('complex_id', $complex->id)->firstOrFail();

        $rolesByUser = $userRole->groupBy('user_id');
        $residents = [];
        foreach ($users as $user) {
            foreach ($rolesByUser->get($user->id, collect()) as $role) {
                $residents[] = [
                    'role' => $role->role ?? 'N/A',
                    'roleId' => $role->id,
                    'name' => $users->get($role->user_id)?->name ?? 'N/A',
                    'unitNumber' => $units->get($role->unit_id)?->unit_number ?? 'N/A',
                ];
            }
        }

        $view = view('building.building', [
            'complex' => $complex,
            'managerCount' => $managerCount,
            'residentCount' => $residentCount,
            'isManager' => $isManager,
            'announcements' => $announcements,
            'expiredCount' => $expiredCount,
            'notExpiredCount' => $notExpiredCount,
            'polls' => $polls,
            'incidentalCosts' => $incidentalCosts,
            'monthlyCharges' => $monthlyCharges,
            'group' => $group,
            'residents' => $residents,
            'userLogInId' => $userLogInId,
        ]);

        if (config('payments.enabled')) {
            $view->with('onlineTransactions', Transaction::with('payable')->where('user_id', $userLogInId)
                ->whereHasMorph('payable', [MonthlyCharge::class, IncidentalCost::class], fn ($query) => $query->where('complex_id', $complex->id))
                ->latest('id')->get());
        }

        return $view;
    }
}
