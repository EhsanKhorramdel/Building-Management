import { expect, test } from '@playwright/test';
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
