<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Models\Complex;
use App\Models\Group;
use App\Models\MonthlyCharge;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Vote;
use App\Services\AnnouncementService;
use App\Services\Payments\OnlineHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
            ->addSelect(['user_roles.*',
                'saved_panel' => DB::table('building_preferences')->select('panel')->whereColumn('complex_id', 'user_roles.complex_id')->whereColumn('user_id', 'user_roles.user_id'),
                'saved_layout' => DB::table('building_preferences')->select('layout')->whereColumn('complex_id', 'user_roles.complex_id')->whereColumn('user_id', 'user_roles.user_id'),
            ])->get();

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
        $announcements = app(AnnouncementService::class)->listing($complex, $userLogInId)->where('archive', false)->get();
        $incidentalCosts = $complex->incidentalCosts;
        foreach ($announcements as $announcement) {
            $announcement->managerName = $announcement->manager?->name ?? 'N/A';
        }
        $polls = $complex->polls()->with('pollOptions')->withCount('votes')->addSelect([
            'current_option_id' => Vote::select('poll_option_id')->whereColumn('poll_id', 'polls.id')
                ->whereIn('role_id', $userRoles->pluck('id'))->orderByDesc('id')->limit(1),
        ])->get();
        $polls = $polls->sortByDesc('end_date');
        $currentDate = Carbon::now();
        $expiredCount = 0;
        $notExpiredCount = 0;

        $monthlyCharges = MonthlyCharge::where('complex_id', $complex->id)
            ->where('end_date', '>', Jalalian::fromDateTime($currentDate)->format('Y-m-d'))
            ->get();

        foreach ($polls as $poll) {
            if ($poll->isExpired()) {
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
        $activeMembers = $userRole->where('active', true)->whereIn('role', ['manager', 'resident'])->pluck('user_id')->flip();
        $residents = [];
        foreach ($users as $user) {
            foreach ($rolesByUser->get($user->id, collect()) as $role) {
                $residents[] = [
                    'role' => $role->role ?? 'N/A',
                    'roleId' => $role->id,
                    'user' => $activeMembers->has($user->id) ? $user : null,
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
            'savedPanel' => $userRoles->first()?->saved_panel,
            'savedLayout' => $userRoles->first()?->saved_layout,
        ]);

        if (config('payments.enabled')) {
            $view->with(app(OnlineHistory::class)->page($complex->id, $userLogInId));
        }

        return $view;
    }
}
