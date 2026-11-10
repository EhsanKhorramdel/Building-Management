import { test } from 'node:test';
import assert from 'node:assert/strict';
import { TYPING_EVENT, TYPING_EXPIRY, typingIndicator, typingPayload } from '../../resources/js/building/chat-typing.js';

function channel(subscribed = true) {
    const handlers = new Map();
    const sent = [];
    return {
        subscribed, sent,
        bind(event, handler) { handlers.set(event, handler); },
        trigger(event, payload) { sent.push({ event, payload }); return true; },
        receive(event, payload) { handlers.get(event)?.(payload); },
    };
}

test('typing whispers contain only an ID, a bounded name and an active flag', () => {
    assert.deepEqual(typingPayload({ id: 12, name: 'مدیر' }, true), { user_id: 12, name: 'مدیر', typing: true });
    assert.equal(typingPayload({ id: 12, name: 'x'.repeat(120) }, false).name.length, 80);
    assert.equal(TYPING_EVENT, 'client-typing');
});

test('log broadcaster use is a silent no-op', () => {
    const output = { textContent: '', hidden: true };
    const typing = typingIndicator(null, { id: 1, name: 'Author' }, output);
    typing.input('Draft');
    typing.input('');
    typing.stop();
    assert.deepEqual(output, { textContent: '', hidden: true });
});

test('keystrokes are throttled and send or clear sends a stop immediately', context => {
    context.mock.timers.enable({ apis: ['setTimeout', 'Date'], now: 10_000 });
    const socket = channel();
    const typing = typingIndicator(socket, { id: 1, name: 'Author' }, {});
    typing.input('A');
    context.mock.timers.tick(100);
    typing.input('AB');
    assert.equal(socket.sent.length, 1);
    context.mock.timers.tick(1400);
    typing.input('ABC');
    assert.equal(socket.sent.length, 2);
    typing.stop();
    assert.equal(socket.sent.at(-1).payload.typing, false);
    typing.input('New');
    typing.input(' ');
    assert.equal(socket.sent.at(-1).payload.typing, false);
});

test('a received name expires five seconds after the most recent signal', context => {
    context.mock.timers.enable({ apis: ['setTimeout', 'Date'], now: 0 });
    const socket = channel();
    const output = { textContent: '', hidden: true };
    typingIndicator(socket, { id: 1, name: 'Self' }, output);
    socket.receive(TYPING_EVENT, { user_id: 2, name: 'ساکن', typing: true });
    assert.equal(output.textContent, 'ساکن در حال نوشتن است');
    context.mock.timers.tick(4000);
    socket.receive(TYPING_EVENT, { user_id: 2, name: 'ساکن', typing: true });
    context.mock.timers.tick(TYPING_EXPIRY - 1);
    assert.equal(output.hidden, false);
    context.mock.timers.tick(1);
    assert.equal(output.hidden, true);
    assert.equal(output.textContent, '');
});

test('a stop removes the peer immediately and invalid or self payloads are ignored', context => {
    context.mock.timers.enable({ apis: ['setTimeout', 'Date'], now: 0 });
    const socket = channel();
    const output = { textContent: '', hidden: true };
    typingIndicator(socket, { id: 1, name: 'Self' }, output);
    for (const payload of [null, { user_id: 1, name: 'Self', typing: true }, { user_id: '2', name: 'Other', typing: true },
        { user_id: 2, name: 'Other', typing: 'yes' }]) socket.receive(TYPING_EVENT, payload);
    assert.equal(output.hidden, true);
    socket.receive(TYPING_EVENT, { user_id: 2, name: 'Other', typing: true });
    socket.receive(TYPING_EVENT, { user_id: 2, name: 'Other', typing: false });
    assert.equal(output.hidden, true);
});

test('typing starts only after the private subscription is ready', context => {
    context.mock.timers.enable({ apis: ['setTimeout', 'Date'], now: 0 });
    const socket = channel(false);
    const typing = typingIndicator(socket, { id: 1, name: 'Self' }, {});
    typing.input('Before subscribing');
    assert.equal(socket.sent.length, 0);
    socket.receive('pusher:subscription_succeeded');
    context.mock.timers.tick(1500);
    typing.input('Subscribed');
    assert.equal(socket.sent.length, 1);
});
