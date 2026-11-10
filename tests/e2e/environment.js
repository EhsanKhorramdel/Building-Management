import { readFileSync } from 'node:fs';
import { delimiter, join } from 'node:path';
import * as util from 'node:util';

export const php = process.env.E2E_PHP || (process.platform === 'win32' ? 'D:/tools/php-8.3/php.exe' : 'php');
export const liveBroadcast = process.env.E2E_BROADCAST_CONNECTION === 'pusher';
const root = process.env.BUILDING_E2E_ROOT;
if (!root || !process.env.BUILDING_E2E_KEY) {
    throw new Error('Start the isolated browser environment with npm run test:e2e.');
}

function environment(mode, port, integrations) {
    const app = join(root, mode);
    const database = join(app, 'database', 'e2e.sqlite');
    const url = `http://127.0.0.1:${port}`;

    return {
        app, database, port, url,
        env: {
            APP_BASE_PATH: app,
            APP_ENV: 'e2e',
            PHP_INI_SCAN_DIR: [process.env.PHP_INI_SCAN_DIR || '', join(app, 'php')].join(delimiter),
            APP_KEY: process.env.BUILDING_E2E_KEY,
            APP_URL: url,
            ASSET_URL: '',
            APP_DEBUG: 'false',
            APP_CONFIG_CACHE: 'bootstrap/cache/config.php',
            APP_ROUTES_CACHE: 'bootstrap/cache/routes.php',
            APP_EVENTS_CACHE: 'bootstrap/cache/events.php',
            APP_PACKAGES_CACHE: 'bootstrap/cache/packages.php',
            APP_SERVICES_CACHE: 'bootstrap/cache/services.php',
            LARAVEL_STORAGE_PATH: join(app, 'storage'),
            VIEW_COMPILED_PATH: join(app, 'storage', 'framework', 'views'),
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: database,
            DATABASE_URL: '',
            DB_FOREIGN_KEYS: 'true',
            SESSION_DRIVER: 'file',
            SESSION_COOKIE: `building_e2e_${mode}`,
            SESSION_DOMAIN: '',
            SESSION_SECURE_COOKIE: 'false',
            CACHE_STORE: 'array',
            CACHE_DRIVER: 'array',
            QUEUE_CONNECTION: 'sync',
            MAIL_MAILER: 'array',
            LOG_CHANNEL: 'single',
            BROADCAST_CONNECTION: mode === 'manual' && liveBroadcast ? 'pusher' : 'log',
            FILESYSTEM_DISK: 'local',
            PAYMENTS_ENABLED: String(integrations),
            PAYMENTS_DRIVER: 'mock',
            SMS_ENABLED: String(integrations),
            SMS_DRIVER: 'log',
            MAP_TILE_URL: url + '/e2e-tile.png',
        },
    };
}

export const environments = {
    manual: environment('manual', 8001, false),
    online: environment('online', 8002, true),
};

if (liveBroadcast) {
    if (!util.parseEnv) {
        throw new Error('Live chat requires Node.js 20.12 or newer with util.parseEnv.');
    }
    const local = util.parseEnv(readFileSync(new URL('../../.env', import.meta.url), 'utf8'));
    const keys = ['PUSHER_APP_ID', 'PUSHER_APP_KEY', 'PUSHER_APP_SECRET', 'PUSHER_APP_CLUSTER'];
    if (keys.some(key => !local[key])) {
        throw new Error('Live chat requires the four PUSHER_APP settings in the local .env.');
    }
    const env = environments.manual.env;
    for (const key of keys) env[key] = local[key];
    env.VITE_PUSHER_APP_KEY = env.PUSHER_APP_KEY;
    env.VITE_PUSHER_APP_CLUSTER = env.PUSHER_APP_CLUSTER;
}
