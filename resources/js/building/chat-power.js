import { config, request } from './api';
import { closeDialog, confirmAction, openDialog, toast } from '../ui';

export function chatPower({ groupUrl, render, jumpTo, applyDeletions }) {
    const toolbar = document.querySelector('[data-chat-selection]');
    const count = toolbar.querySelector('[data-selection-count]');
    const remove = toolbar.querySelector('[data-selection-delete]');
    const reasonDialog = document.querySelector('[data-chat-moderation]');
    const reasonForm = reasonDialog.querySelector('form');
    const selected = new Map();
    const deleted = new Set();
    const search = document.querySelector('[data-chat-search]');
    const searchForm = search.querySelector('form');
    const searchToggle = document.querySelector('[data-search-toggle]');
    const searchState = search.querySelector('[data-search-state]');
    const results = search.querySelector('[data-search-results]');
    const more = search.querySelector('[data-search-more]');
    let selecting = false;
    let selectionOrigin;
    let deletionTask;
    let searchGeneration = 0;
    let searchQuery = '';
    let cursor = null;

    function selectionCount() {
        count.textContent = selected.size.toLocaleString('fa-IR') + ' پیام انتخاب شده';
        remove.disabled = !selected.size;
    }

    function beginSelection(message) {
        if (deleted.has(message.message_id)) return;
        selecting = true;
        selectionOrigin = message.message_id;
        selected.set(message.message_id, message.user_id);
        toolbar.hidden = false;
        selectionCount();
        render();
        document.querySelector('#message-' + message.message_id + ' [data-select-message]')?.focus({ preventScroll: true });
    }

    function cancelSelection() {
        selecting = false;
        selected.clear();
        toolbar.hidden = true;
        render();
        (document.querySelector('#message-' + selectionOrigin + ' summary')
            ?? document.querySelector('#message-text'))?.focus({ preventScroll: true });
    }

    function selectionControl(row, message) {
        let held;
        let start;
        let longPressed = false;
        const bubble = row.querySelector('.chat-bubble');
        bubble.addEventListener('pointerdown', event => {
            if (event.pointerType !== 'touch' || event.target.closest('button, input, summary, label')) return;
            start = { x: event.clientX, y: event.clientY };
            held = setTimeout(() => {
                longPressed = true;
                beginSelection(message);
            }, 600);
        });
        bubble.addEventListener('pointermove', event => {
            if (start && Math.hypot(event.clientX - start.x, event.clientY - start.y) > 8) clearTimeout(held);
        });
        for (const name of ['pointerup', 'pointercancel', 'pointerleave']) {
            bubble.addEventListener(name, () => { clearTimeout(held); start = null; });
        }
        bubble.addEventListener('contextmenu', event => {
            if (longPressed || start) {
                event.preventDefault();
                if (!longPressed) beginSelection(message);
            }
        });
        if (!selecting) return;
        const label = document.createElement('label');
        label.className = 'chat-select-label';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.dataset.selectMessage = message.message_id;
        checkbox.setAttribute('aria-label', 'انتخاب پیام ' + message.user_name);
        checkbox.checked = selected.has(message.message_id);
        checkbox.addEventListener('change', () => {
            if (checkbox.checked) selected.set(message.message_id, message.user_id);
            else selected.delete(message.message_id);
            selectionCount();
        });
        label.append(checkbox);
        row.prepend(label);
    }

    async function execute(task, reason) {
        const response = await task.run(reason);
        if (!response) return;
        if (!reasonDialog.classList.contains('hidden')) closeDialog();
        task.done(response);
    }

    function askReason(task, trigger) {
        reasonForm.reset();
        deletionTask = task;
        openDialog(reasonDialog, trigger);
    }

    reasonForm.addEventListener('submit', async event => {
        event.preventDefault();
        const reason = reasonForm.elements.reason.value.trim();
        if (!reason || !deletionTask) return;
        const submit = reasonForm.querySelector('[type=submit]');
        if (submit.disabled) return;
        submit.disabled = true;
        try {
            await execute(deletionTask, reason);
        } finally {
            submit.disabled = false;
        }
    });

    async function deleteMessage(message, trigger) {
        const task = {
            run: reason => request(groupUrl + '/' + message.message_id, { method: 'DELETE', data: reason ? { reason } : undefined }),
            done: response => applyDeletions([response.deletedMessage]),
        };
        if (message.user_id !== config.userId) askReason(task, trigger);
        else if (await confirmAction('این پیام حذف شود؟')) await execute(task);
    }

    remove.addEventListener('click', async () => {
        const ids = [...selected.keys()];
        if (!ids.length || ids.length > 50) {
            toast('حداکثر ۵۰ پیام را انتخاب کنید.', 'error');
            return;
        }
        const task = {
            run: async reason => {
                remove.disabled = true;
                const data = new FormData();
                ids.forEach(id => data.append('message_ids[]', id));
                if (reason) data.set('reason', reason);
                try {
                    return await request('/dashboard/building/' + config.complexId + '/chat-bulk-delete', { method: 'POST', data });
                } finally {
                    selectionCount();
                }
            },
            done: response => {
                applyDeletions(response.results.filter(item => item.deletedMessage).map(item => item.deletedMessage));
                toast(response.success_count.toLocaleString('fa-IR') + ' پیام حذف شد، '
                    + response.denied_count.toLocaleString('fa-IR') + ' پیام رد شد.', response.denied_count ? 'error' : 'success');
                cancelSelection();
            },
        };
        if (config.canPin && [...selected.values()].some(owner => owner !== config.userId)) askReason(task, remove);
        else if (await confirmAction('پیام‌های انتخاب‌شده حذف شوند؟')) await execute(task);
    });
    toolbar.querySelector('[data-selection-cancel]').addEventListener('click', cancelSelection);

    function closeSearch() {
        search.hidden = true;
        searchToggle.setAttribute('aria-expanded', 'false');
    }
    searchToggle.addEventListener('click', () => {
        if (!search.hidden) {
            closeSearch();
            return;
        }
        search.hidden = false;
        searchToggle.setAttribute('aria-expanded', 'true');
        searchForm.elements.q.focus();
    });
    search.querySelector('[data-search-close]').addEventListener('click', () => {
        closeSearch();
        searchToggle.focus();
    });

    async function searchPage(append) {
        if (!append) {
            searchQuery = searchForm.elements.q.value.trim();
            cursor = null;
            results.replaceChildren();
        }
        if (!searchQuery) return;
        const generation = ++searchGeneration;
        const parameters = new URLSearchParams({ q: searchQuery });
        if (cursor) parameters.set('before_id', cursor);
        searchState.hidden = false;
        searchState.textContent = 'در حال جستجو…';
        more.disabled = true;
        const response = await request('/dashboard/building/' + config.complexId + '/chat-search?' + parameters);
        if (generation !== searchGeneration) return;
        more.disabled = false;
        if (!response) {
            searchState.textContent = 'جستجو انجام نشد. دوباره تلاش کنید.';
            return;
        }
        cursor = response.next_before_id;
        more.hidden = cursor === null;
        results.append(...response.results.filter(result => !deleted.has(result.message_id)).map(result => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'chat-search-result';
            button.dataset.messageId = result.message_id;
            const author = document.createElement('strong');
            author.textContent = result.user_name;
            const time = document.createElement('time');
            time.textContent = result.date;
            const snippet = document.createElement('span');
            snippet.textContent = result.snippet;
            button.append(author, document.createTextNode(' · '), time, snippet);
            button.addEventListener('click', () => {
                closeSearch();
                jumpTo(result.message_id);
            });
            return button;
        }));
        searchState.hidden = results.childElementCount > 0;
        if (!results.childElementCount) searchState.textContent = 'پیامی پیدا نشد.';
    }
    searchForm.addEventListener('submit', event => {
        event.preventDefault();
        void searchPage(false);
    });
    more.addEventListener('click', () => searchPage(true));
    searchForm.elements.q.addEventListener('input', () => {
        searchGeneration++;
        cursor = null;
        more.hidden = true;
    });

    return {
        beginSelection, selectionControl, deleteMessage,
        remove(id) {
            deleted.add(id);
            selected.delete(id);
            selectionCount();
            const result = [...results.querySelectorAll('button')].find(button => button.dataset.messageId === String(id));
            result?.remove();
        },
    };
}
