import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { login, openBuilding, submitMessage, tab } from './ui.js';

const ownActions = ['کپی', 'ویرایش', 'حذف', 'مشاهده کنندگان', 'واکنش', 'پاسخ', 'سنجاق کردن', 'انتخاب'];
const openMenus = '.chat-menu-actions:popover-open';

async function openThread(page, account = 'manager') {
    await login(page, account);
    await openBuilding(page);
    await tab(page, 'chat');
    const latest = page.locator('[data-latest]');
    await latest.click();
    await expect(latest).toBeEnabled();
    await expect(page.getByText('پیام نمونه 36', { exact: true })).toBeVisible();
}

function message(page, text) {
    return page.locator('.chat-message').filter({ has: page.getByText(text, { exact: true }) });
}

async function send(page, text) {
    await page.locator('#message-text').fill(text);
    await submitMessage(page);
    const row = message(page, text);
    await expect(row).toBeVisible();
    return row;
}

async function assertMenu(row, names) {
    const menu = row.locator(openMenus);
    await expect(menu.getByRole('button')).toHaveCount(names.length);
    for (const name of names) {
        const action = menu.getByRole('button', { name, exact: true });
        await expect(action).toBeInViewport({ ratio: 1 });
        await action.click({ trial: true });
    }
    return menu;
}

async function capture(page, name) {
    const directory = 'storage/app/test-screenshots/phase-11h';
    await mkdir(directory, { recursive: true });
    await page.screenshot({ path: directory + '/' + name + '.png', animations: 'disabled' });
}

async function center(locator) {
    await locator.scrollIntoViewIfNeeded();
    const { x, y, width, height } = await locator.boundingBox();
    return { x: Math.round(x + width / 2), y: Math.round(y + height / 2) };
}

test('right-click opens the newest and middle bubble menus at 1440px', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await openThread(page);
    const newest = await send(page, 'پیام آزمون راست کلیک');
    const middle = message(page, 'پیام نمونه 31');
    const selected = message(page, 'پیام نمونه 32').locator('.message-text');
    const composer = page.locator('#message-text');
    await page.evaluate(() => {
        window.contextEvents = [];
        document.addEventListener('contextmenu', event => {
            window.contextEvents.push({ prevented: event.defaultPrevented, trusted: event.isTrusted });
        });
    });

    await composer.fill('متن انتخابی پیش‌نویس');
    await composer.evaluate(input => input.setSelectionRange(0, 8));
    await newest.locator('.message-text').click({ button: 'right' });
    await expect(composer).toBeFocused();
    await expect.poll(() => composer.evaluate(input => [input.selectionStart, input.selectionEnd])).toEqual([0, 8]);
    await assertMenu(newest, ownActions);
    await capture(page, 'right-click-newest-1440');
    await page.keyboard.press('Escape');
    await expect(page.locator(openMenus)).toHaveCount(0);
    await expect(newest.locator('summary')).toBeFocused();

    await selected.evaluate(element => getSelection().selectAllChildren(element));
    await middle.locator('.message-text').click({ button: 'right' });
    expect(await page.evaluate(() => getSelection().toString())).toBe('پیام نمونه 32');
    await assertMenu(middle, ownActions);
    await capture(page, 'right-click-middle-1440');
    await page.locator('.app-header').click({ button: 'right' });
    await expect(page.locator(openMenus)).toHaveCount(0);
    expect(await page.evaluate(() => window.contextEvents)).toEqual([
        { prevented: true, trusted: true },
        { prevented: true, trusted: true },
        { prevented: false, trusted: true },
    ]);
});

test('touch hold opens the action menu at 390px and tap or scrolling leaves it closed', async ({ browser, baseURL }) => {
    const context = await browser.newContext({ baseURL, viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
    const page = await context.newPage();
    const client = await context.newCDPSession(page);
    const touch = (type, point) => client.send('Input.dispatchTouchEvent', { type, touchPoints: point ? [point] : [] });
    try {
        await openThread(page);
        const row = await send(page, 'پیام آزمون نگه داشتن');
        const target = row.locator('.message-text');
        await target.tap();
        await expect(page.locator(openMenus)).toHaveCount(0);
        await expect(row.locator('.chat-bubble-holding')).toHaveCount(0);

        const thread = page.locator('.chatContainer');
        const from = await center(target);
        const scrollTop = await thread.evaluate(element => element.scrollTop);
        await touch('touchStart', from);
        await touch('touchMove', { x: from.x, y: from.y + 30 });
        await touch('touchMove', { x: from.x, y: from.y + 90 });
        await expect(row.locator('.chat-bubble-holding')).toHaveCount(0);
        await page.waitForTimeout(550);
        await touch('touchEnd');
        await expect.poll(() => thread.evaluate(element => element.scrollTop)).toBeLessThan(scrollTop);
        await expect(page.locator(openMenus)).toHaveCount(0);

        await touch('touchStart', await center(target));
        await expect(row.locator('.chat-bubble-holding')).toHaveCount(1);
        const menu = row.locator(openMenus);
        await expect(menu).toBeVisible();
        await touch('touchEnd');
        await assertMenu(row, ownActions);
        await expect(page.locator('[data-chat-selection]')).toBeHidden();
        await capture(page, 'long-press-390');
        await menu.getByRole('button', { name: 'انتخاب', exact: true }).tap();
        await expect(page.locator(openMenus)).toHaveCount(0);
        await expect(row.getByRole('checkbox')).toBeChecked();
    } finally {
        await client.detach();
        await context.close();
    }
});

test('gesture and keyboard openers share one resident menu with the same role actions', async ({ page }) => {
    await openThread(page, 'resident');
    const own = message(page, 'پیام نمونه 36');
    const other = message(page, 'پیام نمونه 35');
    await own.locator('summary').focus();
    await page.keyboard.press('Enter');
    await assertMenu(own, ['کپی', 'ویرایش', 'حذف', 'مشاهده کنندگان', 'واکنش', 'پاسخ', 'انتخاب']);
    await other.locator('.message-text').click({ button: 'right' });
    await expect(page.locator(openMenus)).toHaveCount(1);
    await assertMenu(other, ['کپی', 'واکنش', 'پاسخ', 'انتخاب']);
    await page.locator('.chat-window > .buildings-section-header').click();
    await expect(page.locator(openMenus)).toHaveCount(0);
    await expect(other.locator('summary')).toBeFocused();
});
