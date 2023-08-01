<div class="buildings-section notification-window">
    <div class="buildings-section-header justify-center">
        <p> اعلانات ساختمان </p>
    </div>
    @php
        $counter = 0;
    @endphp
    <div class="building-boxes jsListView">
        @foreach ($announcements as $announcement)
            @if ($announcement->archive == 0)
                @php
                    $counter++;
                @endphp
                <div class="building-box-wrapper">
                    <div class="building-box" style="background-color: #fee4cb">
                        <div class="building-box-content-header">
                            <p class="box-content-header">{{ $announcement->managerName }} </p>
                        </div>
                        <div class="box-address-wrapper overflow-auto">
                            <p class="box-address-header p-8">{{ $announcement->text }}</p>
                        </div>

                        <div class="building-box-footer flex justify-center gap-2.5">
                            @if ($isManager)
                                <a class="text-center"
                                    href="{{ URL::route('building.cancelNotification', $announcement->id) }}">لغو
                                    اعلان</a>
                            @endif
                            <a class="text-center open-Btn">مشاهده تصویر</a>
                            <p class="hidden">
                                {{ asset($announcement->image_url ? '/storage/notificationFiles/' . $announcement->image_url : '/storage/notificationFiles/default.png') }}
                            </p>

                        </div>
                    </div>
                </div>
            @endif
        @endforeach
        @if (!$counter)
            <div class="bg-white shadow overflow-hidden sm:rounded-md ">
                <ul class="divide-y divide-gray-200 contactList"></ul>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded">
                    اعلانی وجود ندارد.
                </div>
            </div>
        @endif
        <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-2xl modal hidden">
            <div class="bg-white p-6 rounded shadow-md max-w-sm mx-auto rounded-xl">
                <h3 class="text-xl font-semibold mb-4 text-center">
                    عکس اعلان
                </h3>
                <img alt="Notification Picture"
                    class="w-full rounded-2xl transition-transform hover:scale-125 transition-all duration-300"
                    id="NotifPicture">
                <div class="flex flex-col mt-5">
                    <button
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mr-2 close-Btn"
                        type="button">
                        بستن
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($isManager)
    <div class="register-section notification-window">
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
            <p>ثبت اعلان جدید</p>
        </div>
        <div class="register">
            @if ($errors->hasBag('notifRegister'))
                <div id="messageBox" class="mb-2 mr-3 ml-3 p-4 rounded bg-red-200 animate__animated animate__shakeX">
                    <ul class=" text-sm text-red-600 text-red-400 space-y-1">
                        @foreach ($errors->getBag('notifRegister')->all() as $error)
                            <li class="text-center">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form class="register-form" method="POST"
                action="{{ URL::route('building.NotificationRegistration', $complex->id) }}"
                enctype="multipart/form-data">
                @csrf
                <div id="image-preview"
                    class="flex items-center justify-center w-full h-64 border-2 border-gray-300 rounded-lg hidden">
                    <img id="uploaded-image" src="#" alt="Uploaded Image"
                        class="hidden rounded-lg w-full h-full" />
                </div>
                <button type="button"
                    class="flex-1 bg-red-500 hover:bg-red-700 text-white font-bold px-4 py-2 rounded text-center md:w-32 mb-4 hidden removeFileBtn"
                    onclick="removeFile()">
                    <span class="text-sm whitespace-nowrap">لغو آپلود</span>
                </button>
                <label for="dropzone-file"
                    class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-bray-800 hover:bg-gray-100 border-gray-600 hover:border-gray-500 dropzone-file">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        <svg class="w-8 h-8 mb-4 text-gray-500 text-gray-400" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2" />
                        </svg>
                        <p class="mb-2 text-sm text-gray-500 text-gray-400"><span class="font-semibold">Click to
                                upload</span> </p>
                        <p class="text-xs text-gray-500 text-gray-400">WebP, PNG, JPG or GIF </p>
                    </div>
                </label>
                <input id="dropzone-file" type="file" class="hidden" name="notifPicture"
                    onchange="previewImage(event)" />

                <textarea rows="4" name="notifText"
                    class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 border-gray-600 placeholder-gray-400 focus:ring-blue-500 focus:border-blue-500  hover:bg-gray-100"
                    placeholder="اعلان خود را در این قسمت بنویسید"></textarea>
                <button type="submit" class="view-btn grid-view active">ثبت</button>
            </form>
        </div>
    </div>
@endif
