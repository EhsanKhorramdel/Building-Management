export const TYPING_EVENT = 'client-typing';
export const TYPING_EXPIRY = 5000;
export const TYPING_THROTTLE = 1500;

export function typingPayload(user, active) {
    return { user_id: user.id, name: String(user.name).slice(0, 80), typing: active };
}

export function typingIndicator(channel, user, output) {
    if (!channel) return { input() {}, stop() {} };
    const peers = new Map();
    let ready = channel.subscribed === true;
    let sentAt = -Infinity;
    let active = false;
    let idle;

    function render() {
        const names = [...peers.values()].map(peer => peer.name);
        output.textContent = names.length ? names.join('، ') + ' در حال نوشتن است' : '';
        output.hidden = !names.length;
    }

    function transmit(value) {
        if (!ready) return;
        try {
            channel.trigger(TYPING_EVENT, typingPayload(user, value));
        } catch {
            // Typing is advisory and expires even when the socket disconnects.
        }
    }

    function stop() {
        clearTimeout(idle);
        if (active) transmit(false);
        active = false;
        sentAt = -Infinity;
    }

    channel.bind('pusher:subscription_succeeded', () => { ready = true; });
    channel.bind('pusher:subscription_error', () => { ready = false; });
    channel.bind(TYPING_EVENT, payload => {
        if (!payload || !Number.isSafeInteger(payload.user_id) || payload.user_id <= 0
            || payload.user_id === user.id || typeof payload.name !== 'string'
            || typeof payload.typing !== 'boolean') return;
        clearTimeout(peers.get(payload.user_id)?.timer);
        peers.delete(payload.user_id);
        if (payload.typing && payload.name.trim()) {
            peers.set(payload.user_id, {
                name: payload.name.trim().slice(0, 80),
                timer: setTimeout(() => {
                    peers.delete(payload.user_id);
                    render();
                }, TYPING_EXPIRY),
            });
        }
        render();
    });

    return {
        input(value) {
            if (!String(value).trim()) {
                stop();
                return;
            }
            const now = Date.now();
            if (!active || now - sentAt >= TYPING_THROTTLE) {
                transmit(true);
                sentAt = now;
                active = true;
            }
            clearTimeout(idle);
            idle = setTimeout(stop, TYPING_EXPIRY);
        },
        stop,
    };
}
