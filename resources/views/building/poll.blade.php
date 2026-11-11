<div class="buildings-section poll-window" data-panel="poll" hidden>
    <div class="buildings-section-header">
        <p>رای گیری های ساختمان</p>
    </div>
    <div class="buildings-section-line">
        <div class="buildings-status">
            <div class="item-status">
                <span class="status-number">{{ $notExpiredCount }}</span>
                <span class="status-type">فعال</span>
            </div>
            <div class="item-status">
                <span class="status-number">{{ $expiredCount }}</span>
                <span class="status-type">غیر فعال</span>
            </div>
            <div class="item-status">
                <span class="status-number">{{ $expiredCount + $notExpiredCount }}</span>
                <span class="status-type">کل</span>
            </div>
        </div>
    </div>
    <div class="building-boxes jsListView">
        @foreach ($polls as $poll)
            <x-building-card :state="$poll->isExpired() ? 'expired' : 'open'">
                <div class="building-box-content-header">
                    <p class="box-content-header">{{ $poll->title }} </p>
                </div>
                <div class="box-address-wrapper overflow-auto">
                    <p class="box-address-header p-8">{{ $poll->question }}</p>
                </div>
                <div class="poll-options hidden">
                    @foreach ($poll->poll_options as $poll_option)
                        <p data-option-id="{{ $poll_option->id }}">
                            {{ $poll_option->option }}</p>
                    @endforeach
                </div>

                <div class="building-box-footer flex justify-center gap-2.5">
                    @if (!$poll->isExpired())
                        <button type="button" class="text-center open-Btn-vote" data-current-choice="{{ $poll->current_option_id }}">شرکت در رای گیری</button>
                    @else
                        <span class="poll-closed">رای گیری بسته شده است</span>
                    @endif
                    <button type="button" class="open-Btn-vote-result" data-poll-id="{{ $poll->id }}" @if ($isManager && $poll->isExpired()) data-named-ballots="{{ route('polls.ballots', [$complex, $poll]) }}" @endif>مشاهده نتیجه</button>
                    @if ($isManager)
                        @if (!$poll->isExpired())
                            <form method="POST" action="{{ route('polls.close', [$complex, $poll]) }}" data-confirm="این رای گیری برای همیشه بسته شود؟">@csrf<button type="submit">بستن رای گیری</button></form>
                        @endif
                        @if (!$poll->votes_count)
                            <button type="button" data-edit-poll data-action="{{ route('polls.update', [$complex, $poll]) }}" data-title="{{ $poll->title }}" data-question="{{ $poll->question }}" data-deadline="{{ $poll->end_date->format('Y-m-d\\TH:i:s') }}">ویرایش رای گیری</button>
                        @else
                            <span class="poll-edit-locked">پس از ثبت اولین رای، ویرایش ممکن نیست.</span>
                        @endif
                    @endif
                </div>
            </x-building-card>
    @endforeach
    @if (!($expiredCount + $notExpiredCount))
        <p class="empty-state">رای گیری ثبت نشده است. برای مشاهده رأی‌گیری‌های جدید به این بخش برگردید.</p>
    @endif
    <form class="register-form" method="POST" action="{{ URL::route('building.vote') }}">
        @csrf
        <div class="dialog-shell modal-vote hidden" role="dialog" aria-modal="true">
            <div class="bg-white p-6 rounded shadow-md max-w-md mx-auto">
                <h3 class="text-xl font-semibold mb-4 text-center">
                    شرکت در رای گیری
                </h3>
                <div class="max-h-64 overflow-auto option-wrapper">


                </div>
                <div class="flex justify-end mt-4 gap-4">
                    <button
                        class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
                        type="submit">ذخیره</button>
                    <button
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline ml-2 close-Btn-vote"
                        type="button">لغو</button>
                </div>
            </div>
        </div>

    </form>
</div>
</div>
<div class="register-section hidden" data-panel="poll" @if ($errors->getBag('pollCreate')->any()) data-reopen @endif role="dialog" aria-modal="true"><div class="dialog-panel">
    <div class="register-dialog-tools"><button type="button" class="register-close" aria-label="بستن"><x-icon name="close" /></button></div>
    <div class="buildings-section-header register-container">
        <p>ایجاد رای گیری</p>
    </div>
    <div class="register">

        <form class="register-form" method="POST" action="{{ URL::route('building.poll', $complex->id) }}">
            @csrf
            <div class="search-wrapper add-wrapper">
                <label class="sr-only" for="poll-title-1">عنوان</label><input id="poll-title-1" class="search-input add-input" type="text" name="title" placeholder="عنوان" value="{{ is_scalar(old('title')) ? old('title') : '' }}" @error('title', 'pollCreate') aria-invalid="true" aria-describedby="poll-title-1-error" @enderror />@error('title', 'pollCreate')<span class="field-error" id="poll-title-1-error">{{ $message }}</span>@enderror
            </div>
            <div class="search-wrapper add-wrapper">
                <label class="sr-only" for="poll-question-2">متن سوال</label><input id="poll-question-2" class="search-input add-input" type="text" name="question" placeholder="متن سوال" value="{{ is_scalar(old('question')) ? old('question') : '' }}" @error('question', 'pollCreate') aria-invalid="true" aria-describedby="poll-question-2-error" @enderror />@error('question', 'pollCreate')<span class="field-error" id="poll-question-2-error">{{ $message }}</span>@enderror
            </div>
            <label class="uppercase text-lg font-bold text-white bg-gray-500 rounded-lg p-2">
                زمان پایان رای گیری
            </label>
            <div class="flex flex-wrap mt-3">
                <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                    <label class="sr-only" for="poll-endMinutes-3">دقیقه</label><input id="poll-endMinutes-3"
                        class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        type="number" name="endMinutes" placeholder="دقیقه" min="0" value="{{ is_scalar(old('endMinutes')) ? old('endMinutes') : '' }}" @error('endMinutes', 'pollCreate') aria-invalid="true" aria-describedby="poll-endMinutes-3-error" @enderror />@error('endMinutes', 'pollCreate')<span class="field-error" id="poll-endMinutes-3-error">{{ $message }}</span>@enderror
                </div>
                <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                    <label class="sr-only" for="poll-endHours-4">ساعت</label><input id="poll-endHours-4"
                        class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        type="number" name="endHours" placeholder="ساعت" min="0" value="{{ is_scalar(old('endHours')) ? old('endHours') : '' }}" @error('endHours', 'pollCreate') aria-invalid="true" aria-describedby="poll-endHours-4-error" @enderror />@error('endHours', 'pollCreate')<span class="field-error" id="poll-endHours-4-error">{{ $message }}</span>@enderror
                </div>
                <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                    <label class="sr-only" for="poll-endDays-5">روز</label><input id="poll-endDays-5"
                        class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        type="number" name="endDays" placeholder="روز" min="0" value="{{ is_scalar(old('endDays')) ? old('endDays') : '' }}" @error('endDays', 'pollCreate') aria-invalid="true" aria-describedby="poll-endDays-5-error" @enderror />@error('endDays', 'pollCreate')<span class="field-error" id="poll-endDays-5-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="grid gap-3" data-options>
    <label>گزینه ۱ <input id="poll-option1-6" type="text" name="option1" value="{{ is_scalar(old('option1')) ? old('option1') : '' }}" @error('option1', 'pollCreate') aria-invalid="true" aria-describedby="poll-option1-6-error" @enderror >@error('option1', 'pollCreate')<span class="field-error" id="poll-option1-6-error">{{ $message }}</span>@enderror</label>
    <label>گزینه ۲ <input id="poll-option2-7" type="text" name="option2" value="{{ is_scalar(old('option2')) ? old('option2') : '' }}" @error('option2', 'pollCreate') aria-invalid="true" aria-describedby="poll-option2-7-error" @enderror >@error('option2', 'pollCreate')<span class="field-error" id="poll-option2-7-error">{{ $message }}</span>@enderror</label>
    @foreach (collect(old())->filter(fn ($value, $key) => preg_match('/^option[0-9]+$/', $key) && (int) substr($key, 6) > 2 && is_scalar($value))->sortKeys(SORT_NATURAL) as $key => $value)
        <label>گزینه {{ substr($key, 6) }} <input type="text" name="{{ $key }}" value="{{ $value }}"></label>
    @endforeach
</div>
<div class="flex items-center justify-center gap-4 button-container">
                <button type="button"
                    class="flex-1 bg-green-700 hover:bg-green-800 text-white font-bold px-4 py-2 rounded text-center md:w-32"
                    data-add-option>
                    <span class="text-sm whitespace-nowrap">افزودن گزینه</span>
                </button>
                <button type="button"
                    class="flex-1 bg-red-700 hover:bg-red-800 text-white font-bold px-4 py-2 rounded text-center md:w-32"
                    data-remove-option>
                    <span class="text-sm whitespace-nowrap">حذف گزینه</span>
                </button>
            </div>

            <button type="submit" class="view-btn grid-view active">ثبت</button>
        </form>
    </div>
</div>
</div>

<div class="dialog-shell modal-vote-result hidden" role="dialog" aria-modal="true">
    <div class="bg-white p-6 rounded shadow-md w-3/4 rounded-xl">
        <div class="register-dialog-tools"><button type="button" class="register-close" data-close-dialog aria-label="بستن نتیجه">بستن</button></div>
        <h3 class="text-xl font-semibold mb-4 text-center">
            نتیجه رای گیری
        </h3>
        <p role="status" data-chart-status></p><div class="chart-frame"><canvas id="voteChart" role="img" aria-label="نمودار نتیجه رأی‌گیری"></canvas></div>
        <div data-named-ballots-list></div>
        <div class="flex flex-col mt-5">
            <button
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2 close-Btn-vote-result"
                type="button">
                بستن
            </button>
        </div>
    </div>
</div>
@if ($isManager)
@php($editingPoll = $polls->firstWhere('id', session('edit_poll_id')))
<div class="dialog-shell hidden" data-poll-edit-dialog @if ($editingPoll && $errors->getBag('pollEdit')->any()) data-reopen @endif role="dialog" aria-modal="true" aria-label="ویرایش رای گیری">
    <div class="dialog-panel"><div class="register-dialog-tools"><button type="button" class="register-close" data-close-dialog>بستن</button></div><h3>ویرایش رای گیری</h3>
        <form method="POST" data-poll-edit-form action="{{ $editingPoll ? route('polls.update', [$complex, $editingPoll]) : '' }}">@csrf @method('PATCH')
            <label>عنوان <input name="title" maxlength="255" value="{{ is_scalar(old('title')) ? old('title') : '' }}" @error('title', 'pollEdit') aria-invalid="true" @enderror></label>
            @error('title', 'pollEdit')<p class="field-error">{{ $message }}</p>@enderror
            <label>متن سوال <textarea name="question" required maxlength="2000" @error('question', 'pollEdit') aria-invalid="true" @enderror>{{ is_scalar(old('question')) ? old('question') : '' }}</textarea></label>
            @error('question', 'pollEdit')<p class="field-error">{{ $message }}</p>@enderror
            <label>زمان پایان (به وقت تهران) <input name="end_date" type="datetime-local" step="1" required value="{{ is_scalar(old('end_date')) ? old('end_date') : '' }}" @error('end_date', 'pollEdit') aria-invalid="true" @enderror></label>
            @error('end_date', 'pollEdit')<p class="field-error">{{ $message }}</p>@enderror
            <p>گزینه‌ها قابل ویرایش نیستند.</p><button type="submit">ذخیره ویرایش</button>
        </form>
    </div>
</div>
@endif
