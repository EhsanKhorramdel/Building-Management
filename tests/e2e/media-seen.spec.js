import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdir, readFile } from 'node:fs/promises';
import { environments, liveBroadcast, php } from './environment.js';
import { seed } from './global-setup.js';
import { login, openBuilding, submitMessage, tab, theme, uploadImage } from './ui.js';

test.beforeEach(({}, info) => {
    const { app, env } = environments.manual;
    const seeder = info.title.startsWith('receipt audience') ? 'E2ESeeder' : 'E2EMediaSeeder';
    execFileSync(php, ['artisan', 'migrate:fresh', '--seed', '--seeder=' + seeder, '--force'], {
        cwd: app, env: { ...process.env, ...env }, stdio: 'pipe',
    });
});
test.afterAll(() => seed('manual'));

async function capture(page, name) {
    const directory = 'storage/app/test-screenshots/phase-11d';
    await mkdir(directory, { recursive: true });
    await page.screenshot({ path: directory + '/' + name + '.png', animations: 'disabled' });
}

for (const width of [390, 1440]) for (const dark of [false, true]) {
    test('authorized shared gallery at ' + width + '-' + (dark ? 'dark' : 'light'), async ({ browser, baseURL }) => {
        test.setTimeout(180_000);
        const context = await browser.newContext({ baseURL, viewport: { width, height: 844 }, hasTouch: width === 390 });
        const page = await context.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        try {
            await theme(context, baseURL, dark);
            await login(page, 'manager');
            await openBuilding(page);
            await tab(page, 'chat');
            await page.locator('[data-latest]').click();
            await expect(page.locator('[data-latest]')).toBeEnabled();
            const row = page.locator('.chat-message').filter({ has: page.getByText('تصویر گالری 24', { exact: true }) });
            const opener = row.locator('.chat-image-open');
            await opener.focus();
            await page.keyboard.press('Enter');
            const dialog = page.locator('.media-viewer');
            const photo = dialog.locator('img');
            await expect(dialog).toBeVisible();
            await expect(photo).toBeVisible();
            const original = await photo.getAttribute('src');
            let pages = 0;
            page.on('response', response => { if (response.url().includes('/chat-media?')) pages++; });
            const initialId = Number(original.split('/').at(-1));
            for (let offset = 1; offset <= 11; offset++) {
                await dialog.locator('[data-media-previous]').click();
                await expect(photo).toHaveAttribute('src', '/chat/attachments/' + (initialId - offset));
            }
            expect(pages).toBeGreaterThanOrEqual(2);
            for (let offset = 10; offset >= 0; offset--) {
                await page.keyboard.press('ArrowRight');
                await expect(photo).toHaveAttribute('src', '/chat/attachments/' + (initialId - offset));
            }
            await page.keyboard.press('+');
            await expect(photo).toHaveCSS('transform', /matrix\(1\.5,/);
            const stage = dialog.locator('.media-stage');
            const area = await stage.boundingBox();
            await page.mouse.move(area.x + area.width / 2, area.y + area.height / 2);
            await page.mouse.down();
            await page.mouse.move(area.x + area.width / 2 + 25, area.y + area.height / 2 + 20);
            await page.mouse.up();
            await expect(photo).toHaveCSS('transform', 'matrix(1.5, 0, 0, 1.5, 25, 20)');
            await page.keyboard.press('-');
            await expect(photo).toHaveCSS('transform', 'matrix(1, 0, 0, 1, 0, 0)');
            await page.mouse.wheel(0, -100);
            await expect(photo).toHaveCSS('transform', /matrix\(1\.25,/);
            await page.mouse.wheel(0, 100);
            await expect(photo).toHaveCSS('transform', 'matrix(1, 0, 0, 1, 0, 0)');
            if (width === 390) {
                const client = await context.newCDPSession(page);
                const center = { x: area.x + area.width / 2, y: area.y + area.height / 2 };
                await client.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [{ x: center.x - 30, y: center.y }, { x: center.x + 30, y: center.y }] });
                await client.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x: center.x - 60, y: center.y }, { x: center.x + 60, y: center.y }] });
                await client.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
                await expect.poll(() => photo.evaluate(image => new DOMMatrix(getComputedStyle(image).transform).a)).toBeGreaterThan(1.5);
                await page.keyboard.press('-');
                await page.keyboard.press('-');
                await expect(photo).toHaveCSS('transform', 'matrix(1, 0, 0, 1, 0, 0)');
            }
            const [download] = await Promise.all([page.waitForEvent('download'), dialog.locator('[data-media-download]').click()]);
            expect(new URL(download.url()).pathname).toBe(original);
            expect(await download.failure()).toBeNull();
            expect(await readFile(await download.path())).toEqual(await readFile(uploadImage));
            await capture(page, 'gallery-' + width + '-' + (dark ? 'dark' : 'light'));
            await dialog.locator('[data-media-close]').focus();
            await page.keyboard.press('Shift+Tab');
            expect(await dialog.evaluate(element => element.contains(document.activeElement))).toBe(true);
            await page.keyboard.press('Escape');
            await expect(dialog).not.toBeVisible();
            await expect(opener).toBeFocused();
            if (width === 390) {
                await opener.click();
                const client = await context.newCDPSession(page);
                const area = await stage.boundingBox();
                const point = { x: area.x + area.width / 2, y: area.y + area.height / 2 };
                await client.send('Input.dispatchTouchEvent', { type: 'touchStart', touchPoints: [point] });
                await client.send('Input.dispatchTouchEvent', { type: 'touchMove', touchPoints: [{ x: point.x, y: point.y + 110 }] });
                await client.send('Input.dispatchTouchEvent', { type: 'touchEnd', touchPoints: [] });
                await expect(dialog).not.toBeVisible();
                await expect(opener).toBeFocused();
            }

            await tab(page, 'notification');
            const announcement = page.locator('[data-announcement-image]').first();
            await announcement.click();
            await expect(dialog).toBeVisible();
            await expect(photo).toHaveAttribute('src', /\/announcements\/\d+\/attachment$/);
            await expect(dialog.locator('[data-media-next]')).toBeDisabled();
            await expect(photo).toBeVisible();
            await capture(page, 'announcement-' + width + '-' + (dark ? 'dark' : 'light'));
            await page.keyboard.press('Escape');
            await expect(announcement).toBeFocused();
            await tab(page, 'cost');
            await page.locator('[data-invoice]').first().click();
            await expect(photo).toHaveAttribute('src', /\/incidental-costs\/\d+\/attachment$/);
            await expect(photo).toBeVisible();
            await capture(page, 'invoice-' + width + '-' + (dark ? 'dark' : 'light'));
            await dialog.locator('[data-media-close]').click();
            let pendingImage;
            await page.route('**/incidental-costs/*/attachment', route => { pendingImage = route; });
            await page.locator('[data-invoice]').first().click();
            await expect(dialog.locator('[data-media-state]')).toHaveText('در حال دریافت تصویر…');
            await expect.poll(() => Boolean(pendingImage)).toBe(true);
            await pendingImage.fulfill({ status: 403, body: '' });
            await expect(dialog.locator('[data-media-state]')).toHaveText('تصویر در دسترس نیست. دوباره باز کنید.');
            await expect(photo).toBeHidden();
            await page.keyboard.press('Escape');
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
            expect(errors).toEqual([]);
        } finally { await context.close(); }
    });
}

test('receipt audience stays scoped and live counts reach the author and manager when configured', async ({ browser, baseURL }) => {
    test.setTimeout(180_000);
    const contexts = [];
    const counts = [];
    async function member(account) {
        const context = await browser.newContext({ baseURL });
        contexts.push(context);
        const page = await context.newPage();
        let subscribed = false;
        if (liveBroadcast) page.on('websocket', socket => socket.on('framereceived', ({ payload }) => {
            const frame = JSON.parse(payload.toString());
            if (frame.event === 'pusher_internal:subscription_succeeded') subscribed = true;
            if (frame.event === 'ChatSeenCountChanged') counts.push(typeof frame.data === 'string' ? JSON.parse(frame.data) : frame.data);
        }));
        await login(page, account);
        await openBuilding(page);
        await tab(page, 'chat');
        await page.locator('[data-latest]').click();
        await expect(page.locator('[data-latest]')).toBeEnabled({ timeout: 45_000 });
        if (liveBroadcast) await expect.poll(() => subscribed, { timeout: 20_000 }).toBe(true);
        return page;
    }
    try {
        const author = await member('resident');
        const text = 'پیام رسید آزمون';
        await author.locator('#message-text').fill(text);
        await submitMessage(author);
        const message = author.locator('.chat-message').filter({ has: author.getByText(text, { exact: true }) });
        await expect(message).toBeVisible();
        const id = await message.getAttribute('data-message-id');
        await expect(message.locator('.chat-seen-count')).toBeHidden();
        await expect(author.locator('.chat-message:not(.own-message) .chat-seen-count')).toHaveCount(0);
        const manager = await member('manager');
        if (!liveBroadcast) { await author.reload(); await tab(author, 'chat'); }
        const count = author.locator('[data-seen-message="' + id + '"]');
        await expect(count).toContainText('۱', { timeout: 20_000 });
        await expect(manager.locator('[data-seen-message="' + id + '"]')).toBeVisible();
        await capture(author, liveBroadcast ? 'seen-live-author' : 'seen-author');
        const neighbor = await member('neighbor');
        await expect(neighbor.locator('#message-' + id + ' .chat-seen-count')).toHaveCount(0);
        if (liveBroadcast) {
            await expect(count).toContainText('۲', { timeout: 20_000 });
            await expect(manager.locator('[data-seen-message="' + id + '"]')).toContainText('۲');
            expect(counts.length).toBeGreaterThan(0);
            for (const payload of counts) expect(Object.keys(payload).sort()).toEqual(['count', 'message_id']);
            await capture(manager, 'seen-live-manager');
        }
    } finally { await Promise.all(contexts.map(context => context.close())); }
});
