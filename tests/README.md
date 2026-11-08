# Tests and local demo data

The current lockfiles use Laravel 10.50.4, Carbon 2.73.0, Sanctum 3.3.3, Pest 2.36.0 / PHPUnit 10.5.36, Vite 6.4.5 and Tailwind 3.4.19, verified with PHP 8.1.34 and Node 22.17.0. Install dependencies with `composer install` and `npm ci`. Copy `.env.example` to `.env`, generate an application key, and run `npm run build` before the feature tests that render shared layouts.

Install PHP 8.3 before the Laravel 11 hop. Its planned Carbon 3, Sanctum 4 and Pest 3 / PHPUnit 11 changes have not been applied. Installed morilog/jalali 3.5.0 permits Carbon 3. Current Pest 2.36.0 explicitly conflicts with PHPUnit above 10.5.36; that pin cannot be removed by a PHPUnit patch update alone.

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

Tests use the registered URLs and methods. Existing GET mutations are exercised only to cover their business effects; the desired method restriction is skipped until Phase 2. Empty scaffold methods without routes and the unregistered profile edit/delete actions have no public endpoint to exercise.

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

The monthly test uses Carbon::setTestNow for Jalali 1405-01-31 and 1405-02-01, which both fall in April 2026. It rejects a second payment in the first Jalali month and accepts a payment of the same charge in the next. A Gregorian-month or lifetime unique rule would fail this test.

## Known-defect skips

`Feature/KnownDefectsTest.php` contains executable expectations for desired behavior, each skipped with its log reference and fixing phase. These skips are not passing assertions about the defective behavior.

| Test | Reason / phase |
| --- | --- |
| chat history rejects a member of another complex | Cross-complex authorization missing; Phase 2 |
| a manager cannot publish an announcement in another complex | Cross-complex authorization missing; Phase 2 |
| chat rejects a non-image upload | Chat image upload unvalidated; Phase 2 |
| incidental cost payments use the recorded share instead of a submitted amount | Client-set payment amounts; Phase 2 |
| expired polls reject new votes | Voting after poll expiry; Phase 4 |
| a blank middle poll option does not discard later options | Option-shift bug; Phase 4 |
| an empty chat returns no last-seen message without an error | Null-dereference crashes; Phase 4 |
| GET cannot delete a chat message | State changes via GET; Phase 2 |
| profile updates may omit optional national code and phone fields | Phase 0 audit B07, missing nullable keys; Phase 4 |

Five stock tests against unregistered GET/PATCH/DELETE `/profile` were removed. Five replacements cover the registered PATCH `/dashboard/update` route, including national-code/phone fields. Login and registration assert `/dashboard`, and admin login asserts `/adminPanel`; the application routes and redirects were not changed.
