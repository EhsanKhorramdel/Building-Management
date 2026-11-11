<section class="settings-card mt-4 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4" data-audit-events>
    <h2 class="mb-3 font-bold">رویدادهای اخیر</h2>
    @if ($adminDetail)
        <form method="get" class="mb-3 flex flex-wrap items-end gap-3">
            <label class="grid gap-1">ساختمان
                <select name="complex" class="min-h-10">
                    <option value="">همه</option>
                    @foreach ($complexes as $building)
                        <option value="{{ $building->id }}" @selected(request('complex') == $building->id)>{{ $building->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="grid gap-1">نوع رویداد
                <select name="event" class="min-h-10">
                    <option value="">همه</option>
                    @foreach ($eventTypes as $eventType)
                        <option value="{{ $eventType }}" @selected(request('event') === $eventType)>{{ __('audit.' . $eventType) }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="action">اعمال فیلتر</button>
            <a href="{{ route('adminPanel.audit') }}" class="action">پاک کردن فیلترها</a>
        </form>
    @endif
    @if ($events->isEmpty())
        <p>هنوز رویدادی ثبت نشده است.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-start">
                <thead>
                    <tr class="border-b">
                        <th scope="col" class="p-2 text-start">رویداد</th>
                        <th scope="col" class="p-2 text-start">کاربر</th>
                        <th scope="col" class="p-2 text-start">ساختمان</th>
                        @if ($detail)<th scope="col" class="p-2 text-start">جزئیات</th>@endif
                        <th scope="col" class="p-2 text-start">زمان</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr class="border-b">
                            <td class="p-2" data-audit-event="{{ $event->event }}">{{ __('audit.' . $event->event) }}</td>
                            <td class="p-2">
                                @if ($event->actor)<x-user-avatar :user="$avatarUsers === null || $avatarUsers->has($event->actor_id) ? $event->actor : null" :name="$event->actor->name" /> {{ $event->actor->name }}@endif
                            </td>
                            <td class="p-2">{{ $event->complex?->name ?? '—' }}</td>
                            @if ($detail)
                                <td class="p-2">
                                    @php
                                        $subject = $event->subject_type === \App\Models\User::class ? $subjects->get($event->subject_id) : null;
                                        $roleChange = in_array($event->event, ['role.upgraded', 'role.downgraded'], true);
                                    @endphp
                                    @if ($subject && ($adminDetail || $roleChange || $event->event === 'join_link.redeemed'))
                                        <span><x-user-avatar :name="$subject->name" :user="$avatarUsers === null || $avatarUsers->has($subject->id) ? $subject : null" /> {{ $subject->name }}</span>
                                    @elseif ($adminDetail && $event->subject)
                                        <span>{{ $event->subject instanceof \App\Models\Complex ? $event->subject->name : ($event->subject->title ?: '#'.$event->subject_id) }}</span>
                                    @endif
                                    @if ($event->event === 'join_link.rotated')
                                        <span>لینک دعوت تعویض شد</span>
                                        @php
                                            $linkModes = __('audit.join_link.lifecycle');
                                            $from = $event->meta['from'] ?? null;
                                            $to = $event->meta['to'] ?? null;
                                        @endphp
                                        @if (is_string($from) && is_string($to) && isset($linkModes[$from], $linkModes[$to]))
                                            <span>{{ $linkModes[$from] }} ← {{ $linkModes[$to] }}</span>
                                        @endif
                                    @endif
                                    @if ($roleChange)
                                        <span>{{ ($event->meta['from'] ?? null) === 'manager' ? 'مدیر' : 'ساکن' }} ← {{ ($event->meta['to'] ?? null) === 'manager' ? 'مدیر' : 'ساکن' }}</span>
                                    @endif
                                    @if ($adminDetail && in_array($event->event, ['membership.approved', 'membership.rejected'], true))
                                        <span>{{ $event->meta['complex_name'] ?? '' }}</span>
                                    @endif
                                </td>
                            @endif
                            <td class="p-2"><time datetime="{{ $event->created_at->toAtomString() }}">{{ \Morilog\Jalali\Jalalian::fromCarbon($event->created_at)->format('Y/m/d H:i') }}</time></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if ($adminDetail) {{ $events->links() }} @endif
</section>
