import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { seed } from './global-setup.js';
import { login, openBuilding, tab, theme, submit, toast } from './ui.js';

test.beforeEach(() => seed('manual'));
test.afterAll(() => seed('manual'));

async function capture(page, state, width, dark) {
    const directory = 'storage/app/test-screenshots/phase-11e';
    await mkdir(directory, { recursive: true });
    await page.screenshot({ path: directory + '/' + state + '-' + width + '-' + (dark ? 'dark' : 'light') + '.png', animations: 'disabled' });
}

for (const width of [390, 1440]) for (const dark of [false, true]) {
    test('pin archive read close and named results at ' + width + '-' + (dark ? 'dark' : 'light'), async ({ browser, baseURL }) => {
        test.setTimeout(240_000);
        const context = await browser.newContext({ baseURL, viewport: { width, height: 844 } });
        const residentContext = await browser.newContext({ baseURL, viewport: { width, height: 844 } });
        const page = await context.newPage();
        const resident = await residentContext.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        resident.on('pageerror', error => errors.push(error.message));
        try {
            await theme(context, baseURL, dark);
            await theme(residentContext, baseURL, dark);
            await login(page, 'manager');
            await openBuilding(page);
            await tab(page, 'notification');
            const title = 'جلسه هفتگی مجتمع سرو';
            const card = () => page.locator('[data-announcement-id]').filter({ hasText: title });
            await card().getByRole('button', { name: 'سنجاق کردن', exact: true }).click();
            await toast(page);
            await expect(card().locator('.building-box')).toHaveClass(/announcement-pinned/);
            await capture(page, 'pinned', width, dark);
            await card().getByRole('button', { name: 'لغو اعلان', exact: true }).click();
            await page.locator('[data-confirm-dialog] [value=confirm]').click();
            await toast(page);
            await expect(card()).toHaveCount(0);
            const search = page.locator('[data-announcement-search]');
            await search.locator('[name=state]').selectOption('archived');
            await search.locator('[name=q]').fill('مجتمع سرو');
            await submit(search);
            await expect(card().getByRole('button', { name: 'بازگرداندن اعلان' })).toBeVisible();
            await capture(page, 'archive', width, dark);
            await login(resident, 'resident');
            await openBuilding(resident);
            await tab(resident, 'notification');
            await expect(resident.locator('[data-announcement-id]')).toHaveCount(0);
            await expect(resident.locator('[data-announcement-search]')).toHaveCount(0);
            await card().getByRole('button', { name: 'بازگرداندن اعلان' }).click();
            await toast(page);
            await expect(card()).toBeVisible();
            let mark;
            await resident.route('**/announcements/*/read', route => { mark = route; });
            await resident.reload();
            await tab(resident, 'notification');
            const notice = resident.locator('[data-announcement-id]').filter({ hasText: title });
            await expect(notice.locator('[data-announcement-unread]')).toBeVisible();
            await capture(resident, 'unread', width, dark);
            await expect.poll(() => Boolean(mark)).toBe(true);
            await mark.continue();
            await resident.unroute('**/announcements/*/read');
            await expect(notice.locator('[data-announcement-unread]')).toHaveCount(0);
            await card().locator('[data-announcement-readers]').click();
            await expect(page.locator('[data-announcement-readers-dialog]')).toContainText('ساکن سرو');
            await capture(page, 'readers', width, dark);
            await page.keyboard.press('Escape');

            await tab(page, 'poll');
            const poll = () => page.locator('.poll-window .building-box').filter({ hasText: 'نظرسنجی باز' });
            await poll().locator('[data-edit-poll]').click();
            const edit = page.locator('[data-poll-edit-form]');
            await expect(edit.locator('[name=end_date]')).not.toHaveValue('');
            const deadline = await edit.locator('[name=end_date]').inputValue();
            await edit.locator('[name=question]').fill('زمان جلسه بعدی؟');
            await edit.locator('[name=end_date]').fill('2000-01-01T00:00');
            await submit(edit);
            await expect(page.locator('[data-poll-edit-dialog]')).toBeVisible();
            await expect(edit.locator('.field-error')).toContainText('زمان پایان');
            await expect(edit.locator('[name=question]')).toHaveValue('زمان جلسه بعدی؟');
            await edit.locator('[name=end_date]').fill(deadline);
            await submit(edit);
            await toast(page);
            await expect(poll()).toContainText('زمان جلسه بعدی؟');
            await tab(resident, 'poll');
            const residentPoll = resident.locator('.poll-window .building-box').filter({ hasText: 'نظرسنجی باز' });
            await residentPoll.locator('.open-Btn-vote').click();
            await resident.locator('.modal-vote').getByRole('radio').first().check();
            await submit(resident.locator('.modal-vote'));
            await toast(resident);
            await residentPoll.locator('.open-Btn-vote').click();
            await expect(resident.locator('.modal-vote').getByRole('radio').first()).toBeChecked();
            await resident.keyboard.press('Escape');
            await page.reload();
            await tab(page, 'poll');
            await expect(poll().locator('[data-edit-poll]')).toHaveCount(0);
            await expect(poll()).toContainText('پس از ثبت اولین رای');
            await poll().locator('.open-Btn-vote-result').click();
            await expect(page.locator('[data-named-ballots-list]')).toBeEmpty();
            await page.keyboard.press('Escape');
            await poll().getByRole('button', { name: 'بستن رای گیری', exact: true }).click();
            await expect(page.locator('[data-confirm-dialog]')).toContainText('برای همیشه');
            await page.locator('[data-confirm-dialog] [value=confirm]').click();
            await toast(page);
            await expect(poll()).toContainText('رای گیری بسته شده است');
            await expect(poll().locator('.open-Btn-vote')).toHaveCount(0);
            await poll().locator('.open-Btn-vote-result').click();
            await expect(page.locator('[data-named-ballots-list]')).toContainText('ساکن سرو');
            await expect(page.locator('#voteChart')).toBeVisible();
            await capture(page, 'closed-ballots', width, dark);
            if (width === 390) {
                await page.setViewportSize({ width, height: 390 });
                await page.locator('.modal-vote-result > div').evaluate(panel => { panel.scrollTop = panel.scrollHeight; });
                const close = page.getByRole('button', { name: 'بستن نتیجه', exact: true });
                await expect(close).toBeInViewport({ ratio: 1 });
                await close.click();
                await expect(page.locator('.modal-vote-result')).not.toBeVisible();
            }
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
            expect(errors).toEqual([]);
        } finally { await Promise.all([context.close(), residentContext.close()]); }
    });
}
