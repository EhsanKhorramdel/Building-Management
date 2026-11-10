import { expect, test } from '@playwright/test';
import { login, openBuilding, submitMessage, tab } from './ui.js';

async function withChatPair({ page, browser, baseURL }, text, verify) {
    const readerContext = await browser.newContext({ baseURL });
    try {
        const reader = await readerContext.newPage();
        await login(page, 'manager');
        await login(reader, 'resident');
        await openBuilding(page);
        await openBuilding(reader);
        await tab(page, 'chat');
        await tab(reader, 'chat');
        await expect(reader.getByText('پیام نمونه 36', { exact: true })).toBeVisible();
        await expect(reader.getByText(text, { exact: true })).toHaveCount(0);
        await page.locator('#message-text').fill(text);
        await submitMessage(page);
        await expect(page.getByText(text, { exact: true })).toBeVisible({ timeout: 20_000 });
        await verify(reader, text);
    } finally {
        await readerContext.close();
    }
}

test('with the log broadcaster, a second signed-in context sees the message after reload', async ({ page, browser, baseURL }) => {
    await withChatPair({ page, browser, baseURL }, 'پیام آزمون دو نشست', async (reader, text) => {
        await reader.reload();
        await tab(reader, 'chat');
        await expect(reader.getByText(text, { exact: true })).toBeVisible({ timeout: 20_000 });
    });
});

// Enable this case when the test environment has a real broadcasting app.
test.fixme('delivers a private chat message to a second context without a reload', async ({ page, browser, baseURL }) => {
    await withChatPair({ page, browser, baseURL }, 'پیام زنده آزمون دو نشست', async (reader, text) => {
        await expect(reader.getByText(text, { exact: true })).toBeVisible();
    });
});
