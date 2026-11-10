import { execFileSync } from 'node:child_process';
import { writeFile } from 'node:fs/promises';
import { environments, php } from './environment.js';

export default async function setup() {
    for (const [mode, { database }] of Object.entries(environments)) {
        await writeFile(database, '', { flag: 'wx' });
        seed(mode);
    }
}

export function seed(mode) {
    const { app, env } = environments[mode];
    execFileSync(php, ['artisan', 'migrate:fresh', '--seed', '--seeder=E2ESeeder', '--force'], {
        env: { ...process.env, ...env },
        cwd: app,
        stdio: 'inherit',
    });
}
