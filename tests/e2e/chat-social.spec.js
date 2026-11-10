import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { liveBroadcast } from './environment.js';
import { seed } from './global-setup.js';
import { login, openBuilding, submitMessage, tab, theme } from './ui.js';

const names = { reaction: 'واکنش', reply: 'پاسخ', pin: 'سنجاق کردن', delete: 'حذف' };

test.afterAll(() => seed('manual'));

function watchSubscription(page) {
    let subscribed = false;
    page.on('websocket', socket => {
        if (!new URL(socket.url()).hostname.endsWith('.pusher.com')) return;
        socket.on('framereceived', ({ payload }) => {
            const { event } = JSON.parse(payload.toString());
            if (event === 'pusher_internal:subscription_succeeded') subscribed = true;
        });
    });
    return () => subscribed;
}

async function latest(page) {
    const button = page.locator('[data-latest]');
    await button.click();
    await expect(button).toBeEnabled();
}

async function findOlder(page, target) {
    for (let step = 0; step < 4 && !await target.count(); step++) {
        const button = page.locator('[data-older]');
        await button.click();
        await expect(button).toBeEnabled();
    }
    await expect(target).toBeVisible();
}

async function choose(row, name) {
    await row.locator('summary').click();
    await row.locator('.chat-menu-actions').getByRole('button', { name, exact: true }).click();
}

async function mutate(page, path, action) {
    const [response] = await Promise.all([
        page.waitForResponse(result => new URL(result.url()).pathname === path, { timeout: 45_000 }),
        action(),
    ]);
    expect(response.status(), path).toBe(200);
}

async function capture(page, name, state) {
    if (process.env.E2E_CAPTURE_11B !== '1') return;
    const directory = 'storage/app/test-screenshots/phase-11b';
    await mkdir(directory, { recursive: true });
    await page.screenshot({ path: directory + '/' + name + '-' + state + '.png', animations: 'disabled' });
}

for (const [width, dark, originalNumber] of [[390, false, '01'], [390, true, '03'], [1440, false, '05'], [1440, true, '07']]) {
    const state = width + '-' + (dark ? 'dark' : 'light');
    test('react, reply, pin and delete at ' + state, async ({ browser, baseURL, page, context }) => {
        if (liveBroadcast) test.setTimeout(240_000);
        const viewport = { width, height: width === 390 ? 844 : 900 };
        await page.setViewportSize(viewport);
        await theme(context, baseURL, dark);
        const originalText = 'پیام نمونه ' + originalNumber;
        const replyText = 'پاسخ به پیام ' + originalNumber;
        const original = page.locator('.chat-message').filter({ hasText: originalText });
        let readerContext;
        try {
            let reader;
            const readerReads = [];
            const subscribed = liveBroadcast ? watchSubscription(page) : null;
            await login(page, 'manager');
            await openBuilding(page);
            await tab(page, 'chat');
            await latest(page);
            await findOlder(page, original);
            const id = await original.getAttribute('id');
            const messageUrl = '/dashboard/building/group/' + id.slice('message-'.length);
            const row = page.locator('#' + id);
            const picker = page.locator('[data-chat-social-dialog]');
            const pins = page.locator('[data-chat-pins]');
            if (liveBroadcast) {
                readerContext = await browser.newContext({ baseURL, viewport });
                reader = await readerContext.newPage();
                const readerSubscribed = watchSubscription(reader);
                await login(reader, 'resident');
                await openBuilding(reader);
                await tab(reader, 'chat');
                await latest(reader);
                await findOlder(reader, reader.locator('#' + id));
                await expect.poll(subscribed, { timeout: 20_000 }).toBe(true);
                await expect.poll(readerSubscribed, { timeout: 20_000 }).toBe(true);
                reader.on('request', request => {
                    const path = new URL(request.url()).pathname;
                    if (request.method() === 'GET' && (/\/group\/\d+$|\/chat-pins$/.test(path))) readerReads.push(path);
                });
            }

            const quick = row.locator('[data-quick-reaction]');
            await quick.click();
            const palette = picker.locator('.chat-reaction-picker button');
            await expect(palette).toHaveCount(7);
            for (const control of await palette.all()) {
                const bounds = await control.boundingBox();
                expect(bounds.width).toBeGreaterThanOrEqual(40);
                expect(bounds.height).toBeGreaterThanOrEqual(40);
            }
            await mutate(page, messageUrl + '/reactions', () => picker.getByRole('button', { name: 'واکنش 👍', exact: true }).click());
            const thumbs = row.locator('[data-reaction="👍"]');
            await expect(thumbs).toHaveAttribute('aria-pressed', 'true');
            await expect(quick).toBeFocused();
            if (reader) {
                await expect(reader.locator('#' + id + ' [data-reaction="👍"]')).toHaveAttribute('aria-pressed', 'false', { timeout: 20_000 });
            }
            await choose(row, names.reaction);
            await mutate(page, messageUrl + '/reactions', () => picker.getByRole('button', { name: 'واکنش 🎉', exact: true }).click());
            await expect(row.locator('[data-reaction="🎉"]')).toHaveAttribute('aria-pressed', 'true');
            if (reader) {
                await expect(reader.locator('#' + id + ' .chat-reaction')).toHaveCount(2, { timeout: 20_000 });
                expect(readerReads).toEqual([]);
            }
            await thumbs.click();
            await expect(picker).toContainText('مدیر سرو');
            await capture(page, 'reactions', state);
            await page.keyboard.press('Escape');

            if (reader) {
                await latest(reader);
                readerReads.length = 0;
            }
            await choose(row, names.reply);
            const preview = page.locator('[data-chat-reply]');
            await expect(preview).toContainText(originalText);
            await expect(page.locator('#message-text')).toBeFocused();
            await page.locator('#message-text').fill(replyText);
            await submitMessage(page);
            await latest(page);
            const reply = page.locator('.chat-message').filter({ has: page.getByText(replyText, { exact: true }) });
            const quote = reply.locator('.chat-quote');
            await expect(quote).toContainText(originalText);
            await capture(page, 'quote', state);
            if (reader) await expect(reader.locator('.chat-quote').filter({ hasText: originalText })).toBeVisible({ timeout: 20_000 });
            await expect(row).toHaveCount(0);
            await quote.click();
            await expect(row).toBeFocused();

            await mutate(page, messageUrl + '/pin', () => choose(row, names.pin));
            const pinned = pins.getByRole('button').filter({ hasText: originalText });
            await expect(pinned).toBeVisible();
            await capture(page, 'pins', state);
            if (reader) await expect(reader.locator('[data-chat-pins]').getByText(originalText, { exact: true })).toBeVisible({ timeout: 20_000 });
            await latest(page);
            await pinned.click();
            await expect(row).toBeFocused();

            await choose(row, names.delete);
            await mutate(page, messageUrl, () => page.locator('[data-confirm-dialog] [value=confirm]').click());
            await expect(row).toHaveText('این پیام حذف شد');
            await expect(row.locator('img, .chat-quote, [data-reaction]')).toHaveCount(0);
            await expect(pinned).toHaveCount(0);
            await capture(page, 'tombstone', state);
            if (reader) {
                await expect(reader.locator('[data-chat-pins]').getByText(originalText, { exact: true })).toHaveCount(0, { timeout: 20_000 });
                await expect(reader.locator('.chat-quote').filter({ hasText: 'این پیام حذف شد' })).toBeVisible({ timeout: 20_000 });
                expect(readerReads).toEqual([]);
            }
            await latest(page);
            await expect(quote).toHaveText('این پیام حذف شد');
            await quote.click();
            await expect(row).toBeFocused();
            await expect(row).toHaveText('این پیام حذف شد');
        } finally {
            await readerContext?.close();
        }
    });
}
