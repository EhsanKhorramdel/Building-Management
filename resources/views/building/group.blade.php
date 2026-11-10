<section class="buildings-section chat-window" data-panel="chat" hidden>
    <div class="buildings-section-header">
        <p>گفت و گو با ساکنین</p>
    </div>
    <div class="chat-shell">
        <div class="chat-heading">
            <strong class="flex-1">{{ $group->group_name }}</strong>
            <button type="button" class="action" data-search-toggle aria-label="جستجوی پیام‌ها" aria-expanded="false">جستجو</button>
            <button type="button" class="action" data-older hidden>پیام‌های پیشین</button>
            <button type="button" class="action" data-newer hidden>پیام‌های بعدی</button>
            <button type="button" class="action" data-latest aria-label="رفتن به آخرین پیام" hidden>آخرین پیام</button>
        </div>
        <section class="chat-search" data-chat-search hidden aria-label="جستجوی پیام‌ها">
            <form class="flex gap-2" data-chat-search-form>
                <label class="sr-only" for="chat-search-query">عبارت جستجو</label>
                <input id="chat-search-query" name="q" type="search" maxlength="100" required placeholder="جستجو در پیام‌ها" class="min-w-0 flex-1">
                <button type="submit" class="action">بیاب</button>
                <button type="button" class="action" data-search-close aria-label="بستن جستجو">بستن</button>
            </form>
            <p class="text-sm" data-search-state role="status" hidden></p>
            <div class="chat-search-results" data-search-results></div>
            <button type="button" class="action" data-search-more hidden>نتایج بیشتر</button>
        </section>
        <div class="chat-pins" data-chat-pins hidden aria-label="پیام‌های سنجاق‌شده"></div>
        <div class="chat-selection" data-chat-selection hidden>
            <p class="flex-1" data-selection-count role="status"></p>
            <button type="button" class="action action-danger" data-selection-delete>حذف انتخاب‌شده‌ها</button>
            <button type="button" class="action" data-selection-cancel>لغو انتخاب</button>
        </div>
        <div class="chatContainer" tabindex="0" aria-label="پیام‌های گفتگو">
            <p class="empty-state" data-chat-state role="status" hidden></p>
            <div class="messagesContainer"></div>
        </div>
        <p class="chat-typing" data-chat-typing role="status" hidden></p>
        <div class="chat-heading" data-chat-edit hidden>
            <span class="flex-1">در حال ویرایش پیام</span>
            <button type="button" class="action">لغو ویرایش</button>
        </div>
        <div class="chat-heading chat-reply-preview" data-chat-reply hidden>
            <div class="min-w-0 flex-1">
                <strong data-reply-author></strong>
                <p data-reply-snippet></p>
            </div>
            <button type="button" class="action">لغو پاسخ</button>
        </div>
        <form id="messageForm" method="POST" class="chat-composer">
            <label class="sr-only" for="message-text">متن پیام</label>
            <textarea id="message-text" class="textarea-input" name="messageText" rows="2" placeholder="پیام خود را بنویسید…"></textarea>
            <label for="file-upload" class="icon-button cursor-pointer" aria-label="پیوست تصویر">
                <x-icon name="plus" />
                <span class="sr-only">پیوست تصویر</span>
            </label>
            <input id="file-upload" type="file" class="sr-only" name="messageImage" accept="image/gif,image/jpeg,image/png,image/webp">
            <button class="sendBtn action" type="submit">ارسال</button>
            <span class="w-full break-all text-xs empty:hidden" data-file-name></span>
        </form>
    </div>
</section>

<div class="dialog-shell modal-message-viewer hidden" role="dialog" aria-modal="true" aria-labelledby="viewer-title">
    <div>
        <h3 class="mb-4 text-lg font-bold" id="viewer-title">مشاهده کنندگان</h3>
        <div class="viewer-wrapper grid max-h-64 gap-2 overflow-auto"></div>
        <button class="action mt-4" type="button">بستن</button>
    </div>
</div>

<div class="dialog-shell hidden" data-chat-social-dialog role="dialog" aria-modal="true" aria-labelledby="chat-social-title">
    <div>
        <h3 class="mb-4 text-lg font-bold" id="chat-social-title"></h3>
        <div data-chat-social-options></div>
        <button class="action mt-4" type="button" data-close-dialog>بستن</button>
    </div>
</div>

<div class="dialog-shell hidden" data-chat-moderation role="dialog" aria-modal="true" aria-labelledby="chat-moderation-title">
    <div>
        <h3 class="mb-4 text-lg font-bold" id="chat-moderation-title">دلیل حذف توسط مدیر</h3>
        <form class="grid gap-3" data-moderation-form>
            <label for="chat-moderation-reason">دلیل حذف پیام‌های دیگران (حداکثر ۲۴۰ نویسه)</label>
            <textarea id="chat-moderation-reason" name="reason" maxlength="240" required rows="3"></textarea>
            <div class="flex flex-wrap gap-2">
                <button class="action action-danger" type="submit">حذف با ثبت دلیل</button>
                <button class="action" type="button" data-close-dialog>لغو</button>
            </div>
        </form>
    </div>
</div>
