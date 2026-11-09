# Building Management

Building Management is a Persian-language web app for apartment complexes. Residents join a complex with an invite, register their units and use a shared dashboard for building activity.

Managers administer their own complexes, publish notices and set charges and incidental costs. A site administrator reviews requests to register new complexes.

## Features

- Complexes and units with global administrator and per-complex manager and resident roles.
- Building registration approvals and manager-controlled join links.
- Monthly charges, incidental costs and a printable payment report.
- Announcements, timed polls and vote results.
- Per-complex group chat with image attachments and read receipts.
- Responsive pages with a light and dark theme.

## Requirements

- PHP 8.3 or later and Composer 2.
- Node.js 20 or later with npm. The project was built with Node.js 22.
- SQLite with the PHP PDO SQLite extension for development and tests. Use MySQL 8 with PDO MySQL in production.

## Local setup

Clone the repository and install its dependencies.

```sh
git clone https://github.com/EhsanKhorramdel/Building-Management.git
cd Building-Management
composer install
npm install
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
- **Uploads.** Announcements, invoices and chat images live under `storage/app/public`, the root of the public disk. `php artisan storage:link` exposes them at `/storage`.
- **Mail.** The template points SMTP to Mailpit on port 1025. The local setup above writes mail to the log. Configure a real mail transport for a deployment that sends password reset links.

## Tests

Run the PHP suite from the repository root. It uses an in-memory SQLite database.

```sh
php artisan test
```

See [visual QA](docs/VISUAL_QA.md#repeat-the-after-capture) for Playwright setup and the browser capture command.

## Screenshots

- Desktop dashboard at 1440px ([light](docs/screenshots/dashboard-1440-light.png), [dark](docs/screenshots/dashboard-1440-dark.png))
- Phone chat at 390px ([light](docs/screenshots/building-chat-390-light.png), [dark](docs/screenshots/building-chat-390-dark.png))
- [Building home on a 768px tablet](docs/screenshots/building-home-768-light.png)

See [Architecture](docs/ARCHITECTURE.md) for domain and deployment details, and the [changelog](CHANGELOG.md) for a summary of recent work.

## Roadmap

Building maps, online payment processing and SMS notifications are planned.
