import { expect, test } from '@playwright/test';
import { mkdir, writeFile } from 'node:fs/promises';
import { createRequire } from 'node:module';
import { smallTouchTargets } from '../Browser/touch-targets.js';
import { environments } from './environment.js';
import { seed } from './global-setup.js';
import { dashboardCard, fields, login, openBuilding, readyForCapture, submit, tab, theme } from './ui.js';

const output = 'storage/app/test-screenshots/phase-9/sweep';
const axePath = createRequire(import.meta.url).resolve('axe-core/axe.min.js');
const tabs = {
    home: 'home', announcements: 'notification', polls: 'poll', costs: 'cost', charges: 'charge',
    payments: 'paymentInfo', chat: 'chat', 'building-settings': 'setting',
};
const pages = {
    main: '/', login: '/login', register: '/register', 'forgot-password': '/forgot-password',
    'reset-password': '/reset-password/e2e-placeholder?email=resident%40example.test',
};
let checkout;

test.beforeAll(async ({ browser }) => {
    test.setTimeout(240_000);
    seed('manual');
    seed('online');
    await mkdir(output, { recursive: true });
    const context = await browser.newContext({ baseURL: environments.online.url });
    try {
        const page = await context.newPage();
        await login(page, 'manager');
        await openBuilding(page);
        await tab(page, 'cost');
        await page.locator('.cost-pay-open-Btn').click();
        const dialog = page.locator('.cost-pay-modal');
        await fields(dialog, { unitNumber: '1' });
        await submit(dialog);
        await expect(page.locator('[data-mock-outcome=pay]')).toBeVisible();
        checkout = new URL(page.url()).pathname;
    } finally {
        await context.close();
    }
});

async function capture(context, state, width, dark) {
    const page = await context.newPage();
    const errors = [];
    const screenshot = `${state.name}-${width}-${dark ? 'dark' : 'light'}.png`;
    const result = { name: state.name, width, dark, screenshot, errors };
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => {
        if (message.type() === 'error') errors.push(message.text());
    });

    try {
        const response = await page.goto(state.path, { waitUntil: 'domcontentloaded' });
        result.status = response.status();
        result.pathname = new URL(page.url()).pathname;
        if (state.panel) await tab(page, state.panel);
        if (state.admin) await page.locator(`[data-admin-tab="${state.admin}"]`).click();
        if (state.ready) await expect(page.locator(state.ready).first()).toBeVisible();
        if (state.scroll) await page.locator(state.scroll).scrollIntoViewIfNeeded();
        await readyForCapture(page);
        Object.assign(result, await page.evaluate(() => ({
            scrollWidth: document.scrollingElement.scrollWidth,
            actualDark: document.documentElement.classList.contains('dark'),
            brokenImages: [...document.images].filter(image => image.getClientRects().length && !image.naturalWidth).map(image => image.getAttribute('src')),
        })));
        result.smallTargets = width <= 768 ? await page.evaluate(smallTouchTargets) : [];
        if (width === 1440 && !dark) {
            await page.addScriptTag({ path: axePath });
            result.axe = await page.evaluate(async () => {
                const scan = await window.axe.run({ runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'] } });
                return scan.violations.map(({ id, impact, nodes }) => ({ id, impact, targets: nodes.map(node => node.target) }));
            });
        }
    } catch (error) {
        result.captureError = error.message;
    } finally {
        try {
            await page.screenshot({ path: output + '/' + screenshot, animations: 'disabled', timeout: 8_000 });
        } catch (error) {
            result.screenshotError = error.message;
        } finally {
            await page.close();
        }
    }

    return result;
}

for (const width of [360, 390, 768, 1024, 1440]) {
    for (const dark of [false, true]) {
        test(`25 states at ${width}px in ${dark ? 'dark' : 'light'} theme`, async ({ browser }) => {
            test.setTimeout(240_000);
            const guards = [];
            const contexts = {};
            try {
                for (const [role, account] of Object.entries({ guest: null, manager: 'manager', admin: 'admin', online: 'manager' })) {
                    const baseURL = environments[role === 'online' ? 'online' : 'manual'].url;
                    const context = contexts[role] = await browser.newContext({
                        baseURL, viewport: { width, height: width < 768 ? 820 : 900 }, hasTouch: width <= 768,
                    });
                    await theme(context, baseURL, dark);
                    if (account) {
                        const page = await context.newPage();
                        await login(page, account);
                        await page.close();
                    }
                }

                const manager = await contexts.manager.newPage();
                await manager.goto('/dashboard');
                const building = new URL(await dashboardCard(manager).locator('a[href*="/dashboard/building/"]').getAttribute('href')).pathname;
                await manager.close();
                const check = (role, name, path, extra = {}) => test.step(name, async () => {
                    const result = await capture(contexts[role], { name, path, ...extra }, width, dark);
                    guards.push(result);
                    expect.soft(result.captureError, name + ' capture').toBeUndefined();
                    expect.soft(result.screenshotError, name + ' screenshot').toBeUndefined();
                    expect.soft(result.status, name + ' HTTP status').toBe(200);
                    expect.soft(result.pathname, name + ' route after navigation').toBe(new URL(path, environments.manual.url).pathname);
                    expect.soft(result.scrollWidth, name + ' horizontal overflow').toBeLessThanOrEqual(width);
                    expect.soft(result.actualDark, name + ' theme').toBe(dark);
                    expect.soft(result.errors, name + ' console errors').toEqual([]);
                    expect.soft(result.brokenImages, name + ' broken images').toEqual([]);
                    expect.soft(result.smallTargets, name + ' undersized touch targets').toEqual([]);
                    expect.soft(result.axe?.filter(violation => ['critical', 'serious'].includes(violation.impact)) ?? [], name + ' critical or serious accessibility violations').toEqual([]);
                });
                for (const [name, path] of Object.entries(pages)) await check('guest', name, path);
                await check('manager', 'dashboard', '/dashboard');
                await check('manager', 'dashboard-settings', '/dashboard', { panel: 'setting' });
                await check('manager', 'building-registration', '/dashboard', { panel: 'register', ready: '.register-window .leaflet-control-zoom' });
                await check('manager', 'confirm-password', '/confirm-password');
                await check('manager', 'verify-email', '/verify-email');
                for (const [name, panel] of Object.entries(tabs)) {
                    const ready = panel === 'chat' ? '.chat-message' : panel === 'paymentInfo' ? '[data-payments-table] tbody tr' : undefined;
                    await check('manager', 'building-' + name, building, { panel, ready });
                }
                await check('manager', 'map-display', building, { panel: 'home', ready: '.home-window .leaflet-marker-icon', scroll: '.home-window [data-complex-map]' });
                await check('manager', 'map-picker', building, { panel: 'setting', ready: '.setting-window .leaflet-marker-icon', scroll: '.setting-window [data-complex-map]' });
                for (const admin of ['pending', 'accepted', 'rejected']) await check('admin', 'admin-' + admin, '/adminPanel', { admin });
                await check('online', 'mock-checkout', checkout, { ready: '[data-mock-outcome=pay]' });
                await check('online', 'online-payments', building, { panel: 'paymentInfo', ready: '[data-online-payments] tbody tr' });
                expect(guards).toHaveLength(25);
            } finally {
                await writeFile(`${output}/guards-${width}-${dark ? 'dark' : 'light'}.json`, JSON.stringify(guards, null, 2));
                await Promise.all(Object.values(contexts).map(context => context.close()));
            }
        });
    }
}
