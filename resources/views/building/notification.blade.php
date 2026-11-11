<div class="buildings-section notification-window" data-panel="notification" hidden>
    <div class="buildings-section-header justify-center">
        <p> اعلانات ساختمان </p>
    </div>
    @if ($isManager)
        <form data-announcement-search class="announcement-search">
            <label>جستجوی اعلان‌ها <input type="search" name="q" maxlength="200"></label>
            <label>وضعیت <select name="state"><option value="active">فعال</option><option value="archived">بایگانی</option><option value="all">همه</option></select></label>
            <button type="submit">جستجو</button>
        </form>
    @endif
    <div class="building-boxes jsListView" data-announcement-list>
        @include('building.announcement-cards')
    </div>
</div>
<div class="dialog-shell hidden" data-announcement-readers-dialog role="dialog" aria-modal="true" aria-label="خوانندگان اعلان">
    <div class="dialog-panel"><div class="register-dialog-tools"><button type="button" class="register-close" data-close-dialog>بستن</button></div><h3>خوانندگان اعلان</h3><div data-announcement-readers-list></div></div>
</div>
@if ($isManager)
    <div class="register-section hidden" data-panel="notification" @if ($errors->getBag('notifRegister')->any()) data-reopen @endif role="dialog" aria-modal="true"><div class="dialog-panel">
        <div class="register-dialog-tools"><button type="button" class="register-close" aria-label="بستن"><x-icon name="close" /></button></div>
        <div class="buildings-section-header register-container">
            <p>ثبت اعلان جدید</p>
        </div>
        <div class="register">

            <form class="register-form" method="POST"
                action="{{ URL::route('building.NotificationRegistration', $complex->id) }}"
                enctype="multipart/form-data">
                @csrf
                <div id="image-preview"
                    class="flex items-center justify-center w-full h-64 border-2 border-gray-300 rounded-lg hidden">
                    <img id="uploaded-image" alt="پیش‌نمایش اعلان"
                        class="hidden rounded-lg w-full h-full" />
                </div>
                <button type="button"
                    class="flex-1 bg-red-700 hover:bg-red-800 text-white font-bold px-4 py-2 rounded text-center md:w-32 mb-4 hidden removeFileBtn"
                    >
                    <span class="text-sm whitespace-nowrap">لغو آپلود</span>
                </button>
                <label for="dropzone-file"
                    class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-bray-800 hover:bg-gray-100 border-gray-600 hover:border-gray-500 dropzoneFileLabel">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-4 text-gray-500 text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                        </svg>
                        <p class="mb-2 text-sm text-gray-500 text-gray-400"><span class="font-semibold">انتخاب تصویر</span> </p>
                        <p class="text-xs text-gray-500 text-gray-400">تصویر WebP، PNG، JPG یا GIF </p>
                    </div>
                </label>
                <input id="dropzone-file" type="file" class="sr-only" name="notifPicture" accept="image/gif,image/jpeg,image/png,image/webp" @error('notifPicture', 'notifRegister') aria-invalid="true" aria-describedby="dropzone-file-error" @enderror />@error('notifPicture', 'notifRegister')<span class="field-error" id="dropzone-file-error">{{ $message }}</span>@enderror

                <label class="sr-only" for="notification-notifText-1">اعلان خود را در این قسمت بنویسید</label><textarea id="notification-notifText-1" rows="4" name="notifText"
                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 border-gray-600 placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500  hover:bg-gray-100"
                    placeholder="اعلان خود را در این قسمت بنویسید" @error('notifText', 'notifRegister') aria-invalid="true" aria-describedby="notification-notifText-1-error" @enderror>{{ is_scalar(old('notifText')) ? old('notifText') : '' }}</textarea>
                @error('notifText', 'notifRegister')
                    <span class="field-error" id="notification-notifText-1-error">{{ $message }}</span>
                @enderror
                <button type="submit" class="view-btn grid-view active">ثبت</button>
            </form>
        </div>
    </div>
</div>
@endif
