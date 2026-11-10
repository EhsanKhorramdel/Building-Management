import { expect, test } from '@playwright/test';
import { dashboardCard, fields, login, register, submit } from './ui.js';

for (const role of ['admin', 'manager', 'resident']) {
    test(`${role} signs in and sees the role landing page`, async ({ page }) => {
        await login(page, role);
        if (role === 'admin') {
            await expect(page.getByRole('heading', { name: 'پنل ادمین' })).toBeVisible();
        } else {
            await expect(dashboardCard(page)).toBeVisible();
        }
    });
}

test('wrong password shows the sign-in error', async ({ page }) => {
    await page.goto('/login');
    const form = page.locator('form').filter({ has: page.locator('[name=password]') });
    await fields(form, { email: 'resident@example.test', password: 'incorrect password' });
    const [response] = await Promise.all([
        page.waitForResponse(response => response.request().method() === 'POST' && new URL(response.url()).pathname === '/login'),
        submit(form),
    ]);
    expect(response.status()).toBe(302);
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('.field-error').first()).toBeVisible();
});

test('logout lands on the public home page and protects the dashboard', async ({ page, baseURL }) => {
    await login(page, 'admin');
    await submit(page.locator('form[action$="/logout"]'));
    await expect(page).toHaveURL(new URL('/', baseURL).href);
    await expect(page.getByRole('link', { name: 'ورود', exact: true })).toBeVisible();
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator('[name=email]')).toBeVisible();
});

test('the signed-out user can sign in again from the public home page', async ({ page, baseURL }) => {
    await login(page, 'admin');
    await submit(page.locator('form[action$="/logout"]'));
    await expect(page).toHaveURL(new URL('/', baseURL).href);
    await page.getByRole('link', { name: 'ورود', exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
    const form = page.locator('form').filter({ has: page.locator('[name=password]') });
    await fields(form, { email: 'admin@example.test', password: 'password' });
    await submit(form);
    await expect(page).toHaveURL(/\/adminPanel$/);
    await expect(page.getByRole('heading', { name: 'پنل ادمین' })).toBeVisible();
});

test('register signs in the new account on its dashboard', async ({ page }) => {
    await register(page, 'new-account', 'کاربر تازه');
    await expect(page.getByText('کاربر تازه', { exact: false }).first()).toBeVisible();
});
