import { expect, test } from '@playwright/test';
import { liveBroadcast } from './environment.js';
import { login, openBuilding, submitMessage, tab } from './ui.js';

async function withChatPair({ page, browser, baseURL }, text, verify, beforeSend = async () => {}) {
    const readerContext = await browser.newContext({ baseURL });
    try {
        const reader = await readerContext.newPage();
        await login(page, 'manager');
        await login(reader, 'resident');
        const senderSocket = liveBroadcast ? observeChatSocket(page) : null;
        const readerSocket = liveBroadcast ? observeChatSocket(reader) : null;
        await openBuilding(page);
        await openBuilding(reader);
        await tab(page, 'chat');
        await tab(reader, 'chat');
        await expect(reader.getByText('پیام نمونه 36', { exact: true })).toBeVisible();
        await expect(reader.getByText(text, { exact: true })).toHaveCount(0);
        await beforeSend(reader, senderSocket, readerSocket);
        await page.locator('#message-text').fill(text);
        const started = performance.now();
        const send = async () => {
            await submitMessage(page);
            await expect(page.getByText(text, { exact: true })).toBeVisible({ timeout: 20_000 });
        };
        if (liveBroadcast) {
            await Promise.all([send(), verify(reader, text, started)]);
        } else {
            await send();
            await verify(reader, text, started);
        }
    } finally {
        await readerContext.close();
    }
}

test('with the log broadcaster, a second signed-in context sees the message after reload', async ({ page, browser, baseURL }) => {
    test.skip(liveBroadcast, 'This case checks the log broadcaster and reload.');
    await withChatPair({ page, browser, baseURL }, 'پیام آزمون دو نشست', async (reader, text) => {
        await reader.reload();
        await tab(reader, 'chat');
        await expect(reader.getByText(text, { exact: true })).toBeVisible({ timeout: 20_000 });
    });
});

function observeChatSocket(page) {
    const state = { channel: null, socketId: null, error: null };
    page.on('websocket', socket => {
        if (!new URL(socket.url()).hostname.endsWith('.pusher.com')) return;
        socket.on('socketerror', error => {
            state.error = error.match(/net::ERR_[A-Z0-9_]+|HTTP (?:status )?\d{3}|response code: \d{3}/)?.[0] ?? 'WebSocket transport error';
        });
        socket.on('framereceived', ({ payload }) => {
            const { event, channel, data } = JSON.parse(payload.toString());
            if (event === 'pusher:connection_established') state.socketId = JSON.parse(data).socket_id;
            if (event === 'pusher_internal:subscription_succeeded') state.channel = channel;
            if (event === 'pusher:error') state.error = 'Pusher protocol error ' + Number(JSON.parse(data).code);
        });
    });
    page.on('response', response => {
        if (new URL(response.url()).pathname === '/broadcasting/auth' && !response.ok()) {
            state.error = 'Broadcast authorization HTTP ' + response.status();
        }
    });
    return state;
}

async function subscribed(state, channel, member) {
    await expect.poll(() => state.channel || state.error, { timeout: 20_000, message: member + ' private subscription' }).toBe(channel);
}

test('delivers a private chat message to a second context without a reload and denies a foreign subscription', async ({ page, browser, baseURL }, testInfo) => {
    test.skip(!liveBroadcast, 'Set E2E_BROADCAST_CONNECTION=pusher with local Pusher credentials to exercise live delivery.');
    await withChatPair({ page, browser, baseURL }, 'پیام زنده آزمون دو نشست', async (reader, text, started) => {
        const navigations = [];
        reader.on('framenavigated', frame => {
            if (frame === reader.mainFrame()) navigations.push(frame);
        });
        await expect(reader.getByText(text, { exact: true })).toBeVisible({ timeout: 20_000 });
        const delay = Math.round(performance.now() - started);
        expect(navigations).toHaveLength(0);
        const evidence = testInfo.outputPath('live-chat-delivery.png');
        await reader.screenshot({ path: evidence });
        await testInfo.attach('Live chat delivery', { path: evidence, contentType: 'image/png' });
        console.log('Live chat visible in approximately ' + delay + ' ms without a reload. Foreign channel authorization returned HTTP 403.');
    }, async (reader, senderSocket, readerSocket) => {
        const channel = 'private-group.' + new URL(reader.url()).pathname.split('/').pop();
        await Promise.all([subscribed(senderSocket, channel, 'Sender'), subscribed(readerSocket, channel, 'Reader')]);
        const outsider = await browser.newContext({ baseURL });
        try {
            const foreign = await outsider.newPage();
            await login(foreign, 'other-resident');
            const foreignSocket = observeChatSocket(foreign);
            const url = await openBuilding(foreign, 'مجتمع کاج');
            await subscribed(foreignSocket, 'private-group.' + new URL(url).pathname.split('/').pop(), 'Foreign member');
            const denied = await foreign.request.post('/broadcasting/auth', {
                form: { channel_name: channel, socket_id: foreignSocket.socketId },
                headers: { 'X-CSRF-TOKEN': await foreign.locator('meta[name=csrf-token]').getAttribute('content') },
            });
            expect(denied.status()).toBe(403);
        } finally {
            await outsider.close();
        }
    });
});
