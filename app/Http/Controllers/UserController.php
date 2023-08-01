<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MembershipRequest;
use App\Models\UserRole;
use App\Models\Complex;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Auth\Notifications\ResetPassword;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->id();
        $userName = User::where('id', $userId)->first()->name;

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
    public function store(Request $request)
    {
        if ($request->filled('link')) {
            $request->validate([
                'link' => 'string',
            ]);

            $userId = auth()->id();
            $link = $request->link;

            $complex = Complex::where('join_link', $link)->first();

            if ($complex) {
                $isUserAdded = UserRole::where('complex_id', $complex->id)
                    ->where('user_id', $userId)
                    ->where('active', '1')
                    ->where('role', 'resident')
                    ->exists();

                if ($isUserAdded) {
                    $message = 'خطا! شما قبلاً این ساختمان را اضافه کرده‌اید.';
                    $status = 'error';
                } else {
                    $userRole = [
                        'complex_id' => $complex->id,
                        'user_id' => $userId,
                        'role' => 'resident',
                    ];
                    UserRole::create($userRole);

                    $message = 'عملیات با موفقیت انجام شد.';
                    $status = 'success';
                }
            } else {
                $message = 'خطا! لینک وارد شده نامعتبر است.';
                $status = 'error';
            }
        } else {
            $message = 'خطا! لینک وارد نشده است.';
            $status = 'error';
        }
        $page = 'home';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string',
            'address' => 'required|string',
            'floors' => 'required|integer|min:1',
            'units' => 'required|integer|min:1',
        ];

        $messages = [
            'name.required' => 'فیلد نام الزامی است.',
            'name.string' => 'فیلد نام باید یک رشته باشد.',

            'address.required' => 'فیلد آدرس الزامی است.',
            'address.string' => 'فیلد آدرس باید یک رشته باشد.',

            'floors.required' => 'فیلد تعداد طبقات الزامی است.',
            'floors.integer' => 'فیلد تعداد طبقات باید عدد صحیح باشد.',
            'floors.min' => 'تعداد طبقات باید بزرگتر یا مساوی ۱ باشد.',

            'units.required' => 'فیلد تعداد واحدها الزامی است.',
            'units.integer' => 'فیلد تعداد واحدها باید عدد صحیح باشد.',
            'units.min' => 'تعداد واحدها باید بزرگتر یا مساوی ۱ باشد.',
        ];

        $validated = $request->validateWithBag('registerBuilding', $rules, $messages);

        $userId = auth()->id();

        $membershipRequest = [
            'user_id' => $userId,
            'name' => $request->name,
            'address' => $request->address,
            'number_of_floors' => $request->floors,
            'number_of_units' => $request->units,
        ];

        MembershipRequest::create($membershipRequest);

        $status = 'success';
        $message = 'درخواست عضویت با موفقیت ارسال شد.';
        $page = 'register';


        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $complexId = $request->complexId;
        $unitExists = Unit::where('complex_id', $complexId)
            ->where('unit_number', $request->currentUnitNumber)
            ->exists();

        if (!$unitExists) {
            $message = 'واحد فعلی ثبت نشده است.';
            $status = 'error';
            $page = 'home';
        } else {
            $newUnitExists = Unit::where('complex_id', $complexId)
                ->where('unit_number', $request->newUnitNumber)
                ->exists();

            if ($newUnitExists) {
                $message = 'واحد جدید درخواستی قبلاً ثبت شده است.';
                $status = 'error';
                $page = 'home';
            } else {
                $unitToUpdate = Unit::where('complex_id', $complexId)
                    ->where('unit_number', $request->currentUnitNumber)
                    ->first();

                $unitToUpdate->unit_number = $request->newUnitNumber;
                $unitToUpdate->zip_code = $request->zipCode;
                $unitToUpdate->floor_number = $request->floorNumber;
                $unitToUpdate->save();

                $message = ' اطلاعات واحد شما بروزرسانی شد.';
                $status = 'success';
                $page = 'home';
            }
        }


        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function addUnit(Request $request)
    {
        $userId = auth()->id();
        $complexId = $request->complexId;

        $unit = [
            'complex_id' => $complexId,
            'zip_code' => $request->zipCode,
            'unit_number' => $request->unitNumber,
            'floor_number' => $request->floorNumber,
        ];

        $newUnit = Unit::firstOrNew(
            ['unit_number' => $request->unitNumber, 'complex_id' => $complexId],
            $unit
        );

        if (!$newUnit->exists) {
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
                $newUserRole = new UserRole();
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
