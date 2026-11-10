# Building Management

Building Management is a Persian-language web app for apartment complexes. Residents join a complex with an invite, register their units and use a shared dashboard for building activity.

Managers administer their own complexes, publish notices and set charges and incidental costs. A site administrator reviews requests to register new complexes.

## Features

- Complexes and units with global administrator and per-complex manager and resident roles.
- Building registration approvals and manager-controlled join links.
- Monthly charges, incidental costs and a printable payment report.
- Announcements, timed polls and vote results.
- Per-complex group chat with private attachments, reactions, replies, pins, moderation, bulk deletion, search and read receipts.
- Optional map locations for complexes, with a picker during registration and in manager settings.
- Responsive pages with a light and dark theme.

## Requirements

- PHP 8.3 or a newer PHP 8.x release, and Composer 2.
- Node.js 20 or later with npm. The project was built with Node.js 22.
- SQLite with the PHP PDO SQLite extension for development and tests. Use MySQL 8 with PDO MySQL in production.

## Local setup

Clone the repository and install its dependencies.

```sh
git clone https://github.com/EhsanKhorramdel/Building-Management.git
cd Building-Management
composer install
npm ci
```

Copy the environment template.

```sh
cp .env.example .env
```

In PowerShell, use `Copy-Item .env.example .env`.

In `.env`, set the following values. Remove the `DB_DATABASE` line so Laravel uses `database/database.sqlite`.

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
MAIL_MAILER=log
```

Create the SQLite file, generate the app key, migrate and seed the local data, then build and serve the app.

```sh
php -r "touch('database/database.sqlite');"
php artisan key:generate
php artisan migrate
php artisan db:seed --class=DemoSeeder
php artisan storage:link
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000/login` and sign in with a demo account. For live asset updates while editing, run `npm run dev` in a second terminal in place of `npm run build`.

If port 8000 is occupied, set `APP_URL=http://127.0.0.1:8001`, run `php artisan serve --port=8001` and open `http://127.0.0.1:8001/login`.

Signing out ends the session and opens the public home page. Opening a protected page after signing out takes you to sign-in.

### Demo accounts

These shared credentials are for local demos only. Never seed them into a deployed database. Every account below uses the password `password`.

| Account | Email |
| --- | --- |
| Administrator | `admin@example.test` |
| Manager | `manager@example.test` |
| Resident 1 | `resident1@example.test` |
| Resident 2 | `resident2@example.test` |
| Resident 3 | `resident3@example.test` |

## Configuration

- **Broadcasting.** The template uses `BROADCAST_CONNECTION=log`, which records chat events locally. Set it to `pusher` for live chat and configure `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET` and `PUSHER_APP_CLUSTER`. The template maps the public key and cluster to `VITE_PUSHER_APP_KEY` and `VITE_PUSHER_APP_CLUSTER`. The browser gets those public values from the rendered page. It authorizes each private complex channel through the signed-in session.
- **Uploads.** Announcement, incidental-cost and chat images are stored under `storage/app/private`. The attachment routes require an active role in the owning complex or a global admin role. The `public/storage` link still serves the bundled placeholder image.

  When upgrading an existing installation, run `php artisan chat:move-attachments` and `php artisan attachments:move-building-images` before reopening web traffic. The commands move legacy uploads to private storage and keep their filenames. Reruns process any remaining files. Resolve reported conflicts or failures before reopening traffic so the legacy public copies are gone.
- **Maps.** The default tile URL and attribution use OpenStreetMap. Set `MAP_TILE_URL` and `MAP_TILE_ATTRIBUTION` together to use another tile source. Keep the source's attribution visible and follow its usage policy. See the [OpenStreetMap tile usage policy](https://operations.osmfoundation.org/policies/tiles/).
- **Mail.** The template points SMTP to Mailpit on port 1025. The local setup above writes mail to the log. Configure a real mail transport for a deployment that sends password reset links.

Typing names use throttled `client-typing` events on the authorized private complex channel. Enable Client Events in the broadcasting app's settings. These advisory client signals expire after five seconds, and the log broadcaster leaves the indicator inactive. [Client event settings](https://pusher.com/docs/channels/using_channels/events/#triggering-client-events)

## Online payments and SMS

Both integrations are disabled by default. With `PAYMENTS_ENABLED=false`, charges and costs use the existing manual payment record. With `SMS_ENABLED=false`, actions enqueue no SMS.

For a local checkout, set `PAYMENTS_ENABLED=true` and `PAYMENTS_DRIVER=mock`. Use a demo manager or resident to open a charge or cost, enter their unit number and select online payment. The mock page offers Pay and Cancel and sends the result through the normal callback. A successful simulation records a payment without moving money. The building's Payments tab shows the signed-in user's online attempts. Production requires an explicit `PAYMENTS_DRIVER=mock` to allow simulation. `PAYMENTS_EXPIRY_MINUTES` defaults to 30.

For a real provider that supports the reference HTTP contract, set `PAYMENTS_DRIVER=gateway` and supply `PAYMENTS_BASE_URL`, `PAYMENTS_MERCHANT_ID`, `PAYMENTS_API_KEY` and the public HTTPS `PAYMENTS_CALLBACK_URL`. Set `PAYMENTS_SANDBOX`, the initiate, verify and checkout paths, and the success and cancellation status values using the keys in `.env.example`. Map request and response field names to the provider's documentation with the `PAYMENTS_FIELD_MAP` JSON value. The verification response must include an authoritative amount and reference. For example, a token under `data.token` can be mapped with `'{"initiate_authority":"data.token"}'`. Set `PAYMENTS_ENABLED=true` after testing the sandbox. Refresh cached Laravel config and routes after changing drivers.

For a local SMS run, set `SMS_ENABLED=true` and `SMS_DRIVER=log`. This records messages in `sms_logs` and writes a log entry. For HTTP delivery, set `SMS_DRIVER=gateway` with `SMS_BASE_URL`, `SMS_API_KEY` and an optional `SMS_SENDER`. The HTTPS endpoint must accept bearer authentication and a JSON body with `to` and `message`. An optional sender is sent as `X-Sender`. Both modes use the dedicated database queue. Run its worker with the same environment as the app.

```sh
php artisan queue:work database --queue=sms
```

## Tests

Run the PHP suite from the repository root. It uses an in-memory SQLite database.

```sh
php artisan test
```

### Browser suite

Run the client typing checks with `node --test tests/JavaScript/chat-typing.test.js`.

Install the Composer and npm dependencies first. Use Node.js 20 or later and PHP 8.3 with SQLite. Playwright needs Chromium. On Windows the default is installed Chrome. To use the managed browser, install it once with `npx playwright install chromium` and set `PLAYWRIGHT_CHANNEL=chromium`. Set `E2E_PHP` if PHP 8.3 is at a different path from the harness default (`D:/tools/php-8.3/php.exe` on Windows and `php` elsewhere).

```sh
npm run test:e2e
```

The command builds assets and runs the suite. To refresh only the four committed visual baselines, run the command below and review the image diff before committing.

```sh
npm run test:e2e:update
```

Leave loopback ports 8001 and 8002 free. The runner starts both servers from temporary app copies with their own SQLite files, sessions and upload storage. The second server enables mock payment and the SMS log driver for integration coverage. Both use the log broadcaster and a local blank map tile by default. The opt-in live chat check reads only the Pusher settings from the checkout's `.env`. Setup seeds the databases automatically and the runner removes them at exit.

See [visual QA](docs/VISUAL_QA.md#phase-9-browser-suite) for the capture matrix and report locations, and [live chat delivery](docs/VISUAL_QA.md#live-chat-delivery) for the opt-in check.

## Screenshots

- Desktop dashboard at 1440px ([light](docs/screenshots/dashboard-1440-light.png), [dark](docs/screenshots/dashboard-1440-dark.png))
- Phone chat at 390px ([light](docs/screenshots/building-chat-390-light.png), [dark](docs/screenshots/building-chat-390-dark.png))
- [Building home on a 768px tablet](docs/screenshots/building-home-768-light.png)

See [Architecture](docs/ARCHITECTURE.md) for domain and deployment details, and the [changelog](CHANGELOG.md) for a summary of recent work.
