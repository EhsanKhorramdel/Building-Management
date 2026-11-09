import { test, expect } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';

const mode = process.env.VISUAL_STAGE || 'after';
const baseURL = process.env.VISUAL_BASE_URL || 'http://127.0.0.1:8155';
const output = 'storage/app/test-screenshots/phase-5/' + mode;
const tabs = {
    home: 'home', announcements: 'notification', poll: 'poll', costs: 'cost',
    charges: 'charge', payments: 'paymentInfo', chat: 'chat', settings: 'setting',
};

async function signIn(context, email, path) {
    const page = await context.newPage();
    await page.goto('/login');
    await page.locator('[name=email]').fill(email);
    await page.locator('[name=password]').fill(process.env.DEMO_PASSWORD);
    await page.locator('form button[type=submit], form input[type=submit]').first().click();
    await page.waitForURL(url => url.pathname === path);
    await page.close();
}

for (const width of [360, 390, 768, 1024, 1440]) {
    test(`${mode} at ${width}px`, async ({ browser }) => {
        await mkdir(output, { recursive: true });
        const contexts = {};
        const guard = [];
        const options = { baseURL, viewport: { width, height: width < 768 ? 820 : 900 } };
        for (const type of ['guest', 'manager', 'admin']) contexts[type] = await browser.newContext(options);
        await signIn(contexts.manager, process.env.DEMO_MANAGER_EMAIL, '/dashboard');
        await signIn(contexts.admin, process.env.DEMO_ADMIN_EMAIL, '/adminPanel');
        const manager = await contexts.manager.newPage();
        await manager.goto('/dashboard');
        const building = await manager.locator('a[href*="/dashboard/building/"]').first().getAttribute('href');
        await manager.close();

        async function capture(context, theme, name, url, tab) {
            await context.addCookies([{
                name: 'userSettings', value: encodeURIComponent(JSON.stringify({ darkMode: theme === 'dark', viewMode: 'gridView' })),
                url: baseURL,
            }]);
            const page = await context.newPage();
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
            const response = await page.goto(url);
            expect(response.status()).toBe(200);
            if (tab && tab !== 'home') {
                if (mode === 'before') {
                    await page.locator('#' + tab).evaluate(element => element.click());
                    await page.waitForTimeout(220);
                } else {
                    if (width < 768) await page.locator('[data-open-drawer]').click();
                    await page.locator('#' + tab).click();
                    await expect(page.locator('.' + tab + '-window')).toBeVisible();
                    if (tab === 'paymentInfo' || tab === 'chat') {
                        await expect(page.locator('[data-loading]:visible')).toHaveCount(0);
                    }
                    if (tab === 'paymentInfo') await expect(page.locator('.payments-table tbody tr').first()).toBeVisible();
                }
            }
            await page.evaluate(() => document.fonts.ready);
            await page.evaluate(() => Promise.all([...document.images].filter(image => image.getClientRects().length).map(image => image.decode().catch(() => {}))));
            await page.screenshot({ path: `${output}/${name}-${width}-${theme}.png`, animations: 'disabled' });
            const metrics = await page.evaluate(() => ({
                scrollWidth: document.documentElement.scrollWidth, width: innerWidth,
                dark: document.documentElement.classList.contains('dark'),
                viewport: Boolean(document.querySelector('meta[name=viewport]')),
                unlabelled: [...document.querySelectorAll('input:not([type=hidden], [type=submit]), textarea, select')]
                    .filter(control => !control.labels?.length && !control.hasAttribute('aria-label') && !control.hasAttribute('aria-labelledby'))
                    .map(control => control.name || control.id),
                brokenImages: [...document.images].filter(image => image.getClientRects().length && image.complete && !image.naturalWidth).map(image => image.getAttribute('src')),
            }));
            guard.push({ name, width, theme, ...metrics, errors });
            await page.close();
        }

        try {
            for (const theme of mode === 'before' ? ['light'] : ['light', 'dark']) {
                await capture(contexts.guest, theme, 'main', '/');
                await capture(contexts.guest, theme, 'login', '/login');
                await capture(contexts.guest, theme, 'register', '/register');
                await capture(contexts.manager, theme, 'dashboard', '/dashboard');
                for (const [name, tab] of Object.entries(tabs)) {
                    await capture(contexts.manager, theme, 'building-' + name, building, tab);
                }
                await capture(contexts.admin, theme, 'admin', '/adminPanel');
            }
            if (mode === 'before') await capture(contexts.manager, 'dark', 'dashboard', '/dashboard');
        } finally {
            await writeFile(`${output}/guard-${width}.json`, JSON.stringify(guard, null, 2));
            await Promise.all(Object.values(contexts).map(context => context.close()));
        }
        if (mode === 'after') {
            expect(guard).toHaveLength(26);
            for (const { name, theme, scrollWidth, dark, errors, brokenImages, viewport, unlabelled } of guard) {
                const screen = `${name} ${width} ${theme}`;
                expect.soft(scrollWidth, screen + ' horizontal overflow').toBeLessThanOrEqual(width);
                expect.soft(errors, screen + ' console errors').toEqual([]);
                expect.soft(brokenImages, screen + ' broken images').toEqual([]);
                expect.soft(dark, screen + ' theme').toBe(theme === 'dark');
                expect.soft(viewport, screen + ' viewport metadata').toBe(true);
                expect.soft(unlabelled, screen + ' unlabelled controls').toEqual([]);
            }
        }
    });
}
