<?php

namespace App\Services;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\UserRole;
use App\Models\Vote;
use Carbon\Carbon;

class PollService
{
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
            'end_date' => $now->copy()->addMinutes($data['endMinutes'] ?? null)->addHours($data['endHours'] ?? null)->addDays($data['endDays'] ?? null),
        ]);
        foreach (array_values($options) as $index => $text) {
            $poll->pollOptions()->create(['option_number' => $index + 1, 'option' => $text]);
        }

        return ['status' => 'success', 'message' => 'رای گیری با موفقیت ایجاد شد', 'page' => 'poll'];
    }

    public function vote($optionId, int $userId): array
    {
        if (! $optionId) {
            return ['status' => 'error', 'message' => 'کاربر محترم، شما گزینه ای انتخاب نکرده اید.', 'page' => 'poll'];
        }
        $option = PollOption::with('poll')->findOrFail($optionId);
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
    }

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
