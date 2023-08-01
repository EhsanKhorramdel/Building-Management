<div class="buildings-section poll-window hidden">
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
            <div class="building-box-wrapper">
                @if ($poll->isExpired())
                    <div class="building-box bg-red-300 hover:bg-red-400">
                    @else
                        <div class="building-box bg-green-300 hover:bg-green-400">
                @endif
                <div class="building-box-content-header">
                    <p class="box-content-header">{{ $poll->title }} </p>
                </div>
                <div class="box-address-wrapper overflow-auto">
                    <p class="box-address-header p-8">{{ $poll->question }}</p>
                </div>
                <div class="poll-options hidden">
                    @foreach ($poll->poll_options as $poll_option)
                        <p class="hidden option {{ 'pollOption_' . $poll_option->id }}">
                            {{ $poll_option->option }}</p>
                    @endforeach
                </div>

                <div class="building-box-footer flex justify-center gap-2.5">
                    @if (!$poll->isExpired())
                        <a class="text-center open-Btn-vote">شرکت در رای گیری</a>
                    @endif
                    <a class="text-center open-Btn-vote-result">مشاهده نتیجه</a>
                    <p class="hidden">{{ $poll->id }}</p>
                </div>
            </div>
    </div>
    @endforeach
    @if (!($expiredCount + $notExpiredCount))
        <div class="bg-white shadow overflow-hidden sm:rounded-md ">
            <ul class="divide-y divide-gray-200 contactList"></ul>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded">
                رای گیری ثبت نشده است
            </div>
        </div>
    @endif
    <form class="register-form" method="POST" action="{{ URL::route('building.vote') }}">
        @csrf
        <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-lg modal-vote hidden">
            <div class="bg-white p-6 rounded shadow-md max-w-md mx-auto">
                <h3 class="text-xl font-semibold mb-4 text-center">
                    شرکت در رای گیری
                </h3>
                <div class="max-h-64 overflow-auto option-wrapper">
                    {{-- <div>
                        <label class="relative flex cursor-pointer items-center rounded-full p-3 gap-2" for="pollOption-id">
                            <input type="radio" id="pollOption-id" name="voteRadio" />
                            <span class="ml-2">گزینه</span>
                        </label>
                    </div> --}}

                </div>
                <div class="flex justify-end mt-4 gap-4">
                    <button
                        class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
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
<div class="register-section poll-window hidden">
    <button class="register-close">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="feather feather-x-circle">
            <circle cx="12" cy="12" r="10" />
            <line x1="15" y1="9" x2="9" y2="15" />
            <line x1="9" y1="9" x2="15" y2="15" />
        </svg>
    </button>
    <div class="buildings-section-header register-container">
        <p>ایجاد رای گیری</p>
    </div>
    <div class="register">
        @if ($errors->hasBag('pollCreate'))
            <div id="messageBox" class="mb-2 mr-3 ml-3 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                <ul class=" text-sm text-red-600 dark:text-red-400 space-y-1">
                    @foreach ($errors->getBag('pollCreate')->all() as $error)
                        <li class="text-center">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form class="register-form" method="POST" action="{{ URL::route('building.poll', $complex->id) }}">
            @csrf
            <div class="search-wrapper add-wrapper">
                <input class="search-input add-input" type="text" name="title" placeholder="عنوان" />
            </div>
            <div class="search-wrapper add-wrapper">
                <input class="search-input add-input" type="text" name="question" placeholder="متن سوال" />
            </div>
            <label class="uppercase text-lg font-bold text-white bg-gray-500 rounded-lg p-2">
                زمان پایان رای گیری
            </label>
            <div class="flex flex-wrap mt-3">
                <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                    <input
                        class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        type="number" name="endMinutes" placeholder="دقیقه" min="0">
                </div>
                <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                    <input
                        class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        type="number" name="endHours" placeholder="ساعت" min="0">
                </div>
                <div class="w-full md:w-1/3 px-3 mb-6 md:mb-0">
                    <input
                        class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                        type="number" name="endDays" placeholder="روز" min="0">
                </div>
            </div>
            <div class="flex items-center justify-center gap-4 button-container">
                <button type="button"
                    class="flex-1 bg-green-500 hover:bg-green-700 text-white font-bold px-4 py-2 rounded text-center md:w-32"
                    onclick="addOption()">
                    <span class="text-sm whitespace-nowrap">افزودن گزینه</span>
                </button>
                <button type="button"
                    class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold px-4 py-2 rounded text-center md:w-32"
                    onclick="removeOption()">
                    <span class="text-sm whitespace-nowrap">حذف گزینه</span>
                </button>
            </div>

            <button type="submit" class="view-btn grid-view active">ثبت</button>
        </form>
    </div>
</div>

<div
    class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-2xl modal-vote-result hidden">
    <div class="bg-white p-6 rounded shadow-md w-3/4 rounded-xl">
        <h3 class="text-xl font-semibold mb-4 text-center">
            نتیجه رای گیری
        </h3>
        <canvas id="voteChart" class="max-w-fit"></canvas>
        <div class="flex flex-col mt-5">
            <button
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2 close-Btn-vote-result"
                type="button">
                بستن
            </button>
        </div>
    </div>
</div>

<div class="alert-wrapper">
    <div class="flex items-center p-5 text-red-800 rounded-xl bg-red-50 bg-gray-800 text-red-400 gap-2.5 text-center hidden"
        role="alert" id="myAlert">
        <svg class="flex-shrink-0 inline w-8 h-8 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
            fill="currentColor" viewBox="0 0 20 20">
            <path
                d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
        </svg>
        <span class="sr-only">Info</span>
        <div>
            <span class="font-medium">کاربر محترم حداقل دو گزینه برای ایجاد رای گیری ضروری است.</span>
        </div>
        <button type="button" class="flex-shrink-0 ml-2 text-red-600 hover:text-red-800 focus:outline-none"
            aria-label="بستن" onclick="closeAlert()">
            <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>




<input hidden id="csrf-token" value="{{ csrf_token() }}" />
