import { expect } from '@playwright/test';
import { fileURLToPath } from 'node:url';

export const sarv = 'مجتمع سرو';
export const uploadImage = fileURLToPath(new URL('../../public/assets/images/app-icon-192.png', import.meta.url));
const password = 'password';

export async function fields(form, values) {
    for (const [name, value] of Object.entries(values)) {
        await form.locator(`[name="${name}"]`).fill(value);
    }
}

export async function submit(form) {
    await form.locator('button[type=submit]').first().click();
}

export async function submitMessage(page, action = 'send') {
    await Promise.all([
        page.waitForResponse(response => new URL(response.url()).pathname === '/dashboard/building/group/' + action, { timeout: 45_000 }),
        page.locator('.sendBtn').click(),
    ]);
}

export async function login(page, account, base = '') {
    await page.goto(base + '/login');
    const form = page.locator('form').filter({ has: page.locator('[name=password]') });
    await fields(form, { email: account + '@example.test', password });
    await submit(form);
    await expect(page).toHaveURL(account === 'admin' ? /\/adminPanel$/ : /\/dashboard$/);
}

export async function register(page, account, name) {
    await page.goto('/register');
    const form = page.locator('form').filter({ has: page.locator('[name=password_confirmation]') });
    await fields(form, { name, email: account + '@example.test', password, password_confirmation: password });
    await submit(form);
    await expect(page).toHaveURL(/\/dashboard$/);
}

export function dashboardCard(page, name = sarv) {
    return page.locator('[data-complex-id]').filter({ hasText: name });
}

export async function openBuilding(page, name = sarv) {
    const link = dashboardCard(page, name).locator('a[href*="/dashboard/building/"]');
    await expect(link).toBeVisible();
    await link.click();
    await expect(page).toHaveURL(/\/dashboard\/building\/\d+/);
    return page.url();
}

export async function tab(page, name) {
    await page.waitForLoadState('domcontentloaded');
    if (page.viewportSize().width < 768) {
        const opener = page.locator('[data-open-drawer]');
        await opener.click();
        await expect(opener).toHaveAttribute('aria-expanded', 'true');
    }
    await page.locator(`[data-tab="${name}"]`).click();
    await expect(page.locator(`.buildings-section[data-panel="${name}"]`)).toBeVisible();
}

export function toast(page, status = 'success') {
    return expect(page.locator(`.toast[data-status="${status}"]`).first()).toBeVisible();
}

export async function formInPanel(page, name) {
    await tab(page, name);
    await page.locator('.register-btn').click();
    const form = page.locator(`.register-section[data-panel="${name}"] form`);
    await expect(form).toBeVisible();
    return form;
}

export async function theme(context, base, darkMode) {
    await context.addCookies([{
        url: base,
        name: 'userSettings',
        value: encodeURIComponent(JSON.stringify({ darkMode, viewMode: 'gridView' })),
    }]);
}

export async function readyForCapture(page) {
    await page.evaluate(() => document.fonts.ready);
    await page.waitForFunction(() => [...document.images].filter(image => image.getClientRects().length).every(image => image.complete), null, { timeout: 8_000 });
}
