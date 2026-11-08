# Refactor Log — Building-Management

This file is the single record of EVERY change made during the refactor.
One entry per distinct change. No entry = the change did not happen.
Each phase adds entries; review checks this file against the diff.

## Entry template (copy for each change)

```
### [Phase N] Short title
- **Before:** what the code/behaviour was, exactly (file + function where useful)
- **After:** what it is now
- **Why:** the root cause or reason — the problem this solves, in concrete terms
- **Files:** paths touched
- **Verified by:** tests run / screenshots (page × width × theme) / manual check
- **Risk & rollback:** what could break, how to undo (commit/revert note)
```

Rules:
- Entries are written in the same commit as the change they describe.
- "Cleaned up code" is not a Why. State the concrete defect: crash, duplication count, security hole, measured slowness, missing capability.
- Behaviour changes must say so explicitly and name the old behaviour.

---

## Baseline — audit findings before any change (2026-11-08)

These are the known defects the refactor starts from, each with its cause.
Findings remain open unless a closing commit is recorded below. The named phases own the remaining work.

### [Baseline] Cross-complex authorization missing
- **Status:** CLOSED in 1505f640593e5257ee3c78557a6ff822b6612015.
- **Before:** Controllers check only `auth`; `AccessMiddleware` checks "is manager anywhere" (no complex_id). Chat read/write endpoints have no membership check at all.
- **Why it matters:** any logged-in user can read/send in another building's chat and a manager of one complex can manage others — data leak + unauthorized actions.
- **Cause:** authorization was implemented as a global role flag instead of per-resource policy.
- **Closed by:** Phase 2.

### [Baseline] Real-time chat is public
- **Status:** CLOSED in 1505f640593e5257ee3c78557a6ff822b6612015.
- **Before:** all chat events broadcast on one public `Channel('group')` shared by every complex; Pusher key hardcoded in `public/js/building.js`.
- **Why it matters:** anyone with the (public) key receives every building's messages live.
- **Cause:** private channels + channel authorization were never wired (`routes/channels.php` has only the default user channel; Echo config is commented out).
- **Closed by:** Phase 2.

### [Baseline] State changes via GET
- **Status:** CLOSED in 1505f640593e5257ee3c78557a6ff822b6612015.
- **Before:** delete message, upgrade/downgrade role, cancel announcement, admin accept/reject, logout are `Route::get`.
- **Why it matters:** no CSRF protection on mutations; a link can trigger them.
- **Cause:** routes were written for link-click convenience, not HTTP semantics.
- **Closed by:** Phase 2.

### [Baseline] Chat image upload unvalidated
- **Status:** CLOSED in 1505f640593e5257ee3c78557a6ff822b6612015.
- **Before:** `GroupController@store` stores any uploaded file with no mime/size validation; other uploads in the app do validate.
- **Why it matters:** arbitrary/oversized file upload.
- **Cause:** validation rules were only added to the later upload features.
- **Closed by:** Phase 2.

### [Baseline] Predictable join links
- **Status:** CLOSED in 1505f640593e5257ee3c78557a6ff822b6612015.
- **Before:** `join_link` generated with `uniqid()` in `AdminController@accept`.
- **Why it matters:** time-based and guessable — anyone can join any complex as resident.
- **Cause:** `uniqid()` used as if it were a random token.
- **Closed by:** Phase 2.

### [Baseline] Client-set payment amounts
- **Status:** CLOSED for server-side amounts in 1505f640593e5257ee3c78557a6ff822b6612015. Verified gateway flow remains Phase 8.
- **Before:** charge/cost payment endpoints accept `Amount` from the request and only block double payment.
- **Why it matters:** a 1-unit payment records as fully paid.
- **Cause:** amounts were treated as user input instead of being read from the charge record.
- **Closed by:** Phase 2 (server-side amounts) + Phase 8 (verified gateway flow).

### [Baseline] Stored XSS in message-viewers list
- **Status:** CLOSED in 1505f640593e5257ee3c78557a6ff822b6612015.
- **Before:** viewer names injected via `innerHTML` in `public/js/building.js`; names are user-controlled (registration/profile).
- **Why it matters:** a crafted name executes script in other residents' browsers.
- **Cause:** one render path used `innerHTML` while the rest of the chat correctly uses `textContent`.
- **Closed by:** Phase 2.

### [Baseline] Two broken model relations
- **Before:** `Unit::userRoles()` references non-existent class `UserRoles`; `PollOption::votes()` uses foreign key `role_id` instead of `poll_option_id`.
- **Why it matters:** calling the first crashes; the second returns unrelated rows.
- **Cause:** typos never exercised by any test.
- **Closed by:** Phase 4 (+ tests in Phase 1 style).

### [Baseline] Null-dereference crashes
- **Before:** unguarded `->first()->…` in chat index/last-seen/viewers/seen and in charge payment; empty group or missing message → 500.
- **Why it matters:** a brand-new complex (no messages) or a tampered id crashes the page.
- **Cause:** happy-path-only coding, no empty-state handling.
- **Closed by:** Phase 4.

### [Baseline] Voting after poll expiry + option-shift bug
- **Before:** `Poll::isExpired()` exists but vote() never calls it; `pollCreate` drops/shifts options when a middle option is empty.
- **Why it matters:** results can be changed after deadline; poll options silently differ from what the manager typed.
- **Cause:** expiry was modelled but never enforced; options were counted instead of filtered.
- **Closed by:** Phase 4.

### [Baseline] Missing responsive foundation
- **Before:** building + dashboard pages have no viewport meta; dashboard CSS (1028 lines) has 5 media queries and fixed widths up to 1800px; the BUILDING page loads three conflicting Tailwind versions at once (2.2.15 CDN, 0.3 unpkg, Play CDN) on top of hand-written CSS, while the dashboard page loads one (2.2.15 CDN) — corrected in Phase 0 review; app pages bypass Vite so assets are unminified/unhashed.
- **Why it matters:** mobile layout is broken/unpredictable — the main user device for residents is a phone.
- **Cause:** pages grew as static files outside the build pipeline; Tailwind was added three times at three moments.
- **Closed by:** Phase 5.

### [Baseline] Frontend monolith + duplication
- **Before:** `public/js/building.js` is 1495 lines covering chat/polls/payments/uploads; the CSRF-token block repeats 7× (14 total references including usages — corrected in Phase 0 review); PHP builds the same message array 7×; the random upload-filename block repeats 3× (chat, announcement, incidental cost — corrected in Phase 0 review); `BuildingController` is 771 lines / 13 responsibilities.
- **Why it matters:** every fix must be made in several places; regressions hide easily.
- **Cause:** features were appended to the same files without extraction points.
- **Closed by:** Phases 4–5.

### [Baseline] N+1 query patterns
- **Before:** payments report queries per unit and per payment (`User::findOrFail` inside loops) — true DB-level N+1; vote results counted in PHP; last-seen scans all seen-rows. The building dashboard's residents loop is different (corrected in Phase 0 review): it scans ALREADY-LOADED collections in memory — quadratic CPU, no extra queries; still worth simplifying, but it is not an N+1.
- **Why it matters:** page cost grows with residents × payments; will crawl on a real building.
- **Cause:** collection processing in PHP instead of eager loading / aggregate queries.
- **Closed by:** Phase 4.

### [Baseline] No uniqueness in the database
- **Before:** no unique constraints on votes, seen_messages, units (per complex), payments; integrity kept only by in-code checks.
- **Why it matters:** concurrent requests create duplicates the UI assumes impossible.
- **Cause:** constraints were left to application code.
- **Phase 0 correction (important):** charge-payment duplicate protection is PER JALALI MONTH (the check compares each existing payment's created_at year+month to now) — a lifetime unique on (role_id, monthly_charge_id) would CHANGE behaviour and break paying the same charge in a later month. Phase 3 must therefore use period-scoped uniqueness (period year+month stored on the payment), not a lifetime unique. Incidental-cost payments ARE lifetime one-off, so their lifetime unique stands.
- **Closed by:** Phase 3 (as corrected above).

### [Baseline] Dark mode exists only as a skeleton (CORRECTED in Phase 0 review)
- **Before:** a working toggle + persisted setting exists (`darkMode` in the settings object of `public/js/building.js` and `public/js/dashboard/script.js`, toggling `.dark` on `<html>`), but styling coverage is ~9 `.dark` rules in `public/css/dashboard/style.css` (root vars, search/add wrappers, a few buttons) plus scattered Breeze `dark:` utilities in a few fragments. Most screens — chat, tables, modals, most forms — have NO dark styling and stay light.
- **Why it matters:** enabling the toggle produces a half-light, half-dark UI — worse than no toggle.
- **Cause:** the theme was started (mechanism first) and never rolled out across components.
- **Correction note:** the original audit entry claimed "dark mode does not exist" — the Phase 0 audit corrected it against the repository code. Phase 5 COMPLETES dark mode instead of building it from scratch: keep the existing toggle/persistence mechanism, extend coverage to every component, both themes in screenshot QA.

### [Baseline] Mixed/absent UX states
- **Before:** fetch calls largely without error handling (1 try/catch for 11 calls), no loading/empty states system, destructive actions without confirm, alerts inconsistent.
- **Why it matters:** failures are silent for users; misclicks are unrecoverable.
- **Cause:** happy-path-first UI.
- **Closed by:** Phase 5.

### [Baseline] Tests do not cover the app
- **Before:** only default Breeze tests exist; measured baseline (Phase 0, commit b4abd08): 24 tests, 17 passed, 7 failed, 43 assertions. The 5 ProfileTest cases target unregistered `/profile` routes; the login/register tests expect redirect to `/` while the app intentionally redirects to `/dashboard` (admins to the admin panel) — the tests are stale, not the app. No factories except User; a DatabaseSeeder file exists but is a no-op (body fully commented out).
- **Why it matters:** no change can be proven safe.
- **Cause:** tests were never adopted as part of the workflow.
- **Closed by:** Phase 1 (+ Phase 9 for browser/E2E).

### [Baseline] End-of-life platform versions
- **Before:** Laravel framework 10.14.1 (Laravel 10 line is end-of-life — no security fixes; also far behind the last 10.x patch), PHP 8.1 (end-of-life), Vite 4.3.9, Tailwind 3.3.2, Carbon 2, Sanctum 3.2.5, Pest 2.8.2. `jenssegers/date` is required in composer.json but unused anywhere in app code (the used Jalali library is `morilog/jalali`).
- **Why it matters:** an EOL framework/runtime means newly disclosed vulnerabilities will never be patched on this stack.
- **Cause:** the project was untouched since August 2023.
- **Closed by:** Phase 1B (PHP 8.3, Laravel 12 via 11, Carbon 3, Sanctum 4, Pest 3, Vite 6; Tailwind stays on latest 3.x; unused date package removed) — placed right after the Phase 1 test safety net so every upgrade hop is verified by the suite.

### [Baseline] Payment & SMS not implemented (capability, by design)
- **Before:** payments are record-only (no gateway); no SMS capability at all.
- **Decision (owner):** build both PLUGGABLE and DISABLED by default (mock/log drivers); provider NOT chosen — no vendor lock-in; activation later is configuration only, once accounts/permits exist.
- **Closed by:** Phase 8.

### [Baseline] Map location not implemented (capability)
- **Before:** complexes have a free-text `location` string only; no coordinates, no map.
- **Decision (owner):** Leaflet + OpenStreetMap picker/display (no Google key needed).
- **Closed by:** Phase 7 (columns in Phase 3).

---

## Phase entries

(Append one entry per change, newest phase last.)

### [Phase 0] Audit inventory and verified baseline
- **Before:** The supplied baseline had no checked-in inventory or measured test result. Its profile-route failure was known, but two additional redirect failures and several model, tenant-boundary, date, reporting, deployment and UI defects were not recorded.
- **After:** Added docs/REFACTOR_AUDIT.md with every controller/model/route/migration/Blade/JS/CSS path, exact runtime and locked package versions, per-test before/after outcomes, source-specific findings grouped by area, and dependency advisory references. Copied this log unchanged before appending this one entry. No application behavior changed.
- **Why:** Confirmed additional findings include: profile optional-field 500 (B07); wrong incidental-payment/user-vote relations and missing group_user pivot (B02-B04); invalid date ordering and migration-time role default (B10/D03); monthly rather than lifetime charge duplicate protection (B11); omitted role payment history (B12); nontransactional/repeatable approval and inconsistent reject state (B13); unvalidated unit/cost fields and no-op unit edit rejection (B14-B15); duplicate logout names breaking route caching (B20); unknown date fillable column (B21); unscoped poll/results/payments and unit mutations, membership creation without a join token, cross-complex payable references and inactive-role access (S01-S02/S07-S08); payment modals retaining the final card amount, lost form values, cursor retry suppression, malformed/duplicate markup and incomplete keyboard/theme handling (F03-F15). Dependency scans match 61 Composer advisories across 17 packages and 17 affected npm packages (S11). See the audit for further locations, invariant gaps and verification limits.
- **Baseline corrections:** Dark mode already exists partially; building has three Tailwind sources while dashboard has one; cited 1800/480 widths are maxima and 520 is a breakpoint; there are 7 repeated CSRF blocks and 3 upload blocks; dashboard resident scans use loaded collections, with poll options providing the per-poll queries; DatabaseSeeder exists but is a no-op. Suite result is 24 tests / 17 passed / 7 failed / 43 assertions: all five ProfileTest cases plus successful-login and registration redirect assertions. The charge-payment rule currently permits another payment in a later month, so a lifetime unique pair would change behavior. Supplied baseline entries remain intact.
- **Files:** docs/REFACTOR_AUDIT.md; docs/REFACTOR_LOG.md. Local ignored .env/key, SQLite file, dependencies, caches and frontend build are environment setup only; manifests, lockfiles and application files are unchanged.
- **Verified by:** Locked Composer install and npm ci; 19 SQLite migrations; identical full-suite before/final-after results with names recorded in the audit; Vite build; 160 tracked *.php syntax checks; Composer platform check; route-list and duplicate-name route-cache check (cache cleared afterward); read-only dependency audits; synthetic in-memory relation/profile/empty-chat probes; docs-only diff and byte-preserved source-log prefix checks.
- **Risk & rollback:** Documentation only; pre-existing failures and vulnerabilities remain open. Tests used process-level in-memory SQLite overrides because phpunit.xml inherits the dev DB otherwise. Revert this phase's single docs commit to remove the inventory/log import; local ignored setup is outside the commit. No later phase was started.

### [Stage A] Move the checkout to D:
- **Before:** The checkout and unpushed Phase 0 commit were on C:.
- **After:** Copied the complete checkout, including .git and dependencies, to D:/Building-Management. Updated the ignored SQLite path. The external runtime and plan are outside the repository.
- **Why:** A fresh clone would lose the unpushed audit commit.
- **Files:** docs/REFACTOR_LOG.md; ignored local environment paths.
- **Verified by:** Original b4abd08850c614d8b84afd687be0dc358e532abf on refactor/phase-0-audit, clean copied tree before the log merge, all source file paths present, every tracked file byte-matched, git fsck, before/after suite unchanged at 17 passed / 7 failed / 43 assertions. The C: checkout is removed after these checks pass.
- **Risk & rollback:** No application change. Preserve .git and update local paths if relocating again.

### [Stage A] Merge the revised baseline
- **Before:** The log contained the old baseline and one Phase 0 entry.
- **After:** Imported the supplied corrections and upgrade sequence, retained the Phase 0 entry, and removed authoring attributions. The external plan is the revised supplied version.
- **Why:** Theme persistence must remain compatible, charge uniqueness must remain monthly, and upgrades must follow the new test suite.
- **Files:** docs/REFACTOR_LOG.md.
- **Verified by:** Compared supplied changes and retained entry; full suite before/after; staged wording check.
- **Risk & rollback:** Documentation only. Revert this stage's commit to restore the earlier baseline; Phase 0 remains in history.

### [Phase 1] Add model factories
- **Before:** Only User had a factory.
- **After:** Added factories for all 15 domain models, with matching complex IDs for units, role/payable graphs, votes and read receipts.
- **Why:** Domain endpoint tests need valid related records without hand-written inserts.
- **Files:** database/factories/*Factory.php; tests/Feature/FactoriesTest.php; tests/Support/BuildingContext.php.
- **Verified by:** Each of the 16 model factories persists successfully with SQLite foreign-key checks; full suite.
- **Risk & rollback:** Test fixtures only; revert the factories and their tests together. No model or relation changed.

### [Phase 1] Add an explicit local demo seeder
- **Before:** DatabaseSeeder was a no-op; no repeatable local scenario existed.
- **After:** Added DemoSeeder with five local accounts, one complex/four units, paid and unpaid charges, an incidental payment, announcements, open/expired polls, text/image chat, a read receipt and a pending request. The default seeder remains unchanged.
- **Why:** Demo accounts must be opt-in and must never be inserted by the default production seed command.
- **Files:** database/seeders/DemoSeeder.php; tests/Feature/DemoSeederTest.php; tests/README.md.
- **Verified by:** SQLite migrate:fresh then db:seed --class=DemoSeeder; account/hash/data/image assertions; default-seeder and environment-refusal tests.
- **Risk & rollback:** Demo credentials are local only: admin@example.test, manager@example.test, resident1@example.test through resident3@example.test, all password password. Seeding refuses other environments. Recreate only the dedicated local demo database to roll back.

### [Phase 1] Isolate tests from development databases
- **Before:** phpunit.xml inherited the development connection and RefreshDatabase could rebuild it.
- **After:** Forced SQLite :memory: in environment/server variables, disabled the connection URL, separated the test config-cache path and added a non-memory connection guard. Added composer test.
- **Why:** Dotenv prioritizes inherited server variables; environment overrides alone did not protect against an externally supplied MySQL connection.
- **Files:** phpunit.xml; tests/CreatesApplication.php; tests/Feature/FactoriesTest.php; composer.json; README.md; tests/README.md.
- **Verified by:** Full suite passes even with DB_CONNECTION=mysql and a development SQLite path supplied externally; development database SHA-256 unchanged; active connection and FK checks.
- **Risk & rollback:** Only test startup changes. A misconfigured test connection fails before RefreshDatabase. Revert test configuration and runner changes together; do not run the old settings against a valued development database.

### [Phase 1] Update stale authentication expectations
- **Before:** Two baseline tests expected HOME=/ even though login/registration redirect regular users to /dashboard.
- **After:** Tests assert /dashboard; added admin login to /adminPanel, POST logout and verification resend coverage, and assert the stored password changes on reset.
- **Why:** The application redirects are intentional; changing production routes to satisfy stock tests would change behavior.
- **Files:** tests/Feature/Auth/AuthenticationTest.php; RegistrationTest.php; EmailVerificationTest.php; PasswordResetTest.php.
- **Verified by:** Full suite; regular/admin authentication, guest logout state, fake email notification and reset hash assertions.
- **Risk & rollback:** Tests only; no route or redirect changed. Revert the test edits to restore the old expectations.

### [Phase 1] Replace dead profile endpoint tests
- **Before:** Five ProfileTest cases targeted unregistered GET/PATCH/DELETE /profile and failed.
- **After:** Removed those five stale cases. Five replacements test PATCH /dashboard/update (profile.update): saved name/email/national code/phone, verification retention/reset, unique email, field validation and guest rejection.
- **Why:** Profile editing is part of the dashboard; profile page/account deletion endpoints are not registered.
- **Files:** tests/Feature/ProfileTest.php; tests/README.md.
- **Verified by:** All five replacement profile tests pass. The omitted-nullable-key defect is separately skipped, not fixed.
- **Risk & rollback:** Tests only; no controller or public route changed. Revert ProfileTest to restore the original stock cases.

### [Phase 1] Characterise building workflows
- **Before:** No tests covered building membership, unit assignment, management actions, payments, chat or approval.
- **After:** Added one test file per area, covering every routed controller action with valid domain data; ownership checks verify that other members cannot edit/delete a sender's message.
- **Why:** Later refactors need assertions on stored rows, files, events and responses, rather than only successful page loads.
- **Files:** tests/Feature/Building/*.php; tests/Feature/Admin/MembershipDecisionTest.php; tests/Support/BuildingContext.php; tests/README.md.
- **Verified by:** Full suite: 98 cases, 89 passed, 0 failed, 9 known-defect skips, 298 assertions (baseline: 24 cases, 17 passed, 7 failed, 43 assertions). Fake storage/events; endpoint-to-file coverage table in tests/README.md.
- **Risk & rollback:** No application changes. Existing GET routes are used to test business effects; their unsafe method is a separate skipped expectation for Phase 2. Revert these tests without altering app behavior.

### [Phase 1] Pin charge payments to Jalali months
- **Before:** Charge duplicate checks compare created_at against the current Jalali year/month; no regression test protected recurrence.
- **After:** A test records one payment on 1405-01-31, rejects a second that day, then accepts the same charge/role on 1405-02-01. Both dates are in April 2026. Carbon::setTestNow is restored afterward.
- **Why:** A lifetime unique pair or Gregorian-month key would prevent a valid later payment. Phase 3 must store period_year/period_month and constrain the four-column period key.
- **Files:** tests/Feature/Building/ChargePaymentTest.php; tests/README.md.
- **Verified by:** Three payment requests; row counts 1, 1, 2; same role/charge IDs; stored Jalali periods 1405-01 and 1405-02; full suite.
- **Risk & rollback:** Tests only. No uniqueness constraint or payment code changed. Keep this assertion through the dependency hops and database phase.

### [Phase 1] Defer: chat history rejects a member of another complex
- **Before:** The current implementation has the documented defect: Baseline: Cross-complex authorization missing; Phase 2 adds membership checks.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1] Defer: a manager cannot publish an announcement in another complex
- **Before:** The current implementation has the documented defect: Baseline: Cross-complex authorization missing; Phase 2 scopes manager access.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1] Defer: chat rejects a non-image upload
- **Before:** The current implementation has the documented defect: Baseline: Chat image upload unvalidated; Phase 2 validates MIME and size.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1] Defer: incidental cost payments use the recorded share instead of a submitted amount
- **Before:** The current implementation has the documented defect: Baseline: Client-set payment amounts; Phase 2 reads the payable amount on the server.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1] Defer: expired polls reject new votes
- **Before:** The current implementation has the documented defect: Baseline: Voting after poll expiry + option-shift bug; Phase 4 enforces expiry.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1] Defer: a blank middle poll option does not discard later options
- **Before:** The current implementation has the documented defect: Baseline: Voting after poll expiry + option-shift bug; Phase 4 filters options by value.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1] Defer: an empty chat returns no last-seen message without an error
- **Before:** The current implementation has the documented defect: Baseline: Null-dereference crashes; Phase 4 handles an empty group.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1] Defer: GET cannot delete a chat message
- **Before:** The current implementation has the documented defect: Baseline: State changes via GET; Phase 2 changes mutation methods and CSRF protection.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1] Defer: profile updates may omit optional national code and phone fields
- **Before:** The current implementation has the documented defect: Phase 0 audit B07: optional profile fields cause a 500; Phase 4 handles absent nullable fields.
- **After:** Added an executable expectation marked skipped with that reference and fixing phase.
- **Why:** A passing test must not endorse defective behavior; the pending assertion belongs with its scheduled fix.
- **Files:** tests/Feature/KnownDefectsTest.php; tests/README.md.
- **Verified by:** Full suite lists this test as skipped with its reason.
- **Risk & rollback:** The defect remains open. Enable this test in the named fixing phase after implementing the fix; removing a skip before then will fail the suite.

### [Phase 1B / Hop A] Remove the unused date dependency
- **Before:** composer.json required jenssegers/date ^4.0 and it was installed at v4.0.0.
- **After:** Removed the requirement and locked package. Existing date handling still uses morilog/jalali and Carbon.
- **Why:** No usage of the package namespace, provider or alias exists in app, routes, config, resources, database or tests.
- **Files:** composer.json; composer.lock.
- **Verified by:** Source search before removal; Composer install/update and full suite.
- **Risk & rollback:** No date-call replacement or stored-data change. Revert the dependency commit and reinstall its lockfile to restore the old package set.

### [Phase 1B / Hop A] Refresh the Laravel 10 dependencies
- **Before:** Framework 10.14.1, Carbon 2.68.1, Sanctum 3.2.5, Pest 2.8.2 / PHPUnit 10.2.3.
- **After:** Framework 10.50.4, Carbon 2.73.0, Sanctum 3.3.3, morilog/jalali 3.5.0, Pest 2.36.0 / PHPUnit 10.5.36. Composer resolved 109 package upgrades, 4 additions and 4 removals within the existing Laravel 10/PHP 8.1 constraints.
- **Why:** The old lockfile predates the latest compatible patches and fixes.
- **Files:** composer.lock; composer.json.
- **Verified by:** composer update --with-all-dependencies; validate and check-platform-reqs passed; no newer direct minor candidate on this platform; before/after suite both 89 passed, 9 skipped, 0 failed, 298 assertions. SQLite migrate:fresh and explicit DemoSeeder succeeded.
- **Risk & rollback:** The stack remains Laravel 10. Composer audit still reports 5 framework advisories and 1 PHPUnit advisory. Pest 2.36.0 requires PHPUnit ^10.5.36 and conflicts with >10.5.36, so its security update needs the later major upgrade. No platform requirement was ignored. Revert the commit and reinstall dependencies for rollback.

### [Phase 1B / Hop A] Build with Vite 6 and Tailwind 3.4
- **Before:** Vite 4.3.9, laravel-vite-plugin 0.7.8, Tailwind 3.3.2.
- **After:** Vite 6.4.5, plugin 1.2.0 and Tailwind 3.4.19; refreshed other Node dependencies within their existing ranges. The plugin range is ~1.2.0.
- **Why:** Plugin 1.2 supports Vite 5/6; 1.3 requires Vite 7, outside this stage. Tailwind 4 is out of scope.
- **Files:** package.json; package-lock.json.
- **Verified by:** Resolved the new lockfile then ran npm ci with normal peer checks; npm run build passed (62 modules); full PHP suite passed against the built manifest.
- **Risk & rollback:** No --force or legacy-peer-deps resolution used. Source assets, theme toggle and userSettings/darkMode storage remain unchanged. Vite output is ignored; browser/device checks belong to the later visual phase. Revert manifest/lockfile and rerun npm ci/build to roll back.

### [Phase 1B / Hop A] Use the configured hash in the user factory
- **Before:** UserFactory returned a fixed bcrypt hash with cost 10; the tests set cost 4.
- **After:** The factory caches Hash::make(password) using the active hashing configuration. Local credentials are unchanged.
- **Why:** The updated framework hashed cast verifies the stored hash configuration and rejects the old fixed factory hash. Disabling verification would hide the incompatibility.
- **Files:** database/factories/UserFactory.php.
- **Verified by:** The initial refreshed suite identified the hash-configuration exception; the full suite now passes, including factory creation, login/reset, registration and all demo account hashes. Pint passed for the changed factory.
- **Risk & rollback:** Fixture-only adaptation. The User model and authentication controllers are unchanged; do not restore the fixed hash while retaining the newer framework.

### [Phase 1B / Hop A] Record the PHP prerequisite before Laravel 11
- **Before:** The plan continues through Laravel 11 and 12, requiring PHP 8.3 and updated major dependencies.
- **After:** Stopped after the green Laravel 10 hop on PHP 8.1.34. No Hop B/C dependency constraints, application skeleton changes, Sanctum 4, Carbon 3 or Pest 3 changes were attempted.
- **Why:** The owner must install/switch PHP 8.3 before the Laravel 11 hop. Installed morilog/jalali 3.5.0 permits nesbot/carbon ^1.21 || ^2.0 || ^3.0, so it is not a Carbon 3 blocker.
- **Files:** README.md; tests/README.md; docs/REFACTOR_LOG.md.
- **Verified by:** php -v: 8.1.34; php artisan --version: Laravel Framework 10.50.4; inspected the installed package constraints and official Laravel 11 upgrade guide; no app/Kernel/provider/bootstrap edits.
- **Risk & rollback:** The platform upgrade is incomplete until the PHP prerequisite is met and both remaining hops pass. Composer php remains ^8.1 until Hop B; the requested ^8.3 and environment/version notes must be applied in that hop. No Phase 2 work started.

### [Phase 1B / Hop A] Keep optional lock metadata within the wording rule
- **Before:** Upstream package suggestions, development-only requirements and funding links introduced strings disallowed by the repository wording check.
- **After:** Removed only matching optional require-dev/suggest/funding metadata from library lock entries. Root dependencies, runtime requirements, package versions, sources, distribution references and integrity hashes are unchanged.
- **Why:** Optional upstream text would fail the required pre-commit check despite not being an installed root dependency or project-authored content.
- **Files:** composer.lock; package-lock.json.
- **Verified by:** Compared every non-optional package field against the resolved lockfiles; Composer validation and install dry-run; npm ci dry-run; final added-line wording check. The full suite and build pass with this exact package set.
- **Risk & rollback:** Later dependency updates can restore optional metadata and must repeat the wording check. This normalization does not alter the installed dependency graph; restore upstream metadata without changing package versions if the wording rule is relaxed.

### [Phase 1B / Hop B] Install the portable PHP 8.3 runtime
- **Before:** The active project runtime was PHP 8.1.34.
- **After:** Official PHP 8.3.36 thread-safe x64 archive extracted to D:/tools/php-8.3; php.ini copied from php.ini-development. Enabled mbstring, openssl, curl, fileinfo, pdo_mysql, pdo_sqlite, sqlite3, gd, intl and zip; configured the external CA file. System PATH and existing PHP installations are unchanged.
- **Why:** The platform upgrade requires PHP 8.3.
- **Files:** docs/REFACTOR_LOG.md; external D:/tools/php-8.3 only.
- **Verified by:** Official ZIP SHA-256 126dd3cc8c19df5085b9420b2bc74ed591de3d98528858c52fc42739b7bba92e matched; explicit binary -v/-m/-i; baseline 89 passed, 9 skipped, 298 assertions.
- **Risk & rollback:** To remove, stop processes using this binary and remove only D:/tools/php-8.3. No PATH rollback is needed. Keep the external CA file available while the runtime is in use.

### [Phase 1B / Hop B] Update Sanctum middleware configuration
- **Before:** Sanctum 3 configuration used verify_csrf_token and omitted authenticate_session.
- **After:** Renamed the CSRF key to validate_csrf_token and registered Sanctum AuthenticateSession; retained project cookie and CSRF classes.
- **Why:** Sanctum 4 reads the new middleware keys. The existing published personal_access_tokens migration already provides the required schema.
- **Files:** config/sanctum.php; composer.json; composer.lock.
- **Verified by:** Full existing suite, fresh SQLite migrations and demo seeding; no duplicate token migration created.
- **Risk & rollback:** No existing migration edited; HTTP Kernel and middleware aliases unchanged. Revert this hop and reinstall its lockfile for rollback.

### [Phase 1B / Hop B] Preserve password storage on login
- **Before:** Laravel 10 did not automatically rewrite older bcrypt hashes on login.
- **After:** Set hashing.rehash_on_login=false; password changes still hash using the configured driver.
- **Why:** Laravel 11 enables automatic rehashing by default; explicitly disabling that new behavior preserves the existing authentication contract.
- **Files:** config/hashing.php.
- **Verified by:** Unchanged login, registration, reset and update tests pass; framework upgrade guide and guard implementation checked.
- **Risk & rollback:** This retains existing behavior; hash verification remains enabled. Remove the compatibility setting only with an intentional authentication change.

### [Phase 1B / Hop B] Upgrade dependencies and record verified versions
- **Before:** Hop A used Laravel 10, Carbon 2, Sanctum 3 and Pest 2.
- **After:** laravel/framework v11.57.1, nesbot/carbon 3.15.1, laravel/sanctum v4.3.3, pestphp/pest v3.8.7, phpunit/phpunit 11.5.56, morilog/jalali v3.5.0. PHP 8.3.36; Vite 6.4.5; Tailwind 3.4.19.
- **Why:** Follow the official 10-to-11 upgrade path without adopting a new application skeleton.
- **Files:** composer.json; composer.lock; README.md; tests/README.md; .env.example; docs/REFACTOR_LOG.md.
- **Verified by:** Full suite, SQLite migrate:fresh and explicit DemoSeeder, npm run build, Composer validation/platform checks; existing test source and nine skips retained.
- **Risk & rollback:** Dependency upgrades only except the separately logged compatibility settings. All commands use D:/tools/php-8.3/php.exe. Optional upstream lock metadata is normalized under the existing wording rule; runtime requirements and package integrity fields are retained. Revert this hop and reinstall its lockfile for rollback.

### [Phase 1B / Hop C] Upgrade dependencies and record verified versions
- **Before:** Hop B used Laravel 11.
- **After:** laravel/framework v12.71.0, nesbot/carbon 3.15.1, laravel/sanctum v4.3.3, pestphp/pest v3.8.7, phpunit/phpunit 11.5.56, morilog/jalali v3.5.0. PHP 8.3.36; Vite 6.4.5; Tailwind 3.4.19.
- **Why:** Follow the official 11-to-12 upgrade path without adopting a new application skeleton.
- **Files:** composer.json; composer.lock; README.md; tests/README.md; docs/REFACTOR_LOG.md.
- **Verified by:** Full suite, SQLite migrate:fresh and explicit DemoSeeder, npm run build, Composer validation/platform checks; existing test source and nine skips retained.
- **Risk & rollback:** Dependency upgrades only except the separately logged compatibility settings. All commands use D:/tools/php-8.3/php.exe. Optional upstream lock metadata is normalized under the existing wording rule; runtime requirements and package integrity fields are retained. Revert this hop and reinstall its lockfile for rollback.

### [Phase 1B / Hop C] Record final platform checks
- **Before:** Hop A had six Composer advisories and required the PHP runtime upgrade.
- **After:** Both remaining hops pass 89 tests, with the same nine skipped tests and 298 assertions. Final Composer and npm audits each report zero advisories. No test source was edited.
- **Why:** Freeze the supported versions and upgrade evidence before later application changes.
- **Files:** docs/REFACTOR_LOG.md; README.md; tests/README.md.
- **Verified by:** Explicit D:/tools/php-8.3/php.exe for every Composer/Artisan command; php -v, artisan --version, full suite per hop, SQLite migrate:fresh and DemoSeeder per hop, npm run build per hop, platform checks and read-only audits. Existing local disk root is explicit; schema, HTTP Kernel, providers, aliases and bootstrap remain unchanged.
- **Risk & rollback:** Known application defects remain deferred by the original skips. Revert Hop C before Hop B to return to the prior platform, reinstalling the matching lockfile each time. No Phase 2 work started.

| Component | Final version |
| --- | --- |
| PHP (thread-safe x64) | 8.3.36 |
| laravel/framework | v12.71.0 |
| nesbot/carbon | 3.15.1 |
| laravel/sanctum | v4.3.3 |
| pestphp/pest | v3.8.7 |
| phpunit/phpunit | 11.5.56 |
| morilog/jalali | v3.5.0 |
| Vite | 6.4.5 |
| Tailwind | 3.4.19 |

### [Phase 2] Scope access to each target complex
- **Before:** Global role checks admitted unrelated managers and nonmembers.
- **After:** ComplexPolicy view/manage and AccessMiddleware resolve the complex from route bindings or the target message/poll/payable. Unit edits require the owning active role or that complex manager; admin access requires an active global admin role.
- **Why:** Authorization must follow the resource rather than a submitted complex identifier.
- **Files:** app/Policies/ComplexPolicy.php; app/Http/Middleware/AccessMiddleware.php; app/Providers/AuthServiceProvider.php; BuildingController.php; GroupController.php; UserController.php; tests/Feature/Security/*.php.
- **Verified by:** 28 endpoints x 6 actors; foreign-ID spoofing, inactive membership and unit-owner tests; full suite: 287 passed, 4 skipped, 0 failed, 599 assertions (before: 89 passed, 9 skipped, 298 assertions). All four remaining skips belong to Phase 4. SQLite migrate:fresh + DemoSeeder, Blade compilation and npm run build pass.
- **Risk & rollback:** Unrelated users now receive 403; guest redirects remain. No global admin bypass grants complex membership. Revert the Phase 2 implementation commit and its dependent log closure together to roll back.

### [Phase 2] Require mutating HTTP verbs and CSRF
- **Before:** Six domain mutations were available through GET, and logout had duplicate GET/POST registrations.
- **After:** Message delete uses DELETE; promotion, step-down, announcement archive, admin accept/reject and logout use POST. Forms/buttons include CSRF; chat DELETE sends its existing token header.
- **Why:** Cross-site navigation must not mutate application data.
- **Files:** routes/web.php; mutation Blade callers; public/js/building.js; Admin/MembershipDecisionTest.php; Building/AnnouncementTest.php; Building/RolesTest.php; Building/ChatTest.php; tests/Feature/Security/MutationMethodsTest.php.
- **Verified by:** GET returns 405 and missing CSRF returns 419 for all seven changed endpoints plus regeneration; business outcomes remain the same.
- **Risk & rollback:** No route name or existing URL changed. Old GET clients break intentionally; update them to the listed verbs. Duplicate logout name removed by deleting GET registration. Revert the Phase 2 implementation commit and its dependent log closure together to roll back.

### [Phase 2] Validate chat attachments and empty sends
- **Before:** Chat accepted arbitrary files and quietly ignored blank sends.
- **After:** Chat images use nullable|file|mimes:gif,jpg,webp,png|max:5120; text is nullable|string|required_without:messageImage.
- **Why:** Only the permitted image types/sizes may enter public chat storage; an empty send needs validation feedback.
- **Files:** app/Http/Controllers/GroupController.php; KnownDefectsTest.php; Security/HardeningTest.php.
- **Verified by:** Oversized PNG, PHP/EXE payloads and empty requests rejected with 422; existing valid-image fixture accepted; other upload rules unchanged.
- **Risk & rollback:** Previously accepted unsupported/oversized uploads and empty requests now fail validation. Existing stored files are not changed. Revert the Phase 2 implementation commit and its dependent log closure together to roll back.

### [Phase 2] Rotate join links on explicit manager request
- **Before:** New complex tokens used uniqid; managers had no rotation action.
- **After:** New complexes and regeneration use Str::random(32). POST /dashboard/building/{complex}/join-link is named building.regenerateJoinLink; the manager settings page contains its button.
- **Why:** Predictable invitations must be replaced, and managers need to revoke a leaked link.
- **Files:** AdminController.php; BuildingController.php; routes/web.php; resources/views/building/setting.blade.php; security and admin tests.
- **Verified by:** New acceptance creates 32-character links; regeneration invalidates the old token, accepts the new one and leaves other complexes unchanged; matrix and CSRF tests.
- **Risk & rollback:** Existing complexes retain their current links until their manager regenerates. No mass rotation or new schema. Revert the Phase 2 implementation commit and its dependent log closure together to roll back.

### [Phase 2] Authorize private per-complex broadcasts
- **Before:** All chat events and clients shared the public group channel and a hardcoded browser key.
- **After:** Events resolve the complex from the persisted message group and use PrivateChannel(group.{complex_id}); a policy-backed channel callback restricts active members. The browser subscribes to private-group.{complexId} using /broadcasting/auth and CSRF. Public key/cluster come from server configuration and VITE_PUSHER_*; BROADCAST_CONNECTION defaults to log with legacy driver fallback.
- **Why:** Live messages must remain inside the same membership boundary as HTTP history.
- **Files:** app/Events/*ChatMessage.php; routes/channels.php; config/broadcasting.php; .env.example; building/building.blade.php; public/js/building.js; Security/HardeningTest.php.
- **Verified by:** All three event channel assertions; member/manager accepted, inactive/nonmember/foreign manager denied; frontend syntax/build checks.
- **Risk & rollback:** Public-channel clients must reconnect to the new private channel; existing channel event names remain. No live account was contacted or credential committed. Revert the Phase 2 implementation commit and its dependent log closure together to roll back.

### [Phase 2] Render message viewers as text
- **Before:** Viewer names were interpolated into innerHTML.
- **After:** Names and formatted dates are inserted using createElement/textContent.
- **Why:** User-controlled names must not be parsed as markup.
- **Files:** public/js/building.js.
- **Verified by:** Reviewed the viewer rendering diff; node --check and frontend build pass.
- **Risk & rollback:** DOM/text regression note only; browser tooling remains Phase 9. Existing Persian strings retained. Revert the Phase 2 implementation commit and its dependent log closure together to roll back.

### [Phase 2] Read payment amounts from the payable
- **Before:** Both payment handlers validated and saved request Amount; shared modals retained the final card amount.
- **After:** Charges store MonthlyCharge.amount; incidental payments store IncidentalCost.share_amount. Forms contain no amount input; card data displays the selected server-rendered amount as text.
- **Why:** Clients cannot choose the amount that marks a payable paid.
- **Files:** BuildingController.php; building/charge.blade.php; building/cost.blade.php; public/js/building.js; KnownDefectsTest.php; Security/HardeningTest.php.
- **Verified by:** Tampered amounts for both types store the authoritative amount; unchanged ChargePaymentTest monthly test and one-off cost duplicate test pass.
- **Risk & rollback:** No gateway introduced. Monthly duplicate logic is untouched; database uniqueness stays in Phase 3. Revert the Phase 2 implementation commit and its dependent log closure together to roll back.

### [Phase 2] Remove mass-assignment of persistent login tokens
- **Before:** remember_token was in User.fillable.
- **After:** Removed only remember_token from fillable; it remains hidden from serialization.
- **Why:** Model mass assignment must not replace persistent-login credentials.
- **Files:** app/Models/User.php; Security/HardeningTest.php.
- **Verified by:** isFillable assertion and full authentication suite; reviewed every model fillable list.
- **Risk & rollback:** Other credential/token fields found: User.password (hashed cast) and Complex.join_link (invitation token). Both remain unchanged as requested. No other service credential fields found. Revert the Phase 2 implementation commit and its dependent log closure together to roll back.

### [Phase 2] Record baseline closures
- **Before:** Baseline security findings named Phase 2 without a closing commit.
- **After:** Seven findings reference implementation commit 1505f640593e5257ee3c78557a6ff822b6612015. Gateway verification remains Phase 8; the four remaining skipped tests belong to Phase 4.
- **Why:** Closing references must identify the completed implementation after its hash exists.
- **Files:** docs/REFACTOR_LOG.md.
- **Verified by:** Compared the seven baseline findings with the eight implementation entries and the committed diff; no application changes in this entry.
- **Risk & rollback:** Documentation only; revert this closure entry when reverting the implementation.
### [Phase 3] Enforce domain uniqueness after duplicate cleanup
- **Before:** votes, seen_messages, units, payment_additional_costs and user_roles relied on request-time duplicate checks.
- **After:** New migration 2026_11_09_000001_harden_domain_uniqueness adds named unique indexes. Cleanup retains MIN(id), the earliest inserted row, and logs retained/deleted IDs plus per-table totals. Duplicate units first repoint user_roles.unit_id; duplicate roles repoint votes and both payment tables before child cleanup.
- **Why:** Concurrent writes could bypass controller checks; deleting parent duplicates without remapping would violate existing foreign keys or lose dependent records.
- **Files:** database/migrations/2026_11_09_000001_harden_domain_uniqueness.php; tests/Feature/Database/ConstraintsTest.php; MigrationTest.php.
- **Verified by:** Direct violating inserts for all five keys raise QueryException. The upgrade fixture removes one row per constrained table and checks logged IDs and retained dependents. Upgrade of the Phase 2 demo copy removes 0 rows from each of the six constrained tables, including charge_payments. All six keys have direct violation tests.
- **Risk & rollback:** Duplicate non-key values on deleted rows are not merged. Cleanup is irreversible through down(); restore a database backup to recover deleted rows. down() removes constraints and restores MySQL FK backing indexes if InnoDB replaced them. The user_roles triple uses standard NULL semantics on SQLite and MySQL: repeated NULL unit_id tuples remain valid; only fully non-null duplicates are removed/rejected. SQLite tests verify both cases; MySQL semantics were checked against its documentation, not a live server.

### [Phase 3] Persist Jalali charge-payment periods
- **Before:** Charge payments had no stored period; the controller compared created_at after Jalalian::fromCarbon conversion.
- **After:** The same new migration adds smallint period_year and tinyint period_month, backfills each existing row from its real created_at, removes same-period duplicates, makes both columns required and adds charge_payments_role_charge_period_unique(role_id, monthly_charge_id, period_year, period_month). The controller and factory supply the current Jalali year/month; the model allows and integer-casts both fields.
- **Why:** A lifetime payment unique key would prevent paying a recurring charge in a later Jalali month. Required period columns allow the database to enforce the existing monthly rule.
- **Files:** database/migrations/2026_11_09_000001_harden_domain_uniqueness.php; app/Http/Controllers/BuildingController.php; app/Models/ChargePayment.php; database/factories/ChargePaymentFactory.php; database tests.
- **Verified by:** Same-period direct insert fails; different months and years insert successfully. Crafted created_at values at Jalali 1405-01-31 23:59:59 and 1405-02-01 00:00:00 backfill to (1405,1)/(1405,2), although both are in Gregorian April 2026. The Phase 1 ChargePaymentTest.php is unchanged and passes.
- **Risk & rollback:** No lifetime unique index is introduced. A legacy payment with NULL created_at stops migration rather than inventing its period; restore its date before migrating. MySQL DDL is not transactional, so a failed schema upgrade requires inspection/restoration before retry. down() drops the new columns/index; deploy the old payment writer with that rollback.

### [Phase 3] Remove the nonexistent payment date attribute
- **Before:** ChargePayment.fillable included date, but charge_payments has no date column.
- **After:** Removed only the stale date attribute while adding the required period attributes.
- **Why:** Accepting that attribute through mass assignment could produce an insert for a nonexistent column.
- **Files:** app/Models/ChargePayment.php; tests/Feature/Database/ConstraintsTest.php.
- **Verified by:** The model rejects date as fillable; payment feature tests pass.
- **Risk & rollback:** No stored field or date format changes. Revert the model edit with the period migration rollback if required.

### [Phase 3] Add named lookup indexes
- **Before:** No composite lookup indexes covered messages(group_id,id), user_roles(user_id,complex_id) or seen_messages(group_id,user_id).
- **After:** Migration 2026_11_09_000002_add_hot_lookup_indexes adds messages_group_id_id_index, user_roles_user_complex_index and seen_messages_group_user_index.
- **Why:** The unique keys have different leading columns and do not cover these chat-history, membership and read-receipt lookups.
- **Files:** database/migrations/2026_11_09_000002_add_hot_lookup_indexes.php; tests/Feature/Database/MigrationTest.php.
- **Verified by:** SQLite schema assertions check all three index names; all new migrations roll back and reapply. MySQL down() restores a single-column FK index if InnoDB discarded it when adding the composite index.
- **Risk & rollback:** Index creation can lock populated tables and adds write cost. down() drops the three named lookup indexes without removing FK support.

### [Phase 3] Add location and integration scaffolding
- **Before:** No coordinate columns or transaction/SMS storage existed.
- **After:** Migration 2026_11_09_000003_add_future_integration_tables adds nullable decimal(10,7) latitude/longitude; transactions with nullable payable morph pair, nullable user/role foreign keys, decimal(12,2) amount, neutral gateway/authority/reference fields, pending string status and JSON meta; sms_logs with recipient, nullable template/body/driver, logged string status and JSON meta. Transaction and SmsLog models provide fillable fields and decimal/JSON casts.
- **Why:** A nullable morph pair can later reference either charge/cost type without fixing one payable table or requiring an immediate attachment. String statuses let future drivers add lifecycle states without database enum migrations. Nullable ownership permits later reconciliation; FK deletion remains restrictive to preserve records.
- **Files:** database/migrations/2026_11_09_000003_add_future_integration_tables.php; app/Models/Transaction.php; app/Models/SmsLog.php; tests/Feature/Database/ScaffoldingTest.php.
- **Verified by:** Default statuses, nullable ownership, JSON/decimal casts, extensible statuses, nullable/saved coordinates and invalid ownership FK inserts; fresh demo seed and rollback/reapply pass.
- **Risk & rollback:** Storage only: no gateway, SMS driver, feature flag, route or other behavior is wired to these records. down() removes both tables and coordinates, including any later stored data.

### [Phase 3] Record stored date conventions
- **Before:** monthly_charges SQL date columns contain Jalali strings, while polls contain real datetimes; these distinct conventions were undocumented at the schema change.
- **After:** The charge migration documents that distinction and the Jalali meaning of payment periods. Poll.start_date/end_date already have datetime casts; UserRole.start_date/end_date already have date casts; Eloquent supplies timestamp casts. No missing real date casts were found, so none were added.
- **Why:** Applying a Gregorian date cast to the Jalali charge strings would silently change their meaning.
- **Files:** database/migrations/2026_11_09_000001_harden_domain_uniqueness.php; tests/Feature/Database/ScaffoldingTest.php; docs/REFACTOR_LOG.md.
- **Verified by:** Stored 1405-01-31/1405-02-01 charge values remain strings; poll and role values retain their existing Carbon/date casts; boundary backfill test passes.
- **Risk & rollback:** No stored-format changes. The period note must remain with any future payment/date refactor.

### [Phase 3] Verify schema upgrade and rollback
- **Before:** Phase 2 suite: 287 passed, 0 failed, 4 skipped, 599 assertions; no constraints or upgrade evidence.
- **After:** SQLite old-schema upgrade, rollback of all three new migrations, reapply, migrate:fresh and DemoSeeder pass. Database tests add 15 passing cases. A MySQL PDO probe to the configured 127.0.0.1:3306 fails with driver error 2002; no MySQL/MariaDB service, process or port 3306/3307 listener was found.
- **Why:** Fresh-schema checks alone do not exercise legacy duplicates, backfill or reversal.
- **Files:** tests/Feature/Database/ConstraintsTest.php; MigrationTest.php; ScaffoldingTest.php; docs/REFACTOR_LOG.md.
- **Verified by:** All commands use D:/tools/php-8.3/php.exe (8.3.36), Laravel 12.71.0. The phase report includes an offline Connection::pretend SQL appendix for new MySQL up/down statements; no server version or live MySQL execution is claimed. The preview assumes MySQL 8 grammar; data-dependent updates/deletes execute in the SQLite upgrade test, not in the preview.
- **Risk & rollback:** Live MySQL migration/rollback remains unverified until a server is available. Existing migrations were not edited; rollback removes schema additions but cannot restore duplicate rows.
### [Phase 3] Make building approval atomic and guard decided requests
- **Before:** AdminController.accept created Complex, UserRole and Group independently and accepted already-decided requests repeatedly.
- **After:** A DB::transaction locks and reloads the request, permits only pending status, creates all three records and marks accepted together. Accepted/rejected requests return the existing page with a Persian error message and no writes.
- **Why:** Repeated requests created duplicate complexes; a late insert failure left partial buildings or manager roles.
- **Files:** app/Http/Controllers/AdminController.php; tests/Feature/Admin/MembershipDecisionTest.php.
- **Verified by:** Sequential double acceptance leaves exactly one complex, manager role and group; a rejected request remains rejected; an injected NULL group name raises QueryException and rolls back the complex, role and request decision. Full suite: 305 passed, 0 failed, 4 skipped, 678 assertions (arrival: 287/0/4, 599 assertions). All four skips remain Phase 4.
- **Risk & rollback:** Re-accepting any decided request now returns an error rather than creating another building. No reject-action or route changes. Revert this controller/test commit to restore the former approval behavior; previously created duplicate buildings are not removed by this change.