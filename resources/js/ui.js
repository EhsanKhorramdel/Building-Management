import './building/settings';

const toastContainer = document.querySelector('[data-toasts]');

export function toast(message, status = 'success', existing = null) {
    if (!toastContainer || !message) return;
    const item = existing || document.createElement('div');
    item.className = 'toast';
    item.dataset.status = status;
    item.setAttribute('role', status === 'error' ? 'alert' : 'status');
    const text = document.createElement('span');
    text.textContent = message;
    const close = document.createElement('button');
    close.type = 'button';
    close.textContent = '×';
    close.setAttribute('aria-label', 'بستن پیام');
    close.addEventListener('click', () => item.remove());
    item.replaceChildren(text, close);
    if (!existing) toastContainer.append(item);
    setTimeout(() => item.remove(), 6000);
}

toastContainer?.querySelectorAll('[data-flash]').forEach(item => toast(item.textContent.trim(), item.dataset.status, item));

let activeDialog = null;

export function closeDialog() {
    if (!activeDialog) return;
    const { element, trigger } = activeDialog;
    if (element instanceof HTMLDialogElement) element.close();
    else element.classList.add('hidden');
    activeDialog = null;
    trigger?.focus();
}

export function openDialog(element, trigger = document.activeElement) {
    if (!element) return;
    closeDialog();
    activeDialog = { element, trigger };
    const title = element.querySelector('h2, h3, .buildings-section-header > p');
    if (title && !element.hasAttribute('aria-labelledby')) element.setAttribute('aria-label', title.textContent.trim());
    if (element instanceof HTMLDialogElement) element.showModal();
    else element.classList.remove('hidden');
    element.querySelector('input[aria-invalid=true], textarea[aria-invalid=true]')?.focus();
    if (!element.contains(document.activeElement)) {
        element.querySelector('input:not([type=hidden]), textarea, button, [tabindex="0"]')?.focus();
    }
}

export function confirmAction(message) {
    const dialog = document.querySelector('[data-confirm-dialog]');
    if (!dialog) return Promise.resolve(window.confirm(message));
    dialog.querySelector('p').textContent = message;
    dialog.returnValue = '';
    return new Promise(resolve => {
        dialog.addEventListener('close', () => {
            const confirmed = dialog.returnValue === 'confirm';
            if (activeDialog?.element === dialog) closeDialog();
            resolve(confirmed);
        }, { once: true });
        openDialog(dialog);
    });
}

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && activeDialog) {
        event.preventDefault();
        closeDialog();
    }
    if (event.key === 'Tab' && activeDialog && !(activeDialog.element instanceof HTMLDialogElement)) {
        const controls = [...activeDialog.element.querySelectorAll('button, input, textarea, select, a[href], [tabindex="0"]')]
            .filter(control => !control.disabled && control.getClientRects().length);
        const first = controls[0];
        const last = controls.at(-1);
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first?.focus();
        }
    }
});

document.addEventListener('click', event => {
    if (event.target.closest('[data-close-dialog]') || event.target === activeDialog?.element) closeDialog();
    const toggle = event.target.closest('[data-password-toggle]');
    if (toggle) {
        const input = document.getElementById(toggle.dataset.passwordToggle);
        const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(showing));
    }
});

const confirmed = new WeakSet();
document.addEventListener('submit', async event => {
    const form = event.target;
    if (event.defaultPrevented || form.method === 'dialog') return;
    if (form.dataset.confirm && !confirmed.has(form)) {
        event.preventDefault();
        if (await confirmAction(form.dataset.confirm)) {
            confirmed.add(form);
            form.requestSubmit(event.submitter || undefined);
            confirmed.delete(form);
        }
        return;
    }
    form.setAttribute('aria-busy', 'true');
    queueMicrotask(() => {
        form.querySelectorAll('button[type=submit], input[type=submit]').forEach(button => {
            if (!button.disabled) {
                button.dataset.pending = '';
                button.disabled = true;
            }
        });
    });
});

window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-pending]').forEach(button => {
        button.disabled = false;
        delete button.dataset.pending;
        button.form?.removeAttribute('aria-busy');
    });
});
