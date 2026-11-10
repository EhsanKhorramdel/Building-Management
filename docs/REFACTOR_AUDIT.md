# Refactor audit and baseline — Phase 0

Audit date: 2026-11-08 (Asia/Tehran). Repository: [Building-Management](https://github.com/EhsanKhorramdel/Building-Management). Audited revision: `aeef0a90c40cf6570979ebb6ca056ec5fa029a26`. Working branch: `refactor/phase-0-audit`.

Phase 0 records the existing behavior. It does not repair any finding. The final repository diff contains only this audit and the supplied change log with one appended Phase 0 entry. Payment remains record-only, SMS and gateway integrations remain absent, and no provider was selected or enabled.

## Baseline result

**24 existing tests: 17 passed, 7 failed, 43 assertions, 0 errors, 0 skipped.** The before and final after runs have identical test names, outcomes and assertion counts. Exit status is 1 because of the seven pre-existing failures. Five are the known missing-profile-route mismatch; two are login/registration redirect expectation mismatches not described in the baseline log.

The inventory contains **15 controllers, 16 models, 5 route files, 19 migrations, 41 Blade views and 10 JS/CSS source assets**, plus 3 build configuration files. Counts use tracked files, excluding vendor, node_modules, compiled views and build output.

## Environment and exact versions

| Component | Observed version / source |
| --- | --- |
| Platform | Windows, x64; application timezone Asia/Tehran |
| PHP CLI | 8.1.34, NTS, Visual C++ 2019 x64; Zend Engine 4.1.34 |
| SQLite library | 3.36.0, through pdo_sqlite/sqlite3 |
| Composer | 2.8.12 (2025-09-19); PHAR SHA-256 checked against the official download checksum |
| Laravel framework | `v10.14.1` in composer.lock; `php artisan --version` reports 10.14.1 |
| PHPUnit | 10.2.3 in composer.lock |
| Pest | v2.8.2 in composer.lock |
| Node.js | 22.17.0 |
| npm | 10.9.2 |
| PHP installation | 128 locked packages installed including dev requirements; 0 updates/removals |
| npm installation | npm ci added 115 packages for this platform; package-lock.json preserved |

PHP/Composer were unavailable on PATH initially. A portable PHP 8.1 runtime and Composer PHAR were placed outside the checkout, using the [official PHP Windows archive](https://downloads.php.net/~windows/releases/archives/) and [Composer downloads](https://getcomposer.org/download/). Runtime extensions enabled locally include curl, fileinfo, mbstring, openssl, pdo_sqlite, sqlite3, zip, intl and gd; DOM/XML and other required modules are also available. `composer check-platform-reqs` succeeds. No platform requirements were ignored and no global system configuration was changed.

### Direct Node packages

Exact installed/locked versions (not the manifest's semver ranges):

| Package | Version | Manifest group |
| --- | --- | --- |
| `laravel-echo` | 1.15.2 | dependencies |
| `pusher-js` | 8.3.0 | dependencies |
| `@tailwindcss/forms` | 0.5.3 | devDependencies |
| `alpinejs` | 3.12.2 | devDependencies |
| `autoprefixer` | 10.4.14 | devDependencies |
| `axios` | 1.4.0 | devDependencies |
| `laravel-vite-plugin` | 0.7.8 | devDependencies |
| `postcss` | 8.4.24 | devDependencies |
| `tailwindcss` | 3.3.2 | devDependencies |
| `vite` | 4.3.9 | devDependencies |

### Direct Composer packages

| Package | Locked version | Manifest group |
| --- | --- | --- |
| `guzzlehttp/guzzle` | 7.7.0 | require |
| `jenssegers/date` | v4.0.0 | require |
| `laravel/framework` | v10.14.1 | require |
| `laravel/sanctum` | v3.2.5 | require |
| `laravel/tinker` | v2.8.1 | require |
| `morilog/jalali` | v3.4.1 | require |
| `nesbot/carbon` | 2.68.1 | require |
| `pusher/pusher-php-server` | 7.2.3 | require |
| `fakerphp/faker` | v1.23.0 | require-dev |
| `laravel/breeze` | v1.21.1 | require-dev |
| `laravel/pint` | v1.10.3 | require-dev |
| `laravel/sail` | v1.23.0 | require-dev |
| `mockery/mockery` | 1.6.2 | require-dev |
| `nunomaduro/collision` | v7.7.0 | require-dev |
| `pestphp/pest` | v2.8.2 | require-dev |
| `pestphp/pest-plugin-laravel` | v2.0.0 | require-dev |
| `spatie/laravel-ignition` | 2.2.0 | require-dev |

## Setup and verification

The existing lockfiles were used without updates. Commands below were executed in the checkout with the portable PHP directory prepended to the process PATH; Composer was invoked as `php ../runtime/composer.phar`.

```powershell
git switch -c refactor/phase-0-audit
php ../runtime/composer.phar install --no-interaction --prefer-dist --no-progress
npm.cmd ci --no-audit --no-fund
Copy-Item .env.example .env
# Set only the local .env database connection to sqlite and DB_DATABASE
# to the absolute path of storage/app/phase0.sqlite; create that empty file.
php artisan key:generate --no-interaction
php artisan migrate --no-interaction
npm.cmd run build
php artisan route:list --json

# Test DB isolation is a process override, not an edit to phpunit.xml.
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
php artisan test --colors=never --log-junit ../baseline-before.xml
# After documentation is assembled, use the identical environment:
php artisan test --colors=never --log-junit ../baseline-after.xml
```

The `.env` file and generated key remain ignored. The dev SQLite file is under ignored storage/app; all suite executions and targeted probes use their own in-memory database. The test runner's existing RefreshDatabase trait handles test migration. This process override is necessary because the repository's in-memory settings in phpunit.xml are commented out; the file was left unchanged. Runtime support, command transcripts and JUnit evidence remain outside the checkout; the complete per-test baseline is preserved below.

| Verification | Result |
| --- | --- |
| composer install | Passed; locked dependencies and package-discovery scripts installed successfully. Windows installer noted an existing conflicting paratest.bat binary name; single-process tests are unaffected. |
| npm ci | Passed; lockfile unchanged. |
| app key generation | Passed; no key value recorded in docs or commits. |
| fresh local SQLite migration | All 19 migrations passed. No existing migration edited. |
| before/final-after existing tests | Same 24 tests, 17 passed / 7 failed, 43 assertions; no new failing case. |
| npm run build | Passed with Vite 4.3.9; 47 modules; output CSS app-54512bf0.css 41.77 kB (7.27 kB gzip), JS app-6870bb4e.js 70.27 kB (26.24 kB gzip), manifest 0.26 kB. Existing Browserslist data is reported outdated. Output is ignored. |
| PHP syntax | All 160 tracked *.php files (including Blade sources) passed php -l. This is syntax lint, not proof that each Blade page renders correctly. |
| composer check-platform-reqs | Passed for every installed requirement. |
| route:list | 54 registered route records, including package routes; full inventory below. |
| route:cache | Failed on duplicate logout route name (B20); route:clear succeeded afterward. No stale route cache left behind. |
| dependency metadata audits | Composer: 61 advisories / 17 packages. npm: 17 affected packages (1 critical / 11 high / 5 moderate). Read-only; no fixes applied. |
| targeted in-memory probes | Confirmed missing UserRoles class, missing group_user table in both pivot relations, wrong inferred relation keys, unpadded date-order error, profile optional-field 500, and empty-chat 500s. These are supplemental observations, not added suite tests. |

### Every existing test and outcome

Names are taken from the test source and matched to both JUnit runs. “Before / after” records the final unchanged baseline; the final column explains each failure.

| Test file | Exact test name | Before / after | Failure reason |
| --- | --- | --- | --- |
| `tests/Unit/ExampleTest.php` | that true is true | PASS / PASS | — |
| `tests/Feature/Auth/AuthenticationTest.php` | login screen can be rendered | PASS / PASS | — |
| `tests/Feature/Auth/AuthenticationTest.php` | users can authenticate using the login screen | FAIL / FAIL | Expected http://localhost (HOME=/); received http://localhost/dashboard. Authentication succeeds. |
| `tests/Feature/Auth/AuthenticationTest.php` | users can not authenticate with invalid password | PASS / PASS | — |
| `tests/Feature/Auth/EmailVerificationTest.php` | email verification screen can be rendered | PASS / PASS | — |
| `tests/Feature/Auth/EmailVerificationTest.php` | email can be verified | PASS / PASS | — |
| `tests/Feature/Auth/EmailVerificationTest.php` | email is not verified with invalid hash | PASS / PASS | — |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | confirm password screen can be rendered | PASS / PASS | — |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | password can be confirmed | PASS / PASS | — |
| `tests/Feature/Auth/PasswordConfirmationTest.php` | password is not confirmed with invalid password | PASS / PASS | — |
| `tests/Feature/Auth/PasswordResetTest.php` | reset password link screen can be rendered | PASS / PASS | — |
| `tests/Feature/Auth/PasswordResetTest.php` | reset password link can be requested | PASS / PASS | — |
| `tests/Feature/Auth/PasswordResetTest.php` | reset password screen can be rendered | PASS / PASS | — |
| `tests/Feature/Auth/PasswordResetTest.php` | password can be reset with valid token | PASS / PASS | — |
| `tests/Feature/Auth/PasswordUpdateTest.php` | password can be updated | PASS / PASS | — |
| `tests/Feature/Auth/PasswordUpdateTest.php` | correct password must be provided to update password | PASS / PASS | — |
| `tests/Feature/Auth/RegistrationTest.php` | registration screen can be rendered | PASS / PASS | — |
| `tests/Feature/Auth/RegistrationTest.php` | new users can register | FAIL / FAIL | Expected http://localhost (HOME=/); received http://localhost/dashboard. Authentication succeeds. |
| `tests/Feature/ExampleTest.php` | it returns a successful response | PASS / PASS | — |
| `tests/Feature/ProfileTest.php` | profile page is displayed | FAIL / FAIL | GET /profile is unregistered: expected 200, received 404. |
| `tests/Feature/ProfileTest.php` | profile information can be updated | FAIL / FAIL | PATCH /profile is unregistered: expected redirect, received 404. |
| `tests/Feature/ProfileTest.php` | email verification status is unchanged when the email address is unchanged | FAIL / FAIL | PATCH /profile is unregistered: expected redirect, received 404. |
| `tests/Feature/ProfileTest.php` | user can delete their account | FAIL / FAIL | DELETE /profile is unregistered: expected redirect, received 404. |
| `tests/Feature/ProfileTest.php` | correct password must be provided to delete account | FAIL / FAIL | DELETE /profile is unregistered: no userDeletion errors in session (request is 404). |

`routes/web.php` comments out GET /profile and DELETE /profile and registers profile.update at PATCH /dashboard/update. The tests still hardcode /profile for GET, PATCH and DELETE. No application middleware/route/controller/test change was made to conceal this mismatch. PasswordUpdateTest is a different case: PUT /password exists and its redirect-back assertions pass even though their supplied Referer is /profile.

## Complete source inventory

Line counts count logical source lines, including blank/comment lines, on the audited revision. Controller/model methods include public relation/scaffold methods, even if unrouted. Route middleware is taken from the running route collection, not inferred from URL names.

### Controllers (15)

| File | Lines | Public methods |
| --- | ---: | --- |
| `app/Http/Controllers/AdminController.php` | 119 | `__construct`, `index`, `accept`, `reject`, `create`, `store`, `show`, `edit`, `update`, `destroy` |
| `app/Http/Controllers/Auth/AuthenticatedSessionController.php` | 51 | `create`, `store`, `destroy` |
| `app/Http/Controllers/Auth/ConfirmablePasswordController.php` | 41 | `show`, `store` |
| `app/Http/Controllers/Auth/EmailVerificationNotificationController.php` | 25 | `store` |
| `app/Http/Controllers/Auth/EmailVerificationPromptController.php` | 22 | `__invoke` |
| `app/Http/Controllers/Auth/NewPasswordController.php` | 61 | `create`, `store` |
| `app/Http/Controllers/Auth/PasswordController.php` | 44 | `update` |
| `app/Http/Controllers/Auth/PasswordResetLinkController.php` | 44 | `create`, `store` |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | 55 | `create`, `store` |
| `app/Http/Controllers/Auth/VerifyEmailController.php` | 28 | `__invoke` |
| `app/Http/Controllers/BuildingController.php` | 771 | `__construct`, `index`, `upgradeRole`, `downgradeRole`, `NotificationRegistration`, `cancelNotification`, `pollCreate`, `vote`, `voteResult`, `incidentalCost`, `incidentalCostPayment`, `buildingCharge`, `buildingChargePayment`, `payments` |
| `app/Http/Controllers/Controller.php` | 12 | No declared public methods |
| `app/Http/Controllers/GroupController.php` | 396 | `__construct`, `index`, `getMessageViewers`, `create`, `store`, `seen`, `show`, `edit`, `update`, `destroy`, `lastMessageSeenId`, `getMessagesBeforeFrom`, `getMessagesAfterFrom` |
| `app/Http/Controllers/ProfileController.php` | 121 | `edit`, `update`, `destroy` |
| `app/Http/Controllers/UserController.php` | 249 | `__construct`, `index`, `store`, `register`, `update`, `addUnit` |

### Models (16)

| File | Lines | Public methods |
| --- | ---: | --- |
| `app/Models/Announcement.php` | 27 | `complex`, `manager` |
| `app/Models/ChargePayment.php` | 27 | `role`, `MonthlyCharge` |
| `app/Models/Complex.php` | 50 | `units`, `userRoles`, `announcements`, `monthlyCharges`, `incidentalCosts`, `groups`, `polls` |
| `app/Models/Group.php` | 39 | `complex`, `users`, `messages`, `seenMessages` |
| `app/Models/IncidentalCost.php` | 28 | `complex`, `PaymentAdditionalCosts` |
| `app/Models/MembershipRequest.php` | 21 | `user` |
| `app/Models/Message.php` | 38 | `group`, `user`, `seenMessages` |
| `app/Models/MonthlyCharge.php` | 33 | `complex`, `user`, `chargePayments` |
| `app/Models/PaymentAdditionalCost.php` | 27 | `role`, `incidentalCost` |
| `app/Models/Poll.php` | 43 | `complex`, `pollOptions`, `votes`, `isExpired` |
| `app/Models/PollOption.php` | 28 | `poll`, `votes` |
| `app/Models/SeenMessage.php` | 33 | `message`, `user`, `group` |
| `app/Models/Unit.php` | 27 | `complex`, `userRoles` |
| `app/Models/User.php` | 92 | `userRoles`, `announcements`, `monthlyCharges`, `groups`, `messages`, `votes`, `membershipRequests`, `seenMessages` |
| `app/Models/UserRole.php` | 54 | `complex`, `unit`, `user`, `chargePayments`, `paymentAdditionalCosts`, `votes` |
| `app/Models/Vote.php` | 32 | `poll`, `role`, `pollOption` |

### Route files (5)

| File | Lines | Role |
| --- | ---: | --- |
| `routes/api.php` | 19 | GET /api/user (Sanctum) |
| `routes/auth.php` | 59 | Guest/authenticated account routes |
| `routes/channels.php` | 18 | Default user broadcast authorization callback only |
| `routes/console.php` | 19 | inspire command only |
| `routes/web.php` | 76 | Web/domain endpoints + auth.php include |

### Migrations (19)

| File | Lines | Role |
| --- | ---: | --- |
| `database/migrations/2014_10_12_000000_create_users_table.php` | 34 | Creates `users` |
| `database/migrations/2014_10_12_100000_create_password_reset_tokens_table.php` | 28 | Creates `password_reset_tokens` |
| `database/migrations/2019_08_19_000000_create_failed_jobs_table.php` | 32 | Creates `failed_jobs` |
| `database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php` | 33 | Creates `personal_access_tokens` |
| `database/migrations/2023_07_01_191512_create_complexes_table.php` | 34 | Creates `complexes` |
| `database/migrations/2023_07_01_191600_create_units_table.php` | 31 | Creates `units` |
| `database/migrations/2023_07_01_191644_create_user_roles_table.php` | 36 | Creates `user_roles` |
| `database/migrations/2023_07_01_191750_create_announcements_table.php` | 32 | Creates `announcements` |
| `database/migrations/2023_07_01_191807_create_monthly_charges_table.php` | 32 | Creates `monthly_charges` |
| `database/migrations/2023_07_01_191822_create_charge_payments_table.php` | 30 | Creates `charge_payments` |
| `database/migrations/2023_07_01_191836_create_incidental_costs_table.php` | 33 | Creates `incidental_costs` |
| `database/migrations/2023_07_01_191854_create_payment_additional_costs_table.php` | 30 | Creates `payment_additional_costs` |
| `database/migrations/2023_07_01_191907_create_groups_table.php` | 29 | Creates `groups` |
| `database/migrations/2023_07_01_191917_create_messages_table.php` | 33 | Creates `messages` |
| `database/migrations/2023_07_01_191927_create_polls_table.php` | 33 | Creates `polls` |
| `database/migrations/2023_07_01_191943_create_poll_options_table.php` | 30 | Creates `poll_options` |
| `database/migrations/2023_07_01_191954_create_votes_table.php` | 30 | Creates `votes` |
| `database/migrations/2023_07_05_223344_create_membership_requests_table.php` | 34 | Creates `membership_requests` |
| `database/migrations/2023_07_25_150330_create_seen_messages_table.php` | 30 | Creates `seen_messages` |

### Blade views (41)

| File | Lines | Role |
| --- | ---: | --- |
| `resources/views/adminPanel.blade.php` | 211 | Blade template / partial / component |
| `resources/views/auth/confirm-password.blade.php` | 27 | Blade template / partial / component |
| `resources/views/auth/forgot-password.blade.php` | 63 | Blade template / partial / component |
| `resources/views/auth/login.blade.php` | 101 | Blade template / partial / component |
| `resources/views/auth/register.blade.php` | 120 | Blade template / partial / component |
| `resources/views/auth/reset-password.blade.php` | 39 | Blade template / partial / component |
| `resources/views/auth/verify-email.blade.php` | 31 | Blade template / partial / component |
| `resources/views/building/alert.blade.php` | 48 | Blade template / partial / component |
| `resources/views/building/building.blade.php` | 85 | Blade template / partial / component |
| `resources/views/building/charge.blade.php` | 157 | Blade template / partial / component |
| `resources/views/building/cost.blade.php` | 162 | Blade template / partial / component |
| `resources/views/building/group.blade.php` | 134 | Blade template / partial / component |
| `resources/views/building/header.blade.php` | 31 | Blade template / partial / component |
| `resources/views/building/main.blade.php` | 80 | Blade template / partial / component |
| `resources/views/building/notification.blade.php` | 129 | Blade template / partial / component |
| `resources/views/building/payments.blade.php` | 26 | Blade template / partial / component |
| `resources/views/building/poll.blade.php` | 202 | Blade template / partial / component |
| `resources/views/building/setting.blade.php` | 118 | Blade template / partial / component |
| `resources/views/building/sidebar.blade.php` | 78 | Blade template / partial / component |
| `resources/views/components/application-logo.blade.php` | 3 | Blade template / partial / component |
| `resources/views/components/auth-session-status.blade.php` | 7 | Blade template / partial / component |
| `resources/views/components/danger-button.blade.php` | 3 | Blade template / partial / component |
| `resources/views/components/dropdown-link.blade.php` | 1 | Blade template / partial / component |
| `resources/views/components/dropdown.blade.php` | 43 | Blade template / partial / component |
| `resources/views/components/input-error.blade.php` | 9 | Blade template / partial / component |
| `resources/views/components/input-label.blade.php` | 5 | Blade template / partial / component |
| `resources/views/components/modal.blade.php` | 77 | Blade template / partial / component |
| `resources/views/components/nav-link.blade.php` | 11 | Blade template / partial / component |
| `resources/views/components/primary-button.blade.php` | 3 | Blade template / partial / component |
| `resources/views/components/responsive-nav-link.blade.php` | 11 | Blade template / partial / component |
| `resources/views/components/secondary-button.blade.php` | 3 | Blade template / partial / component |
| `resources/views/components/text-input.blade.php` | 3 | Blade template / partial / component |
| `resources/views/dashboard.blade.php` | 467 | Blade template / partial / component |
| `resources/views/layouts/app.blade.php` | 39 | Blade template / partial / component |
| `resources/views/layouts/guest.blade.php` | 30 | Blade template / partial / component |
| `resources/views/layouts/navigation.blade.php` | 100 | Blade template / partial / component |
| `resources/views/mainPage.blade.php` | 63 | Blade template / partial / component |
| `resources/views/profile/edit.blade.php` | 29 | Blade template / partial / component |
| `resources/views/profile/partials/delete-user-form.blade.php` | 55 | Blade template / partial / component |
| `resources/views/profile/partials/update-password-form.blade.php` | 48 | Blade template / partial / component |
| `resources/views/profile/partials/update-profile-information-form.blade.php` | 64 | Blade template / partial / component |

### JS/CSS assets (10)

| File | Lines | Bytes |
| --- | ---: | --- |
| `public/css/dashboard/normalize.css` | 189 | 2704 |
| `public/css/dashboard/style.css` | 1028 | 19718 |
| `public/css/fonts.css` | 118 | 4126 |
| `public/css/mainPage.css` | 445 | 9005 |
| `public/js/building.js` | 1495 | 51609 |
| `public/js/dashboard/script.js` | 214 | 6746 |
| `public/js/signUp-logIn.js` | 34 | 968 |
| `resources/css/app.css` | 3 | 59 |
| `resources/js/app.js` | 7 | 96 |
| `resources/js/bootstrap.js` | 32 | 1248 |

### Build configuration (3)

| File | Lines | Role |
| --- | ---: | --- |
| `postcss.config.js` | 6 | Tailwind + Autoprefixer |
| `tailwind.config.js` | 21 | Blade/vendor content + forms plugin |
| `vite.config.js` | 14 | resources/css/app.css + resources/js/app.js entrypoints |

### Registered HTTP routes (54)

Method GET|HEAD represents a single route registration. The console command and channel callback are inventoried above and are not HTTP endpoints. Standard aliases below: `auth` = App Http Authenticate middleware; `guest` = RedirectIfAuthenticated; `access:role` = AccessMiddleware. Framework signature/throttle middleware is shortened for readability.

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET/HEAD | `/` | — | `Closure` | web |
| POST | `/_ignition/execute-solution` | ignition.executeSolution | `Spatie\LaravelIgnition\Http\Controllers\ExecuteSolutionController` | RunnableSolutionsEnabled |
| GET/HEAD | `/_ignition/health-check` | ignition.healthCheck | `Spatie\LaravelIgnition\Http\Controllers\HealthCheckController` | RunnableSolutionsEnabled |
| POST | `/_ignition/update-config` | ignition.updateConfig | `Spatie\LaravelIgnition\Http\Controllers\UpdateConfigController` | RunnableSolutionsEnabled |
| GET/HEAD | `/adminPanel` | adminPanel.index | `AdminController@index` | web, auth, access:admin |
| GET/HEAD | `/adminPanel/accept/{MembershipRequest}` | adminPanel.accept | `AdminController@accept` | web, auth, access:admin |
| GET/HEAD | `/adminPanel/reject/{MembershipRequest}` | adminPanel.reject | `AdminController@reject` | web, auth, access:admin |
| GET/HEAD | `/api/user` | — | `Closure` | api, auth:sanctum |
| GET/POST/HEAD | `/broadcasting/auth` | — | `Illuminate\Broadcasting\BroadcastController@authenticate` | web |
| GET/HEAD | `/confirm-password` | password.confirm | `Auth\ConfirmablePasswordController@show` | web, auth |
| POST | `/confirm-password` | — | `Auth\ConfirmablePasswordController@store` | web, auth |
| GET/HEAD | `/dashboard` | dashboard | `UserController@index` | web, auth |
| POST | `/dashboard/addUnit` | dashboard.addUnit | `UserController@addUnit` | web, auth |
| POST | `/dashboard/building/buildingCharge/payment` | building.buildingChargePayment | `BuildingController@buildingChargePayment` | web, auth |
| GET/HEAD | `/dashboard/building/cancelNotification/{announcement}` | building.cancelNotification | `BuildingController@cancelNotification` | web, auth, access:manager |
| GET/HEAD | `/dashboard/building/downgradeRole/{complex}` | building.downgradeRole | `BuildingController@downgradeRole` | web, auth, access:manager |
| POST | `/dashboard/building/getMessageViewers` | — | `GroupController@getMessageViewers` | web, auth |
| GET/HEAD | `/dashboard/building/getMessagesAfterFrom/{complex}/{fromLastMessageId}` | — | `GroupController@getMessagesAfterFrom` | web, auth |
| GET/HEAD | `/dashboard/building/getMessagesBeforeFrom/{complex}/{fromFirstMessageId}` | — | `GroupController@getMessagesBeforeFrom` | web, auth |
| POST | `/dashboard/building/group/edit` | — | `GroupController@edit` | web, auth |
| POST | `/dashboard/building/group/seen` | — | `GroupController@seen` | web, auth |
| POST | `/dashboard/building/group/send` | — | `GroupController@store` | web, auth |
| GET/HEAD | `/dashboard/building/group/{Message}` | — | `GroupController@destroy` | web, auth |
| POST | `/dashboard/building/incidentalCost/payment` | building.incidentalCostPayment | `BuildingController@incidentalCostPayment` | web, auth |
| POST | `/dashboard/building/payments` | building.payments | `BuildingController@payments` | web, auth |
| POST | `/dashboard/building/poll/{complexId}` | building.poll | `BuildingController@pollCreate` | web, auth |
| GET/HEAD | `/dashboard/building/upgradeRole/{userRole}` | building.upgradeRole | `BuildingController@upgradeRole` | web, auth, access:manager |
| POST | `/dashboard/building/vote` | building.vote | `BuildingController@vote` | web, auth |
| POST | `/dashboard/building/vote/Result` | building.voteResult | `BuildingController@voteResult` | web, auth |
| GET/HEAD | `/dashboard/building/{complex}` | building | `BuildingController@index` | web, auth |
| POST | `/dashboard/building/{complex}/NotificationRegistration` | building.NotificationRegistration | `BuildingController@NotificationRegistration` | web, auth, access:manager |
| POST | `/dashboard/building/{complex}/buildingCharge` | building.buildingCharge | `BuildingController@buildingCharge` | web, auth, access:manager |
| GET/HEAD | `/dashboard/building/{complex}/group/{lastReceivedMessageId}` | — | `GroupController@index` | web, auth |
| POST | `/dashboard/building/{complex}/incidentalCost` | building.incidentalCost | `BuildingController@incidentalCost` | web, auth, access:manager |
| GET/HEAD | `/dashboard/building/{complex}/lastMessageSeenId` | — | `GroupController@lastMessageSeenId` | web, auth |
| POST | `/dashboard/joinBuilding` | dashboard.store | `UserController@store` | web, auth |
| POST | `/dashboard/registerBuilding` | dashboard.register | `UserController@register` | web, auth |
| POST | `/dashboard/unit` | dashboard.unit | `UserController@update` | web, auth |
| PATCH | `/dashboard/update` | profile.update | `ProfileController@update` | web, auth |
| POST | `/email/verification-notification` | verification.send | `Auth\EmailVerificationNotificationController@store` | web, auth, throttle:6,1 |
| GET/HEAD | `/forgot-password` | password.request | `Auth\PasswordResetLinkController@create` | web, guest |
| POST | `/forgot-password` | password.email | `Auth\PasswordResetLinkController@store` | web, guest |
| GET/HEAD | `/login` | login | `Auth\AuthenticatedSessionController@create` | web, guest |
| POST | `/login` | — | `Auth\AuthenticatedSessionController@store` | web, guest |
| GET/HEAD | `/logout` | logout | `Auth\AuthenticatedSessionController@destroy` | web |
| POST | `/logout` | logout | `Auth\AuthenticatedSessionController@destroy` | web, auth |
| PUT | `/password` | password.update | `Auth\PasswordController@update` | web, auth |
| GET/HEAD | `/register` | register | `Auth\RegisteredUserController@create` | web, guest |
| POST | `/register` | — | `Auth\RegisteredUserController@store` | web, guest |
| POST | `/reset-password` | password.store | `Auth\NewPasswordController@store` | web, guest |
| GET/HEAD | `/reset-password/{token}` | password.reset | `Auth\NewPasswordController@create` | web, guest |
| GET/HEAD | `/sanctum/csrf-cookie` | sanctum.csrf-cookie | `Laravel\Sanctum\Http\Controllers\CsrfCookieController@show` | web |
| GET/HEAD | `/verify-email` | verification.notice | `Auth\EmailVerificationPromptController` | web, auth |
| GET/HEAD | `/verify-email/{id}/{hash}` | verification.verify | `Auth\VerifyEmailController` | web, auth, signed, throttle:6,1 |

Unregistered declarations are still relevant: GET /profile (`profile.edit`) and DELETE /profile (`profile.destroy`) are comments only. PATCH /profile is also absent; PATCH /dashboard/update is the active profile.update route. Both GET /logout and POST /logout use the same name.

### Supporting source reviewed

Reviewed all controller/model files above, first-party routes/migrations/views/assets, both request classes, all middleware/providers/events, auth factories/seeder/tests, config and package manifests/lockfiles, README and ignored-runtime rules. The 3 chat events are `app/Events/NewChatMessage.php`, `EditChatMessage.php`, `DeleteChatMessage.php`; all implement ShouldBroadcast with Channel('group'). The only factory is `database/factories/UserFactory.php`; the only seeder is `database/seeders/DatabaseSeeder.php` (no-op). The tracked fallback image exists at `storage/app/public/default.png`; missing-default-image is not a finding.

## Findings

All locations refer to the audited source revision. **Known** means already represented in the supplied baseline log; **Additional** identifies a defect or material detail absent from that log. These are findings, not changes made in this phase. Runtime probes used synthetic records in a separate in-memory SQLite connection. No live messaging, payment, or external account was used.

### Frontend

| ID | Location / function | Concrete finding |
| --- | --- | --- |
| F01 | `resources/views/building/building.blade.php:4`, `resources/views/dashboard.blade.php:4` / head | **Known.** Both standalone pages omit the viewport meta tag, so mobile browsers use a desktop layout viewport. Other standalone heads (`mainPage`, admin, login, register, forgot-password) and both shared layouts contain it. |
| F02 | `resources/views/building/building.blade.php:11-16`; `resources/views/dashboard.blade.php:11`; `resources/views/adminPanel.blade.php:8`; `resources/views/auth/{login,register,forgot-password}.blade.php:8` | **Known, with scope correction.** Building loads Tailwind 2.2.15, 0.3.0, and Play CDN together; dashboard loads only 2.2.15; admin loads 2.2.17. These pages bypass the existing Vite entrypoints and depend on external assets. Building also loads Chart.js 2.9.4, randomColor 0.6.2 and Pusher JS 8.2.0 directly, outside the lockfile (which installs Pusher JS 8.3.0). |
| F03 | `public/js/building.js:8-85`, `public/js/dashboard/script.js:8-85` / readSettingsFromCookie, applySettings; `public/css/dashboard/style.css:28`; `resources/views/building/header.blade.php:7` | **Additional correction.** Dark mode already has header toggles, a cookie preference, root variables, `.dark` selectors and Blade variants. It is incomplete: white/hardcoded light panels remain in `building/{poll,cost,charge,group,setting}.blade.php`, auth and admin have no equivalent toggle, the scripts default to light instead of system preference, and apply the preference only after DOMContentLoaded. `tailwind.config.js` does not select class-based dark variants. The baseline's claim of no dark implementation or variants is false. |
| F04 | Both settings scripts / readSettingsFromCookie | **Additional.** `JSON.parse(decodeURIComponent(settingsCookie))` has no guard. A malformed `userSettings` cookie throws before settings and page interaction listeners are installed. Cookies are written without a common path, so dashboard/building preferences can diverge by URL directory. |
| F05 | `public/js/building.js` / CSRF reads, fetch handlers, upload preview; `public/js/dashboard/script.js` / settings | **Known, measured correction.** Building JS has 1,495 lines, 11 fetch calls, 7 CSRF reads and 7 header uses, one try/catch plus one promise catch. The token block is repeated 7 times, not 14. Dashboard duplicates the settings implementation instead of sharing it. |
| F06 | `resources/views/building/charge.blade.php` / shared payment modal; `resources/views/building/cost.blade.php` / shared payment modal; `public/js/building.js:492-513` / modal listeners | **Additional.** Hidden `Amount` fields are rendered outside their loops from the final `$monthlyCharge` / `$incidentalCost`. Opening another card changes only its ID, so its amount remains the last card's amount. Differently priced records can be paid at the wrong amount even without request tampering. |
| F07 | `public/js/building.js:728-779` / sendMessage, sendEditedMessage; `1339` / deleteMessage | **Known error-handling gap; additional data-loss detail.** Sending/editing clears the form before a successful response; deletion removes the message before the request succeeds. Failures have no retry state or rollback, so typed content disappears and the displayed history can disagree with storage. |
| F08 | `public/js/building.js:1052-1095` / getMessagesBeforeMessageId, getMessagesAfterMessageId; handleScroll | **Additional.** Attempted cursor IDs are saved before fetch succeeds. A failed request leaves `currentFromFirstMessageId` or `currentFromLastMessageId` equal to the boundary, and handleScroll suppresses another attempt for that boundary. |
| F09 | `resources/views/auth/{login,register,forgot-password}.blade.php` / plain HTML inputs | **Additional.** `:value="old(...)"` is component-binding syntax used on ordinary `<input>` elements. These standalone pages load no Alpine/Vue binding for it and Blade does not turn it into a value attribute, so name/email values are lost on validation errors. Login/register password labels also contain an extra quote after `:value`. |
| F10 | `GroupController` / message payload created_at; `public/js/building.js:1097-1134` / getTime, getDate, isToday | **Additional.** Message payloads format timestamps as `Y-m-d H:i:s` without an offset, while viewers and model-backed event timestamps use serialized datetimes. JavaScript passes both through new Date and uses the browser timezone. A browser outside the server's Asia/Tehran timezone interprets the offset-free message timestamp as its own local time, so message/viewer time and day grouping can disagree for the same instant. |
| F11 | `resources/views/building/alert.blade.php`, `building/poll.blade.php:179`; `public/js/building.js` / closeAlert, removeOption | **Additional.** A flashed building alert and the always-present poll warning both use `id="myAlert"`. `getElementById` can select the flashed alert when removeOption intends to display the minimum-options warning. `building/setting.blade.php` also gives both copy and downgrade controls `id="copyButton"`. |
| F12 | `resources/views/building/group.blade.php` / send and close controls; `resources/views/auth/{login,register}.blade.php` / password visibility images; dashboard/building creation forms | **Known accessibility gap, specific instances.** Chat send is a clickable div without keyboard/button semantics, password eye controls are clickable images without alt text or keyboard activation, and building/unit/charge fields rely on placeholders without associated labels. Custom building modals only wire click-to-close, with no Escape/focus management. |
| F13 | `public/css/dashboard/style.css:57,747-920`; `resources/views/building/group.blade.php` / fixed-position scroll control | **Known responsive risk, corrected evidence.** Body overflow is hidden, the sidebar remains a compact icon rail rather than a drawer, and the chat scroll button uses fixed left/bottom offsets. The cited 1800px and 480px rules are maximum widths, and 520px is a breakpoint, not a fixed content width. Source establishes missing viewport and layout risks; actual overflow at 360/390/768/1024/1440 was not measured in this phase. |
| F14 | `public/js/building.js` / chart rendering and showAlert; `tailwind.config.js` / content | **Known partial-theme/feedback gap; additional build gap.** Chart colors are random and have no theme update handler. showAlert constructs `bg-${color}-300` / `text-${color}-800`; JS assets are absent from Tailwind content and no safelist preserves those dynamic utilities for a future Vite-only build. |
| F15 | `resources/views/building/setting.blade.php` / share anchors and copy action | **Additional.** Share targets and copied values contain the raw join token rather than an application URL; there is no GET join-by-token route in the route inventory. The dashboard actually expects users to paste a token into a POST form. The UI presents a token as a navigable link. |
| F16 | `resources/views/building/building.blade.php`, `building/payments.blade.php`; public asset inventory | **Known missing capabilities.** There is no web manifest/theme-color installation setup or payments print stylesheet. Several destructive actions (message delete, role changes, admin request decisions) execute without confirmation. Existing inline alerts, a chat toast helper and some empty states do exist; they do not form a consistent loading/error/retry system. |

### Backend

| ID | Location / function | Concrete finding |
| --- | --- | --- |
| B01 | `app/Models/Unit.php:27` / userRoles; `app/Models/PollOption.php:25` / votes | **Known.** Unit references nonexistent `App\Models\UserRoles` and throws on invocation. PollOption joins votes by `role_id` instead of `poll_option_id`, mixing unrelated votes. Runtime introspection confirmed both. |
| B02 | `app/Models/PaymentAdditionalCost.php` / incidentalCost | **Additional.** Eloquent infers `incidental_cost_id`, while the persisted column is `incidental_costs_id`. The relation reads a missing attribute and returns no parent even for a valid payment. |
| B03 | `app/Models/User.php` / votes | **Additional.** `hasMany(Vote::class, 'role_id')` compares vote.role_id with user.id, although role_id points to user_roles.id. It can return another user's votes when the IDs happen to match or omit the user's own votes. |
| B04 | `app/Models/{User,Group}.php` / groups, users | **Additional.** Both belongsToMany relations infer `group_user`; none of the migrations creates that table. Invoking either relation after migrating produces SQLite `no such table: group_user`. Actual membership is recorded through user_roles/complexes. |
| B05 | `app/Http/Controllers/GroupController.php:24,76,118,145,205,239,273,305,310,355` / index, getMessageViewers, store, seen, edit, lastMessageSeenId, before/after fetch | **Known, expanded locations.** Group lookups are dereferenced without checking existence; index dereferences a missing/deleted/wrong-group anchor; viewers/seen/edit dereference unknown message IDs; lastMessageSeenId dereferences a missing last message. A fresh group with no messages returns 500 for last-seen and anchor fetch (confirmed at lines 305 and 76). |
| B06 | `app/Http/Controllers/BuildingController.php:78,117,357,608`; `UserController.php:25,167` | **Known, qualified.** Announcement manager lookup uses `first()['name']` without a fallback; vote dereferences `PollOption::find` without checking; charge payment dereferences an unknown monthly charge. Resident user lookup is built from the same prefetched set and is normally consistent; unit lookup at line 118 already has `?? 'N/A'`, so it is not an unguarded-null instance. User index assumes the authenticated user exists; unit update's exists-then-first sequence also permits a deletion race. |
| B07 | `app/Http/Controllers/ProfileController.php:35-38` / update; `app/Http/Requests/ProfileUpdateRequest.php` / rules | **Additional.** Nullable optional `ncode` and `phoneNumber` can be absent from validated data, but update indexes both unconditionally. A valid PATCH to the registered `/dashboard/update` with only name/email returns 500 (`Undefined array key "ncode"`), independently of the dead `/profile` tests. |
| B08 | `BuildingController.php:270-348` / pollCreate | **Known.** It counts non-null options then persists the first N original positions. Inputs `option1=A, option2=null, option3=C` attempt to persist a null second option and omit C; the non-null DB column can fail after the poll and first option have already been written. Option values are not validated as strings. No transaction wraps the poll and options. |
| B09 | `BuildingController.php:352-394` / vote; `app/Models/Poll.php` / isExpired | **Known expiry gap; additional missing-role detail.** vote never checks the poll deadline. It uses all matching roles, including inactive/ended ones. An invalid option ID dereferences null; an authenticated nonmember with a valid option has an empty role loop and reaches `compact` with undefined message/status/page. |
| B10 | `BuildingController.php:572-581` / buildingCharge | **Additional.** Unpadded date strings are compared lexicographically, so `1405-10-1 < 1405-2-1` evaluates true. Valid intervals can be rejected and reversed ones accepted. Individual day/month ranges also admit invalid Jalali dates such as month 12/day 31. |
| B11 | `BuildingController.php:608-638` / buildingChargePayment | **Additional qualification.** Duplicate detection is scoped to the current Jalali year/month of payment creation for the same charge, not to the lifetime `(role_id, monthly_charge_id)` pair. The same charge can be paid in later months, apparently supporting recurring charges. A future lifetime unique constraint would change this behavior and needs a period model/explicit decision. Payment date gating also compares a stored date string with a Jalalian object rather than consistently parsed dates. |
| B12 | `BuildingController.php:704-771` / payments | **Additional.** It selects only the first role for each unit without active/current-user criteria, then reports payments for that role alone. When a unit has historical/other role rows, valid payments attached to the others are omitted or attributed through an arbitrary role. |
| B13 | `AdminController.php:33-81` / accept, reject | **Additional to baseline log; already anticipated by brief.** accept creates complex, manager role and group sequentially without a transaction or pending-status guard. Repeating acceptance creates duplicate complexes; a mid-flow error leaves partial data. reject can overwrite an accepted request without reversing those records. |
| B14 | `UserController.php:144-245` / update, addUnit | **Additional.** Unit number, floor, zip code and complex ID have no validation. Negative/empty/out-of-range values can be stored; addUnit can save a new unit before discovering that no valid membership exists. There is no transaction for unit plus role creation. update refuses a no-op unit-number edit because its duplicate query finds the current unit itself. |
| B15 | `BuildingController.php:412-490` / incidentalCost; `UserController.php:94-139` / register | **Additional.** Cost total/share accept negative numbers and do not constrain scale/range to decimal(10,2). Request/building names and addresses have no max matching varchar(255); cost title/explanation likewise have no matching max for their string columns. MySQL can reject values that SQLite accepts. |
| B16 | `BuildingController.php:102-104,704-771`; `GroupController.php:271-295`; `BuildingController.php:396-410` | **Known, corrected mechanism.** Each poll lazy-loads pollOptions (one query per poll). Payments performs role/payment queries per unit plus User::findOrFail per payment. Last-seen loads all seen rows and queries messages until a survivor is found. Vote results load/count full vote rows in PHP. Dashboard resident user/unit scans are on already-loaded collections, so those nested scans cost CPU, not one SQL query per resident. |
| B17 | `BuildingController.php`; `GroupController.php` / message payloads and uploads | **Known, measured correction.** BuildingController is 771 lines with 13 non-constructor actions. GroupController has 7 message payload arrays. The sha1 upload naming/storage sequence appears 3 times (notification, incidental invoice, chat), not 6. Changes to payloads or upload rules require repeated edits. |
| B18 | `ProfileController.php` / update; `ProfileUpdateRequest.php` / rules; `AdminController.php` / create,store,show,edit,update,destroy; `GroupController.php` / create,show,update; `resources/js/bootstrap.js` | **Known.** Superseded profile implementation and Echo initialization remain commented out; empty scaffold controller methods are unrouted stubs. The active frontend loads Pusher separately, so the installed Echo package is not used by the shipped chat. |
| B19 | `app/Providers/RouteServiceProvider.php:20`; Auth controllers / store, verification/reset redirections | **Additional.** HOME is `/`, but login and registration explicitly choose `/dashboard` or `/adminPanel`; password/verification helpers still use HOME. This divergence explains the two additional failing tests and produces inconsistent post-authentication destinations. |
| B20 | `routes/web.php:73`, `routes/auth.php` / logout route declarations | **Additional.** GET and POST logout both have the name `logout`. `php artisan route:cache` fails with a LogicException because that name has already been assigned; normal uncached routing still works. The failed cache operation was followed by route:clear. |
| B21 | `app/Models/ChargePayment.php` / fillable | **Additional latent mismatch.** `date` is mass assignable, but charge_payments has no date column; a caller assigning it would issue SQL for a nonexistent column. Current payment handlers use created_at and do not set it. |

### Database

| ID | Location / function | Concrete finding |
| --- | --- | --- |
| D01 | `database/migrations/2023_07_01_191954_create_votes_table.php`, `2023_07_25_150330_create_seen_messages_table.php`, `2023_07_01_191600_create_units_table.php`, `2023_07_01_191822_create_charge_payments_table.php`, `2023_07_01_191854_create_payment_additional_costs_table.php`, `2023_07_01_191644_create_user_roles_table.php` / up | **Known.** Business uniqueness is absent for vote `(poll_id,role_id)`, seen `(message_id,user_id)`, unit `(complex_id,unit_number)`, cost payment `(role_id,incidental_costs_id)` and role assignments. Payment existence checks race. Charge payments also lack a DB uniqueness rule, but a lifetime pair constraint conflicts with B11; preserve/decide recurrence before choosing its key. The database does have primary keys and unique email/join_link/token/uuid constraints. |
| D02 | `2023_07_01_191917_create_messages_table.php`, `2023_07_25_150330_create_seen_messages_table.php`, `2023_07_01_191644_create_user_roles_table.php` / up | **Known missing lookup indexes.** No explicit composite indexes support messages `(group_id,id)`, seen_messages `(group_id,user_id)` and user_roles `(user_id,complex_id)`. MySQL may create individual foreign-key indexes, which are not the same composite lookup definitions; SQLite does not automatically index FK child columns. |
| D03 | `2023_07_01_191644_create_user_roles_table.php:22` / up | **Additional.** start_date's SQL default is the literal date evaluated by `Date::now()` when migration runs, rather than each insert's date. Controllers omit start_date when joining/approving, so future memberships inherit the migration day's start date. |
| D04 | `2023_07_01_191807_create_monthly_charges_table.php` / date columns; `app/Models/{MonthlyCharge,UserRole,Poll}.php`; `BuildingController::buildingCharge` | **Known mixed-date issue, concrete storage.** Monthly charge dates are Jalali year strings in SQL date columns without model casts, role dates use Gregorian date casts, poll dates are Gregorian datetimes, and created_at/updated_at are framework timestamps. Charge input is not zero-padded; SQL comparisons assume sortable values. The formats are undocumented and cross-driver behavior differs. |
| D05 | `2023_07_01_191954_create_votes_table.php`; `2023_07_25_150330_create_seen_messages_table.php`; `2023_07_01_191644_create_user_roles_table.php` / foreign keys | **Additional.** Independent FKs do not ensure that a selected option belongs to vote.poll_id, that seen.message_id belongs to seen.group_id, or that role.unit_id belongs to role.complex_id. Application writers must enforce these compound invariants; the current raw-ID payment paths have a similar complex/payable mismatch (S07). |
| D06 | `2023_07_01_191600_create_units_table.php`, `2023_07_01_191836_create_incidental_costs_table.php`, payment migrations / up | **Additional.** Unit/floor numbers and money fields have no CHECK for positive/nonnegative values; nullable unit_number also allows unidentified units. Alongside missing request validation (B14/B15), invalid business values reach storage. |
| D07 | `2023_07_01_191512_create_complexes_table.php` / up; all migrations | **Known missing capabilities.** Complex location is nullable text only: no coordinates. No transaction or SMS log tables exist. Payment/SMS drivers, feature flags and map UI are absent; none were introduced in this audit. |

### Security

| ID | Location / function | Concrete finding |
| --- | --- | --- |
| S01 | `app/Http/Middleware/AccessMiddleware.php:25` / handle; `BuildingController::__construct` | **Known; additional inactive-role detail.** Access checks only user ID and a global role, ignoring target complex, active and end_date. A manager in one complex passes middleware for another; even an inactive manager/admin row passes. Building index admits authenticated nonmembers (an empty role list passes hasNullUnit), exposing residents and building content. |
| S02 | `BuildingController::__construct`, pollCreate, voteResult, payments | **Additional route-specific detail.** pollCreate is missing from the manager-only middleware list, so any authenticated user can create a poll in an arbitrary complex. Results and payment history take unscoped IDs and permit cross-complex reads. Hiding controls in Blade is not authorization. |
| S03 | `GroupController` / all routed actions; three `app/Events/*ChatMessage.php` / broadcastOn; `public/js/building.js:1466-1495`; `routes/channels.php` | **Known.** Chat fetch/send/seen/viewers lack membership checks. All events broadcast on public `group`, and JS subscribes globally without filtering a complex/group; new-message payloads omit group identity entirely. The hardcoded public application key/cluster bypass env configuration. Owner checks do exist for edit/delete; they do not authorize membership. The only channel callback is the default user channel. |
| S04 | `routes/web.php` / GET group destroy, upgradeRole, downgradeRole, cancelNotification, admin accept/reject, logout | **Known.** Seven mutation routes use GET (plus implicit HEAD), outside normal CSRF enforcement for mutating methods. GET logout is outside the auth route group; POST logout also exists. A cross-site/navigation request can trigger the GET mutations in an authenticated browser. |
| S05 | `GroupController.php:138-176` / store | **Known.** Uploaded chat files have no mime/size validation; complexId and message text have no validation, and a blank message quietly succeeds without a validation error. Files are written under publicly linked storage. Other announcement/cost uploads do restrict images to 5 MB. |
| S06 | `AdminController.php:42` / accept; `UserController::store` | **Known.** uniqid creates a time-derived join token, and no regeneration action exists. An authenticated user who guesses/obtains it can create a resident role. In addition, S08 bypasses the token flow completely. |
| S07 | `BuildingController::incidentalCostPayment`, buildingChargePayment | **Known amount defect; additional reference-scoping defect.** Both persist request Amount instead of the payable's amount. Ownership is checked for a unit/role in request complexId, but incidentalCostId/monthlyChargeId is never required to belong to that same complex; users can attach their role to another complex's payable. Neither role query restricts active memberships. These are record-only payments with no verified gateway transaction. |
| S08 | `UserController::update`, addUnit | **Additional exploit path.** update selects units solely by client complex ID/number, so any authenticated user can change another complex's unit. addUnit never requires existing membership; a user can supply another complex ID and an available unit and create a resident role without the join token or approval. |
| S09 | `public/js/building.js:1220-1228` / viewers rendering; registration/profile / names | **Known.** User-controlled viewer.user_name is interpolated into innerHTML. Registration allows arbitrary name strings, so stored markup can execute when another user opens the viewers list. Main message text/name rendering uses textContent and is not this sink. |
| S10 | `app/Models/User.php` / fillable | **Known in brief, absent as a standalone baseline log entry.** remember_token is mass assignable. Current registration/profile handlers do not bulk-copy it, but model callers can overwrite a persistent-login credential through mass assignment. |
| S11 | `composer.lock`, `package-lock.json` / locked dependencies | **Additional.** Read-only dependency audits report 61 advisories across 17 Composer packages and 17 affected npm packages (1 critical, 11 high, 5 moderate). Versions and individual advisory references are recorded below. A dependency advisory is not proof that the application reaches its vulnerable path. No update/audit-fix command was run. |

### Tests

| ID | Location / function | Concrete finding |
| --- | --- | --- |
| T01 | `tests/Feature/ProfileTest.php` / all five tests; `routes/web.php:67-71` | **Known, confirmed.** GET/DELETE /profile declarations are commented out; PATCH /profile also does not exist because the active update route is PATCH /dashboard/update named profile.update. All five tests fail on the dead URL; the fifth observes missing validation errors because no deletion handler runs. None were fixed/quarantined. |
| T02 | `tests/Feature/Auth/{AuthenticationTest,RegistrationTest}.php` / successful login, new registration; `RouteServiceProvider::HOME` | **Additional.** Both expect HOME (`/`) while their controllers redirect nonadmins to `/dashboard`. Authentication succeeds; only the redirect assertions fail. |
| T03 | `phpunit.xml:24-25`; `tests/Pest.php` / Feature binding | **Known test setup gap.** SQLite/in-memory configuration is commented out, while every Feature test uses RefreshDatabase. An ordinary test run inherits the development DB from .env and can rebuild it. This audit supplied DB_CONNECTION=sqlite and DB_DATABASE=:memory: at process level; no test configuration file was changed. |
| T04 | Entire `tests/` inventory; `database/factories/UserFactory.php`; `database/seeders/DatabaseSeeder.php` / run | **Known, precise scope.** There are 24 default auth/profile/example tests in 9 test files; no building/admin/unit/charge/cost/poll/chat authorization, race, upload or payment tests. Only User has a factory. DatabaseSeeder exists but its run method only contains commented examples and creates no data. No browser test configuration, JS test script, or composer test script exists. |
| T05 | `tests/Feature/Auth/PasswordResetTest.php` / password can be reset with valid token; `PasswordUpdateTest.php` / from('/profile') | **Additional coverage limitation.** The reset test checks response/session errors but never asserts the password changed or authentication with it. Password update tests pass while redirecting back to nonexistent `/profile`, so a green redirect assertion does not establish a usable destination. |

### Docs

| ID | Location | Concrete finding |
| --- | --- | --- |
| O01 | `README.md` / entire document | **Known.** It is the Laravel skeleton README: no project installation, SQLite/test isolation, storage link, real-time setup, domain roles, or demo data instructions. The default .env uses log broadcasting while shipped chat listens to an external public channel; this configuration mismatch is not explained. |
| O02 | Supplied baseline log / dark mode, responsive foundation, duplication, N+1, tests | **Additional corrections.** Existing dark mode is partial, not absent; dashboard has one Tailwind CDN, building three; width examples are maxima/breakpoints, not fixed widths; CSRF block count is 7, upload blocks 3; resident scans operate on collections; a stub seeder exists; baseline has 7 failing tests, including the two redirect assertions. The original supplied text is preserved and the qualifications are recorded here and in the single Phase 0 entry. |
| O03 | `app/Http/Controllers/{Building,Group,User,Admin}Controller.php`, model relations, `resources/js/bootstrap.js` | **Known documentation debt, specific examples.** Public domain actions such as pollCreate/payments/seen lack PHPDoc describing accepted IDs, tenant ownership and response contracts; models do not document stored date formats; bootstrap comments describe Echo while its initialization is commented out. `ProfileController::update` retains an entire superseded implementation after its return. |
| O04 | Existing repository documentation inventory | **Known.** No architecture/domain-role matrix, data format guide, operational payment/SMS configuration guide or project changelog existed before this phase. Future provider choice is intentionally open; this phase adds neither integrations nor enabled flags. |

## Qualifications to the supplied baseline

The confirmed security findings remain open. These corrections matter for later planning:

1. **Dark mode exists partially.** Both app headers/scripts support a cookie-backed class toggle and custom dark CSS. The missing work is consistent theme coverage, system preference, pre-paint application and matching Tailwind strategy.
2. **Actual dependency/layout scope differs.** Three Tailwind sources occur on building, one on dashboard, and a different CDN version on admin. 1800/480 are maximum widths; 520 is a media breakpoint. No viewport screenshots were taken, so exact overflow claims remain unverified.
3. **Duplication counts:** 7 CSRF reads + 7 header references are 7 blocks, not 14; there are 3 sha1 upload blocks, not 6. The 1,495/771/1,028 source line counts and 7 message arrays match.
4. **Dashboard query explanation:** resident user/unit scans use already-loaded collections; poll options are the actual per-poll lazy query. Payments and last-seen N+1 patterns are confirmed.
5. **Test baseline:** all five ProfileTest cases fail, and two auth redirect tests also fail. DatabaseSeeder exists as a no-op stub; “no seeders” means no working domain/demo seeder.
6. **Charge duplicate protection is monthly.** It does not permanently block another payment for a charge. A lifetime unique pair proposed for a later phase would remove existing cross-month recurrence; no such constraint was added here.
7. **Model relations:** the two named in the baseline are real, but B02-B04 identify four additional broken relation methods. ChargePayment::MonthlyCharge was inspected and correctly infers monthly_charge_id despite its capitalization.
8. **Uniqueness wording:** business composite constraints are missing, not all uniqueness. Existing email, join_link, access-token and failed-job UUID constraints remain present.
9. **Existing UI states:** there are some empty states, inline alerts, and a chat toast helper. The gap is consistent coverage/error recovery; complete absence would overstate the source evidence.

## Scope and limitations

Only `docs/REFACTOR_AUDIT.md` and `docs/REFACTOR_LOG.md` are committed. No application source, route, migration, test, configuration, package manifest, or lockfile is edited. Local .env, app key, installed dependencies, SQLite file and build/cache artifacts are ignored runtime setup. The supplied log was copied byte-for-byte before appending exactly one Phase 0 entry. The master brief stays outside the repository.

This is a static source audit with existing-suite execution, fresh SQLite migration, frontend build, syntax checks, route-cache check, dependency metadata audits and targeted in-memory runtime probes. It is not a claim of exhaustive penetration testing, measured production query latency, MySQL migration verification, or visual/browser/device QA. No later phase, feature, integration, screenshot tooling or application fix was started.


## Dependency advisory snapshot

Read-only commands: `php ../runtime/composer.phar audit --locked --format=json` and `npm.cmd audit --json`. Advisory metadata was downloaded during this audit and changes over time. These are scanner-reported version matches; application exploitability was not established. Composer reports no abandoned package in this snapshot. No vulnerable package version was changed.

### Composer (61 advisory records in 17 packages)

Each row identifies the locked package/version and the advisory ID, severity (when supplied), and source link.

| Locked package/version | Advisory | Severity | Title / reference |
| --- | --- | --- | --- |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-gcrk-3vtt-1r14 | high | [Guzzle: Noncanonical host can bypass host-based checks](https://github.com/advisories/GHSA-v5mv-p594-2x33) |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-cnw1-2ytm-cgr8 | medium | [Guzzle: Noncanonical cookie domain keeps subdomain scope](https://github.com/advisories/GHSA-f7vp-7xgx-4w4r) |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-fy2t-3c5f-827y | medium | [Guzzle: URI fragments disclosed in redirect Referer headers](https://github.com/advisories/GHSA-h95v-h523-3mw8) |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-qxvb-2bpp-dnk6 | medium | [Guzzle: Host-only cookie scope is not preserved](https://github.com/advisories/GHSA-wm3w-8rrp-j577) |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-bbs6-q5q9-f3t4 | medium | [Guzzle: Unbounded response cookies risk denial of service](https://github.com/advisories/GHSA-f283-ghqc-fg79) |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-bcdd-5xc7-gwfb | medium | [Guzzle: Cookie Disclosure and Injection via IP-Address Domains](https://github.com/advisories/GHSA-g446-98w2-8p5w) |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-pwsk-hy21-4gby | medium | [Guzzle: Proxy-Authorization headers can be sent to origin servers](https://github.com/advisories/GHSA-94pj-82f3-465w) |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-93qv-9n9h-6k6p | medium | [Dot-only cookie domains match all hosts](https://github.com/guzzle/guzzle/security/advisories/GHSA-cwxw-98qj-8qjx) |
| `guzzlehttp/guzzle` 7.7.0 | PKSA-k22t-f949-t9g6 | medium | [Silent HTTPS proxy downgrade to cleartext](https://github.com/guzzle/guzzle/security/advisories/GHSA-wpwq-4j6v-78m3) |
| `guzzlehttp/psr7` 2.5.0 | PKSA-vznr-tgp9-fd7d | medium | [guzzlehttp/psr7: Host Confusion via Weak URI Host Validation](https://github.com/advisories/GHSA-c2w2-prh8-qm98) |
| `guzzlehttp/psr7` 2.5.0 | PKSA-7qs6-zvnz-h66r | medium | [CRLF injection in HTTP start-line serialization](https://github.com/guzzle/psr7/security/advisories/GHSA-vm85-hxw5-5432) |
| `guzzlehttp/psr7` 2.5.0 | PKSA-gm5x-j3mz-71n9 | medium | [CRLF injection via URI host component](https://github.com/guzzle/psr7/security/advisories/GHSA-hq7v-mx3g-29hw) |
| `guzzlehttp/psr7` 2.5.0 | PKSA-jj5t-2zs1-dcfm | medium | [Host confusion via authority reinterpretation](https://github.com/guzzle/psr7/security/advisories/GHSA-34xg-wgjx-8xph) |
| `laravel/framework` v10.14.1 | PKSA-b41t-wbzk-9q51 | critical | [Laravel TokenGuard authentication bypass through non-string API credentials](https://github.com/advisories/GHSA-f9x3-7vq9-cph6) |
| `laravel/framework` v10.14.1 | PKSA-d5tc-s1qs-h781 | low | [Laravel: XSS in Debug Page Information](https://github.com/advisories/GHSA-jh5r-qr3c-85q8) |
| `laravel/framework` v10.14.1 | PKSA-m5cs-t1y6-qpcs | medium | [Laravel Framework: Temporary Signed URL Path Confusion](https://github.com/advisories/GHSA-crmm-hgp2-wgrp) |
| `laravel/framework` v10.14.1 | PKSA-3r5d-mb8f-1qw9 | high | [Laravel Framework: CRLF injection in default email rule ](https://github.com/advisories/GHSA-5vg9-5847-vvmq) |
| `laravel/framework` v10.14.1 | PKSA-mdq4-51ck-6kdq | not supplied | [Laravel CRLF injection in default email rule](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq) |
| `laravel/framework` v10.14.1 | PKSA-8qx3-n5y5-vvnd | medium | [Laravel has a File Validation Bypass](https://github.com/advisories/GHSA-78fx-h6xr-vch4) |
| `laravel/framework` v10.14.1 | PKSA-w7xr-vk7n-rstm | high | [Laravel environment manipulation via query string](https://github.com/advisories/GHSA-gv7v-rgg6-548h) |
| `league/commonmark` 2.4.0 | PKSA-m2dq-1fhr-29b1 | medium | [league/commonmark: DisallowedRawHtml bypassed when a disallowed tag name ends the raw-HTML literal](https://github.com/advisories/GHSA-97jj-33gv-5xf9) |
| `league/commonmark` 2.4.0 | PKSA-m4t9-vsgq-8khn | high | [league/commonmark: Quadratic-time denial of service in the GitHub Flavored Markdown Table extension block-start scan](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8) |
| `league/commonmark` 2.4.0 | PKSA-zyf5-hrxv-hrd7 | high | [league/commonmark: Denial of service via distinctly-named attributes in the Attributes extension](https://github.com/advisories/GHSA-8rr7-cvq3-gmfh) |
| `league/commonmark` 2.4.0 | PKSA-nv44-1b4d-6gjg | high | [league/commonmark: Denial of service in the SmartPunct and Attributes extensions](https://github.com/advisories/GHSA-jjv6-8j6v-6j52) |
| `league/commonmark` 2.4.0 | PKSA-9q1p-3s19-bp1q | high | [league/commonmark: Denial of service via crafted code fences, reference links, and emphasis delimiters](https://github.com/advisories/GHSA-j8pm-gj4c-rq4x) |
| `league/commonmark` 2.4.0 | PKSA-5mzr-szzf-z6cn | medium | [league/commonmark: Denial of service via deeply nested XML output](https://github.com/advisories/GHSA-mj63-m3rc-8ppr) |
| `league/commonmark` 2.4.0 | PKSA-cqd6-fg4n-nxpf | high | [league/commonmark: Denial of service via colliding heading slugs](https://github.com/advisories/GHSA-mh25-x5hq-wrqp) |
| `league/commonmark` 2.4.0 | PKSA-1q6p-sqkj-8mmj | high | [league/commonmark:  Denial of service via duplicate footnote definitions](https://github.com/advisories/GHSA-jfm3-95jq-q3rf) |
| `league/commonmark` 2.4.0 | PKSA-mc58-w91n-f5gv | high | [league/commonmark: Denial of service via adjacent inline attribute blocks](https://github.com/advisories/GHSA-g2gp-3wwq-f4ph) |
| `league/commonmark` 2.4.0 | PKSA-t21r-vtr5-3mdz | high | [league/commonmark: Quadratic-time denial of service when parsing crafted Markdown](https://github.com/advisories/GHSA-2q4p-g7hv-5rgv) |
| `league/commonmark` 2.4.0 | PKSA-scnn-p8mm-jbft | medium | [league/commonmark: AttributesExtension href/src unsafe-link filter bypass via embedded control bytes](https://github.com/advisories/GHSA-29pj-957v-52mc) |
| `league/commonmark` 2.4.0 | PKSA-21fb-n1x5-5nf7 | medium | [league/commonmark has an embed extension allowed_domains bypass](https://github.com/advisories/GHSA-hh8v-hgvp-g3f5) |
| `league/commonmark` 2.4.0 | PKSA-2cx9-ynrq-qdk3 | medium | [CommonMark has DisallowedRawHtml extension bypass via whitespace in HTML tag names](https://github.com/advisories/GHSA-4v6x-c7xx-hw9f) |
| `league/commonmark` 2.4.0 | PKSA-rqc2-tcc6-nc79 | medium | [league/commonmark contains a XSS vulnerability in Attributes extension](https://github.com/advisories/GHSA-3527-qv2q-pfvx) |
| `league/commonmark` 2.4.0 | PKSA-fndg-qryc-dyc9 | high | [league/commonmark's quadratic complexity bugs may lead to a denial of service](https://github.com/advisories/GHSA-c2pc-g5qf-rfrf) |
| `league/flysystem` 3.15.1 | PKSA-w9tt-7782-78jx | low | [Flysystem: WhitespacePathNormalizer's control-character (CorruptedPathDetected) check is bypassed by malformed UTF-8 in the path, affecting every adapter](https://github.com/advisories/GHSA-cxf4-7mrp-vvpr) |
| `nesbot/carbon` 2.68.1 | PKSA-csyb-yc4p-mnbs | medium | [Carbon has an arbitrary file include via unvalidated input passed to Carbon::setLocale](https://github.com/advisories/GHSA-j3f9-p6hm-5w6q) |
| `paragonie/sodium_compat` v1.20.0 | PKSA-32g2-byr9-drtw | not supplied | [Incorrect Ed25519 public key validation](https://github.com/paragonie/sodium_compat/pull/206) |
| `paragonie/sodium_compat` v1.20.0 | PKSA-bjyb-w14t-61js | medium | [libsodium has Incomplete List of Disallowed Inputs](https://github.com/advisories/GHSA-mrfv-m5wm-5w6w) |
| `paragonie/sodium_compat` v1.20.0 | PKSA-8x19-j2j3-bn67 | not supplied | [Missing check that a point is on the prime subgroup for Edwards25519](https://00f.net/2025/12/30/libsodium-vulnerability) |
| `phpunit/phpunit` 10.2.3 | PKSA-z3gr-8qht-p93v | high | [Unsafe Deserialization in PHPT Code Coverage Handling](https://github.com/sebastianbergmann/phpunit/security/advisories/GHSA-vvj3-c3rp-c85p) |
| `psy/psysh` v0.11.18 | PKSA-4s4z-t146-6123 | medium | [PsySH has Local Privilege Escalation via CWD .psysh.php auto-load](https://github.com/advisories/GHSA-4486-gxhx-5mg7) |
| `symfony/http-foundation` v6.3.1 | PKSA-24ph-xkrh-y79j | not supplied | [CVE-2026-118514: HEAD Requests Bypass the methods Option of security.access_control Rules and Firewalls: Incomplete Fix for CVE-2026-45075](https://symfony.com/cve-2026-118514) |
| `symfony/http-foundation` v6.3.1 | PKSA-b2gg-vk1g-6d5q | not supplied | [Generated URLs Can Point to a Host Chosen by the Client](https://github.com/symfony/symfony/security/advisories/GHSA-5mx8-mwrp-5693) |
| `symfony/http-foundation` v6.3.1 | PKSA-xmwy-rmx5-m7h4 | high | [CVE-2026-108082: ReDoS in HeaderUtils::split() Reachable Through the Cache-Control Request Header](https://symfony.com/cve-2026-108082) |
| `symfony/http-foundation` v6.3.1 | PKSA-365x-2zjk-pt47 | high | [CVE-2025-64500: Incorrect parsing of PATH_INFO can lead to limited authorization bypass](https://symfony.com/blog/cve-2025-64500-incorrect-parsing-of-path-info-can-lead-to-limited-authorization-bypass) |
| `symfony/http-foundation` v6.3.1 | PKSA-b35n-565h-rs4q | low | [CVE-2024-50345: Open redirect via browser-sanitized URLs](https://symfony.com/cve-2024-50345) |
| `symfony/http-kernel` v6.3.1 | PKSA-3gxv-7xhq-mgmz | not supplied | [Inline Fragments Let Untrusted Forwarded Headers Set the Host, Scheme and Port of the URLs Generated for the Main Request](https://github.com/symfony/symfony/security/advisories/GHSA-wg5q-m2ww-f3c9) |
| `symfony/mailer` v6.3.0 | PKSA-28rh-rzzn-djk4 | medium | [CVE-2026-45068: Argument Injection in SendmailTransport via Dash-Prefixed Recipient Address](https://symfony.com/cve-2026-45068) |
| `symfony/mime` v6.3.0 | PKSA-wtxr-p26d-nn42 | medium | [CVE-2026-45070: Email Header Injection via Non-Token Characters in Mime Parameter Names](https://symfony.com/cve-2026-45070) |
| `symfony/mime` v6.3.0 | PKSA-2n2k-66v2-bwg3 | high | [CVE-2026-45067: Email Header / SMTP Command Injection via CRLF in Symfony\Component\Mime\Address](https://symfony.com/cve-2026-45067) |
| `symfony/polyfill-intl-idn` v1.27.0 | PKSA-dwsq-ppd2-mb1x | low | [CVE-2026-46644: symfony/polyfill-intl-idn accepts xn-- labels whose Punycode payload decodes to ASCII-only: insecure equivalence](https://symfony.com/cve-2026-46644) |
| `symfony/process` v6.3.0 | PKSA-wws7-mr54-jsny | high | [CVE-2024-51736: Command execution hijack on Windows with Process class](https://symfony.com/cve-2024-51736) |
| `symfony/routing` v6.3.1 | PKSA-y8c5-1b7f-r1gj | not supplied | [CVE-2026-118516: Host Placeholders Can Match the Start of the Path in Compiled URL Matchers, Bypassing Path-Based Access Control](https://symfony.com/cve-2026-118516) |
| `symfony/routing` v6.3.1 | PKSA-bf7t-jnpz-492k | medium | [CVE-2026-48784: UrlGenerator Dot-Segment Encoding Skips Every Other Chained `../` or `./` → Generated URL Collapses Off-Route Under RFC 3986 Normalization](https://symfony.com/cve-2026-48784) |
| `symfony/routing` v6.3.1 | PKSA-yc7t-91v9-99xs | medium | [CVE-2026-45065: UrlGenerator Route-Requirement Bypass via Unanchored Regex Alternation → Off-Site //host URL Injection](https://symfony.com/cve-2026-45065) |
| `symfony/yaml` v6.3.0 | PKSA-nktx-f1n1-b4yd | not supplied | [Exponential and Quadratic Parse Times in the YAML Parser Let a Single Document Hold a Process](https://github.com/symfony/symfony/security/advisories/GHSA-hwfg-qwvh-2wfx) |
| `symfony/yaml` v6.3.0 | PKSA-xz3r-9gp4-4m1z | high | [CVE-2026-109413: Unbounded Recursion in the YAML Inline Lexer Lets a Single Document Crash the Process](https://symfony.com/cve-2026-109413) |
| `symfony/yaml` v6.3.0 | PKSA-v5yj-8nmz-sk2q | low | [CVE-2026-45304: YAML Parser Exponential Memory Allocation via Recursive Collection-Alias Expansion ("Billion Laughs")](https://symfony.com/cve-2026-45304) |
| `symfony/yaml` v6.3.0 | PKSA-ft77-7h5f-p3r6 | low | [CVE-2026-45305: YAML Parser ReDoS via Catastrophic Backtracking in Parser::cleanup() Regex](https://symfony.com/cve-2026-45305) |
| `symfony/yaml` v6.3.0 | PKSA-b14r-zh1d-vdrc | low | [CVE-2026-45133: YAML Parser Stack Exhaustion via Unbounded Recursion in Nested Blocks, Sequences, and Mappings](https://symfony.com/cve-2026-45133) |

### npm affected packages

Counts below are affected package entries (some caused by vulnerable transitive dependencies), not a count of distinct advisory IDs.

| Package | Locked version(s) | Severity | Advisory sources / affected dependencies |
| --- | --- | --- | --- |
| `axios` | 1.4.0 | high | [Axios Cross-Site Request Forgery Vulnerability](https://github.com/advisories/GHSA-wf5p-g6vw-rhxx); [Server-Side Request Forgery in axios](https://github.com/advisories/GHSA-8hc4-vh64-cxmj); [axios Requests Vulnerable To Possible SSRF and Credential Leakage via Absolute URL](https://github.com/advisories/GHSA-jr5f-v2jv-69x6); [Axios is vulnerable to DoS attack through lack of data size check](https://github.com/advisories/GHSA-4hjh-wcwx-xvwj); [Axios has a NO_PROXY Hostname Normalization Bypass that Leads to SSRF](https://github.com/advisories/GHSA-3p68-rc4w-qgx5); [Axios: Authentication Bypass via Prototype Pollution Gadget in `validateStatus` Merge Strategy](https://github.com/advisories/GHSA-w9j2-pvgh-6h63); [Axios: Incomplete Fix for CVE-2025-62718 — NO_PROXY Protection Bypassed via RFC 1122 Loopback Subnet (127.0.0.0/8) in Axios 1.15.0](https://github.com/advisories/GHSA-pmwg-cvhr-8vh7); [Axios: Invisible JSON Response Tampering via Prototype Pollution Gadget in `parseReviver`](https://github.com/advisories/GHSA-3w6x-2g7m-8v23); [Axios: Null Byte Injection via Reverse-Encoding in AxiosURLSearchParams](https://github.com/advisories/GHSA-xhjh-pmcv-23jw); [Axios: CRLF Injection in multipart/form-data body via unsanitized blob.type in formDataToStream](https://github.com/advisories/GHSA-445q-vr5w-6q77); [Axios: no_proxy bypass via IP alias allows SSRF](https://github.com/advisories/GHSA-m7pr-hjqh-92cm); [Axios' HTTP adapter-streamed uploads bypass maxBodyLength when maxRedirects: 0](https://github.com/advisories/GHSA-5c9x-8gcm-mpgx); [Axios: HTTP adapter streamed responses bypass maxContentLength](https://github.com/advisories/GHSA-vf2m-468p-8v99); [Axios: Prototype Pollution Gadgets - Response Tampering, Data Exfiltration, and Request Hijacking](https://github.com/advisories/GHSA-pf86-5x62-jrwf); [Axios: Header Injection via Prototype Pollution](https://github.com/advisories/GHSA-6chq-wfr3-2hj9); [Axios: XSRF Token Cross-Origin Leakage via Prototype Pollution Gadget in `withXSRFToken` Boolean Coercion](https://github.com/advisories/GHSA-xx6v-rp6x-q39c); [Axios is Vulnerable to Denial of Service via __proto__ Key in mergeConfig](https://github.com/advisories/GHSA-43fc-jf86-j433); [Axios has prototype pollution read-side gadgets in HTTP adapter that allow credential injection and request hijacking](https://github.com/advisories/GHSA-q8qp-cvcw-x6jj); [Axios has Unrestricted Cloud Metadata Exfiltration via Header Injection Chain](https://github.com/advisories/GHSA-fvcv-3m26-pcqx); [Axios: unbounded recursion in toFormData causes DoS via deeply nested request data](https://github.com/advisories/GHSA-62hf-57xw-28j9); [Axios: Regular Expression Denial of Service (ReDoS) via Cookie Name Injection](https://github.com/advisories/GHSA-hfxv-24rg-xrqf); [Axios: Proxy-Authorization Credential Leak to Origin Server Across HTTP-to-HTTPS Redirect in Axios Node.js HTTP Adapter](https://github.com/advisories/GHSA-p92q-9vqr-4j8v); [Axios: Proxy-Authorization header leaks to redirect target when proxy is re-evaluated to direct connection](https://github.com/advisories/GHSA-j5f8-grm9-p9fc); [axios Vulnerable to Credential Theft and Response Hijacking via Prototype Pollution Gadget in Config Merge](https://github.com/advisories/GHSA-3g43-6gmg-66jw); [axios Vulnerable to Full Man-in-the-Middle via Prototype Pollution Gadget in `config.proxy`](https://github.com/advisories/GHSA-35jp-ww65-95wh); [axios has DoS & Header Injection via Prototype Pollution Read-Side Gadgets in axios merge functions](https://github.com/advisories/GHSA-898c-q2cr-xwhg); [Axios: Prototype pollution gadgets can alter axios request construction](https://github.com/advisories/GHSA-mmx7-hfxf-jppx); [Axios: Deep formToJSON Key Recursion Can Cause Denial of Service](https://github.com/advisories/GHSA-pmv8-rq9r-6j72); [Axios: Nested axios option objects can consume polluted prototype values](https://github.com/advisories/GHSA-7q8q-rj6j-mhjq); [Axios: Excessive recursion in formDataToJSON can cause denial of service](https://github.com/advisories/GHSA-42h9-826w-cgv3); [Axios: Prototype-Pollution Gadget in the Default Instance Allows Inherited Object.prototype.method to Override HTTP Method](https://github.com/advisories/GHSA-9fr6-4gfg-395g); [Axios: Header Injection via Inherited headers After Minimal Interceptor](https://github.com/advisories/GHSA-j8rh-479h-cp32) |
| `brace-expansion` | 1.1.11 | high | [brace-expansion Regular Expression Denial of Service vulnerability](https://github.com/advisories/GHSA-v6h2-p8h4-qcjw); [brace-expansion: Zero-step sequence causes process hang and memory exhaustion](https://github.com/advisories/GHSA-f886-m6hf-6m8v); [brace-expansion: DoS via exponential-time expansion of consecutive non-expanding {} groups](https://github.com/advisories/GHSA-3jxr-9vmj-r5cp); [brace-expansion: DoS via unbounded expansion length causing an out-of-memory process crash](https://github.com/advisories/GHSA-mh99-v99m-4gvg); [brace-expansion: DoS via unbounded intermediate arrays, bypassing the CVE-2026-14257 mitigation](https://github.com/advisories/GHSA-rgw5-rvv9-x895); [brace-expansion: Quadratic-time expansion of the `{a},b}` rewrite causes CPU denial of service](https://github.com/advisories/GHSA-q2hr-2g5m-vwhr); [brace-expansion: DoS via uncontrolled recursion on nested brace groups causing stack exhaustion](https://github.com/advisories/GHSA-qhr7-859c-m2p7); [brace-expansion: DoS via uncontrolled recursion in parseCommaParts causing stack exhaustion](https://github.com/advisories/GHSA-6j4f-fj2g-mc7p) |
| `braces` | 3.0.2 | high | [Uncontrolled resource consumption in braces](https://github.com/advisories/GHSA-grv7-fg5c-xmjg) |
| `browserslist` | 4.21.9 | high | [Browserslist: Unbounded memory growth (no cache eviction) via distinct query results, leading to eventual OOM](https://github.com/advisories/GHSA-c83g-rgw3-j3cx); [Browserslist: Uncaught crash / prototype write via untrusted browserslist-stats.json custom stats (normalizeStats)](https://github.com/advisories/GHSA-73wf-gq98-2v4g) |
| `esbuild` | 0.17.19 | moderate | [esbuild enables any website to send any requests to the development server and read the response](https://github.com/advisories/GHSA-67mh-4wv8-2f99) |
| `follow-redirects` | 1.15.2 | moderate | [follow-redirects' Proxy-Authorization header kept across hosts](https://github.com/advisories/GHSA-cxjh-pqwp-8mfp); [Follow Redirects improperly handles URLs in the url.parse() function](https://github.com/advisories/GHSA-jchw-25xp-jwwc); [follow-redirects leaks Custom Authentication Headers to Cross-Domain Redirect Targets](https://github.com/advisories/GHSA-r4q5-vmmm-2653) |
| `form-data` | 4.0.0 | critical | [form-data uses unsafe random function in form-data for choosing boundary](https://github.com/advisories/GHSA-fjxv-7rqg-78g4); [form-data: CRLF injection in form-data via unescaped multipart field names and filenames](https://github.com/advisories/GHSA-hmw2-7cc7-3qxx) |
| `micromatch` | 4.0.5 | moderate | [Regular Expression Denial of Service (ReDoS) in micromatch](https://github.com/advisories/GHSA-952p-6rrq-rcjv) |
| `minimatch` | 3.1.2 | high | [minimatch has a ReDoS via repeated wildcards with non-matching literal in pattern](https://github.com/advisories/GHSA-3ppc-4f35-3m26); [minimatch has ReDoS: matchOne() combinatorial backtracking via multiple non-adjacent GLOBSTAR segments](https://github.com/advisories/GHSA-7r86-cg39-jmmj); [minimatch ReDoS: nested *() extglobs generate catastrophically backtracking regular expressions](https://github.com/advisories/GHSA-23c5-xmqv-rm74) |
| `nanoid` | 3.3.6 | high | [Predictable results in nanoid generation when given non-integer values](https://github.com/advisories/GHSA-mwcw-c2x4-8c55); [nanoid: non-secure generators can loop indefinitely with negative size](https://github.com/advisories/GHSA-28wg-ghj8-5hjv); [nanoid: custom generators can loop indefinitely when size is zero](https://github.com/advisories/GHSA-2v37-7h3g-55p8); [nanoid: Integer Overflow or Wraparound](https://github.com/advisories/GHSA-xwg4-73v4-xw9w) |
| `picomatch` | 2.3.1 | high | [Picomatch: Method Injection in POSIX Character Classes causes incorrect Glob Matching](https://github.com/advisories/GHSA-3v7f-55p6-f55p); [Picomatch has a ReDoS vulnerability via extglob quantifiers](https://github.com/advisories/GHSA-c2c7-rcm5-vvqj) |
| `postcss` | 8.4.24 | high | [PostCSS line return parsing error](https://github.com/advisories/GHSA-7fh5-64p2-3v2j); [PostCSS has XSS via Unescaped </style> in its CSS Stringify Output](https://github.com/advisories/GHSA-qx2v-qp2m-jg93); [PostCSS: Arbitrary file read and information disclosure via attacker-controlled sourceMappingURL in CSS comments](https://github.com/advisories/GHSA-6g55-p6wh-862q); [PostCSS: incomplete fix of GHSA-6g55-p6wh-862q — attacker-controlled sourceMappingURL reads arbitrary .map files when `from` is unset](https://github.com/advisories/GHSA-fxqj-rqcc-2cmp); [PostCSS: Path Traversal in Previous Source Map Auto-Loading (sourceMappingURL) leads to Arbitrary .map File Disclosure](https://github.com/advisories/GHSA-r28c-9q8g-f849) |
| `postcss-selector-parser` | 6.0.13 | moderate | [PostCSS: Quadratic complexity in flat selector parsing allows CPU exhaustion](https://github.com/advisories/GHSA-rj75-hqrm-r3gf) |
| `rollup` | 3.26.0 | high | [DOM Clobbering Gadget found in rollup bundled scripts that leads to XSS](https://github.com/advisories/GHSA-gcx4-mw62-g8wm); [Rollup 4 has Arbitrary File Write via Path Traversal](https://github.com/advisories/GHSA-mw96-cpmx-2vgc) |
| `source-map-js` | 1.0.2 | high | [source-map-js allows event-loop denial of service through indexed source-map section offsets](https://github.com/advisories/GHSA-68fv-2mgg-jv7q) |
| `vite` | 4.3.9 | high | [Vite dev server option `server.fs.deny` can be bypassed when hosted on case-insensitive filesystem](https://github.com/advisories/GHSA-c24v-8rfc-w8vw); [Vite's `server.fs.deny` did not deny requests for patterns with directories.](https://github.com/advisories/GHSA-8jhw-289h-jh2g); [Vite DOM Clobbering gadget found in vite bundled scripts that leads to XSS](https://github.com/advisories/GHSA-64vr-g452-qvp3); [Vite's `server.fs.deny` is bypassed when using `?import&raw`](https://github.com/advisories/GHSA-9cwx-2883-4wfx); [Websites were able to send any requests to the development server and read the response in vite](https://github.com/advisories/GHSA-vg6x-rcgg-rjx6); [Vite bypasses server.fs.deny when using ?raw??](https://github.com/advisories/GHSA-x574-m823-4x7w); [Vite has an `server.fs.deny` bypass with an invalid `request-target`](https://github.com/advisories/GHSA-356w-63v5-8wf4); [Vite's server.fs.deny bypassed with /. for files under project root](https://github.com/advisories/GHSA-859w-5945-r5v3); [Vite allows server.fs.deny to be bypassed with .svg or relative paths](https://github.com/advisories/GHSA-xcj6-pq6g-qj4x); [Vite middleware may serve files starting with the same name with the public directory](https://github.com/advisories/GHSA-g4jq-h2w9-997c); [Vite's `server.fs` settings were not applied to HTML files](https://github.com/advisories/GHSA-jqfw-vq24-v9c3); [Vite has a `server.fs.deny` bypassed for `inline` and `raw` with `?import` query](https://github.com/advisories/GHSA-4r4m-qw57-chr8); [Vite Vulnerable to Path Traversal in Optimized Deps `.map` Handling](https://github.com/advisories/GHSA-4w7w-66w2-5vf9); [launch-editor vulnerable to command injection via the crafted request on Windows](https://github.com/advisories/GHSA-c27g-q93r-2cwf); [launch-editor: NTLMv2 hash disclosure via UNC path handling on Windows](https://github.com/advisories/GHSA-v6wh-96g9-6wx3); [launch-editor: escapeCmdArgs() omits % escaping on Windows](https://github.com/advisories/GHSA-698v-4r3x-3vcv); [launch-editor: Editor CLI flag misinterpretation causing arbitrary command execution for some editors](https://github.com/advisories/GHSA-r825-w8mm-5pxw); [launch-editor: `fs.existsSync()` existence check can be bypassed on Windows](https://github.com/advisories/GHSA-587h-7wmj-h5pm); [Vite: Cross origin script execution through Vite custom middleware](https://github.com/advisories/GHSA-9jrq-w75r-8gcw); [Vite: Files outside `server.fs.allow` could be served when project paths match system paths on non-Windows machine](https://github.com/advisories/GHSA-rq7h-c2jc-7f22); [vite: `server.fs.deny` bypass on Windows alternate paths](https://github.com/advisories/GHSA-fx2h-pf6j-xcff); `esbuild` |
| `yaml` | 2.3.1 | moderate | [yaml is vulnerable to Stack Overflow via deeply nested YAML collections](https://github.com/advisories/GHSA-48c2-rrv3-qjmp) |

## Complete locked Node package versions (137 entries)

This includes transitive dependencies and platform-optional binaries. `npm ci` installed the applicable Windows entries; the presence of a lock entry does not mean a binary for another OS was installed. Nested paths distinguish packages with multiple versions. The lockfile is unchanged (lockfileVersion 3).

| Lockfile package path | Version | Optional |
| --- | --- | --- |
| `node_modules/@alloc/quick-lru` | 5.2.0 | no |
| `node_modules/@esbuild/android-arm` | 0.17.19 | yes |
| `node_modules/@esbuild/android-arm64` | 0.17.19 | yes |
| `node_modules/@esbuild/android-x64` | 0.17.19 | yes |
| `node_modules/@esbuild/darwin-arm64` | 0.17.19 | yes |
| `node_modules/@esbuild/darwin-x64` | 0.17.19 | yes |
| `node_modules/@esbuild/freebsd-arm64` | 0.17.19 | yes |
| `node_modules/@esbuild/freebsd-x64` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-arm` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-arm64` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-ia32` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-loong64` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-mips64el` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-ppc64` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-riscv64` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-s390x` | 0.17.19 | yes |
| `node_modules/@esbuild/linux-x64` | 0.17.19 | yes |
| `node_modules/@esbuild/netbsd-x64` | 0.17.19 | yes |
| `node_modules/@esbuild/openbsd-x64` | 0.17.19 | yes |
| `node_modules/@esbuild/sunos-x64` | 0.17.19 | yes |
| `node_modules/@esbuild/win32-arm64` | 0.17.19 | yes |
| `node_modules/@esbuild/win32-ia32` | 0.17.19 | yes |
| `node_modules/@esbuild/win32-x64` | 0.17.19 | yes |
| `node_modules/@jridgewell/gen-mapping` | 0.3.3 | no |
| `node_modules/@jridgewell/resolve-uri` | 3.1.0 | no |
| `node_modules/@jridgewell/set-array` | 1.1.2 | no |
| `node_modules/@jridgewell/sourcemap-codec` | 1.4.15 | no |
| `node_modules/@jridgewell/trace-mapping` | 0.3.18 | no |
| `node_modules/@jridgewell/trace-mapping/node_modules/@jridgewell/sourcemap-codec` | 1.4.14 | no |
| `node_modules/@nodelib/fs.scandir` | 2.1.5 | no |
| `node_modules/@nodelib/fs.stat` | 2.0.5 | no |
| `node_modules/@nodelib/fs.walk` | 1.2.8 | no |
| `node_modules/@tailwindcss/forms` | 0.5.3 | no |
| `node_modules/@vue/reactivity` | 3.1.5 | no |
| `node_modules/@vue/shared` | 3.1.5 | no |
| `node_modules/alpinejs` | 3.12.2 | no |
| `node_modules/any-promise` | 1.3.0 | no |
| `node_modules/anymatch` | 3.1.3 | no |
| `node_modules/arg` | 5.0.2 | no |
| `node_modules/asynckit` | 0.4.0 | no |
| `node_modules/autoprefixer` | 10.4.14 | no |
| `node_modules/axios` | 1.4.0 | no |
| `node_modules/balanced-match` | 1.0.2 | no |
| `node_modules/binary-extensions` | 2.2.0 | no |
| `node_modules/brace-expansion` | 1.1.11 | no |
| `node_modules/braces` | 3.0.2 | no |
| `node_modules/browserslist` | 4.21.9 | no |
| `node_modules/camelcase-css` | 2.0.1 | no |
| `node_modules/caniuse-lite` | 1.0.30001509 | no |
| `node_modules/chokidar` | 3.5.3 | no |
| `node_modules/chokidar/node_modules/glob-parent` | 5.1.2 | no |
| `node_modules/combined-stream` | 1.0.8 | no |
| `node_modules/commander` | 4.1.1 | no |
| `node_modules/concat-map` | 0.0.1 | no |
| `node_modules/cssesc` | 3.0.0 | no |
| `node_modules/delayed-stream` | 1.0.0 | no |
| `node_modules/didyoumean` | 1.2.2 | no |
| `node_modules/dlv` | 1.1.3 | no |
| `node_modules/electron-to-chromium` | 1.4.447 | no |
| `node_modules/esbuild` | 0.17.19 | no |
| `node_modules/escalade` | 3.1.1 | no |
| `node_modules/fast-glob` | 3.3.0 | no |
| `node_modules/fast-glob/node_modules/glob-parent` | 5.1.2 | no |
| `node_modules/fastq` | 1.15.0 | no |
| `node_modules/fill-range` | 7.0.1 | no |
| `node_modules/follow-redirects` | 1.15.2 | no |
| `node_modules/form-data` | 4.0.0 | no |
| `node_modules/fraction.js` | 4.2.0 | no |
| `node_modules/fs.realpath` | 1.0.0 | no |
| `node_modules/fsevents` | 2.3.2 | yes |
| `node_modules/function-bind` | 1.1.1 | no |
| `node_modules/glob` | 7.1.6 | no |
| `node_modules/glob-parent` | 6.0.2 | no |
| `node_modules/has` | 1.0.3 | no |
| `node_modules/inflight` | 1.0.6 | no |
| `node_modules/inherits` | 2.0.4 | no |
| `node_modules/is-binary-path` | 2.1.0 | no |
| `node_modules/is-core-module` | 2.12.1 | no |
| `node_modules/is-extglob` | 2.1.1 | no |
| `node_modules/is-glob` | 4.0.3 | no |
| `node_modules/is-number` | 7.0.0 | no |
| `node_modules/jiti` | 1.18.2 | no |
| `node_modules/laravel-echo` | 1.15.2 | no |
| `node_modules/laravel-vite-plugin` | 0.7.8 | no |
| `node_modules/lilconfig` | 2.1.0 | no |
| `node_modules/lines-and-columns` | 1.2.4 | no |
| `node_modules/merge2` | 1.4.1 | no |
| `node_modules/micromatch` | 4.0.5 | no |
| `node_modules/mime-db` | 1.52.0 | no |
| `node_modules/mime-types` | 2.1.35 | no |
| `node_modules/mini-svg-data-uri` | 1.4.4 | no |
| `node_modules/minimatch` | 3.1.2 | no |
| `node_modules/mz` | 2.7.0 | no |
| `node_modules/nanoid` | 3.3.6 | no |
| `node_modules/node-releases` | 2.0.12 | no |
| `node_modules/normalize-path` | 3.0.0 | no |
| `node_modules/normalize-range` | 0.1.2 | no |
| `node_modules/object-assign` | 4.1.1 | no |
| `node_modules/object-hash` | 3.0.0 | no |
| `node_modules/once` | 1.4.0 | no |
| `node_modules/path-is-absolute` | 1.0.1 | no |
| `node_modules/path-parse` | 1.0.7 | no |
| `node_modules/picocolors` | 1.0.0 | no |
| `node_modules/picomatch` | 2.3.1 | no |
| `node_modules/pify` | 2.3.0 | no |
| `node_modules/pirates` | 4.0.6 | no |
| `node_modules/postcss` | 8.4.24 | no |
| `node_modules/postcss-import` | 15.1.0 | no |
| `node_modules/postcss-js` | 4.0.1 | no |
| `node_modules/postcss-load-config` | 4.0.1 | no |
| `node_modules/postcss-nested` | 6.0.1 | no |
| `node_modules/postcss-selector-parser` | 6.0.13 | no |
| `node_modules/postcss-value-parser` | 4.2.0 | no |
| `node_modules/proxy-from-env` | 1.1.0 | no |
| `node_modules/pusher-js` | 8.3.0 | no |
| `node_modules/queue-microtask` | 1.2.3 | no |
| `node_modules/read-cache` | 1.0.0 | no |
| `node_modules/readdirp` | 3.6.0 | no |
| `node_modules/resolve` | 1.22.2 | no |
| `node_modules/reusify` | 1.0.4 | no |
| `node_modules/rollup` | 3.26.0 | no |
| `node_modules/run-parallel` | 1.2.0 | no |
| `node_modules/source-map-js` | 1.0.2 | no |
| `node_modules/sucrase` | 3.32.0 | no |
| `node_modules/supports-preserve-symlinks-flag` | 1.0.0 | no |
| `node_modules/tailwindcss` | 3.3.2 | no |
| `node_modules/thenify` | 3.3.1 | no |
| `node_modules/thenify-all` | 1.6.0 | no |
| `node_modules/to-regex-range` | 5.0.1 | no |
| `node_modules/ts-interface-checker` | 0.1.13 | no |
| `node_modules/tweetnacl` | 1.0.3 | no |
| `node_modules/update-browserslist-db` | 1.0.11 | no |
| `node_modules/util-deprecate` | 1.0.2 | no |
| `node_modules/vite` | 4.3.9 | no |
| `node_modules/vite-plugin-full-reload` | 1.0.5 | no |
| `node_modules/wrappy` | 1.0.2 | no |
| `node_modules/yaml` | 2.3.1 | no |

## Integrity record

Original lockfile SHA-256 values, unchanged by installation/build/testing:

| File | SHA-256 |
| --- | --- |
| `composer.lock` | `7250a10332aed358d06d40c16213edc5ecbdf06f7ad6f8b82f8b63f4f9ee03cd` |
| `package-lock.json` | `7e950f2efa03079d99a1f1c38209d091e08b0f5c7079e3e680d447d014e20610` |

The phase commit is deliberately not embedded in its own audit: that would require another commit to record its hash. It is reported in the handover. Local setup artifacts are excluded by existing ignore rules; the plan, runtime downloads and scratch scripts are outside the checkout. The final tracked diff is restricted to the two documentation files.
