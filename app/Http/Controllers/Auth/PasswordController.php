<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {

        $rules = [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];

        $messages = [
            'current_password.required' => 'فیلد رمز عبور فعلی الزامی است.',
            'current_password.current_password' => 'رمز عبور فعلی نادرست است.',

            'password.required' => 'فیلد رمز عبور جدید الزامی است.',
            'password.confirmed' => 'تأیید رمز عبور جدید با رمز عبور وارد شده مطابقت ندارد.',
        ];

        $validated = $request->validateWithBag('updatePassword', $rules, $messages);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $status = 'success';
        $message = 'کلمه عبور با موفقت تغییر یافت';
        $page = 'setting';

        return redirect()->back()->with(compact('message', 'status', 'page'));
    }
}
