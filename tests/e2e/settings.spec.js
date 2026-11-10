import { expect, test } from '@playwright/test';
import { fields, login, openBuilding, submit, tab, toast } from './ui.js';

test('an older profile form can omit national code and phone without clearing them', async ({ page }) => {
    await login(page, 'neighbor');
    await tab(page, 'setting');
    const form = page.locator('form[action$="/dashboard/update"]');
    await expect(form.locator('[name=ncode]')).toHaveValue('0012345678');
    await expect(form.locator('[name=phoneNumber]')).toHaveValue('09123456789');
    await fields(form, { name: 'همسایه به‌روزشده سرو' });
    await form.locator('[name=ncode], [name=phoneNumber]').evaluateAll(inputs => inputs.forEach(input => { input.disabled = true; }));
    await submit(form);
    await toast(page);
    await expect(form.locator('[name=name]')).toHaveValue('همسایه به‌روزشده سرو');
    await expect(form.locator('[name=ncode]')).toHaveValue('0012345678');
    await expect(form.locator('[name=phoneNumber]')).toHaveValue('09123456789');
});

test('theme preference survives reload and navigation between dashboard and building', async ({ page }) => {
    await login(page, 'manager');
    const toggle = page.locator('.mode-switch').first();
    await expect(toggle).toHaveAttribute('aria-pressed', 'false');
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-pressed', 'true');
    await page.reload();
    await expect(toggle).toHaveAttribute('aria-pressed', 'true');
    await openBuilding(page);
    await expect(toggle).toHaveAttribute('aria-pressed', 'true');
    await expect(page.locator('html')).toHaveClass(/\bdark\b/);
});

test('role upgrade appears in the complex audit list and the global admin list', async ({ page, browser, baseURL }) => {
    const adminContext = await browser.newContext({ baseURL });
    try {
        await login(page, 'manager');
        await openBuilding(page);
        const resident = page.locator('.home-window .building-box').filter({ hasText: 'ساکن سرو' });
        await resident.getByRole('button', { name: 'ارتقاء به مدیر' }).click();
        await toast(page);
        await expect(resident.locator('.box-address-header')).toHaveText('مدیر');
        await tab(page, 'setting');
        const row = page.locator('[data-audit-events] tr').filter({ hasText: 'ارتقا به مدیر' });
        await expect(row).toContainText('مدیر سرو');
        await expect(row).toContainText('مجتمع سرو');

        const admin = await adminContext.newPage();
        await login(admin, 'admin');
        await expect(admin.locator('[data-audit-events] tr').filter({ hasText: 'ارتقا به مدیر' })).toContainText('مجتمع سرو');
    } finally {
        await adminContext.close();
    }
});
