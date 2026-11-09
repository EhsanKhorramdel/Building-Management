<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\PollCreateRequest;
use App\Http\Requests\PollResultsRequest;
use App\Http\Requests\VoteRequest;
use App\Services\PollService;

class PollController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
    }

    public function pollCreate(PollCreateRequest $request, int $complexId, PollService $polls)
    {
        return redirect()->back()->with($polls->create($complexId, $request->validated()));
    }

    public function vote(VoteRequest $request, PollService $polls)
    {
        return redirect()->back()->with($polls->vote($request->validated()['voteRadio'] ?? null, auth()->id()));
    }

    /**
     * @return \Illuminate\Http\JsonResponse Body is list<array{option: string, voteCount: int}>.
     */
    public function voteResult(PollResultsRequest $request, PollService $polls)
    {
        return response()->json($polls->results($request->validated()['poll_id']));
    }
}
