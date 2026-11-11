@if ($page->previousPageUrl() || $page->nextPageUrl())
    <nav class="flex flex-wrap gap-2 mt-4" aria-label="صفحه‌بندی">
        @if ($page->previousPageUrl())<a class="action" href="{{ $page->previousPageUrl() }}">صفحه قبل</a>@endif
        @if ($page->nextPageUrl())<a class="action" href="{{ $page->nextPageUrl() }}">صفحه بعد</a>@endif
    </nav>
@endif
