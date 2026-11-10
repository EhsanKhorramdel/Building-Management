import { spawn } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { copyFile, mkdir, mkdtemp, readdir, rm, symlink, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { basename, dirname, join, resolve } from 'node:path';
import { PNG } from 'pngjs';

const temp = resolve(tmpdir());
const root = await mkdtemp(join(temp, 'building-e2e-'));
if (dirname(root) !== temp || !basename(root).startsWith('building-e2e-')) {
    throw new Error('The browser workspace must be inside the temporary directory.');
}
process.env.BUILDING_E2E_ROOT = root;
process.env.BUILDING_E2E_KEY = 'base64:' + randomBytes(32).toString('base64');
const { environments } = await import('./environment.js');
const args = process.argv.slice(2);
const blankTile = PNG.sync.write(new PNG({ width: 1, height: 1 }));
const refresh = args[0] === '--refresh-baselines';
process.env.E2E_REFRESH_BASELINES = refresh ? '1' : '0';
const directoryLink = process.platform === 'win32' ? 'junction' : 'dir';

try {
    for (const { app } of Object.values(environments)) {
        for (const path of ['bootstrap/cache', 'database', 'php', 'public', 'storage/app/public', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs']) {
            await mkdir(join(app, path), { recursive: true });
        }
        for (const path of ['app', 'config', 'database/migrations', 'lang', 'resources', 'routes', 'vendor']) {
            await symlink(resolve(path), join(app, path), directoryLink);
        }
        for (const path of ['artisan', 'composer.json', 'bootstrap/app.php']) {
            await copyFile(path, join(app, path));
        }
        for (const entry of await readdir('public', { withFileTypes: true })) {
            if (['storage', 'hot'].includes(entry.name)) continue;
            const path = join('public', entry.name);
            if (entry.isDirectory()) await symlink(resolve(path), join(app, path), directoryLink);
            else if (entry.isFile()) await copyFile(path, join(app, path));
        }
        await writeFile(join(app, 'public', 'e2e-tile.png'), blankTile);
        await writeFile(join(app, 'php', 'e2e.ini'), 'max_execution_time=120\n');
        await symlink(join(app, 'storage', 'app', 'public'), join(app, 'public', 'storage'), directoryLink);
    }

    if (refresh) {
        args.shift();
        args.unshift('baselines.spec.js');
    }

    process.exitCode = await new Promise((resolve, reject) => {
        const child = spawn(process.execPath, ['node_modules/@playwright/test/cli.js', 'test', ...args], { stdio: 'inherit' });
        child.once('error', reject);
        child.once('exit', code => resolve(code ?? 1));
    });
} finally {
    await rm(root, { recursive: true, force: true, maxRetries: 5, retryDelay: 200 });
}
