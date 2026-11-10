import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { login, openBuilding, submitMessage, tab, uploadImage } from './ui.js';

test.beforeEach(async ({ page }) => {
    await login(page, 'manager');
    await openBuilding(page);
    await tab(page, 'chat');
    const latest = page.getByRole('button', { name: 'رفتن به آخرین پیام' });
    await latest.click();
    await expect(latest).toBeEnabled();
    await expect(page.getByText('پیام نمونه 36', { exact: true })).toBeVisible();
});

test('sends, edits and confirms deletion of a chat message', async ({ page }) => {
    const input = page.locator('#message-text');
    await input.fill('پیام آزمون');
    await submitMessage(page);
    const original = page.locator('.chat-message').filter({ hasText: 'پیام آزمون' });
    await expect(original).toBeVisible({ timeout: 20_000 });
    await original.locator('summary').click();
    await original.getByRole('button', { name: 'ویرایش', exact: true }).click();
    await input.fill('پیام اصلاح‌شده آزمون');
    await submitMessage(page, 'edit');
    const edited = page.locator('.chat-message').filter({ hasText: 'پیام اصلاح‌شده آزمون' });
    await expect(edited).toBeVisible({ timeout: 20_000 });
    await expect(original).toHaveCount(0);
    const row = page.locator('#' + await edited.getAttribute('id'));
    await edited.locator('summary').click();
    await edited.getByRole('button', { name: 'حذف', exact: true }).click();
    await expect(page.locator('[data-confirm-dialog]')).toBeVisible();
    await page.locator('[data-confirm-dialog] button[value=confirm]').click();
    await expect(edited).toHaveCount(0);
    await expect(row).toHaveText('این پیام حذف شد');
});

test('pages backward and jumps to the latest visible message', async ({ page }) => {
    const messages = page.locator('.chat-message');
    const count = await messages.count();
    const newest = await messages.last().getAttribute('id');
    const older = page.locator('[data-older]');
    await older.click();
    await expect.poll(() => messages.count()).toBeGreaterThan(count);
    const latest = page.getByRole('button', { name: 'رفتن به آخرین پیام' });
    await latest.click();
    await expect(latest).toBeEnabled();
    await expect(messages).toHaveCount(count);
    await expect(messages.last()).toHaveAttribute('id', newest);
    await expect.poll(() => page.locator('.chatContainer').evaluate(box => box.scrollHeight - box.clientHeight - box.scrollTop)).toBeLessThanOrEqual(1);
    await expect(older).toBeVisible();
    await expect(page.locator('[data-newer]')).toBeVisible();
});

test('shows a chat upload to members and denies the attachment URL to outsiders', async ({ page, browser, baseURL }) => {
    const foreignContext = await browser.newContext({ baseURL });
    const guestContext = await browser.newContext({ baseURL });
    try {
        const seedImage = page.locator('.chat-message').filter({ hasText: 'پیام نمونه 35' }).locator('img');
        await expect.poll(() => seedImage.evaluate(image => image.naturalWidth)).toBe(192);
        await page.locator('#message-text').fill('تصویر پیوست آزمون');
        await page.locator('[name=messageImage]').setInputFiles(uploadImage);
        await submitMessage(page);
        const upload = page.locator('.chat-message').filter({ hasText: 'تصویر پیوست آزمون' }).locator('img');
        await expect(upload).toBeVisible();
        await expect.poll(() => upload.evaluate(image => image.naturalWidth)).toBe(192);
        const url = await upload.evaluate(image => image.src);

        const foreign = await foreignContext.newPage();
        await login(foreign, 'other-resident');
        const forbidden = await foreign.goto(url);
        expect(forbidden.status()).toBe(403);
        expect(forbidden.headers()['content-type']).not.toMatch(/^image\//);

        const guest = await guestContext.newPage();
        await guest.goto(url);
        await expect(guest).toHaveURL(/\/login$/);
        await expect(guest.locator('[name=email]')).toBeVisible();
    } finally {
        await Promise.all([foreignContext.close(), guestContext.close()]);
    }
});

async function captureInteraction(page, name) {
    if (process.env.E2E_CAPTURE_11A !== '1') return;
    const directory = 'storage/app/test-screenshots/phase-11a';
    await mkdir(directory, { recursive: true });
    await page.screenshot({ path: directory + '/' + name + '.png', animations: 'disabled' });
}

async function latestOwnMessage(page, text) {
    await page.locator('#message-text').fill(text);
    await submitMessage(page);
    const message = page.locator('.own-message').filter({ hasText: text });
    await expect(message).toBeVisible();
    return message;
}

for (const width of [390, 1440]) {
    for (const dark of [false, true]) {
        const state = width + '-' + (dark ? 'dark' : 'light');
        test('all newest-message actions are reachable at ' + state, async ({ page, context }) => {
            await page.setViewportSize({ width, height: width === 390 ? 844 : 900 });
            if (dark) await page.locator('.mode-switch').click();
            await context.grantPermissions(['clipboard-read', 'clipboard-write']);
            const text = 'پیام منوی آزمون ' + state;
            const message = await latestOwnMessage(page, text);
            const trigger = message.locator('summary');
            const actions = ['کپی', 'ویرایش', 'حذف', 'مشاهده کنندگان', 'واکنش', 'پاسخ', 'سنجاق کردن', 'انتخاب'];
            const menu = message.locator('.chat-menu-actions');
            await trigger.click();
            await expect(menu.getByRole('button')).toHaveCount(actions.length);
            for (const name of actions) {
                const action = menu.getByRole('button', { name, exact: true });
                await expect(action).toBeInViewport({ ratio: 1 });
                expect(await action.evaluate(button => {
                    const rect = button.getBoundingClientRect();
                    return button.contains(document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2));
                })).toBe(true);
            }
            await captureInteraction(page, 'F11-01-newest-menu-' + state);

            await message.getByRole('button', { name: actions[0], exact: true }).click();
            await expect.poll(() => page.evaluate(() => navigator.clipboard.readText())).toBe(text);
            await trigger.click();
            await message.getByRole('button', { name: actions[1], exact: true }).click();
            await expect(page.locator('#message-text')).toHaveValue(text);
            await page.locator('[data-chat-edit] button').click();
            await trigger.click();
            await message.getByRole('button', { name: actions[2], exact: true }).click();
            const confirm = page.locator('[data-confirm-dialog]');
            await expect(confirm).toBeVisible();
            await confirm.locator('[value=cancel]').click();
            await expect(message).toBeVisible();
            await trigger.click();
            await message.getByRole('button', { name: actions[3], exact: true }).click();
            await expect(page.locator('.modal-message-viewer')).toBeVisible();
            await page.keyboard.press('Escape');
            await trigger.click();
            await menu.getByRole('button', { name: actions[4], exact: true }).click();
            await expect(page.locator('[data-chat-social-dialog]')).toBeVisible();
            await page.keyboard.press('Escape');
            await trigger.click();
            await menu.getByRole('button', { name: actions[5], exact: true }).click();
            await expect(page.locator('[data-chat-reply]')).toContainText(text);
            await page.getByRole('button', { name: 'لغو پاسخ' }).click();
            await trigger.click();
            await menu.getByRole('button', { name: actions[6], exact: true }).click();
            await expect(page.locator('[data-chat-pins]')).toContainText(text);
            await trigger.click();
            await menu.getByRole('button', { name: 'برداشتن سنجاق', exact: true }).click();
            await expect(page.locator('[data-chat-pins]')).toBeHidden();
            await trigger.click();
            await menu.getByRole('button', { name: actions[7], exact: true }).click();
            await expect(message.getByRole('checkbox')).toBeChecked();
            await page.locator('[data-selection-cancel]').click();
            await expect(page.locator('[data-chat-selection]')).toBeHidden();
        });
    }
}

test('only one chat menu opens and Escape and outside clicks return focus', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    const message = await latestOwnMessage(page, 'پیام بستن منو');
    const newest = message.locator('summary');
    const earlier = page.locator('.chat-message').filter({ hasText: 'پیام نمونه 36' }).locator('summary');
    await newest.click();
    await expect(newest.locator('..')).toHaveAttribute('open', '');
    await earlier.click();
    await expect(page.locator('.chat-menu[open]')).toHaveCount(1);
    await expect(earlier.locator('..')).toHaveAttribute('open', '');
    await page.keyboard.press('Escape');
    await expect(page.locator('.chat-menu[open]')).toHaveCount(0);
    await expect(earlier).toBeFocused();
    await newest.click();
    await page.locator('.chat-window > .buildings-section-header').click();
    await expect(page.locator('.chat-menu[open]')).toHaveCount(0);
    await expect(newest).toBeFocused();
    await captureInteraction(page, 'F11-02-dismissed-menu-390');
});

test('the dashboard drawer keeps keyboard focus inside and restores it on close', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/dashboard');
    const opener = page.locator('[data-open-drawer]');
    const drawer = page.locator('[data-drawer]');
    const close = drawer.locator('[data-close-drawer]');
    const logout = drawer.locator('button[type=submit]');
    await opener.click();
    await expect(close).toBeFocused();
    await expect(page.locator('.app-header')).toHaveAttribute('inert', '');
    await expect(page.locator('.home-window')).toHaveAttribute('inert', '');
    await page.keyboard.press('Shift+Tab');
    await expect(logout).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(close).toBeFocused();
    for (let step = 0; step < 10; step++) {
        await page.keyboard.press('Tab');
        await expect(drawer.locator(':focus')).toHaveCount(1);
    }
    await captureInteraction(page, 'F11-03-drawer-focus-390');
    await page.keyboard.press('Escape');
    await expect(opener).toBeFocused();
    await expect(page.locator('.home-window')).not.toHaveAttribute('inert');
});

test('phone landscape keeps the chat heading, thread and composer in view together', async ({ page }) => {
    await page.setViewportSize({ width: 844, height: 390 });
    const heading = page.locator('.chat-window > .buildings-section-header');
    const thread = page.locator('.chatContainer');
    const composer = page.locator('#messageForm');
    const paging = page.locator('.chat-shell > .chat-heading').first();
    await page.evaluate(() => window.scrollTo(0, 0));
    await expect(page.locator('[data-open-drawer]')).toBeVisible();
    for (const element of [heading, paging, thread, composer]) {
        await expect(element).toBeInViewport({ ratio: 1 });
    }
    expect((await thread.boundingBox()).height).toBeGreaterThanOrEqual(100);
    await page.locator('#message-text').focus();
    await expect(heading).toBeInViewport({ ratio: 1 });
    await expect(page.locator('.sendBtn')).toBeInViewport({ ratio: 1 });
    await captureInteraction(page, 'F11-04-chat-landscape-light');
    await page.locator('.mode-switch').click();
    await captureInteraction(page, 'F11-04-chat-landscape-dark');
});

test('a tall dialog keeps Close available after scrolling to Submit', async ({ page }) => {
    await page.setViewportSize({ width: 844, height: 390 });
    await page.locator('[data-open-drawer]').click();
    await page.locator('[data-tab=charge]').click();
    const launch = page.locator('.register-btn');
    const dialog = page.locator('.register-section[data-panel=charge]');
    const close = dialog.locator('.register-close');
    await launch.click();
    const panel = dialog.locator('.dialog-panel');
    await panel.evaluate(element => { element.scrollTop = element.scrollHeight; });
    await expect.poll(() => panel.evaluate(element => element.scrollTop)).toBeGreaterThan(0);
    await expect(dialog.locator('button[type=submit]')).toBeInViewport({ ratio: 1 });
    await expect(close).toBeInViewport({ ratio: 1 });
    await captureInteraction(page, 'F11-05-dialog-scrolled-landscape');
    await close.click();
    await expect(launch).toBeFocused();
    await launch.click();
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(launch).toBeFocused();
});

test('viewer names are available to the author and complex manager only', async ({ page, browser, baseURL }) => {
    const contexts = await Promise.all(['resident', 'neighbor', 'other-manager'].map(async account => {
        const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 } });
        return { account, context, session: await context.newPage() };
    }));
    const [author, neighbor, foreign] = contexts;
    try {
        for (const { account, session } of contexts) await login(session, account);
        await openBuilding(author.session);
        await tab(author.session, 'chat');
        const text = 'پیام خصوصی رسید مشاهده';
        const message = await latestOwnMessage(author.session, text);
        const messageId = Number(await message.getAttribute('data-message-id'));
        await openBuilding(neighbor.session);
        const seen = neighbor.session.waitForResponse(response =>
            new URL(response.url()).pathname.endsWith('/group/seen')
            && Number(response.request().postDataJSON()?.messageId) === messageId);
        await tab(neighbor.session, 'chat');
        const received = neighbor.session.getByText(text, { exact: true });
        await received.scrollIntoViewIfNeeded();
        expect((await seen).ok()).toBe(true);

        async function viewers(session) {
            return session.request.post('/dashboard/building/getMessageViewers', {
                data: { message_id: messageId },
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': await session.locator('meta[name=csrf-token]').getAttribute('content'),
                },
            });
        }

        for (const session of [author.session, page]) {
            const response = await viewers(session);
            expect(response.status()).toBe(200);
            expect(await response.json()).toEqual([
                expect.objectContaining({ user_name: 'همسایه سرو' }),
            ]);
        }
        expect((await viewers(neighbor.session)).status()).toBe(403);
        expect((await viewers(foreign.session)).status()).toBe(403);
        await message.locator('summary').click();
        await message.getByRole('button', { name: 'مشاهده کنندگان' }).click();
        await expect(author.session.locator('.modal-message-viewer')).toContainText('همسایه سرو');
        await captureInteraction(author.session, 'F11-07-author-viewers-390');
        const otherMessage = received.locator('xpath=ancestor::article');
        await otherMessage.locator('summary').click();
        const otherMenu = otherMessage.locator('.chat-menu-actions');
        await expect(otherMenu.getByRole('button')).toHaveText(['کپی', 'واکنش', 'پاسخ', 'انتخاب']);
        await expect(otherMenu.getByRole('button', { name: /ویرایش|حذف|سنجاق|مشاهده کنندگان/ })).toHaveCount(0);
        await captureInteraction(neighbor.session, 'F11-07-non-author-menu-390');
    } finally {
        await Promise.all(contexts.map(({ context }) => context.close()));
    }
});
