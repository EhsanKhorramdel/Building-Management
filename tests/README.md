# Tests and local demo data

The current lockfiles use PHP 8.3.36, laravel/framework v12.71.0, nesbot/carbon 3.15.1, laravel/sanctum v4.3.3, pestphp/pest v3.8.7, phpunit/phpunit 11.5.56, morilog/jalali v3.5.0, Vite 6.4.5 and Tailwind 3.4.19. Install dependencies with Composer and npm ci, configure a local .env and build the assets before tests. Use D:/tools/php-8.3/php.exe explicitly on this Windows checkout.

Run the full suite:

```sh
composer test
```

`phpunit.xml` forces SQLite `:memory:` in both environment and server variables, clears the connection URL, and uses a separate config-cache path. `Tests\CreatesApplication` refuses a non-memory connection before RefreshDatabase runs. Tests do not migrate or seed the development database. A regression check also verifies the active SQLite connection and foreign-key enforcement.

## Demo database

Use a separate local SQLite file, set `DB_CONNECTION=sqlite` and an absolute `DB_DATABASE` path in your local `.env`, then run:

```sh
php artisan migrate:fresh
php artisan db:seed --class=DemoSeeder
php artisan storage:link
```

`migrate:fresh` clears that local database. `DemoSeeder` is separate from `DatabaseSeeder` and refuses environments other than local/testing, including when invoked directly. The default seed command does not create demo accounts. Rebuild an empty database before reseeding.

All five local accounts use password `password`:

| Account | Role | Unit |
| --- | --- | --- |
| admin@example.test | Admin | None |
| manager@example.test | Manager | 1 |
| resident1@example.test | Resident | 2 |
| resident2@example.test | Resident | 3 |
| resident3@example.test | Resident | 4 |

The demo contains one complex, two charges (the first paid this Jalali month by resident1, the second unpaid), an incidental cost with a resident2 payment, active and archived announcements, open and expired polls with options/votes, three chat messages including a stored image, a read receipt, and one pending building request from resident3. No gateway call, SMS or broadcast event is dispatched by seeding. The image is copied from the tracked fallback image; the public storage link is needed to serve it.

## Coverage

Tests use the registered URLs and methods. Mutations now use POST or DELETE with CSRF. Existing business assertions were updated to those verbs without changing their expected outcomes. Empty scaffold methods without routes and the unregistered profile edit/delete actions have no public endpoint to exercise.

| Area | Test file |
| --- | --- |
| Login, admin login, logout | Feature/Auth/AuthenticationTest.php |
| Registration | Feature/Auth/RegistrationTest.php |
| Password confirmation/reset/update, verification/resend | Feature/Auth/PasswordConfirmationTest.php, PasswordResetTest.php, PasswordUpdateTest.php, EmailVerificationTest.php |
| Active profile update, optional values present, field validation | Feature/ProfileTest.php |
| Dashboard and building page | Feature/Building/BuildingDashboardTest.php |
| Join token | Feature/Building/JoinBuildingTest.php |
| Unit assignment, duplicate unit, edit | Feature/Building/UnitTest.php |
| Building approval request submission | Feature/Building/MembershipRequestTest.php |
| Announcement publish/upload/archive | Feature/Building/AnnouncementTest.php |
| Poll create, vote/change, results | Feature/Building/PollTest.php |
| Charge create | Feature/Building/MonthlyChargeTest.php |
| Incidental cost create/upload | Feature/Building/IncidentalCostTest.php |
| Monthly charge-payment rule | Feature/Building/ChargePaymentTest.php |
| One-off cost payment | Feature/Building/IncidentalCostPaymentTest.php |
| Payment history | Feature/Building/PaymentReportTest.php |
| Promote and step down | Feature/Building/RolesTest.php |
| Chat send/upload/edit/delete/history/viewers/seen | Feature/Building/ChatTest.php |
| Admin review/accept/reject | Feature/Admin/MembershipDecisionTest.php |
| Factory FK graphs and memory connection | Feature/FactoriesTest.php |
| Demo contents, default seed safety, environment guard | Feature/DemoSeederTest.php |

The security matrix covers 28 endpoints across six actor roles (168 cases), including foreign managers/residents and global admins. Mutation-method tests enable the CSRF middleware and assert 419 without a token.

The monthly test uses Carbon::setTestNow for Jalali 1405-01-31 and 1405-02-01, which both fall in April 2026. It rejects a second payment in the first Jalali month and accepts a payment of the same charge in the next. A Gregorian-month or lifetime unique rule would fail this test.

## Defect regression coverage

All nine original known-defect tests are active and passing. Phase 4 closes expired voting, sparse poll options, empty chat and omitted optional profile fields. No tests remain skipped. The service tests cover upload storage/naming, message and broadcast payloads, chat cursor boundaries, poll aggregate results and Jalali payment periods. Backend query-shape tests keep the payments endpoint at eight queries for both two and eight populated units.

Five stock tests against unregistered GET/PATCH/DELETE `/profile` were removed. Five replacements cover the registered PATCH `/dashboard/update` route, including national-code/phone fields. Login and registration assert `/dashboard`, and admin login asserts `/adminPanel`; the application routes and redirects were not changed.
