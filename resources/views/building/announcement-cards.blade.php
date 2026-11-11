@forelse ($announcements as $announcement)
    <div class="building-box-wrapper" data-announcement-id="{{ $announcement->id }}" data-read="{{ $announcement->is_read ? '1' : '0' }}" data-archived="{{ $announcement->archive ? '1' : '0' }}">
        <div class="building-box {{ $announcement->pinned_at ? 'announcement-pinned' : '' }}">
            <div class="building-box-content-header">
                <p class="box-content-header">{{ $announcement->manager?->name ?? 'N/A' }}</p>
                @if ($announcement->pinned_at)<span class="announcement-pin-label">سنجاق شده</span>@endif
                @if (!$announcement->is_read && !$announcement->archive)<span data-announcement-unread class="announcement-unread">خوانده نشده</span>@endif
            </div>
            <div class="box-address-wrapper overflow-auto"><p class="box-address-header p-8">{{ $announcement->text }}</p></div>
            <div class="building-box-footer flex flex-wrap justify-center gap-2.5">
                @if ($isManager)
                    <form method="POST" action="{{ route('announcements.pin', $announcement) }}">@csrf
                        <input type="hidden" name="pinned" value="{{ $announcement->pinned_at ? '0' : '1' }}">
                        <button type="submit">{{ $announcement->pinned_at ? 'برداشتن سنجاق' : 'سنجاق کردن' }}</button>
                    </form>
                    @if ($announcement->archive)
                        <form method="POST" action="{{ route('announcements.restore', $announcement) }}">@csrf<button type="submit">بازگرداندن اعلان</button></form>
                    @else
                        <form method="POST" action="{{ route('building.cancelNotification', $announcement) }}" data-confirm="این اعلان لغو شود؟">@csrf<button type="submit">لغو اعلان</button></form>
                    @endif
                    <button type="button" data-announcement-readers="{{ route('announcements.readers', $announcement) }}">خوانندگان • <span data-announcement-count>{{ $announcement->reads_count }}</span></button>
                @endif
                <button type="button" data-announcement-image="{{ $announcement->image_url ? route('announcements.attachment', $announcement) : asset('storage/default.png') }}">مشاهده تصویر</button>
            </div>
        </div>
    </div>
@empty
    <p class="empty-state">{{ $isManager ? 'اعلانی پیدا نشد. عبارت جستجو یا وضعیت را تغییر دهید.' : 'اعلانی وجود ندارد. اعلان‌های تازه اینجا نمایش داده می‌شوند.' }}</p>
@endforelse
