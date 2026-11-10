import { config, request } from './api';
import { closeDialog, openDialog } from '../ui';

export function chatSocial(groupUrl, pinsUrl, jumpTo) {
    const dialog = document.querySelector('[data-chat-social-dialog]');
    const title = dialog.querySelector('h3');
    const options = dialog.querySelector('[data-chat-social-options]');
    const strip = document.querySelector('[data-chat-pins]');
    const reactions = new Map();
    const pins = new Map();
    const pinVersions = new Map();
    const deleted = new Set();
    let pendingReaction = false;

    function button(className, text) {
        const control = document.createElement('button');
        control.type = 'button';
        control.className = className;
        control.textContent = text;
        return control;
    }

    function showReactors(reaction, trigger) {
        if (!dialog.classList.contains('hidden')) return;
        title.textContent = 'واکنش‌ها ' + reaction.emoji;
        options.className = 'grid gap-2';
        options.replaceChildren(...reaction.reactors.map(reactor => {
            const name = document.createElement('p');
            name.className = 'viewer-row';
            name.textContent = reactor.name;
            return name;
        }));
        openDialog(dialog, trigger);
    }

    function renderReactions(row, id) {
        const selected = document.activeElement?.dataset.reaction;
        const hadFocus = row.contains(document.activeElement);
        row.replaceChildren(...(reactions.get(id)?.reactions ?? []).map(reaction => {
            const control = button('chat-reaction', reaction.emoji + ' ' + reaction.count.toLocaleString('fa-IR'));
            control.dataset.reaction = reaction.emoji;
            control.title = reaction.reactors.map(reactor => reactor.name).join('، ');
            control.setAttribute('aria-label', 'مشاهده واکنش ' + reaction.emoji + '، ' + reaction.count.toLocaleString('fa-IR'));
            // A private group event has one payload for every subscriber. Each client checks its own ID.
            control.setAttribute('aria-pressed', String(reaction.reacted ?? reaction.reactors.some(reactor => reactor.id === config.userId)));
            control.addEventListener('click', () => showReactors(reaction, control));
            control.addEventListener('contextmenu', event => {
                event.preventDefault();
                showReactors(reaction, control);
            });
            return control;
        }));
        if (hadFocus) {
            ([...row.children].find(control => control.dataset.reaction === selected)
                ?? row.closest('.chat-message')?.querySelector('[data-quick-reaction]'))?.focus({ preventScroll: true });
        }
    }

    function rememberReactions(update) {
        const id = update.message_id;
        if (deleted.has(id) || (reactions.get(id)?.version ?? -1) > update.version) return false;
        reactions.set(id, update);
        return true;
    }

    function applyReactions(update) {
        if (!rememberReactions(update)) return;
        const row = document.getElementById('message-' + update.message_id)?.querySelector('.chat-reactions');
        if (row) renderReactions(row, update.message_id);
    }

    function pickReaction(id, trigger) {
        title.textContent = 'انتخاب واکنش';
        options.className = 'chat-reaction-picker';
        const active = reactions.get(id)?.reactions ?? [];
        options.replaceChildren(...config.reactionPalette.map(emoji => {
            const control = button('chat-reaction', emoji);
            control.setAttribute('aria-label', 'واکنش ' + emoji);
            const reaction = active.find(item => item.emoji === emoji);
            control.setAttribute('aria-pressed', String(reaction?.reactors.some(reactor => reactor.id === config.userId) ?? false));
            control.addEventListener('click', async () => {
                if (pendingReaction || deleted.has(id)) return;
                pendingReaction = true;
                try {
                    const result = await request(groupUrl + '/' + id + '/reactions', { method: 'POST', data: { emoji } });
                    if (!result) return;
                    applyReactions(result);
                    if (options.contains(control)) closeDialog();
                } finally {
                    pendingReaction = false;
                }
            });
            return control;
        }));
        openDialog(dialog, trigger);
    }

    function renderPins() {
        const focusedId = document.activeElement?.dataset.pinMessage;
        const restore = strip.contains(document.activeElement);
        const ordered = [...pins.values()].sort((left, right) =>
            right.pinned_at.localeCompare(left.pinned_at) || right.message_id - left.message_id);
        strip.replaceChildren(...ordered.map(pin => {
            const control = button('chat-pin', '');
            control.dataset.pinMessage = pin.message_id;
            const author = document.createElement('strong');
            author.textContent = pin.user_name;
            const snippet = document.createElement('span');
            snippet.textContent = pin.snippet;
            control.append(author, snippet);
            control.addEventListener('click', () => jumpTo(pin.message_id));
            return control;
        }));
        strip.hidden = !ordered.length;
        if (restore) [...strip.children].find(control => control.dataset.pinMessage === focusedId)?.focus();
    }

    function applyPin(update) {
        const id = update.message_id;
        if (deleted.has(id) || (pinVersions.get(id) ?? -1) > update.version) return;
        pinVersions.set(id, update.version);
        if (update.pin) pins.set(id, update.pin);
        else pins.delete(id);
        renderPins();
    }

    async function loadPins() {
        const previous = new Map(pins);
        const result = await request(pinsUrl);
        if (!result) return;
        const incoming = new Set(result.map(pin => pin.message_id));
        for (const [id, pin] of previous) {
            if (!incoming.has(id) && pins.get(id) === pin) pins.delete(id);
        }
        for (const pin of result) applyPin({ message_id: pin.message_id, version: pin.version, pin });
        renderPins();
    }

    async function togglePin(id) {
        const result = await request(groupUrl + '/' + id + '/pin', { method: 'PUT', data: { pinned: pins.has(id) ? 0 : 1 } });
        if (result) applyPin(result);
    }

    function remove(id) {
        deleted.add(id);
        reactions.delete(id);
        if (pins.delete(id)) renderPins();
    }

    return {
        pickReaction, rememberReactions, renderReactions, applyReactions,
        loadPins, togglePin, applyPin, remove,
        pinLabel: id => pins.has(id) ? 'برداشتن سنجاق' : 'سنجاق کردن',
    };
}
