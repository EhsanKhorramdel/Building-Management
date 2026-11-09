<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Models\Complex;
use App\Models\Group;
use App\Models\MembershipRequest;
use App\Models\UserRole;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(AccessMiddleware::class.':admin');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allMembershipRequests = MembershipRequest::all();

        return view('adminPanel', ['allMembershipRequests' => $allMembershipRequests]);
    }

    public function accept(MembershipRequest $MembershipRequest, SmsService $sms)
    {
        $accepted = DB::transaction(function () use ($MembershipRequest) {
            $request = MembershipRequest::whereKey($MembershipRequest->id)->lockForUpdate()->firstOrFail();
            if ($request->request_status !== 'pending') {
                return false;
            }

            $complex = Complex::create([
                'name' => $request->name,
                'address' => $request->address,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'number_of_floors' => $request->number_of_floors,
                'number_of_units' => $request->number_of_units,
                'join_link' => Str::random(32),
            ]);
            UserRole::create([
                'complex_id' => $complex->id,
                'user_id' => $request->user_id,
                'role' => 'manager',
            ]);
            Group::create([
                'complex_id' => $complex->id,
                'group_name' => $request->name,
            ]);
            $request->request_status = 'accepted';
            $request->save();

            return true;
        });

        if (! $accepted) {
            return redirect()->back()->with(['status' => 'error', 'message' => 'این درخواست قبلاً بررسی شده است.']);
        }

        $sms->queue('membership_approved', $MembershipRequest->user_id);

        return redirect()->back();
    }

    public function reject(MembershipRequest $MembershipRequest)
    {

        $MembershipRequest->request_status = 'rejected';
        $MembershipRequest->save();

        return redirect()->back();
    }
}
