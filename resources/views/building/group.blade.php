<div class="buildings-section chat-window hidden">
    <div class="buildings-section-header justify-center">
        <p>گفت و گو با ساکنین</p>
    </div>
    <div>
        <div class="w-full h-32 rounded-lg" style="background-color: #449388"></div>

        <div style="margin-top: -128px">
            <div class="py-6 h-screen">
                <div class="flex border border-grey rounded shadow-lg h-full">
                    <div class="w-full border flex flex-col">
                        <!-- Header -->
                        <div class="py-2 px-3 bg-grey-lighter flex flex-row justify-between items-center">
                            <div class="flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-8 h-8 rounded-full">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                                <div class="ml-4">
                                    <p class="text-grey-darkest mr-2">{{ $group->group_name }}</p>
                                    {{-- <p class="text-grey-darker text-xs mt-1">
                                        Names of online members
                                    </p> --}}
                                </div>
                            </div>
                            <div id="alertContainer" class="fixed transform -translate-x-1/2 mt-4 z-50">
                            </div>

                            {{-- <div class="flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24"
                                        height="24">
                                        <path fill="#263238" fill-opacity=".5"
                                            d="M15.9 14.3H15l-.3-.3c1-1.1 1.6-2.7 1.6-4.3 0-3.7-3-6.7-6.7-6.7S3 6 3 9.7s3 6.7 6.7 6.7c1.6 0 3.2-.6 4.3-1.6l.3.3v.8l5.1 5.1 1.5-1.5-5-5.2zm-6.2 0c-2.6 0-4.6-2.1-4.6-4.6s2.1-4.6 4.6-4.6 4.6 2.1 4.6 4.6-2 4.6-4.6 4.6z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24"
                                        height="24">
                                        <path fill="#263238" fill-opacity=".5"
                                            d="M1.816 15.556v.002c0 1.502.584 2.912 1.646 3.972s2.472 1.647 3.974 1.647a5.58 5.58 0 0 0 3.972-1.645l9.547-9.548c.769-.768 1.147-1.767 1.058-2.817-.079-.968-.548-1.927-1.319-2.698-1.594-1.592-4.068-1.711-5.517-.262l-7.916 7.915c-.881.881-.792 2.25.214 3.261.959.958 2.423 1.053 3.263.215l5.511-5.512c.28-.28.267-.722.053-.936l-.244-.244c-.191-.191-.567-.349-.957.04l-5.506 5.506c-.18.18-.635.127-.976-.214-.098-.097-.576-.613-.213-.973l7.915-7.917c.818-.817 2.267-.699 3.23.262.5.501.802 1.1.849 1.685.051.573-.156 1.111-.589 1.543l-9.547 9.549a3.97 3.97 0 0 1-2.829 1.171 3.975 3.975 0 0 1-2.83-1.173 3.973 3.973 0 0 1-1.172-2.828c0-1.071.415-2.076 1.172-2.83l7.209-7.211c.157-.157.264-.579.028-.814L11.5 4.36a.572.572 0 0 0-.834.018l-7.205 7.207a5.577 5.577 0 0 0-1.645 3.971z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24"
                                        height="24">
                                        <path fill="#263238" fill-opacity=".6"
                                            d="M12 7a2 2 0 1 0-.001-4.001A2 2 0 0 0 12 7zm0 2a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 9zm0 6a2 2 0 1 0-.001 3.999A2 2 0 0 0 12 15z">
                                        </path>
                                    </svg>
                                </div>
                            </div> --}}
                        </div>

                        <!-- Messages -->
                        <div class="flex-1 overflow-auto chatContainer scroll-smooth bg-[#dad3cc] h-full">
                            <div class="py-2 px-3 messagesContainer h-full relative">
                                {{-- <div class="flex justify-center mb-2">
                                    <div class="rounded py-2 px-4" style="background-color: #ddecf2">
                                        <p class="text-sm uppercase">Date</p>
                                    </div>
                                </div> --}}
                                {{-- <div class="flex justify-end mb-2">
                                    <div class="rounded py-2 px-3 bg-gray-100">
                                        <p class="text-sm text-purple font-bold">name</p>
                                        <div class="max-w-lg mt-2">
                                            <img src='' alt=""
                                                class="w-full h-auto rounded-lg shadow-md mb-4">
                                        </div>

                                        <p class="text-sm mt-1 text-gray-800 whitespace-pre-wrap">text</p>
                                        <div class="flex justify-between items-center gap-3">
                                            <p class="text-right text-xs text-gray-500 mt-1">hh:mm</p>
                                            <p class="text-left text-xs text-gray-500 mt-1">ویرایش شده</p>
                                        </div>


                                    </div>
                                </div> --}}



                            </div>
                        </div>
                        <button
                            class="cursor-pointer fixed left-24 bottom-44 z-10 rounded-full border border-gray-200 text-gray-600 bg-gray-300 scrollToBottomBtn hidden">
                            <svg stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24"
                                stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 m-1" height="1em"
                                width="1em" xmlns="http://www.w3.org/2000/svg">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <polyline points="19 12 12 19 5 12"></polyline>
                            </svg>
                        </button>
                        <!-- Input -->
                        <div class="flex justify-between items-center rounded-sm bg-gray-200 hidden z-10"
                            id="showOperationBoxContainer">
                            <p class="rounded-lg mx-2 p-3 truncate w-full" id="textOperationBox"></p>
                            <div class="cursor-pointer hover:text-red-500 ml-2" id="closeButton">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>
                        <form id="messageForm" method="POST">
                            <div class="bg-grey-lighter px-4 py-4 flex items-center">
                                <div class="sendBtn py-2 px-2 rounded-lg text-[#acacbe]">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                                    </svg>
                                </div>

                                <div class="flex-1 mx-4">
                                    <textarea class="w-full border rounded-lg px-2 py-2 textarea-input" placeholder="پیام خود را اینجا بنویسید ..."
                                        name="messageText"></textarea>
                                </div>
                                <label for="file-upload" class="relative file-upload-label">
                                    <div class="cursor-pointer">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24"
                                            height="24">
                                            <path fill="#263238" fill-opacity=".5"
                                                d="M1.816 15.556v.002c0 1.502.584 2.912 1.646 3.972s2.472 1.647 3.974 1.647a5.58 5.58 0 0 0 3.972-1.645l9.547-9.548c.769-.768 1.147-1.767 1.058-2.817-.079-.968-.548-1.927-1.319-2.698-1.594-1.592-4.068-1.711-5.517-.262l-7.916 7.915c-.881.881-.792 2.25.214 3.261.959.958 2.423 1.053 3.263.215l5.511-5.512c.28-.28.267-.722.053-.936l-.244-.244c-.191-.191-.567-.349-.957.04l-5.506 5.506c-.18.18-.635.127-.976-.214-.098-.097-.576-.613-.213-.973l7.915-7.917c.818-.817 2.267-.699 3.23.262.5.501.802 1.1.849 1.685.051.573-.156 1.111-.589 1.543l-9.547 9.549a3.97 3.97 0 0 1-2.829 1.171 3.975 3.975 0 0 1-2.83-1.173 3.973 3.973 0 0 1-1.172-2.828c0-1.071.415-2.076 1.172-2.83l7.209-7.211c.157-.157.264-.579.028-.814L11.5 4.36a.572.572 0 0 0-.834.018l-7.205 7.207a5.577 5.577 0 0 0-1.645 3.971z">
                                            </path>
                                        </svg>
                                    </div>
                                    <input id="file-upload" type="file" class="hidden" name="messageImage" />
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div
    class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 rounded-lg modal-message-viewer hidden">
    <div class="bg-white p-6 rounded shadow-md max-w-md mx-auto">
        <h3 class="text-xl font-semibold mb-4 text-center">
            مشاهده کنندگان
        </h3>
        <div class="max-h-64 overflow-auto viewer-wrapper flex flex-col gap-4">


        </div>
        <div class="flex justify-center mt-6">
            <button
                class="w-full bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline close-Btn-message-viewer"
                type="button">بستن
            </button>
        </div>
    </div>
</div>
