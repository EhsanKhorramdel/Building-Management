<section class="space-y-3 p-4">
    <h2 class="font-bold">تصویر پروفایل</h2>
    <x-user-avatar :name="auth()->user()->name" :user="auth()->user()" />
    <form action="{{ route('profile.avatar.update') }}" method="post" enctype="multipart/form-data" class="space-y-2">
        @csrf
        <label for="profile-avatar" class="block">انتخاب تصویر</label>
        <input id="profile-avatar" class="block min-h-10 max-w-full file:min-h-10" type="file" name="avatar" accept="image/png,image/jpeg,image/webp,image/gif" required>
        @error('avatar') <p class="field-error">{{ $message }}</p> @enderror
        <button class="action" type="submit">ذخیره تصویر</button>
    </form>
    @if (auth()->user()->avatar_path)
        <form action="{{ route('profile.avatar.destroy') }}" method="post">
            @csrf
            @method('DELETE')
            <button class="action" type="submit">حذف تصویر</button>
        </form>
    @endif
</section>
<section class="space-y-3 p-4">
    <h2 class="font-bold">تنظیمات اعلان‌ها</h2>
    <a class="action" href="{{ route('profile.notifications.index') }}">اعلان‌های من</a>
    <form action="{{ route('profile.notifications.update') }}" method="post" class="space-y-2">
        @csrf
        @method('PATCH')
        @foreach (\App\Services\AccountNotifications::LABELS as $event => $label)
            <fieldset class="rounded-lg border border-[var(--line)] px-3">
                <legend>{{ $label }}</legend>
                @foreach (['in_app' => 'درون برنامه', 'sms' => 'پیامک'] as $channel => $channelLabel)
                    <input type="hidden" name="{{ $event }}[{{ $channel }}]" value="0">
                    <label class="inline-flex min-h-10 items-center gap-2 me-3">
                        <input type="checkbox" name="{{ $event }}[{{ $channel }}]" value="1" @checked(\App\Services\AccountNotifications::allows(auth()->user(), $event, $channel))>
                        {{ $channelLabel }}
                    </label>
                @endforeach
            </fieldset>
        @endforeach
        <button class="action" type="submit">ذخیره تنظیمات اعلان‌ها</button>
    </form>
</section>
