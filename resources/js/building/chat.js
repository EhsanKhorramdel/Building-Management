import Pusher from 'pusher-js';
import { config, csrf, request } from './api';
import { closeDialog, confirmAction, openDialog, toast } from '../ui';

const root = '/dashboard/building';
const group = root + '/group';
const date = new Intl.DateTimeFormat('fa-IR', { dateStyle: 'medium' });
const time = new Intl.DateTimeFormat('fa-IR', { timeStyle: 'short' });

function node(tag, className, text) {
    const element = document.createElement(tag);
    element.className = className;
    if (text != null) element.textContent = text;
    return element;
}

export function chat() {
    const list = document.querySelector('.messagesContainer');
    const scroll = document.querySelector('.chatContainer');
    const form = document.querySelector('#messageForm');
    const text = form.elements.messageText;
    const image = form.elements.messageImage;
    const send = form.querySelector('.sendBtn');
    const fileName = form.querySelector('[data-file-name]');
    const state = document.querySelector('[data-chat-state]');
    const older = document.querySelector('[data-older]');
    const newer = document.querySelector('[data-newer]');
    const latest = document.querySelector('[data-latest]');
    const editState = document.querySelector('[data-chat-edit]');
    const viewerDialog = document.querySelector('.modal-message-viewer');
    const messages = new Map();
    const marked = new Set();
    let editing = null;
    let first = null;
    let last = null;
    let pending = false;
    let pagePending = false;
    let openMenu = null;

    function closeMenu(restore = true) {
        if (!openMenu) return;
        const { menu, trigger, actions } = openMenu;
        openMenu = null;
        actions.hidePopover();
        menu.open = false;
        if (restore) trigger.focus({ preventScroll: true });
    }

    function placeMenu() {
        if (!openMenu) return;
        const { trigger, actions } = openMenu;
        const anchor = trigger.getBoundingClientRect();
        const listBounds = scroll.getBoundingClientRect();
        if (anchor.bottom <= listBounds.top || anchor.top >= listBounds.bottom) {
            closeMenu(false);
            return;
        }

        const gap = 6;
        const viewport = document.documentElement;
        const below = viewport.clientHeight - anchor.bottom - gap * 2;
        const above = anchor.top - gap * 2;
        const flip = actions.scrollHeight > below && above > below;
        actions.style.maxHeight = Math.max(0, flip ? above : below) + 'px';
        const width = actions.offsetWidth;
        const left = getComputedStyle(trigger).direction === 'rtl' ? anchor.left : anchor.right - width;
        actions.style.left = Math.max(gap, Math.min(left, viewport.clientWidth - width - gap)) + 'px';
        actions.style.top = (flip ? anchor.top - actions.offsetHeight - gap : anchor.bottom + gap) + 'px';
    }

    document.addEventListener('click', event => {
        if (openMenu && !openMenu.menu.contains(event.target)) closeMenu();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && openMenu) {
            event.preventDefault();
            closeMenu();
        }
    });
    window.addEventListener('resize', placeMenu);
    window.addEventListener('scroll', placeMenu, { capture: true });

    function cancelEdit() {
        editing = null;
        editState.hidden = true;
        text.value = '';
    }

    editState.querySelector('button').addEventListener('click', cancelEdit);
    image.addEventListener('change', () => { fileName.textContent = image.files[0]?.name || ''; });

    async function markVisible() {
        if (document.querySelector('.chat-window').hidden) return;
        const bounds = scroll.getBoundingClientRect();
        const visible = [...list.querySelectorAll('[data-message-id]')].filter(item => {
            const rect = item.getBoundingClientRect();
            return rect.bottom > bounds.top && rect.top < bounds.bottom;
        });
        for (const item of visible) {
            const id = Number(item.dataset.messageId);
            if (marked.has(id)) continue;
            marked.add(id);
            if (!await request(group + '/seen', { method: 'POST', data: { messageId: id }, json: false })) marked.delete(id);
        }
    }

    async function viewers(messageId, trigger) {
        const results = await request(root + '/getMessageViewers', { method: 'POST', data: { message_id: messageId } });
        if (!results) return;
        const wrapper = viewerDialog.querySelector('.viewer-wrapper');
        wrapper.replaceChildren(...results.map(viewer => {
            const viewed = new Date(viewer.viewed_at);
            return node('p', 'viewer-row', [viewer.user_name, date.format(viewed), time.format(viewed)].join(' · '));
        }));
        if (!results.length) wrapper.append(node('p', 'empty-state', 'هنوز کسی این پیام را ندیده است.'));
        openDialog(viewerDialog, trigger);
    }

    function render() {
        const entries = [...messages.values()].sort((left, right) => left.message_id - right.message_id);
        let day;
        closeMenu(false);
        list.replaceChildren();
        for (const message of entries) {
            const created = new Date(message.created_at.replace(' ', 'T'));
            const currentDay = date.format(created);
            if (currentDay !== day) list.append(node('p', 'chat-date', currentDay));
            day = currentDay;
            const own = message.user_id === config.userId;
            const row = node('article', own ? 'chat-message own-message' : 'chat-message');
            row.dataset.messageId = message.message_id;
            row.id = 'message-' + message.message_id;
            const bubble = node('div', 'chat-bubble');
            const heading = node('div', 'chat-message-heading');
            heading.append(node('strong', 'chat-author', message.user_name));
            const menu = node('details', 'chat-menu');
            const trigger = node('summary', 'chat-menu-toggle', '⋯');
            trigger.setAttribute('aria-label', 'گزینه‌های پیام');
            const actions = node('div', 'chat-menu-actions');
            actions.setAttribute('popover', 'manual');
            trigger.addEventListener('click', event => {
                event.preventDefault();
                if (openMenu?.menu === menu) {
                    closeMenu();
                    return;
                }
                closeMenu(false);
                menu.open = true;
                actions.showPopover();
                openMenu = { menu, trigger, actions };
                placeMenu();
            });
            function action(label, run) {
                const button = node('button', 'chat-action', label);
                button.type = 'button';
                button.addEventListener('click', async () => {
                    closeMenu();
                    await run(trigger);
                });
                actions.append(button);
            }
            if (message.text) action('کپی', async () => {
                try {
                    await navigator.clipboard.writeText(message.text);
                    toast('متن کپی شد.');
                } catch {
                    toast('کپی متن انجام نشد.', 'error');
                }
            });
            if (own) {
                action('ویرایش', () => {
                    editing = message;
                    editState.hidden = false;
                    text.value = message.text || '';
                    text.focus();
                });
                action('حذف', async () => {
                    if (!await confirmAction('این پیام حذف شود؟')) return;
                    const result = await request(group + '/' + message.message_id, { method: 'DELETE' });
                    if (!result) return;
                    if (String(result.status) === '403') toast(result.message, 'error');
                    else {
                        messages.delete(message.message_id);
                        render();
                    }
                });
                action('مشاهده کنندگان', opener => viewers(message.message_id, opener));
            }
            if (actions.childElementCount) {
                menu.append(trigger, actions);
                heading.append(menu);
            }
            bubble.append(heading);
            if (message.image_url) {
                const photo = node('img', 'chat-image');
                photo.src = message.image_url;
                photo.alt = 'تصویر پیام ' + message.user_name;
                bubble.append(photo);
            }
            if (message.text) bubble.append(node('p', 'message-text', message.text));
            bubble.append(node('p', 'chat-time', time.format(created) + (message.is_edited ? ' · ویرایش شده' : '')));
            row.append(bubble);
            list.append(row);
        }
        latest.hidden = entries.length === 0;
        state.hidden = entries.length > 0;
        if (!entries.length) state.textContent = 'هنوز پیامی وجود ندارد. گفت‌وگو را آغاز کنید.';
    }

    function merge(results) {
        for (const item of results.messages) messages.set(item.message_id, item);
    }

    async function load(direction = 'initial') {
        if (pagePending) return;
        pagePending = true;
        older.disabled = newer.disabled = latest.disabled = true;
        try {
            if (direction === 'initial' || direction === 'latest') {
                state.textContent = 'در حال دریافت پیام‌ها…';
                state.hidden = false;
                state.dataset.loading = '';
                const receipt = direction === 'latest' ? {} : await request(root + '/' + config.complexId + '/lastMessageSeenId');
                const result = receipt && await request(root + '/' + config.complexId + '/group/' + (receipt.last_message_seen_id ?? 0));
                if (!result) {
                    state.textContent = 'پیام‌ها دریافت نشدند.';
                    return;
                }
                messages.clear();
                merge(result);
                first = result.first_message_id;
                last = result.last_message_id;
                older.hidden = first === null;
                newer.hidden = last === null;
                render();
                if (direction === 'latest') {
                    await Promise.allSettled([...list.querySelectorAll('img')].map(image => image.decode()));
                }
                const anchor = document.getElementById('message-' + receipt.last_message_seen_id);
                if (anchor) scroll.scrollTop = anchor.offsetTop - scroll.offsetTop;
                else scroll.scrollTop = scroll.scrollHeight;
            } else {
                const before = direction === 'before';
                const cursor = before ? first : last;
                if (cursor === null) return;
                const endpoint = before ? 'getMessagesBeforeFrom' : 'getMessagesAfterFrom';
                const result = await request(root + '/' + endpoint + '/' + config.complexId + '/' + cursor);
                if (!result) return;
                const oldHeight = scroll.scrollHeight;
                const oldTop = scroll.scrollTop;
                merge(result);
                if (before) first = result.first_message_id;
                else last = result.last_message_id;
                (before ? older : newer).hidden = !result.messages.length;
                render();
                // Keep the same message in view after inserting an older page above it.
                if (before) scroll.scrollTop = oldTop + scroll.scrollHeight - oldHeight;
            }
            void markVisible();
        } finally {
            delete state.dataset.loading;
            older.disabled = newer.disabled = latest.disabled = false;
            pagePending = false;
        }
    }

    older.addEventListener('click', () => load('before'));
    newer.addEventListener('click', () => load('after'));
    latest.addEventListener('click', () => load('latest'));
    let scrollTimer;
    scroll.addEventListener('scroll', () => {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(markVisible, 180);
    });
    document.addEventListener('panelchange', event => {
        if (event.detail === 'chat') void load();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (pending || (!text.value.trim() && (!image.files.length || editing))) return;
        const editedText = text.value.trim();
        if (editing && editedText === editing.text) {
            cancelEdit();
            return;
        }
        pending = true;
        send.disabled = true;
        form.setAttribute('aria-busy', 'true');
        try {
            const data = editing ? { messageId: editing.message_id, messageText: editedText } : new FormData(form);
            if (!editing) {
                data.set('complexId', config.complexId);
                if (!image.files.length) data.delete('messageImage');
            }
            const result = await request(group + (editing ? '/edit' : '/send'), { method: 'POST', data, json: false });
            if (!result) return;
            form.reset();
            fileName.textContent = '';
            cancelEdit();
            await load();
        } finally {
            pending = false;
            send.disabled = false;
            form.removeAttribute('aria-busy');
        }
    });

    text.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            form.requestSubmit(send);
        }
    });
    viewerDialog.querySelector('button').addEventListener('click', closeDialog);

    if (config.connection === 'pusher' && config.key) {
        const pusher = new Pusher(config.key, {
            cluster: config.cluster,
            channelAuthorization: {
                endpoint: config.broadcastAuth,
                transport: 'ajax',
                headers: { 'X-CSRF-TOKEN': csrf() },
            },
        });
        const channel = pusher.subscribe('private-group.' + config.complexId);
        channel.bind('pusher:subscription_error', () => toast('اتصال گفت‌وگوی زنده برقرار نشد.', 'error'));
        channel.bind('NewChatMessage', ({ message }) => {
            if (document.querySelector('.chat-window').hidden) return;
            messages.set(message.message_id, message);
            first ??= message.message_id;
            last = Math.max(last ?? 0, message.message_id);
            render();
            scroll.scrollTop = scroll.scrollHeight;
            void markVisible();
        });
        channel.bind('EditChatMessage', ({ editedMessage }) => {
            const message = messages.get(editedMessage.id);
            if (message) {
                message.text = editedMessage.text;
                message.is_edited = true;
                render();
            }
        });
        channel.bind('DeleteChatMessage', ({ deletedMessage }) => {
            if (messages.delete(deletedMessage.id)) render();
        });
    }
}
