<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use App\Models\User;


class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();

        $user->name = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->national_code = $validatedData['ncode'];
        $user->phone_number = $validatedData['phoneNumber'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $status = 'success';
        $message = 'پروفایل شما با موفقیت بروزرسانی شد.';
        $page = 'setting';

        return redirect()->back()->with(compact('message', 'status', 'page'));


        //----------------------------------------------------------------


        // $rules = [
        //     'name' => ['required', 'string', 'max:255'],
        //     'email' => ['required', 'email', 'max:255', Rule::unique(User::class)->ignore(Auth::user()->id)],
        //     'ncode' => ['nullable', 'string', 'regex:/^[0-9]{1,10}$/'],
        //     'phoneNumber' => ['nullable', 'string', 'regex:/^[0-9]{1,13}$/'],
        // ];

        // $messages = [
        //     'name.required' => 'فیلد نام الزامی است.',
        //     'name.string' => 'فیلد نام باید یک رشته باشد.',
        //     'name.max' => 'طول فیلد نام نباید بیشتر از 255 کاراکتر باشد.',

        //     'email.required' => 'فیلد ایمیل الزامی است.',
        //     'email.email' => 'فرمت ایمیل وارد شده صحیح نیست.',
        //     'email.max' => 'طول فیلد ایمیل نباید بیشتر از 255 کاراکتر باشد.',
        //     'email.unique' => 'این ایمیل قبلاً استفاده شده است.',

        //     'ncode.regex' => 'فیلد کد ملی باید شامل 10 عدد باشد.',

        //     'phoneNumber.regex' => 'فیلد شماره تلفن باید شامل 13 عدد باشد.',
        // ];

        // $validated = $request->validateWithBag('profileUpdate', $rules, $messages);

        // $user = Auth::user();

        // $user->name = $validated['name'];
        // $user->email = $validated['email'];
        // $user->national_code = $validated['ncode'];
        // $user->phone_number = $validated['phoneNumber'];


        // $user->save();

        // $status = 'success';
        // $message = 'پروفایل شما با موفقیت بروزرسانی شد.';
        // $page = 'setting';

        // return redirect()->back()->with(compact('message', 'status', 'page'));



    }


    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
