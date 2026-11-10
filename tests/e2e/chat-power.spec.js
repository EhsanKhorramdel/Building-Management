import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { environments, liveBroadcast } from './environment.js';
import { seed } from './global-setup.js';
import { login, openBuilding, submitMessage, tab, theme } from './ui.js';

test.beforeEach(() => seed('manual'));
test.afterAll(() => seed('manual'));

async function choose(row, name) {
    await row.locator('summary').click();
    await row.locator('.chat-menu-actions').getByRole('button', { name, exact: true }).click();
}

async function mutation(page, path, action) {
    const [response] = await Promise.all([
        page.waitForResponse(response => new URL(response.url()).pathname === path, { timeout: 45_000 }),
        action(),
    ]);
    expect(response.status()).toBe(200);
    return response.json();
}

async function capture(page, name, state) {
    if (process.env.E2E_CAPTURE_11C !== '1') return;
    const path = 'storage/app/test-screenshots/phase-11c';
    await mkdir(path, { recursive: true });
    await page.screenshot({ path: path + '/' + name + '-' + state + '.png', animations: 'disabled' });
}

function subscription(page) {
    let ready = false;
    let error = null;
    let typingSent = 0;
    let typingReceived = 0;
    page.on('websocket', socket => {
        if (!new URL(socket.url()).hostname.endsWith('.pusher.com')) return;
        socket.on('framesent', ({ payload }) => {
            if (JSON.parse(payload.toString()).event === 'client-typing') typingSent++;
        });
        socket.on('framereceived', ({ payload }) => {
            const frame = JSON.parse(payload.toString());
            if (frame.event === 'client-typing') typingReceived++;
            if (frame.event === 'pusher_internal:subscription_succeeded') ready = true;
            if (frame.event === 'pusher:error') {
                const data = typeof frame.data === 'string' ? JSON.parse(frame.data) : frame.data;
                let message = String(data.message ?? '');
                for (const key of ['PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER']) {
                    const value = environments.manual.env[key];
                    if (value) message = message.replaceAll(value, '[redacted]');
                }
                error = 'Pusher protocol error ' + Number(data.code) + ': ' + message;
            }
        });
    });
    return { ready: () => ready, error: () => error, stats: () => ({ typingSent, typingReceived, error }) };
}

for (const width of [390, 1440]) for (const dark of [false, true]) {
    const state = width + '-' + (dark ? 'dark' : 'light');
    test('moderate, bulk delete and search jump at ' + state, async ({ browser, baseURL }) => {
        test.setTimeout(240_000);
        const context = await browser.newContext({
            baseURL, viewport: { width, height: width === 390 ? 844 : 900 }, hasTouch: width === 390,
        });
        let readerContext;
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        try {
            await theme(context, baseURL, dark);
            const sender = liveBroadcast ? subscription(page) : null;
            await login(page, 'manager');
            await openBuilding(page);
            await tab(page, 'chat');
            await page.locator('[data-latest]').click();
            await expect(page.locator('[data-latest]')).toBeEnabled();
            const input = page.locator('#message-text');
            if (liveBroadcast) {
                readerContext = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 } });
                const reader = await readerContext.newPage();
                const receiver = subscription(reader);
                await login(reader, 'resident');
                await openBuilding(reader);
                await tab(reader, 'chat');
                await expect.poll(sender.ready, { timeout: 20_000 }).toBe(true);
                await expect.poll(receiver.ready, { timeout: 20_000 }).toBe(true);
                await input.fill('پیش‌نویس زنده');
                try {
                    await expect(reader.locator('[data-chat-typing]')).toContainText('مدیر سرو', { timeout: 12_000 });
                } catch (error) {
                    console.log('Typing transport ' + JSON.stringify({ sender: sender.stats(), receiver: receiver.stats() }));
                    throw error;
                }
                expect(sender.error()).toBeNull();
                await capture(reader, 'typing-live', state);
                await input.fill('');
                await expect(reader.locator('[data-chat-typing]')).toBeHidden({ timeout: 3000 });
                await input.fill('پیش‌نویس بعدی');
                await expect(reader.locator('[data-chat-typing]')).toBeVisible({ timeout: 12_000 });
                await expect(reader.locator('[data-chat-typing]')).toBeHidden({ timeout: 6500 });
                await input.fill('');
            } else {
                await input.fill('پیش‌نویس');
                await expect(page.locator('[data-chat-typing]')).toBeHidden();
                await input.fill('');
            }
            const victim = page.locator('.chat-message').filter({ hasText: 'پیام نمونه 36' });
            const id = await victim.getAttribute('id');
            await choose(victim, 'حذف توسط مدیر');
            const reason = page.locator('[data-chat-moderation]');
            const submit = reason.locator('[type=submit]');
            await submit.click();
            expect(await reason.locator('textarea').evaluate(input => input.validity.valueMissing)).toBe(true);
            await reason.locator('textarea').fill('پیام خارج از موضوع ساختمان');
            await mutation(page, '/dashboard/building/group/' + id.slice(8), () => submit.click());
            await expect(page.locator('#' + id)).toHaveText('توسط مدیر حذف شد');
            await capture(page, 'moderation', state);

            const draft = 'پیام انتخابی ' + state;
            await input.fill(draft);
            await submitMessage(page);
            const own = page.locator('.chat-message').filter({ hasText: draft });
            await expect(own).toBeVisible();
            if (width === 390) {
                const target = own.locator('.message-text');
                const bounds = await target.boundingBox();
                const client = await context.newCDPSession(page);
                await client.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: bounds.x + bounds.width / 2, y: bounds.y + bounds.height / 2 }] });
                await page.waitForTimeout(700);
                await client.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
                await client.detach();
            } else await choose(own, 'انتخاب');
            await expect(page.locator('[data-chat-selection]')).toBeVisible();
            await expect(own.getByRole('checkbox')).toBeChecked();
            for (const number of ['32', '34']) {
                await page.locator('.chat-message').filter({ hasText: 'پیام نمونه ' + number }).getByRole('checkbox').check();
            }
            await expect(page.locator('[data-selection-count]')).toContainText('۳');
            await capture(page, 'selection', state);
            await page.locator('[data-selection-delete]').click();
            await expect(reason).toBeVisible();
            await reason.locator('textarea').fill('حذف گروهی پیام‌های خارج از موضوع');
            const complex = new URL(page.url()).pathname.split('/').pop();
            const result = await mutation(page, '/dashboard/building/' + complex + '/chat-bulk-delete', () => submit.click());
            expect(result.success_count).toBe(3);
            expect(result.denied_count).toBe(0);
            for (const deleted of result.results) await expect(page.locator('#message-' + deleted.message_id)).toContainText('حذف شد');
            await expect(page.locator('[data-chat-selection]')).toBeHidden();
            await expect(page.locator('.toast').filter({ hasText: '۳ پیام حذف شد، ۰ پیام رد شد.' })).toBeVisible();
            await capture(page, 'bulk-result', state);

            await page.locator('[data-search-toggle]').click();
            const form = page.locator('[data-chat-search-form]');
            for (const control of await form.locator('input, button').all()) {
                await expect(control).toBeInViewport({ ratio: 1 });
                expect(await control.evaluate(element => {
                    const bounds = element.getBoundingClientRect();
                    return element.contains(document.elementFromPoint(bounds.x + bounds.width / 2, bounds.y + bounds.height / 2));
                })).toBe(true);
            }
            await form.locator('input').fill('پيام نمونه ۰۱');
            await form.locator('[type=submit]').click();
            const match = page.locator('[data-search-results] button').filter({ hasText: 'پیام نمونه 01' });
            await expect(match).toBeVisible();
            await expect(match.locator('time')).toHaveText(/^\d{4}\/\d{2}\/\d{2}/);
            await capture(page, 'search', state);
            await match.click();
            const jumped = page.locator('.chat-message').filter({ hasText: 'پیام نمونه 01' });
            await expect(jumped).toBeFocused();
            await expect(jumped).toHaveClass(/chat-message-highlight/);
            await capture(page, 'search-jump', state);
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
            expect(errors).toEqual([]);
        } finally {
            await readerContext?.close();
            await context.close();
        }
    });
}
