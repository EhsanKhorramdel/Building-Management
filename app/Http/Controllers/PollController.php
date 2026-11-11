<?php

namespace App\Http\Controllers;

use App\Http\Middleware\AccessMiddleware;
use App\Http\Requests\PollCreateRequest;
use App\Http\Requests\PollResultsRequest;
use App\Http\Requests\PollUpdateRequest;
use App\Http\Requests\VoteRequest;
use App\Models\Complex;
use App\Models\Poll;
use App\Services\PollService;
use Illuminate\Http\JsonResponse;

class PollController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', AccessMiddleware::class.':member']);
        $this->middleware(AccessMiddleware::class.':manager')->only(['close', 'update', 'ballots']);
    }

    public function close(Complex $complex, Poll $poll, PollService $polls)
    {
        abort_unless($poll->complex_id === $complex->id, 403);

        return back()->with($polls->close($poll, auth()->id()));
    }

    public function update(PollUpdateRequest $request, Complex $complex, Poll $poll, PollService $polls)
    {
        abort_unless($poll->complex_id === $complex->id, 403);

        return back()->with($polls->update($poll, $request->validated()));
    }

    public function ballots(Complex $complex, Poll $poll, PollService $polls)
    {
        abort_unless($poll->complex_id === $complex->id, 403);
        abort_unless($poll->isExpired(), 403);

        return response()->json(['options' => $polls->ballots($poll)]);
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
     * @return JsonResponse Body is list<array{option: string, voteCount: int}>.
     */
    public function voteResult(PollResultsRequest $request, PollService $polls)
    {
        return response()->json($polls->results($request->validated()['poll_id']));
    }
}
