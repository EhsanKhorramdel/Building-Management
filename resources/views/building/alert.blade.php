@if (session('status') === 'error')
    <div class="alert-wrapper">
        <div class="flex items-center p-5 text-red-800 rounded-xl bg-red-50 bg-gray-800 text-red-400 gap-2.5 text-center"
            role="alert" id="myAlert">
            <svg class="flex-shrink-0 inline w-8 h-8 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                fill="currentColor" viewBox="0 0 20 20">
                <path
                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
            </svg>
            <span class="sr-only">Info</span>
            <div>
                <span class="font-medium">{{ session('message') }}</span>
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
@else
    @if (session('status') === 'success')
        <div class="alert-wrapper">
            <div class="flex items-center p-5 text-green-800 rounded-xl bg-green-50 bg-gray-800 text-green-400 gap-2.5 text-center"
                role="alert" id="myAlert">
                <svg class="flex-shrink-0 inline w-8 h-8 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
                </svg>
                <span class="sr-only">Info</span>
                <div>
                    <span class="font-medium">{{ session('message') }}</span>
                </div>
                <button type="button" class="flex-shrink-0 ml-2 text-green-600 hover:text-green-800 focus:outline-none"
                    aria-label="بستن" onclick="closeAlert()">
                    <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif
@endif
