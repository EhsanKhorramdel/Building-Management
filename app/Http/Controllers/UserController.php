<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\JoinBuildingRequest;
use App\Http\Requests\MembershipRequestStoreRequest;
use App\Http\Requests\UnitStoreRequest;
use App\Http\Requests\UnitUpdateRequest;
use App\Models\Complex;
use App\Models\MembershipRequest;
use App\Models\Unit;
use App\Models\UserRole;
use App\Services\JoinLinks;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(AccessMiddleware::class.':member')->only(['update', 'addUnit']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->id();
        $userName = auth()->user()->name;

        $results = UserRole::where('user_id', $userId)
            ->where('active', 1)
            ->get();

        $managerCount = $results->where('role', 'manager')->pluck('complex_id')->unique()->count();
        $residentCount = $results->where('role', 'resident')->pluck('complex_id')->unique()->count();

        $complexIds = $results->pluck('complex_id')->unique();
        $allComplexes = Complex::whereIn('id', $complexIds)->get();

        return view('dashboard', [
            'userName' => $userName,
            'managerCount' => $managerCount,
            'residentCount' => $residentCount,
            'allComplexes' => $allComplexes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(JoinBuildingRequest $request, JoinLinks $links)
    {
        $data = $request->validated();
        $result = filled($data['link'] ?? null)
            ? $links->redeem($data['link'], $request->user())
            : ['status' => 'error', 'message' => 'خطا! لینک وارد نشده است.'];

        return back()->with($result + ['page' => 'home']);
    }

    public function register(MembershipRequestStoreRequest $request)
    {
        $data = $request->validated();

        $userId = auth()->id();

        $membershipRequest = [
            'user_id' => $userId,
            'name' => $data['name'] ?? null,
            'address' => $data['address'] ?? null,
            'number_of_floors' => $data['floors'] ?? null,
            'number_of_units' => $data['units'] ?? null,
        ];

        MembershipRequest::create($membershipRequest + $request->coordinates());

        $status = 'success';
        $message = 'درخواست عضویت با موفقیت ارسال شد.';
        $page = 'register';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UnitUpdateRequest $request)
    {
        $data = $request->validated();
        $complexId = $data['complexId'] ?? null;
        $unitExists = Unit::where('complex_id', $complexId)
            ->where('unit_number', $data['currentUnitNumber'] ?? null)
            ->exists();

        if (! $unitExists) {
            $message = 'واحد فعلی ثبت نشده است.';
            $status = 'error';
            $page = 'home';
        } else {
            $newUnitExists = Unit::where('complex_id', $complexId)
                ->where('unit_number', $data['newUnitNumber'] ?? null)
                ->exists();

            if ($newUnitExists) {
                $message = 'واحد جدید درخواستی قبلاً ثبت شده است.';
                $status = 'error';
                $page = 'home';
            } else {
                $unitToUpdate = Unit::where('complex_id', $complexId)
                    ->where('unit_number', $data['currentUnitNumber'] ?? null)
                    ->firstOrFail();

                $unitToUpdate->unit_number = $data['newUnitNumber'] ?? null;
                $unitToUpdate->zip_code = $data['zipCode'] ?? null;
                $unitToUpdate->floor_number = $data['floorNumber'] ?? null;
                $unitToUpdate->save();

                $message = ' اطلاعات واحد شما بروزرسانی شد.';
                $status = 'success';
                $page = 'home';
            }
        }

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function addUnit(UnitStoreRequest $request)
    {
        $data = $request->validated();
        $userId = auth()->id();
        $complexId = $data['complexId'] ?? null;

        $unit = [
            'complex_id' => $complexId,
            'zip_code' => $data['zipCode'] ?? null,
            'unit_number' => $data['unitNumber'] ?? null,
            'floor_number' => $data['floorNumber'] ?? null,
        ];

        $newUnit = Unit::firstOrNew(
            ['unit_number' => $data['unitNumber'] ?? null, 'complex_id' => $complexId],
            $unit
        );

        if (! $newUnit->exists) {
            $newUnit->save();
        }

        $unitId = $newUnit->id;

        $userRoles = UserRole::where('complex_id', $complexId)->get();
        $unitExistsInUserRoles = $userRoles->contains('unit_id', $unitId);

        if ($unitExistsInUserRoles) {
            $message = ' این واحد قبلا ثبت شده است.';
            $status = 'error';
            $page = 'home';
        } else {
            $userRoleToUpdate = $userRoles->first(function ($userRole) use ($userId) {
                return $userRole->unit_id === null && $userRole->user_id === $userId;
            });

            if ($userRoleToUpdate) {
                $userRoleToUpdate->unit_id = $unitId;
                $userRoleToUpdate->save();
            } else {
                $newUserRole = new UserRole;
                $newUserRole->complex_id = $complexId;
                $newUserRole->unit_id = $unitId;
                $newUserRole->user_id = $userId;

                $userRoleWithRole = $userRoles->first(function ($userRole) use ($userId) {
                    return $userRole->user_id === $userId && $userRole->role !== null;
                });

                if ($userRoleWithRole) {
                    $newUserRole->role = $userRoleWithRole->role;
                } else {
                    $newUserRole->role = 'resident';
                }

                $newUserRole->save();
            }

            $message = 'واحد شما با موفقیت ثبت شد.';
            $status = 'success';
            $page = 'home';
        }

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }
}
