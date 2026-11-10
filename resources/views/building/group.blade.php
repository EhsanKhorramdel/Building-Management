<section class="buildings-section chat-window" data-panel="chat" hidden>
    <div class="buildings-section-header">
        <p>گفت و گو با ساکنین</p>
    </div>
    <div class="chat-shell">
        <div class="chat-heading">
            <strong class="flex-1">{{ $group->group_name }}</strong>
            <button type="button" class="action" data-older hidden>پیام‌های پیشین</button>
            <button type="button" class="action" data-newer hidden>پیام‌های بعدی</button>
            <button type="button" class="action" data-latest aria-label="رفتن به آخرین پیام" hidden>آخرین پیام</button>
        </div>
        <div class="chat-pins" data-chat-pins hidden aria-label="پیام‌های سنجاق‌شده"></div>
        <div class="chatContainer" tabindex="0" aria-label="پیام‌های گفتگو">
            <p class="empty-state" data-chat-state role="status" hidden></p>
            <div class="messagesContainer"></div>
        </div>
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
