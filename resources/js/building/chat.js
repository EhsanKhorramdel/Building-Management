import Pusher from 'pusher-js';
import { config, csrf, request } from './api';
import { closeDialog, openDialog, toast } from '../ui';
import { chatSocial } from './chat-social';
import { chatPower } from './chat-power';
import { typingIndicator } from './chat-typing';
import { mediaViewer } from '../media-viewer';

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
    const replyState = document.querySelector('[data-chat-reply]');
    const viewerDialog = document.querySelector('.modal-message-viewer');
    const messages = new Map();
    const marked = new Set();
    const deletions = new Map();
    const receipts = new Map();
    const media = mediaViewer();
    const social = chatSocial(group, root + '/' + config.complexId + '/chat-pins', jumpTo);
    const power = chatPower({ groupUrl: group, render, jumpTo, applyDeletions });
    const typingOutput = document.querySelector('[data-chat-typing]');
    const typingUser = { id: config.userId, name: config.userName };
    let typing = typingIndicator(null, typingUser, typingOutput);
    let editing = null;
    let replying = null;
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
        typing.stop();
    }

    editState.querySelector('button').addEventListener('click', cancelEdit);
    function clearReply() {
        replying = null;
        replyState.hidden = true;
        replyState.querySelector('[data-reply-author]').textContent = '';
        replyState.querySelector('[data-reply-snippet]').textContent = '';
    }
    replyState.querySelector('button').addEventListener('click', () => {
        clearReply();
        text.focus();
    });
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
            if (marked.has(id) || deletions.has(id)) continue;
            marked.add(id);
            if (!await request(group + '/seen', { method: 'POST', data: { messageId: id }, json: false })) marked.delete(id);
        }
    }

    function redact(message, kind) {
        Object.assign(message, { is_deleted: true, deletion_kind: kind, text: null, image_url: null, quote: null });
    }

    function rememberDeletion(id, kind) {
        media.remove(id);
        deletions.set(id, kind);
        social.remove(id);
        power.remove(id);
        const message = messages.get(id);
        if (message) redact(message, kind);
        for (const reply of messages.values()) {
            if (reply.quote?.message_id === id) reply.quote = { message_id: id, deleted: true };
        }
        if (replying === id) clearReply();
        if (editing?.message_id === id) cancelEdit();
    }

    function applyDeletion({ id, deletion_kind: kind }) {
        applyDeletions([{ id, deletion_kind: kind }]);
    }

    function applyDeletions(items) {
        for (const { id, deletion_kind: kind } of items) rememberDeletion(id, kind);
        const top = scroll.scrollTop;
        render();
        scroll.scrollTop = top;
    }

    function startReply(message) {
        if (editing) cancelEdit();
        replying = message.message_id;
        replyState.querySelector('[data-reply-author]').textContent = message.user_name;
        const snippet = (message.text ?? '').replace(/\s+/g, ' ').trim() || 'تصویر';
        replyState.querySelector('[data-reply-snippet]').textContent = snippet.length > 96 ? snippet.slice(0, 96) + '…' : snippet;
        replyState.hidden = false;
        text.focus();
    }

    function focusMessage(id) {
        const row = document.getElementById('message-' + id);
        if (!row) return false;
        scroll.scrollTop += row.getBoundingClientRect().top - scroll.getBoundingClientRect().top
            - (scroll.clientHeight - row.offsetHeight) / 2;
        row.focus({ preventScroll: true });
        row.classList.add('chat-message-highlight');
        setTimeout(() => row.classList.remove('chat-message-highlight'), 2500);
        return true;
    }

    function jumpTo(id) {
        if (!focusMessage(id)) void load('jump', id);
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

    function receipt({ message_id: id, count }) {
        const message = messages.get(id);
        if (!message || message.is_deleted || (message.user_id !== config.userId && !config.canPin)) return;
        receipts.set(id, Math.max(receipts.get(id) ?? 0, count));
        const label = list.querySelector('[data-seen-message="' + id + '"]');
        if (label) {
            label.textContent = 'دیده شد • ' + receipts.get(id).toLocaleString('fa-IR');
            label.hidden = receipts.get(id) === 0;
        }
    }

    async function loadReceipts() {
        const ids = [...messages.values()].filter(message => !message.is_deleted
            && (message.user_id === config.userId || config.canPin)).map(message => message.message_id);
        for (let offset = 0; offset < ids.length; offset += 50) {
            const parameters = new URLSearchParams();
            ids.slice(offset, offset + 50).forEach(id => parameters.append('message_ids[]', id));
            const result = await request(root + '/' + config.complexId + '/chat-seen-counts?' + parameters);
            result?.counts.forEach(receipt);
        }
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
            row.tabIndex = -1;
            const bubble = node('div', 'chat-bubble');
            if (message.is_deleted) {
                const label = message.deletion_kind === 'manager' ? 'توسط مدیر حذف شد' : 'این پیام حذف شد';
                bubble.append(node('p', 'chat-tombstone', label));
                row.append(bubble);
                list.append(row);
                continue;
            }
            const heading = node('div', 'chat-message-heading');
            heading.append(node('strong', 'chat-author', message.user_name));
            const menu = node('details', 'chat-menu');
            const trigger = node('summary', 'chat-menu-toggle', '⋯');
            trigger.setAttribute('aria-label', 'گزینه‌های پیام');
            const actions = node('div', 'chat-menu-actions');
            actions.setAttribute('popover', 'manual');
            let pinAction;
            trigger.addEventListener('click', event => {
                event.preventDefault();
                if (openMenu?.menu === menu) {
                    closeMenu();
                    return;
                }
                closeMenu(false);
                if (pinAction) pinAction.textContent = social.pinLabel(message.message_id);
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
                return button;
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
                    clearReply();
                    editing = message;
                    editState.hidden = false;
                    text.value = message.text || '';
                    text.focus();
                });
                action('حذف', opener => power.deleteMessage(message, opener));
                action('مشاهده کنندگان', opener => viewers(message.message_id, opener));
            } else if (config.canPin) {
                action('حذف توسط مدیر', opener => power.deleteMessage(message, opener));
            }
            action('واکنش', opener => social.pickReaction(message.message_id, opener));
            action('پاسخ', () => startReply(message));
            if (config.canPin) {
                pinAction = action(social.pinLabel(message.message_id), () => social.togglePin(message.message_id));
            }
            action('انتخاب', () => power.beginSelection(message));
            const quick = node('button', 'chat-social-button', '☺');
            quick.type = 'button';
            quick.dataset.quickReaction = '';
            quick.setAttribute('aria-label', 'افزودن واکنش');
            quick.addEventListener('click', () => social.pickReaction(message.message_id, quick));
            menu.append(trigger, actions);
            const controls = node('div', 'chat-social-actions');
            controls.append(quick, menu);
            heading.append(controls);
            bubble.append(heading);
            if (message.quote) {
                const quote = node('button', 'chat-quote');
                quote.type = 'button';
                if (message.quote.deleted) quote.textContent = 'این پیام حذف شد';
                else quote.append(node('strong', '', message.quote.user_name), node('span', '', message.quote.snippet));
                quote.addEventListener('click', () => jumpTo(message.quote.message_id));
                bubble.append(quote);
            }
            if (message.image_url) {
                const photo = node('img', 'chat-image');
                photo.src = message.image_url;
                photo.alt = 'تصویر پیام ' + message.user_name;
                const control = node('button', 'chat-image-open');
                control.type = 'button';
                control.setAttribute('aria-label', 'باز کردن ' + photo.alt);
                control.append(photo);
                control.addEventListener('click', () => media.open({ id: message.message_id, url: message.image_url, alt: photo.alt }, control));
                bubble.append(control);
            }
            if (message.text) bubble.append(node('p', 'message-text', message.text));
            const timestamp = node('p', 'chat-time', time.format(created) + (message.is_edited ? ' · ویرایش شده' : ''));
            if (own || config.canPin) {
                const count = receipts.get(message.message_id) ?? 0;
                const label = node('span', 'chat-seen-count', 'دیده شد • ' + count.toLocaleString('fa-IR'));
                label.dataset.seenMessage = message.message_id;
                label.hidden = count === 0;
                timestamp.append(label);
            }
            bubble.append(timestamp);
            const reactionRow = node('div', 'chat-reactions');
            social.renderReactions(reactionRow, message.message_id);
            bubble.append(reactionRow);
            row.append(bubble);
            power.selectionControl(row, message);
            list.append(row);
        }
        latest.hidden = entries.length === 0;
        state.hidden = entries.length > 0;
        if (!entries.length) state.textContent = 'هنوز پیامی وجود ندارد. گفت‌وگو را آغاز کنید.';
    }

    function merge(results) {
        for (const item of results.messages) {
            if (item.is_deleted) {
                rememberDeletion(item.message_id, item.deletion_kind);
            }
        }
        for (const item of results.messages) {
            if (deletions.has(item.message_id)) redact(item, deletions.get(item.message_id));
            if (deletions.has(item.quote?.message_id)) item.quote = { message_id: item.quote.message_id, deleted: true };
            if (item.reactions) social.rememberReactions({
                message_id: item.message_id, reactions: item.reactions, version: item.reaction_version,
            });
            messages.set(item.message_id, item);
        }
    }

    async function load(direction = 'initial', target = 0) {
        if (pagePending) return;
        pagePending = true;
        older.disabled = newer.disabled = latest.disabled = true;
        try {
            if (direction === 'initial' || direction === 'latest' || direction === 'jump') {
                state.textContent = 'در حال دریافت پیام‌ها…';
                state.hidden = false;
                state.dataset.loading = '';
                const receipt = direction === 'initial' ? await request(root + '/' + config.complexId + '/lastMessageSeenId')
                    : { last_message_seen_id: target };
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
                if (direction !== 'initial') {
                    await Promise.allSettled([...list.querySelectorAll('img')].map(image => image.decode()));
                }
                if (direction === 'jump') focusMessage(target);
                else {
                    const anchor = document.getElementById('message-' + receipt.last_message_seen_id);
                    if (anchor) scroll.scrollTop = anchor.offsetTop - scroll.offsetTop;
                    else scroll.scrollTop = scroll.scrollHeight;
                }
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
            void loadReceipts();
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
        if (event.detail === 'chat') {
            void load();
            void social.loadPins();
        } else typing.stop();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (pending || (!text.value.trim() && (!image.files.length || editing))) return;
        typing.stop();
        const editedText = text.value.trim();
        if (editing && editedText === editing.text) {
            cancelEdit();
            return;
        }
        pending = true;
        send.disabled = true;
        form.setAttribute('aria-busy', 'true');
        try {
            const editedId = editing?.message_id;
            const data = editing ? { messageId: editing.message_id, messageText: editedText } : new FormData(form);
            if (!editing) {
                data.set('complexId', config.complexId);
                if (replying !== null) data.set('parent_id', replying);
                if (!image.files.length) data.delete('messageImage');
            }
            const result = await request(group + (editing ? '/edit' : '/send'), { method: 'POST', data, json: false });
            if (!result) return;
            form.reset();
            fileName.textContent = '';
            cancelEdit();
            clearReply();
            if (editedId) {
                await load('jump', editedId);
                void social.loadPins();
            } else {
                await load('latest');
            }
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
    text.addEventListener('input', () => typing.input(text.value));
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) typing.stop();
    });
    window.addEventListener('pagehide', () => typing.stop());
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
        typing = typingIndicator(channel, typingUser, typingOutput);
        channel.bind('pusher:subscription_error', () => toast('اتصال گفت‌وگوی زنده برقرار نشد.', 'error'));
        channel.bind('NewChatMessage', ({ message }) => {
            if (document.querySelector('.chat-window').hidden) return;
            merge({ messages: [message] });
            first ??= message.message_id;
            last = Math.max(last ?? 0, message.message_id);
            render();
            scroll.scrollTop = scroll.scrollHeight;
            void markVisible();
        });
        channel.bind('EditChatMessage', ({ editedMessage }) => {
            const message = messages.get(editedMessage.id);
            if (message && !message.is_deleted && !editedMessage.deletion_kind) {
                message.text = editedMessage.text;
                message.is_edited = true;
                render();
            }
        });
        channel.bind('DeleteChatMessage', ({ deletedMessage }) => applyDeletion(deletedMessage));
        channel.bind('ChatMessagesDeleted', ({ messages: deletedMessages }) => applyDeletions(deletedMessages));
        channel.bind('ChatReactionsChanged', social.applyReactions);
        channel.bind('ChatPinChanged', social.applyPin);
        channel.bind('ChatSeenCountChanged', receipt);
    }
}
