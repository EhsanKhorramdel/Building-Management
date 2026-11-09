<div class="toast-stack" data-toasts aria-live="polite">
    @if (session('message'))
        <div class="toast" data-flash data-status="{{ session('status') === 'error' ? 'error' : 'success' }}">{{ session('message') }}</div>
    @elseif (session('status') === 'verification-link-sent')
        <div class="toast" data-flash data-status="success">{{ __('A new verification link has been sent to the email address you provided during registration.') }}</div>
    @elseif (is_string(session('status')) && !in_array(session('status'), ['error', 'success'], true))
        <div class="toast" data-flash data-status="success">{{ session('status') }}</div>
    @endif
    @php($errorBag = collect($errors->getBags())->first(fn ($bag) => $bag->any()))
    @if ($errorBag)
        <div class="toast" data-flash data-status="error">{{ $errorBag->first() }}</div>
    @endif
</div>

<dialog class="confirm-dialog" data-confirm-dialog aria-labelledby="confirm-title">
    <p class="mb-4 font-bold" id="confirm-title"></p>
    <form method="dialog" class="flex flex-wrap gap-2">
        <button class="action" value="cancel" autofocus>لغو</button>
        <button class="action action-danger" value="confirm">تأیید</button>
    </form>
</dialog>
