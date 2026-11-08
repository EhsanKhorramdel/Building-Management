<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Models\Complex;
use App\Models\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':manager']);
    }

    public function upgradeRole(UserRole $userRole)
    {
        $complexId = $userRole->complex_id;
        $userId = $userRole->user_id;
        $complex = Complex::findOrFail($complexId);

        if ($userRole->role == 'manager') {
            $message = 'خطا! کاربر انتخابی مدیر ساختمان است.';
            $status = 'error';
            $page = 'home';
        } elseif ($userRole->role == 'resident') {

            $complex->userRoles()
                ->where('role', 'resident')
                ->where('user_id', $userId)
                ->where('complex_id', $complexId)
                ->update(['role' => 'manager']);

            $message = 'نقش کاربر به مدیر تغییر یافت.';
            $status = 'success';
            $page = 'home';
        }

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function downgradeRole(Complex $complex)
    {
        $userId = auth()->id();
        $complexId = $complex->id;

        $managers = $complex->userRoles->where('role', 'manager')->pluck('user_id')->unique();
        $managersNum = $managers->count();

        if (--$managersNum) {
            $complex->userRoles()
                ->where('role', 'manager')
                ->where('user_id', $userId)
                ->where('complex_id', $complexId)
                ->update(['role' => 'resident']);

            Auth::guard('web')->logout();

            return redirect()->route('login');
        }

        $message = 'خطا! کاربر محترم شما تنها مدیر ساختمان می باشید .';
        $status = 'error';
        $page = 'setting';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }

    public function regenerateJoinLink(Complex $complex)
    {
        $complex->update(['join_link' => Str::random(32)]);

        return redirect()->back()->with(['status' => 'success', 'message' => 'لینک عضویت بروزرسانی شد.', 'page' => 'setting']);
    }
}
