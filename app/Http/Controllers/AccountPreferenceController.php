<?php

namespace App\Http\Controllers;

use App\Models\Complex;
use App\Services\AccountNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AccountPreferenceController extends Controller
{
    public static function panels(bool $manager): array
    {
        return ['home', 'notification', 'poll', 'cost', 'charge', 'paymentInfo', 'chat', ...($manager ? ['setting'] : [])];
    }

    public function building(Request $request, Complex $complex)
    {
        Gate::authorize('view', $complex);
        $data = $request->validate([
            'panel' => ['required_without:layout', Rule::in(self::panels($request->user()->can('manage', $complex)))],
            'layout' => ['required_without:panel', Rule::in(['gridView', 'listView'])],
        ]);
        DB::table('building_preferences')->upsert(
            $data + ['complex_id' => $complex->id, 'user_id' => $request->user()->id],
            ['user_id', 'complex_id'],
            array_keys($data),
        );

        return response()->noContent();
    }

    public function notifications(Request $request)
    {
        $rules = [];
        foreach (array_keys(AccountNotifications::LABELS) as $event) {
            foreach (['in_app', 'sms'] as $channel) {
                $rules["$event.$channel"] = ['required', 'boolean'];
            }
        }
        $request->user()->forceFill(['notification_preferences' => $request->validate($rules)])->save();

        return back()->with(['page' => 'setting', 'status' => 'success', 'message' => 'تنظیمات اعلان‌ها ذخیره شد.']);
    }

    public function inbox(Request $request)
    {
        return view('profile.notification-inbox', ['notices' => $request->user()->notifications()->paginate(25)]);
    }
}
