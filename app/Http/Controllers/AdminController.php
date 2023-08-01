<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MembershipRequest;
use App\Models\UserRole;
use App\Models\Complex;
use App\Models\Group;
use App\Http\Middleware\AccessMiddleware;



class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(AccessMiddleware::class . ':admin');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allMembershipRequests = MembershipRequest::all();

        return  view('adminPanel', ['allMembershipRequests' => $allMembershipRequests]);
    }

    public function accept(MembershipRequest $MembershipRequest)
    {
        $complex = [
            'name' => $MembershipRequest->name,
            'address' => $MembershipRequest->address,
            'location' => $MembershipRequest->location,
            'number_of_floors' => $MembershipRequest->number_of_floors,
            'number_of_units' => $MembershipRequest->number_of_units,
            'join_link' => uniqid(),
        ];
        $newComplex = Complex::Create($complex);


        $userRole = [
            'complex_id' => $newComplex->id,
            'user_id' => $MembershipRequest->user_id,
            'role' => 'manager',

        ];
        UserRole::Create($userRole);

        $group = [
            'complex_id' => $newComplex->id,
            'group_name' => $MembershipRequest->name,
        ];
        Group::Create($group);

        $MembershipRequest->request_status = 'accepted';
        $MembershipRequest->save();

        return redirect()->back();
    }

    public function reject(MembershipRequest $MembershipRequest)
    {

        $MembershipRequest->request_status = 'rejected';
        $MembershipRequest->save();

        return redirect()->back();
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //
    }
}
