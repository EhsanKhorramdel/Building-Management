<section class="settings-card mt-4 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-4" data-audit-events>
    <h2 class="mb-3 font-bold">رویدادهای اخیر</h2>
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
                        <th scope="col" class="p-2 text-start">زمان</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($events as $event)
                        <tr class="border-b">
                            <td class="p-2" data-audit-event="{{ $event->event }}">{{ __('audit.' . $event->event) }}</td>
                            <td class="p-2">{{ $event->actor->name }}</td>
                            <td class="p-2">{{ $event->complex?->name ?? '—' }}</td>
                            <td class="p-2"><time datetime="{{ $event->created_at->toAtomString() }}">{{ \Morilog\Jalali\Jalalian::fromCarbon($event->created_at)->format('Y/m/d H:i') }}</time></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
