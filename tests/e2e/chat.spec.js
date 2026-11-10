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
    await edited.locator('summary').click();
    await edited.getByRole('button', { name: 'حذف', exact: true }).click();
    await expect(page.locator('[data-confirm-dialog]')).toBeVisible();
    await page.locator('[data-confirm-dialog] button[value=confirm]').click();
    await expect(edited).toHaveCount(0);
});

test('pages backward and jumps to the latest visible message', async ({ page }) => {
    const messages = page.locator('.chat-message');
    const count = await messages.count();
    const newest = await messages.last().locator('.message-text').textContent();
    const older = page.locator('[data-older]');
    await older.click();
    await expect.poll(() => messages.count()).toBeGreaterThan(count);
    const latest = page.getByRole('button', { name: 'رفتن به آخرین پیام' });
    await latest.click();
    await expect(latest).toBeEnabled();
    await expect(messages).toHaveCount(count);
    await expect(messages.last().locator('.message-text')).toHaveText(newest);
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
            const actions = ['کپی', 'ویرایش', 'حذف', 'مشاهده کنندگان'];
            await trigger.click();
            await expect(message.getByRole('button')).toHaveCount(4);
            for (const name of actions) {
                const action = message.getByRole('button', { name, exact: true });
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
        });
    }
}
