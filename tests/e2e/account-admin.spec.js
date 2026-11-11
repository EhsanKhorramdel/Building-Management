import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';
import { seed } from './global-setup.js';
import { login, openBuilding, readyForCapture, submit, tab, toast, uploadImage } from './ui.js';

test.beforeEach(() => seed('manual'));
test.afterAll(() => seed('manual'));

for (const width of [390, 1440]) {
    test('avatar preferences audit detail and calendar at ' + width, async ({ browser, baseURL }) => {
        const options = { baseURL, viewport: { width, height: 844 }, hasTouch: width === 390 };
        const managerContext = await browser.newContext(options);
        const adminContext = await browser.newContext(options);
        const page = await managerContext.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        const directory = 'storage/app/test-screenshots/phase-11g';
        await mkdir(directory, { recursive: true });
        const capture = async (subject, state) => {
            await readyForCapture(subject);
            expect(await subject.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
            await subject.screenshot({ path: directory + '/' + state + '-' + width + '.png', animations: 'disabled' });
        };
        try {
            await login(page, 'manager');
            await tab(page, 'setting');
            const upload = page.locator('form[action$="/dashboard/avatar"][enctype]');
            await upload.locator('[name=avatar]').setInputFiles(uploadImage);
            await submit(upload);
            await toast(page);
            const avatar = page.locator('[data-panel=setting] .user-avatar img');
            await expect.poll(() => avatar.evaluate(image => image.naturalWidth)).toBe(192);
            await capture(page, 'avatar');

            const form = page.locator('form[action$="/dashboard/notifications"]');
            const announcement = form.locator('input[type=checkbox][name="announcement[in_app]"]');
            const sms = form.locator('input[type=checkbox][name="announcement[sms]"]');
            await expect(announcement).toBeChecked();
            await announcement.uncheck();
            await submit(form);
            await toast(page);
            await expect(announcement).not.toBeChecked();
            await expect(sms).toBeChecked();
            await capture(page, 'notification-preferences');

            await tab(page, 'home');
            const building = await openBuilding(page);
            await Promise.all([
                page.waitForResponse(response => response.url().endsWith('/preferences') && response.status() === 204),
                page.getByRole('button', { name: 'نمایش فهرستی' }).click(),
            ]);
            await page.reload();
            await expect(page.locator('[data-panel=home] [data-layout]')).toHaveClass(/\bjsListView\b/);
            const resident = page.locator('[data-panel=home] .building-box').filter({ hasText: 'ساکن سرو' });
            await resident.getByRole('button', { name: 'ارتقاء به مدیر' }).click();
            await toast(page);
            await tab(page, 'setting');
            const invite = page.locator('form[action$="/join-link"]');
            await invite.locator('[name=lifecycle]').selectOption('single');
            await submit(invite);
            await expect(page.locator('[data-join-link-state]')).toContainText('تعداد استفاده باقی‌مانده: 1');
            await page.getByRole('link', { name: 'جزئیات رویدادهای ساختمان' }).click();
            const roleChange = page.locator('tr:has([data-audit-event="role.upgraded"])');
            await expect(roleChange).toContainText('ساکن سرو');
            await expect(roleChange).toContainText('ساکن ← مدیر');
            await expect(page.locator('tr:has([data-audit-event="join_link.rotated"])')).toContainText('قابل استفاده تا تعویض ← یک‌بار مصرف');
            await capture(page, 'manager-audit');

            const admin = await adminContext.newPage();
            await login(admin, 'admin');
            await admin.getByRole('link', { name: 'جزئیات و فیلتر رویدادها' }).click();
            await admin.locator('select[name=event]').selectOption('role.upgraded');
            await admin.getByRole('button', { name: 'اعمال فیلتر' }).click();
            await expect(admin.locator('[data-audit-events] tbody tr')).toHaveCount(1);
            await expect(admin.locator('[data-audit-events] tbody tr')).toContainText('ساکن سرو');
            await capture(admin, 'admin-audit');

            await page.goto(building + '?tab=home');
            await page.getByRole('link', { name: 'تقویم ساختمان' }).click();
            await expect(page.getByRole('heading', { name: 'تقویم ساختمان مجتمع سرو' })).toBeVisible();
            await expect(page.locator('a[href*="tab=charge"]').first()).toBeVisible();
            await expect(page.locator('a[href*="tab=cost"]').first()).toBeVisible();
            await capture(page, 'calendar');
            await Promise.all([
                page.waitForResponse(response => response.url().endsWith('/preferences') && response.status() === 204),
                page.locator('a[href*="tab=charge"]').first().click(),
            ]);
            await page.goto(building);
            await expect(page.locator('.buildings-section[data-panel=charge]')).toBeVisible();
            expect(errors).toEqual([]);
        } finally {
            await Promise.all([managerContext.close(), adminContext.close()]);
        }
    });
}
