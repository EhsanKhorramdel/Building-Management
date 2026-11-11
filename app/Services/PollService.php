<?php

namespace App\Services;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\UserRole;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PollService
{
    /**
     * @return array{status: 'success'|'error', message: string, page: 'poll'}
     */
    public function create(int $complexId, array $data): array
    {
        $options = [];
        foreach ($data as $key => $value) {
            if (preg_match('/^option([0-9]+)$/', $key, $matches) && $value !== null && $value !== '') {
                $options[(int) $matches[1]] = $value;
            }
        }
        ksort($options);
        if (count($options) < 2) {
            return ['status' => 'error', 'message' => 'کاربر محترم حداقل دو گزینه برای ایجاد رای گیری ضروری است.', 'page' => 'poll'];
        }
        $now = Carbon::now();
        $poll = Poll::create([
            'complex_id' => $complexId, 'title' => $data['title'] ?? null, 'question' => $data['question'],
            'start_date' => $now,
            'end_date' => $now->copy()
                ->addMinutes($data['endMinutes'] ?? 0)
                ->addHours($data['endHours'] ?? 0)
                ->addDays($data['endDays'] ?? 0),
        ]);
        foreach (array_values($options) as $index => $text) {
            $poll->pollOptions()->create(['option_number' => $index + 1, 'option' => $text]);
        }

        app(AccountNotifications::class)->building('poll_opened', $complexId, $poll->title ?: $poll->question, 'poll');
        app(SmsService::class)->queue('poll_opened', $poll->id);

        return ['status' => 'success', 'message' => 'رای گیری با موفقیت ایجاد شد', 'page' => 'poll'];
    }

    /**
     * @return array{status: 'success'|'error', message: string, page: 'poll'}
     */
    public function vote($optionId, int $userId): array
    {
        return DB::transaction(function () use ($optionId, $userId) {
            if (! $optionId) {
                return ['status' => 'error', 'message' => 'کاربر محترم، شما گزینه ای انتخاب نکرده اید.', 'page' => 'poll'];
            }
            $option = PollOption::with('poll')->findOrFail($optionId);
            $option->setRelation('poll', Poll::lockForUpdate()->findOrFail($option->poll_id));
            if ($option->poll->isExpired()) {
                return ['status' => 'error', 'message' => 'مهلت شرکت در این رای گیری به پایان رسیده است.', 'page' => 'poll'];
            }
            $roles = UserRole::where('complex_id', $option->poll->complex_id)->where('user_id', $userId)->get();
            $message = 'رای شما ثبت شد';
            foreach ($roles as $role) {
                $vote = Vote::updateOrCreate(['poll_id' => $option->poll_id, 'role_id' => $role->id], ['poll_option_id' => $option->id]);
                $message = $vote->wasRecentlyCreated ? 'رای شما ثبت شد' : 'رای شما بروزرسانی شد';
            }

            return ['status' => 'success', 'message' => $message, 'page' => 'poll'];
        });
    }

    public function close(Poll $poll, int $actorId): array
    {
        return DB::transaction(function () use ($poll, $actorId) {
            $poll = Poll::lockForUpdate()->findOrFail($poll->id);
            if ($poll->isExpired()) {
                return ['status' => 'error', 'message' => 'این رای گیری بسته شده است.', 'page' => 'poll'];
            }
            $poll->forceFill(['closed_at' => now(), 'closed_by' => $actorId])->save();

            return ['status' => 'success', 'message' => 'رای گیری بسته شد.', 'page' => 'poll'];
        });
    }

    public function update(Poll $poll, array $data): array
    {
        return DB::transaction(function () use ($poll, $data) {
            $poll = Poll::lockForUpdate()->findOrFail($poll->id);
            if ($poll->votes()->exists()) {
                return ['status' => 'error', 'message' => 'پس از ثبت اولین رای، ویرایش ممکن نیست.', 'page' => 'poll'];
            }
            $poll->update(['title' => $data['title'] ?? null, 'question' => $data['question'], 'end_date' => $data['end_date']]);

            return ['status' => 'success', 'message' => 'رای گیری ویرایش شد.', 'page' => 'poll'];
        });
    }

    /** @return list<array{option: string, voters: list<string>}> */
    public function ballots(Poll $poll): array
    {
        abort_unless($poll->isExpired(), 403);

        return $poll->pollOptions()->with(['votes' => fn ($query) => $query->where('poll_id', $poll->id)->with('role.user')])->orderBy('id')->get()->map(function ($option) use ($poll) {
            $votes = $option->votes->filter(fn ($vote) => $vote->role?->complex_id === $poll->complex_id && $vote->role->user !== null)
                ->unique(fn ($vote) => $vote->role->user_id)->values();
            $result = ['option' => $option->option, 'voters' => $votes->map(fn ($vote) => $vote->role->user->name)->all()];
            $avatars = $votes->map(fn ($vote) => $vote->role->active ? $vote->role->user->avatarUrl() : null);
            if ($avatars->filter()->isNotEmpty()) {
                $result['avatars'] = $avatars->all();
            }

            return $result;
        })->all();
    }

    /**
     * The left join keeps options with zero votes in the same ordered result.
     *
     * @return list<array{option: string, voteCount: int}>
     */
    public function results(int $pollId): array
    {
        return PollOption::leftJoin('votes', function ($join) {
            $join->on('votes.poll_option_id', '=', 'poll_options.id')->on('votes.poll_id', '=', 'poll_options.poll_id');
        })->where('poll_options.poll_id', $pollId)
            ->groupBy('poll_options.id', 'poll_options.option')->orderBy('poll_options.id')
            ->select('poll_options.option')->selectRaw('COUNT(votes.id) as voteCount')->get()
            ->map(fn ($option) => ['option' => $option->option, 'voteCount' => (int) $option->voteCount])->all();
    }
}
