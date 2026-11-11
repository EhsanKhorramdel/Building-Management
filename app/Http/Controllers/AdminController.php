<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Models\Complex;
use App\Models\Group;
use App\Models\MembershipRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AuditLogger;
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

    public function audit()
    {
        return view('admin-audit');
    }

    public function accept(MembershipRequest $MembershipRequest, SmsService $sms, AuditLogger $audit)
    {
        $complex = DB::transaction(function () use ($MembershipRequest) {
            $request = MembershipRequest::whereKey($MembershipRequest->id)->lockForUpdate()->firstOrFail();
            if ($request->request_status !== 'pending') {
                return null;
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

            return $complex;
        });

        if (! $complex) {
            return redirect()->back()->with(['status' => 'error', 'message' => 'این درخواست قبلاً بررسی شده است.']);
        }

        $audit->record('membership.approved', auth()->id(), $complex->id, User::class, $MembershipRequest->user_id, ['complex_name' => $complex->name]);
        $sms->queue('membership_approved', $MembershipRequest->user_id);

        return redirect()->back();
    }

    public function reject(MembershipRequest $MembershipRequest, AuditLogger $audit)
    {

        $MembershipRequest->request_status = 'rejected';
        $MembershipRequest->save();

        if ($MembershipRequest->wasChanged('request_status')) {
            $audit->record('membership.rejected', auth()->id(), null, User::class, $MembershipRequest->user_id, ['complex_name' => $MembershipRequest->name]);
        }

        return redirect()->back();
    }
}
