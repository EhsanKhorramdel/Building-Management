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
- **Before:** `AdminController@accept` used `uniqid()` for `join_link`.
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
- **Status:** CLOSED in e81d1ba8aeca5be8cbfe61abf1ece5ef8bd2c6af. See the Phase 4 relation/null-safety, poll-defect and query-count entries below.
- **Before:** `Unit::userRoles()` references non-existent class `UserRoles`; `PollOption::votes()` uses foreign key `role_id` instead of `poll_option_id`.
- **Why it matters:** calling the first crashes; the second returns unrelated rows.
- **Cause:** typos never exercised by any test.
- **Closed by:** Phase 4 (+ tests in Phase 1 style).

### [Baseline] Null-dereference crashes
- **Status:** CLOSED in e81d1ba8aeca5be8cbfe61abf1ece5ef8bd2c6af. See the Phase 4 relation/null-safety, poll-defect and query-count entries below.
- **Before:** unguarded `->first()->…` in chat index/last-seen/viewers/seen and in charge payment; empty group or missing message → 500.
- **Why it matters:** a brand-new complex (no messages) or a tampered id crashes the page.
- **Cause:** happy-path-only coding, no empty-state handling.
- **Closed by:** Phase 4.

### [Baseline] Voting after poll expiry + option-shift bug
- **Status:** CLOSED in e81d1ba8aeca5be8cbfe61abf1ece5ef8bd2c6af. See the Phase 4 relation/null-safety, poll-defect and query-count entries below.
- **Before:** `Poll::isExpired()` exists but vote() never calls it; `pollCreate` drops/shifts options when a middle option is empty.
- **Why it matters:** results can be changed after deadline; poll options silently differ from what the manager typed.
- **Cause:** expiry was modelled but never enforced; options were counted instead of filtered.
- **Closed by:** Phase 4.

### [Baseline] Missing responsive foundation
- **Status:** CLOSED in fdcbe5e3f66157178233dc4f93bbf0606d30eb5c. See the Phase 5 entries and docs/VISUAL_QA.md.
- **Before:** building + dashboard pages have no viewport meta; dashboard CSS (1028 lines) has 5 media queries and fixed widths up to 1800px; the BUILDING page loads three conflicting Tailwind versions at once (2.2.15 CDN, 0.3 unpkg, Play CDN) on top of hand-written CSS, while the dashboard page loads one (2.2.15 CDN) — corrected in Phase 0 review; app pages bypass Vite so assets are unminified/unhashed.
- **Why it matters:** mobile layout is broken/unpredictable — the main user device for residents is a phone.
- **Cause:** pages grew as static files outside the build pipeline; Tailwind was added three times at three moments.
- **Closed by:** Phase 5.

### [Baseline] Frontend monolith + duplication
- **Status:** Backend duplication CLOSED in e81d1ba8aeca5be8cbfe61abf1ece5ef8bd2c6af (upload naming, message presentation and controller split). Frontend JS/CSRF duplication CLOSED in fdcbe5e3f66157178233dc4f93bbf0606d30eb5c (Vite modules and shared fetch/CSRF setup).
- **Before:** `public/js/building.js` is 1495 lines covering chat/polls/payments/uploads; the CSRF-token block repeats 7× (14 total references including usages — corrected in Phase 0 review); PHP builds the same message array 7×; the random upload-filename block repeats 3× (chat, announcement, incidental cost — corrected in Phase 0 review); `BuildingController` is 771 lines / 13 responsibilities.
- **Why it matters:** every fix must be made in several places; regressions hide easily.
- **Cause:** features were appended to the same files without extraction points.
- **Closed by:** Phases 4–5.

### [Baseline] N+1 query patterns
- **Status:** CLOSED in e81d1ba8aeca5be8cbfe61abf1ece5ef8bd2c6af. See the Phase 4 relation/null-safety, poll-defect and query-count entries below.
- **Before:** payments report queries per unit and per payment (`User::findOrFail` inside loops) — true DB-level N+1; vote results counted in PHP; last-seen scans all seen-rows. The building dashboard's residents loop is different (corrected in Phase 0 review): it scans ALREADY-LOADED collections in memory — quadratic CPU, no extra queries; still worth simplifying, but it is not an N+1.
- **Why it matters:** page cost grows with residents × payments; will crawl on a real building.
- **Cause:** collection processing in PHP instead of eager loading / aggregate queries.
- **Closed by:** Phase 4.

### [Baseline] No uniqueness in the database
- **Status:** CLOSED in 13e8b7d0220cc220f88f0c5de3bdeedd5e51f274 for the six requested domain keys. See [Phase 3] Persist Jalali charge-payment periods: the charge key includes period_year/period_month and is never lifetime-scoped.
- **Before:** no unique constraints on votes, seen_messages, units (per complex), payments; integrity kept only by in-code checks.
- **Why it matters:** concurrent requests create duplicates the UI assumes impossible.
- **Cause:** constraints were left to application code.
- **Phase 0 correction (important):** charge-payment duplicate protection is PER JALALI MONTH (the check compares each existing payment's created_at year+month to now) — a lifetime unique on (role_id, monthly_charge_id) would CHANGE behaviour and break paying the same charge in a later month. Phase 3 must therefore use period-scoped uniqueness (period year+month stored on the payment), not a lifetime unique. Incidental-cost payments ARE lifetime one-off, so their lifetime unique stands.
- **Closed by:** Phase 3 (as corrected above).

### [Baseline] Dark mode exists only as a skeleton (CORRECTED in Phase 0 review)
- **Status:** CLOSED in fdcbe5e3f66157178233dc4f93bbf0606d30eb5c. See the Phase 5 entries and docs/VISUAL_QA.md.
- **Before:** a working toggle + persisted setting exists (`darkMode` in the settings object of `public/js/building.js` and `public/js/dashboard/script.js`, toggling `.dark` on `<html>`), but styling coverage is ~9 `.dark` rules in `public/css/dashboard/style.css` (root vars, search/add wrappers, a few buttons) plus scattered Breeze `dark:` utilities in a few fragments. Most screens — chat, tables, modals, most forms — have NO dark styling and stay light.
- **Why it matters:** enabling the toggle produces a half-light, half-dark UI — worse than no toggle.
- **Cause:** the theme was started (mechanism first) and never rolled out across components.
- **Correction note:** the original audit entry claimed "dark mode does not exist" — the Phase 0 audit corrected it against the repository code. Phase 5 COMPLETES dark mode instead of building it from scratch: keep the existing toggle/persistence mechanism, extend coverage to every component, both themes in screenshot QA.

### [Baseline] Mixed/absent UX states
- **Status:** CLOSED in fdcbe5e3f66157178233dc4f93bbf0606d30eb5c. See the Phase 5 entries and docs/VISUAL_QA.md.
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
### [Phase 3] Record database baseline closure
- **Before:** The baseline uniqueness finding still pointed to planned Phase 3 work.
- **After:** It references schema commit 13e8b7d0220cc220f88f0c5de3bdeedd5e51f274 and the Jalali period-scoping note. Approval transaction commit: 9236f3c25d619511db444237232792b0b870a42d.
- **Why:** Closure must name the implementation that enforces the constraints, including the recurring-payment exception.
- **Files:** docs/REFACTOR_LOG.md.
- **Verified by:** Reviewed the six unique keys, period boundary test, rollback evidence and full suite (305 passed, 0 failed, 4 Phase 4 skips, 678 assertions). Pre-commit added-line style scans and diff whitespace checks pass.
- **Risk & rollback:** Documentation only; reopen the finding if the schema commit is reverted. MySQL execution remains unverified as recorded above.
### [Phase 4] Reject expired votes
- **Before:** BuildingController.vote wrote votes after the poll deadline.
- **After:** PollService.vote checks Poll.isExpired() before creating or changing a vote and returns the standard error/page redirect data. The new Persian message is: مهلت شرکت در این رای گیری به پایان رسیده است.
- **Why:** The existing expiry method was never called from voting.
- **Files:** app/Services/PollService.php; app/Http/Controllers/PollController.php; tests/Feature/KnownDefectsTest.php; Services/PollServiceTest.php; Backend/DefectRegressionTest.php.
- **Verified by:** The expired-vote test is un-skipped; an expired change preserves the previous vote, while open voting/change tests pass unchanged. Redirect, status, page and message are tested.
- **Risk & rollback:** Expired voting is now rejected as specified. Revert the Phase 4 implementation and its closure record together to restore the prior behavior.

### [Phase 4] Preserve sparse poll option order
- **Before:** pollCreate counted filled options, then stored the first N original positions, losing later options or inserting NULL.
- **After:** PollService filters empty option values, sorts their numeric positions and stores sequential option_number values starting at 1. Fewer than two non-empty values returns the existing Persian error.
- **Why:** Counting filled values did not identify their original positions.
- **Files:** app/Services/PollService.php; app/Http/Requests/PollCreateRequest.php; tests/Feature/KnownDefectsTest.php; Services/PollServiceTest.php; Backend/DefectRegressionTest.php.
- **Verified by:** The middle-blank test is un-skipped; sparse option2/option4 with blank option3 produces the original texts numbered 1/2; a single option creates no poll.
- **Risk & rollback:** Empty options no longer shift/drop subsequent text. Revert the Phase 4 implementation to restore the former creation path.

### [Phase 4] Handle empty chat and absent read receipts
- **Before:** Empty groups crashed last-seen and anchor fetch; populated groups without receipts returned their latest visible message as a last-seen fallback.
- **After:** lastSeen returns null when no surviving receipt exists, including populated groups without receipts, as required by item 1c. Empty anchor fetch returns messages=[] and null cursors. The single client guard passes cursor 0 for null; cursor 0 loads the latest page when messages exist.
- **Why:** A missing receipt/message is a valid initial state, not a model to dereference. Loading the initial page remains possible without inventing a read receipt.
- **Files:** app/Services/ChatService.php; app/Http/Controllers/GroupController.php; public/js/building.js; tests/Feature/KnownDefectsTest.php; Building/ChatTest.php; service/backend tests.
- **Verified by:** The empty-last-seen test is un-skipped; empty anchor and unread populated-chat tests pass. The one existing ChatTest expectation was changed from latest-message fallback to null and renamed because item 1c explicitly changes that case; its fixtures and other business tests are unchanged.
- **Risk & rollback:** The last_message_seen_id API now reports null without receipts. The one-line JS guard keeps initial loading usable. Revert the implementation and test expectation together if rolling back this contract.

### [Phase 4] Preserve omitted optional profile fields
- **Before:** ProfileController.update indexed ncode/phoneNumber even when validation omitted those keys, causing an undefined-key error.
- **After:** array_key_exists controls updates to national_code and phone_number. Absent keys retain their values; present empty inputs become null through the existing request middleware and clear them. Name/email handling is unchanged.
- **Why:** Nullable validation does not guarantee an optional key is present.
- **Files:** app/Http/Controllers/ProfileController.php; tests/Feature/KnownDefectsTest.php; Backend/DefectRegressionTest.php.
- **Verified by:** The omitted-fields test is un-skipped; populated optional fields survive omission and clear on explicit empty input. Existing ProfileTest.php is unchanged.
- **Risk & rollback:** Omitted optional values no longer crash or clear data. Revert the guarded assignments and associated defect tests together to roll back.

### [Phase 4] Move payload validation into FormRequests
- **Before:** Domain and authentication controllers mixed validation, error bags and raw payload reads; several payload readers had no FormRequest.
- **After:** Added AnnouncementRequest, ChargeRequest, IncidentalCostRequest, ChargePaymentRequest, IncidentalPaymentRequest, PollCreateRequest, VoteRequest, PollResultsRequest, PaymentsReportRequest, ChatStoreRequest, ChatEditRequest, ChatSeenRequest, MessageViewersRequest, UnitStoreRequest, UnitUpdateRequest, MembershipRequestStoreRequest and JoinBuildingRequest. RegistrationRequest, PasswordResetRequest, PasswordResetLinkRequest, PasswordUpdateRequest and PasswordConfirmationRequest cover the remaining authentication payload endpoints. Controllers read validated() data; session/user access and route-bound models remain request context.
- **Why:** Validation and its named error bags belong at the request boundary rather than beside persistence logic.
- **Files:** app/Http/Requests/*.php; domain controllers; UserController.php; Auth/{RegisteredUser,NewPassword,PasswordResetLink,Password,ConfirmablePassword}Controller.php.
- **Verified by:** Compared all 71 existing custom message entries across eight request classes with the baseline: identical keys and strings. Existing error bags and validation rules are retained. Fields previously read without validation use nullable pass-through rules so this extraction does not tighten acceptance. Poll option keys are collected dynamically for the specified sparse-option fix. Existing auth/domain/security tests pass without assertion changes.
- **Risk & rollback:** No Persian validation message was rewritten. Join-link validation retains its filled-input condition; missing vote selection keeps its existing business error flow. Bound-model/no-payload actions need no payload FormRequest; the unregistered profile delete method remains outside the route surface. Revert request signatures and rules with the implementation if rolling back.

### [Phase 4] Extract uploads messages chat polls and payments
- **Before:** Three filename blocks, seven message arrays and duplicated cursor queries lived in controllers; polls counted loaded votes in PHP; two controller methods created payments.
- **After:** FileUploadService owns SHA1/timestamp/two-random-number naming and per-call directories. MessagePresenter owns the HTTP/new-event shape and the existing model-shaped edit/delete payload. ChatService owns cursor fetch, storage, viewers, receipts and last-seen lookup. PollService owns option creation, voting and aggregate results. PaymentService is the only application location that creates either payment type and retains server amounts and Jalali period fields.
- **Why:** Repeated mapping and persistence logic made fixes diverge across endpoints.
- **Files:** app/Services/{FileUploadService,MessagePresenter,ChatService,PollService,PaymentService}.php; GroupController.php; domain controllers; app/Events/*ChatMessage.php.
- **Verified by:** Dedicated tests for all five services; stored names remain 40 lowercase hex characters plus detected extension in the same three directories. HTTP/new-message and model-shaped edit/delete event payloads remain distinct and unchanged. Cursor ordering, deletion filtering, ownership, both directions and exhausted cursors are tested. Payment monthly feature test is unchanged; service tests assert authoritative amounts, duplicate messages and current Jalali period fields. Chat text '0' retains its existing no-write path.
- **Risk & rollback:** Public event names/private channel scoping and payload field names are unchanged. Revert service delegates, event adapters and their callers together if rolling back the extraction.

### [Phase 4] Split the building controller by domain
- **Before:** BuildingController had 772 physical lines and mixed dashboard, role, announcement, poll, cost, charge and report actions.
- **After:** Deleted BuildingController. Routes target BuildingDashboardController (113 lines), RoleController (77), AnnouncementController (56), PollController (32), IncidentalCostController (51), ChargeController (56) and PaymentsReportController (44). GroupController falls from 401 to 97 lines. regenerateJoinLink stays with role/manager actions.
- **Why:** Independent domain actions should not share one controller's validation, queries and persistence responsibilities.
- **Files:** app/Http/Controllers/*Controller.php; routes/web.php.
- **Verified by:** route:list was captured outside the repository as the first action. All 54 method/URI/name tuples match after extraction; 14 controller targets change, with action method names preserved. The existing 168-case authorization matrix remains unchanged and passes.
- **Risk & rollback:** Member/manager middleware is applied on each new domain controller; admin access is untouched. No route name, HTTP method or URI changes. Revert routes and domain controller files together to roll back.

### [Phase 4] Guard missing models and correct the two named relations
- **Before:** Null-sensitive sites were BuildingController.index (announcement manager and resident user lookups), upgradeRole (complex), vote (option), buildingChargePayment (charge); GroupController index/store/last-seen/before/after (group), index (anchor), viewers/seen/edit (message), last-seen (final fallback); UserController.index (user) and update (exists-then-first race).
- **After:** Dashboard uses keyed users/units, eager-loaded manager and null-safe existing N/A fallbacks; RoleController uses findOrFail for the parent complex. PollService/PaymentService use findOrFail for options/payables. GroupController resolves its group with firstOrFail; ChatService handles empty/missing anchors explicitly and finds messages with findOrFail; lastSeen returns a nullable scalar. UserController uses the authenticated user and firstOrFail after the unit existence check. Unit.userRoles references UserRole::class; PollOption.votes uses poll_option_id.
- **Why:** Missing records must not be dereferenced, and relation keys must reference the actual model/table linkage.
- **Files:** domain controllers; GroupController.php; UserController.php; ChatService.php; PollService.php; PaymentService.php; app/Models/{Unit,PollOption}.php; Backend/RelationsTest.php.
- **Verified by:** Re-grep finds no chained first()-> or first()[...] dereferences in controllers. Remaining first() calls are explicitly checked or null-safe. Tests invoke both repaired relations and assert exact row IDs; missing chat resources produce explicit model/HTTP failures and empty groups return the specified payload.
- **Risk & rollback:** Invalid/missing anchors in populated groups now return 404 instead of crashing; empty groups use the item-1c response. The unit-number lookup already had an N/A fallback and retains it. Unrelated audited relations were not changed. Revert these guards/relations with the implementation to roll back.

### [Phase 4] Keep report query counts constant
- **Before:** Measured the controller-only payments report at 12 queries for 2 units/4 payments and 42 for 8 units/16 payments. It queried roles, payment types and users inside loops. Dashboard scanned role/user/unit collections repeatedly; last-seen loaded receipt collections and queried messages per receipt.
- **After:** PaymentsReportController eager-loads roles, users and both payment types before iteration: 6 queries at both sizes. The existing first-role-per-unit selection and charge-then-cost ordering are preserved. Dashboard groups roles by user and keys users/units once; poll options and announcement managers are eager-loaded. lastSeen uses one joined ordered query with LIMIT 1. Poll results use one grouped COUNT query, including zero-vote options.
- **Why:** Query cost should not grow with the report's unit/payment count, and loaded collections should not be rescanned for every resident.
- **Files:** PaymentsReportController.php; BuildingDashboardController.php; ChatService.php; PollService.php; Backend/QueryShapeTest.php; service tests.
- **Verified by:** Same external fixture/harness measured before 12/42 and after 6/6; the HTTP endpoint test asserts 8/8 including authorization with lazy loading prohibited. Shared-unit first-role output is tested. Chat/poll service tests each assert one query and the expected ordering/aggregate SQL.
- **Risk & rollback:** No all-roles report expansion or eligibility change. Eager loading uses more memory per report while removing repeated queries. Revert the query/presentation changes together to roll back.

### [Phase 4] Remove unused controller scaffolding and client setup
- **Before:** Dead implementations/comments and unused imports obscured the routed code.
- **After:** Deleted AdminController.create/store/show/edit/update/destroy (six empty methods), GroupController.create/show/update (three empty methods), the commented validation/update block in ProfileController, the commented old rules in ProfileUpdateRequest and RegisteredUserController's commented HOME redirect. Removed the entire commented Echo setup from resources/js/bootstrap.js; direct Pusher remains the active client.
- **Why:** None of those methods/blocks had a live route or executable behavior; keeping Echo setup suggested a second client that was never initialized.
- **Files:** AdminController.php; GroupController.php; ProfileController.php; ProfileUpdateRequest.php; Auth/RegisteredUserController.php; resources/js/bootstrap.js; controller/event imports.
- **Verified by:** Route tuple comparison and full suite. Removed imports were checked against executable code per file: AdminController Request; PaymentsReportController ChargePayment/PaymentAdditionalCost/User/UserRole; ProfileController Rule/User; UserController User; AuthenticatedSessionController RouteServiceProvider; NewPasswordController Rules; PasswordController Rules\Password; PasswordResetLinkController Request; RegisteredUserController RouteServiceProvider/Request/Rules; NewChatMessage/EditChatMessage/DeleteChatMessage Channel (used only in old docblocks, updated to PrivateChannel). Three Auth controller ValidationException imports introduced by formatting were docblock-only; their generic throws annotations/imports were removed too.
- **Risk & rollback:** No registered resource action was deleted. Axios and the direct Pusher client are retained; no Echo dependency or broadcasting configuration was changed. Revert the listed deletions if restoring that source layout.

### [Phase 4] Add service regression evidence and update test coverage notes
- **Before:** Phase 3 had 305 passed, 4 skipped, 678 assertions; no tests targeted extracted services or constant query counts.
- **After:** Added Services/{FileUploadService,MessagePresenter,ChatService,PollService,PaymentService}Test.php and Backend/{Relations,QueryShape,DefectRegression}Test.php: 23 new cases. Four skipped tests are enabled. tests/README.md records zero skips and the service/query coverage.
- **Why:** Existing endpoint tests protect behavior while service tests check the extracted boundaries and query shapes directly.
- **Files:** tests/Feature/Services/*.php; tests/Feature/Backend/*.php; tests/Feature/KnownDefectsTest.php; tests/Feature/Building/ChatTest.php; tests/README.md; docs/REFACTOR_LOG.md.
- **Verified by:** Full suite: 332 passed, 0 failed, 0 skipped, 776 assertions. Existing feature tests edited: KnownDefectsTest.php removes only four skip declarations; Building/ChatTest.php changes only the no-receipts test name/expected null value required by item 1c. All other pre-existing feature files, including the monthly payment and authorization matrix tests, are unchanged. SQLite migrate:fresh + DemoSeeder, Blade compilation, npm run build and node syntax check pass. Every Artisan command used D:/tools/php-8.3/php.exe (PHP 8.3.36, Laravel 12.71.0).
- **Risk & rollback:** No dependencies or migrations changed. Revert the implementation and matching service/defect tests together. Browser QA remains in its planned phase.
### [Phase 4] Record backend baseline closures
- **Before:** Baseline entries still pointed to planned backend work without closing commit references.
- **After:** Relation, null-crash, voting/options and query-pattern findings reference e81d1ba8aeca5be8cbfe61abf1ece5ef8bd2c6af. The mixed frontend/backend duplication entry closes only its backend portion and leaves frontend JS/CSRF work for Phase 5.
- **Why:** Closure must identify the implementation while preserving the remaining frontend scope.
- **Files:** docs/REFACTOR_LOG.md.
- **Verified by:** Reviewed the implementation against the baseline entries; 332 passed, 0 failed/skipped, 776 assertions; 54 route tuples unchanged; query-count evidence recorded above.
- **Risk & rollback:** Documentation only. Reopen these findings if the implementation is reverted. Phase 5 has not started.
### [Phase 5] Capture the responsive baseline and verify the final matrix
- **Before:** No repeatable browser capture or overflow/console guard existed.
- **After:** Playwright runs Chromium at 360, 390, 768, 1024 and 1440px. It captures 13 screen types. The before set contains 65 light screens and 5 dark dashboard screens. The after set contains 65 light and 65 dark screens. Captures and browser results stay under the ignored storage/app/test-screenshots directory.
- **Why:** A successful build could not show clipped phone controls or incomplete dark surfaces.
- **Files:** playwright.config.js, tests/Browser/visual.spec.js, .gitignore, package files, docs/VISUAL_QA.md.
- **Verified by:** 70 before and 130 final after captures. All five after width tests pass. All 130 have zero document overflow, console/page errors, broken visible images and unlabelled form controls. Reviewed the final contact sheets and full-size phone/dialog samples.
- **Risk & rollback:** Local demo credentials are supplied through environment variables. The managed Chromium download returned regional HTTP 403, so both sets use installed Chrome via Playwright's Chromium channel. Revert the test setup and ignore entry together if removing the capture harness.

### [Phase 5] Route all page assets through Vite
- **Before:** Dashboard and building loaded four Tailwind CDN styles/scripts. Auth and admin added CDN styles. Chart.js 2.9.4, randomcolor and Pusher were loaded as browser globals.
- **After:** A shared Blade assets component loads the Tailwind 3.4 CSS and Vite entries on every page. Tailwind scans Blade and source modules. Chart.js 4.6.1 registers the doughnut controller, arcs, legend and tooltip, with legend options under plugins.legend. A deterministic HSL palette replaces randomcolor. The existing npm Pusher package supplies the client. Building and dashboard render viewport and CSRF meta tags.
- **Why:** Multiple CSS versions and browser globals produced order-dependent rendering and extra network dependencies.
- **Files:** vite.config.js, tailwind.config.js, package files, resources/views/components/page-assets.blade.php, page heads, resources/js/building/poll.js and chat.js.
- **Verified by:** Vite build passes, the requested CDN grep returns no matches, all screen captures have viewport metadata, and the vote/results smoke check passes in both themes.
- **Risk & rollback:** A Vite build is required before serving the views. Revert entry imports and Blade asset tags together when rolling back.

### [Phase 5] Replace static CSS with themed Vite styles
- **Before:** The dashboard stylesheet was 1,028 lines. The four static stylesheets totaled 1,780 lines and 35,553 bytes, in addition to the 3-line Tailwind entry and 31 nonblank lines in four inline style blocks.
- **After:** 342 CSS source lines across app.css, theme.css, panels.css, chat.css and pages.css. Tailwind preflight replaces normalize.css. Component classes use Tailwind for layout and CSS tokens for custom surfaces, bubbles, scrollbars and print. Two IranSans WOFF2 files are bundled and fingerprinted. All four old public CSS files and the inline style blocks are retired.
- **Why:** The original standalone styles repeated layout and normalization while inline backgrounds prevented consistent theming.
- **Files:** resources/css/, resources/fonts/, public/css/ deletions, auth/admin and panel views.
- **Verified by:** Built CSS is 60,553 bytes, compared with 56,413 bytes of prior Vite CSS plus 35,553 bytes of static CSS. Measured decoded bytes from the four retired CDN stylesheet URLs add 6,145,431 bytes to the old inventory. Full asset figures and measurement boundaries are in the phase report.
- **Risk & rollback:** Browser layout depends on the shared CSS bundle. Revert the view/CSS changes together. No stored data depends on the styles.

### [Phase 5] Split browser code by panel
- **Before:** public/js/building.js was 1,501 lines at arrival. It repeated the CSRF lookup, used hidden text nodes for IDs and contained fetch calls with silent failures. The dashboard had its own settings code and a listener for a missing button.
- **After:** Building imports API, navigation, chat, poll, payments, announcements, uploader and settings modules. Dashboard and admin have separate entries. One fetch helper reads the CSRF meta tag and sends Persian error toasts. Building reads one escaped JSON config for complex/user IDs, Pusher settings and authorization URL. Cards, poll options, invoice URLs and payables use data attributes. Private subscriptions use private-group.{complexId} and /broadcasting/auth. The three obsolete public JS files are removed.
- **Why:** Hidden layout text was an unstable data source, repeated fetch setup obscured failures, and unrelated UI actions shared a single script.
- **Files:** resources/js/, building/dashboard Blade data attributes, deleted public/js files.
- **Verified by:** Real local chat send/edit/delete, vote, payments list and settings toggle. One fetch call site remains in the source. A controlled failed report request shows a toast. The existing chat routes, payloads and Phase 2 private channel names are preserved.
- **Risk & rollback:** Chat exposes previous/next page controls in its header. The seeded server uses the log broadcaster, so live Pusher credentials were not exercised. Revert the JS entries and dependent views together if rolling back.

### [Phase 5] Fit navigation and content to the viewport
- **Before:** Phone pages kept the sidebar beside the content. The chat composer was below the visible pane, settings clipped the join field, and admin actions crowded the header.
- **After:** Under 768px the sidebar is a drawer with overlay and close control. Cards and headers wrap, modals have an internal scroll limit, report tables scroll in their container, and the chat composer stays at the bottom of its scrolling shell. Phone actions use 40px targets.
- **Why:** Fixed columns and viewport-height content made phone actions hard to reach.
- **Files:** resources/css/panels.css, chat.css, pages.css, navigation module, dashboard/building/admin views.
- **Verified by:** The five-width matrix passes. Visual review caught a CSS order conflict that let the drawer intercept clicks. The selector fix keeps the closed drawer off the page. Phone chat, settings, modal and report captures were inspected.
- **Risk & rollback:** Mobile navigation now opens explicitly. Reverting layout requires reverting the drawer markup and navigation binding together.

### [Phase 5] Complete the existing dark theme
- **Before:** The userSettings cookie and html.dark toggle existed, but cards, auth, modals, chat, admin and charts retained light styling.
- **After:** The same cookie key and JSON darkMode/viewMode fields drive html.dark and Tailwind's class strategy. Theme tokens cover surfaces, fields, nav, cards, tables, chat sides/menus/timestamps and chart colors. The cookie uses the root path across pages. Changing a preference clears the obsolete building-path copy after reading the old value.
- **Why:** The theme skeleton colored only part of each page and the default cookie path could isolate the preference to one building page.
- **Files:** resources/js/building/settings.js, resources/css/theme.css and component styles, tailwind.config.js, theme button and page assets components.
- **Verified by:** 65 dark captures, both chart themes, persisted choice across building reload and dashboard navigation, and the original cookie shape in both capture modes.
- **Risk & rollback:** Rollback keeps the persisted JSON readable by the old toggle. Dark overrides and shared asset loading must be reverted together.

### [Phase 5] Unify flash messages and request errors
- **Before:** Server alerts, duplicate status blocks and chat-generated alerts used separate markup and lifetimes.
- **After:** One Blade toast host displays server flash and validation summaries. The same JS function handles request success/errors, dismissal and six-second timeout. Field errors remain next to their controls.
- **Why:** Users need visible feedback for server redirects and failed asynchronous requests.
- **Files:** resources/views/components/toasts.blade.php, resources/js/ui.js, building/api.js, auth/panel views, deleted building/alert.blade.php.
- **Verified by:** Vote success flash, poll validation failure, controlled 503 error and automatic dismissal in the local browser.
- **Risk & rollback:** Toasts are transient. Inline validation still conveys the field error after a toast closes. Revert the host, callers and former flash markup together.

### [Phase 5] Confirm destructive actions
- **Before:** Delete message, step down, reject request and cancel announcement ran immediately.
- **After:** A shared native dialog confirms all four actions. The existing POST/DELETE routes, CSRF tokens and business outcomes are preserved.
- **Why:** A tap or click should not commit a destructive action before the user can cancel.
- **Files:** toast/confirmation component, ui.js, chat.js, settings, announcement and admin views.
- **Verified by:** Cancel and confirm chat delete, Escape to cancel announcement/downgrade/rejection, and unchanged backend authorization/mutation tests.
- **Risk & rollback:** A confirmed action still uses the original endpoint. Remove the confirmation bindings and dialog together to roll back.

### [Phase 5] Preserve form feedback and prevent repeat submits
- **Before:** Buttons stayed enabled during requests and most named-bag validation was summarized above the form.
- **After:** Submit controls become disabled and busy during navigation or chat requests. Fields restore safe old values and display existing error text with aria-invalid and aria-describedby. The building form reopens on named validation errors and focuses the invalid input. Passwords and files follow browser/server rules for re-entry.
- **Why:** Repeat clicks could resubmit a request and detached errors made corrections difficult.
- **Files:** resources/js/ui.js, building/chat.js, navigation.js, dashboard/building/auth forms.
- **Verified by:** Invalid poll submission preserved its title, reopened with the question error and focused that input. Chat controls re-enable after requests. Backend tests remain identical.
- **Risk & rollback:** Form methods, names and actions remain the existing ones. Remove client disabling and view feedback together if rolling back.

### [Phase 5] Add list loading and empty states
- **Before:** Some empty lists showed no guidance, while chat and reports lacked a clear loading/error state.
- **After:** Dashboard, building lists and admin tabs show an icon and one-line guidance when empty. Chat, report and poll results show request status and empty/error text.
- **Why:** An empty list and a failed request need different user feedback.
- **Files:** dashboard/building/admin views, theme CSS, chat, payments and poll modules.
- **Verified by:** Seeded chat, payment and chart captures. The capture waits for report rows. A forced report failure shows both its error state and toast.
- **Risk & rollback:** UI-only state. Remove presentation branches with their handlers if rolling back.

### [Phase 5] Add install metadata
- **Before:** Pages had no shared web manifest or theme color.
- **After:** Shared page assets advertise a standalone manifest, theme color and 192px/512px icons. The theme color follows the selected appearance.
- **Why:** The browser needs app name, launch URL, display mode and icons to install the site.
- **Files:** public/app.webmanifest, public/assets/images/app-icon files, shared page assets, settings.js.
- **Verified by:** Chrome installability check reports no errors in a temporary normal profile and both icon URLs load.
- **Risk & rollback:** Installation uses the existing online site. Remove the manifest link and icons to roll back the metadata.

### [Phase 5] Print the payment report
- **Before:** Payment cards shared the same screen layout when printed.
- **After:** A print action uses the report table on white with black text and hides the sidebar and controls.
- **Why:** Printed reports need all columns and the transaction data without navigation.
- **Files:** resources/views/building/payments.blade.php, building/payments.js, resources/css/panels.css.
- **Verified by:** The table loads from the existing endpoint and stays visible in print media while the header/sidebar are hidden.
- **Risk & rollback:** Print is local and read-only. Revert the button and print CSS together.

### [Phase 5] Label controls and share Blade components
- **Before:** Several controls relied on placeholders or icon clicks. Dialogs did not consistently restore focus. Cards, close/view icons and alerts repeated markup.
- **After:** Controls have labels, images have Persian alt text, buttons replace click-only controls, focus is visible and Escape closes dialogs with focus restoration. Blade components cover cards, icons, theme/drawer controls and toast markup.
- **Why:** Keyboard and assistive-technology users need named controls and predictable focus.
- **Files:** resources/views/components/, dashboard/building/auth/admin views, ui.js, theme.css.
- **Verified by:** All 130 captures pass the form-label/image checks. Unit and chart dialogs restore focus on Escape. The suite passes 332 tests, 0 failed, 0 skipped, 776 assertions. All 54 route entries match Phase 4, including controller targets. No existing feature test was edited.
- **Risk & rollback:** UI components and dependent Blade callers must be reverted together. The backend and route files are unchanged.

### [Phase 5] Record frontend baseline closures
- **Before:** Responsive foundation, frontend duplication, dark coverage and UX findings still pointed to planned Phase 5 work.
- **After:** All four reference fdcbe5e3f66157178233dc4f93bbf0606d30eb5c and the visual review evidence.
- **Why:** The closure record must identify the implementation and its checks.
- **Files:** docs/REFACTOR_LOG.md.
- **Verified by:** 332 backend tests passed with 776 assertions, 54 route entries matched, all 130 after captures passed, and both diff style scans are clean.
- **Risk & rollback:** Documentation only. Reopen the findings if the frontend implementation is reverted.

### [Phase 6] Document domain rules and response contracts
- **Before:** The recurring payment rule, chat cursor contract and channel authorization conventions were scattered across code. Disabled scaffold statements and old stock descriptions obscured the active paths.
- **After:** Comments explain Jalali payment periods, server-owned prices, chat cursor and payload shapes, poll counts, channel authorization and the retained kernel layout. Central models identify their key relations. Removed 13 disabled statements and values across the app and web routes, two misleading descriptions and the boilerplate notes on reset handling and model relations. No unowned task markers were present.
- **Why:** The same charge is payable again next month, chat clients depend on cursor and payload details, and role checks must follow the resource's complex.
- **Files:** app/ models, services, controllers, events, middleware and providers, routes/channels.php, routes/web.php and resources/js/ comments.
- **Verified by:** 111 files reviewed. The commented PHP grep under app/ returned zero matches. The task-marker grep under app/, routes/ and resources/js/ also returned zero. PHP token and JavaScript syntax-tree comparisons preserved the executable source. The suite passed 332 tests with 776 assertions.
- **Risk & rollback:** Comments and annotations only. Reverting them leaves runtime behavior unchanged.

### [Phase 6] Replace the stock readme with project setup
- **Before:** The readme described Laravel and the framework upgrade rather than the app or a fresh local install.
- **After:** The readme covers local SQLite, demo accounts, configuration, tests, a fallback when the default server port is occupied, and five representative Phase 5 images.
- **Why:** A new checkout needs the database settings and setup sequence in one place.
- **Files:** README.md and docs/screenshots/.
- **Verified by:** All five images match their Phase 5 after captures. The clean-room entry records setup verification.
- **Risk & rollback:** Demo credentials are shared and limited to local seeding. The new text and screenshots can be reverted together.

### [Phase 6] Record the architecture and deployment boundaries
- **Before:** Domain relationships, scoped permissions, stored dates and broadcast behavior were documented across phase entries.
- **After:** One document summarizes the entity map, permission matrix, date conventions, private chat flow, tests and deployment requirements. It identifies live MySQL and Pusher verification as pending.
- **Why:** Deployment and feature changes need an explicit account of what the current application runs and what remains unverified.
- **Files:** docs/ARCHITECTURE.md.
- **Verified by:** Cross-checked against the models, scoped authorization tests, migrations, broadcaster config and visual QA record.
- **Risk & rollback:** Documentation only. Update the matrix and date inventory with any change to those contracts.

### [Phase 6] Summarize the refactor in the changelog
- **Before:** Phases 0 through 5 were recorded in the engineering log.
- **After:** One Unreleased section summarizes the user-visible changes and links to the engineering record.
- **Why:** Readers need a short account of the security, responsiveness and workflow fixes without tracing individual commits.
- **Files:** CHANGELOG.md.
- **Verified by:** Compared the entries with the Phase 0 through 5 log and checked the engineering log link.
- **Risk & rollback:** Documentation only. The engineering log remains the detailed record.

### [Phase 6] Verify the readme from a clean checkout
- **Before:** The local setup had no end-to-end check from a fresh checkout.
- **After:** Cloned the phase branch to a separate directory on D:. Installed Composer and npm dependencies, copied the environment template, configured SQLite, generated the key, ran all 22 migrations, seeded demo data, linked storage and built assets. A server from the main checkout occupied port 8000. Added a documented port 8001 fallback, fast-forwarded the scratch checkout and retried serving. The demo manager signed in and the seeded chat image loaded. Stopped the scratch server and moved the scratch copy to the Recycle Bin.
- **Why:** The setup needs to work without a prepared database, installed dependencies or built assets.
- **Files:** README.md, docs/REFACTOR_LOG.md. The scratch checkout was outside the repository.
- **Verified by:** Setup started at e25d0f25a31db69daee8902e0e5c4eeb0dfd3987 and the retry and tests ran at 017d39a869094a481b9a93ee9e52b7d8dbe65a38. The sign-in redirected to the dashboard with the seeded complex visible. The storage image returned HTTP 200. Both checkouts passed 332 tests with 776 assertions. The test files are unchanged.
- **Risk & rollback:** The check used only a local SQLite database and the log broadcaster. Live MySQL migrations and Pusher delivery still require their deployment environments.

### [Phase 7] Use the existing complex coordinate columns
- **Before:** Phase 3 defined nullable decimal(10,7) latitude and longitude on complexes. The model did not expose them for writes or cast them.
- **After:** Complex accepts both coordinates and reads them as floats. The existing free-text location field keeps its behavior.
- **Why:** Forms and map data need the stored point as a numeric pair.
- **Files:** app/Models/Complex.php.
- **Verified by:** Checked the Phase 3 migration and the SQLite schema. The settings test reloads both coordinates as floats and verifies that the text location is unchanged.
- **Risk & rollback:** The Phase 3 schema remains in place. Reverting the map can leave saved complex coordinates in those columns.

### [Phase 7] Keep registration coordinates with the pending request
- **Before:** Building registration writes a pending membership request. Complexes only exist after approval, and the request table had no coordinate columns.
- **After:** One migration adds the nullable decimal(10,7) pair to membership_requests. The applicant's point stays with the request and is copied to the complex inside the existing approval transaction. An approval without coordinates leaves them null. Rejection still creates no complex. An expandable map in the admin request card shows the submitted point without edit controls.
- **Why:** The applicant knows the location when submitting the other building details. The approver needs to see that same point before deciding.
- **Files:** database/migrations/2026_11_09_000004_add_coordinates_to_membership_requests.php, MembershipRequest, UserController, AdminController, admin request cards and map component.
- **Verified by:** Registration saves the pair as floats. Tests approve requests with and without coordinates and check the read-only review markup. The browser submitted a point and opened it in the pending admin card before approval.
- **Risk & rollback:** The migration is limited to pending-request storage and leaves existing migrations untouched. Rolling it back drops the saved coordinates on membership requests.

### [Phase 7] Validate the coordinate pair in both forms
- **Before:** Registration had no coordinate validation and settings had no location save.
- **After:** Both requests require a numeric latitude in -90..90 and longitude in -180..180 when either is supplied. Persian validation errors remain in each form's own error bag. Values are rounded to the storage precision of seven decimal places. Omitting both fields keeps the saved settings. Submitting both blank clears them.
- **Why:** Partial points cannot be mapped, and SQLite and MySQL should write the same precision.
- **Files:** ComplexLocationRequest, MembershipRequestStoreRequest, coordinate picker and ComplexLocationController.
- **Verified by:** Feature cases cover both bounds, both missing-partner cases, a nonnumeric value, omission, clearing, and persistence with a zero coordinate.
- **Risk & rollback:** A malformed pair is rejected before any write. Removing the forms and their validation together reverts this behavior.

### [Phase 7] Bundle the location picker
- **Before:** The registration and settings screens could not select a point.
- **After:** The shared picker supports map click, marker drag, editable coordinate fields, browser location and clearing. It opens at zoom 15 for a saved point or at Tehran at zoom 11 for an empty pair. Hidden map panels initialize once they have dimensions. Vite bundles Leaflet 1.9.4, its CSS and explicit standard, retina and shadow image imports.
- **Why:** The picker needs to work in both layouts without relying on a remote script or runtime marker path detection.
- **Files:** package.json, package-lock.json, resources/js/building/map.js, Vite page entries, resources/css/map.css and coordinate picker component.
- **Verified by:** The production build emitted the marker PNGs and the browser rendered the marker from those assets. The browser checked click, drag, typed values, clearing and geolocation. A simulated denial left the fields empty and showed a toast.
- **Risk & rollback:** The browser may deny geolocation. Manual entry remains available. Remove the picker calls and imports together when reverting.

### [Phase 7] Show the complex location on the home tab
- **Before:** The building home tab had no map.
- **After:** The address sits above a map for saved coordinates, with zoom controls and an external directions link. Dragging and wheel zoom are disabled. Without coordinates, the address stays visible and only managers get a settings hint. Coordinates are passed through Blade data attributes.
- **Why:** Members need to see the location without changing it or losing page scroll to the map.
- **Files:** building home view, building page entry, map component and styles.
- **Verified by:** Feature cases cover manager and resident views with and without coordinates. The browser saved a point in settings and found the same marker on home.
- **Risk & rollback:** Removing the display leaves saved coordinates and the address intact.

### [Phase 7] Configure the tile source
- **Before:** There was no tile configuration.
- **After:** MAP_TILE_URL and MAP_TILE_ATTRIBUTION default to OpenStreetMap's standard tiles and contributor attribution. Both overrides appear as commented examples.
- **Why:** Deployments can use a local mirror or another source without a rebuild.
- **Files:** config/map.php, .env.example, map component and README.md.
- **Verified by:** Live browser requests returned OpenStreetMap tiles and showed attribution. The Vite bundle uses rendered tile configuration.
- **Risk & rollback:** Tile availability depends on the configured source. Clearing both overrides restores the defaults.

### [Phase 7] Limit location settings to the scoped manager
- **Before:** The manager settings panel had no coordinate write action.
- **After:** PATCH /dashboard/building/{complex}/location uses the existing authenticated manager middleware and Complex policy. Existing route names, methods and URLs are unchanged.
- **Why:** Only active managers in the target complex can change its settings.
- **Files:** routes/web.php, ComplexLocationController and building settings view.
- **Verified by:** The matching manager can save. Foreign managers, residents, inactive managers and global admins without a matching role receive 403. Guests are redirected to sign in.
- **Risk & rollback:** Removing the endpoint and form removes manager editing without affecting existing authorization.

### [Phase 7] Verify the map and document its setup
- **Before:** The visual suite had no map picker or saved-map captures.
- **After:** The map test saves a location, opens home and captures both screens at 390px and 1440px in light and dark. The README lists map locations and the tile settings. The architecture and changelog record the registration and manager flows.
- **Why:** The map must stay usable in both themes and viewport sizes after a real form save.
- **Files:** tests/Browser/map.spec.js, ComplexLocationTest, MembershipLocationTest, docs/VISUAL_QA.md, README.md, docs/ARCHITECTURE.md and CHANGELOG.md.
- **Verified by:** The PHP suite passed 354 tests with 895 assertions, up from 332 tests and 776 assertions. SQLite migrate:fresh and DemoSeeder passed. The asset build passed. Four browser cases captured eight screens with no horizontal overflow or console errors. Mobile zoom buttons meet the 40px target.
- **Risk & rollback:** Browser captures use an isolated seeded database because the flow writes a location. The screenshots remain outside Git.

### [Phase 8] Index gateway authority and add the SMS queue table
- **Before:** Transactions already had payable, amount, gateway, authority, reference and meta fields. SMS logs already had driver and meta fields. The database queue was configured, but its jobs table was absent.
- **After:** One new migration adds uniqueness on transactions.gateway and authority. A second migration creates the standard jobs table for the dedicated SMS worker. Both models define their lifecycle statuses.
- **Why:** Repeated callbacks must resolve to a single attempt. SMS work must remain durable without switching the app's existing synchronous queue.
- **Files:** Transaction, SmsLog and migrations 2026_11_09_000005 and 2026_11_09_000006.
- **Verified by:** Duplicate authority insertion is rejected. Fresh SQLite migrations, the existing reversal check and DemoSeeder pass.
- **Risk & rollback:** Existing migrations and columns remain. Removing the jobs table discards queued work, so drain it before rollback.

### [Phase 8] Add payment driver configuration and the local checkout
- **Before:** Charge and cost payments could only write records inside the app.
- **After:** The payment flag defaults off and the manager resolves either the mock or the generic HTTPS driver. The mock page is registered for the selected mock driver only. Production requires an explicit mock selection. The reference driver takes credentials, endpoints, status values and a JSON field map from config.
- **Why:** Local checkout needs a complete simulation. A deployment using the reference HTTP contract can set its field names and credentials without changing application code.
- **Files:** config/payments.php, .env.example, PaymentGateway, GatewayManager, MockGateway, GatewayDriver and mock checkout view.
- **Verified by:** Tests cover production mock opt-in, missing credentials with no HTTP call, mapped request and nested response fields, and mock Pay and Cancel in a browser.
- **Risk & rollback:** Keep the flag off until a matching provider contract has been verified in a sandbox. A cached route list must be refreshed when the selected driver changes.

### [Phase 8] Verify callbacks and write payments through the existing service
- **Before:** The payment writer had no online entry point or verification step.
- **After:** Initiation checks the complex and unit role, reads the amount from the payable and blocks paid or already pending items. The callback locks the attempt before verification, checks expiry, rechecks uniqueness and records the payment and reference atomically. Both online and manual payments use PaymentService's amount, duplicate and write methods. The verified payment uses the Jalali month at settlement.
- **Why:** A client cannot choose a price or create duplicate payments by replaying a callback. SQLite needs a write to serialize attempts because it does not implement row locks.
- **Files:** OnlinePaymentRequest, OnlinePaymentController, OnlinePaymentService, PaymentService and routes/web.php.
- **Verified by:** Mock and HTTP-fake tests cover paid, cancelled, failed and expired attempts, server amount checks, replay, database uniqueness, duplicate-at-settlement checks and role authorization.
- **Risk & rollback:** The callback route alone is exempt from CSRF because an external gateway cannot supply a session token. Mock POSTs still check the payer and session token. Reconciliation may be needed when a verified provider result conflicts with the payable or an existing payment. Its reference is retained.

### [Phase 8] Switch the payment UI only while enabled
- **Before:** Building payment forms always wrote manual records and there was no online history.
- **After:** With payments enabled the forms start online checkout, the old manual POSTs refuse writes, and the Payments tab shows only the signed-in user's attempts in that complex. With the flag off the original actions, form fields and manual flow are used.
- **Why:** An enabled checkout must verify funds before writing a payment. Another resident's attempts must stay private.
- **Files:** ChargeController, IncidentalCostController, BuildingDashboardController, building payment views and browser module.
- **Verified by:** The flags-off baseline passes unchanged. Added tests cover the disabled UI and manual path and isolate history by payer and complex. The browser captures show both online actions.
- **Risk & rollback:** Turning the flag off hides online history and restores the manual forms and endpoints. Existing attempts remain stored.

### [Phase 8] Add the log and HTTPS SMS drivers
- **Before:** The SMS log table had no application writer.
- **After:** The SMS flag defaults off. SmsService writes one delivery row per recipient and selects a local log driver or a generic HTTPS sender. The HTTP sender uses bearer authentication, records the response status and fails closed on missing configuration.
- **Why:** Deliveries and failures need a local record without assuming a provider.
- **Files:** config/sms.php, .env.example, SmsSender, SmsService, LogSmsSender and GatewaySmsSender.
- **Verified by:** HTTP fakes assert the URL, headers and JSON payload. Responses 202 and 500 become sent and failed rows. Missing config sends no request.
- **Risk & rollback:** Keep the flag off until the endpoint and worker are configured. Logs contain the recipient and message body.

### [Phase 8] Queue SMS after the four successful actions
- **Before:** Announcements, new charges, paid attempts and approvals sent no notifications.
- **After:** A dedicated database queue processes the four triggers after their writes. A user without a phone is skipped and logged. The charge text uses its Jalali start month. Persian messages are grouped in lang/fa/sms.php. The integration translation namespace leaves the existing resource translations in place.
- **Why:** Provider failures and network time must stay outside the action. A receipt should be queued only when an attempt first becomes paid.
- **Files:** SendSmsJob, SmsService, announcement, charge and approval controllers, OnlinePaymentService, AppServiceProvider and lang/fa/sms.php.
- **Verified by:** Each trigger produces zero jobs and logs with SMS off. With it on, the worker receives the expected recipient set. A 500 and an enqueue exception leave the announcement successful. Callback replay queues only one receipt.
- **Risk & rollback:** SMS requires a worker on the sms queue. Disabling the flag prevents new jobs and makes any queued job skip delivery.

### [Phase 8] Verify default-off behavior and document local integration QA
- **Before:** The suite and visual captures covered manual payments only.
- **After:** New cases cover online payment and SMS paths. Browser captures show mock checkout and the enabled Payments tab. The README documents env settings and the worker command. The architecture notes cover lifecycle and recipients.
- **Why:** The local flow needs repeatable proof without a real provider account.
- **Files:** OnlinePaymentTest, SmsIntegrationTest, integrations.spec.js, README.md, ARCHITECTURE.md, CHANGELOG.md and VISUAL_QA.md.
- **Verified by:** All 354 existing tests passed with 895 assertions, both flags off and no edits. The full run passed 387 tests with 1,102 assertions. It starts with both flags off and the 21 payment and 12 SMS cases opt in as needed. The build, fresh SQLite setup and four browser cases passed. Final captures have no overflow or browser errors.
- **Risk & rollback:** The browser spec writes demo payments. Use a fresh isolated demo database. HTTP integrations were verified against fakes.

### [Phase 8B] Compare the original application and audit the accepted build
- **Before:** Individual phase reports covered their own changes. The project had no current side-by-side comparison or combined record of browser and performance findings.
- **After:** The comparison records both revisions, measured query counts and the original page controls. Findings records the browser, accessibility and security evidence. Proposals groups work outside the current plan for owner review.
- **Why:** The owner needs to distinguish regressions and usability gaps from optional improvements before the next phase.
- **Files:** docs/COMPARISON.md, docs/FINDINGS.md, docs/PROPOSALS.md and this entry.
- **Verified by:** The current PHP suite passed 387 tests with 1,102 assertions. Both frontend builds completed. The authorization matrix passed 168 cases. The sweep produced 250 screenshots with 49 raw guard flags, including six measurements of the hidden file input whose visible proxy passed. Poll creation returned HTTP 500 from browser forms and voting passed on an existing poll. The audit recorded 13 findings.
- **Risk & rollback:** Documentation only. The findings need separate approval before changes are made. Browser writes used isolated demo data and the audit captures are ignored by Git.

### [Phase 8C] F01 Normalize browser poll durations
- **Before:** Validated HTML form durations reached Carbon as strings or null.
- **After:** PollService casts all three parts to integers and treats omitted parts as zero.
- **Why:** Carbon 3 expects numeric PHP values when adding time.
- **Files:** app/Services/PollService.php, BrowserPollDurationTest, findings.spec.js.
- **Verified by:** Hours-only, all-parts and days-only requests stored the expected end times. The browser created, listed and voted on the same hours-only poll.
- **Risk & rollback:** The duration rules and option handling are unchanged. Reverting the casts restores the browser type error.

### [Phase 8C] F02 Restore jump-to-latest in chat
- **Before:** The previous and next controls replaced the original jump-to-latest action.
- **After:** A labelled button requests the latest page with the zero cursor, replaces the visible window and scrolls to the bottom. Explicit paging remains.
- **Why:** Readers need a direct return to the current conversation after browsing older messages.
- **Files:** building/group.blade.php, resources/js/building/chat.js, findings.spec.js.
- **Verified by:** The browser loaded an older page, clicked the new control, observed the latest request and reached the bottom with the newest message visible.
- **Risk & rollback:** The action replaces the visible window. A reader can load older messages again.

### [Phase 8C] F03 Name the shared guest home link
- **Before:** The linked logo did not have an accessible name.
- **After:** The link has a Persian label and the nested SVG is hidden from assistive technology.
- **Why:** The link's destination must be announced without relying on the logo graphic.
- **Files:** resources/views/layouts/guest.blade.php.
- **Verified by:** The axe rerun found zero link-name violations on reset, confirm and verify pages.
- **Risk & rollback:** The home link and logo rendering are unchanged.

### [Phase 8C] F04 Set the favicon in shared layouts
- **Before:** The browser requested the missing default favicon from the guest layout. The shared app layout also had no icon link.
- **After:** Both shared layouts reference the existing project favicon.
- **Why:** The browser should use the same icon across the shared and standalone layouts.
- **Files:** resources/views/layouts/guest.blade.php, resources/views/layouts/app.blade.php.
- **Verified by:** The full sweep returned zero console errors, including all three shared guest pages.
- **Risk & rollback:** Layout head only. Reverting the links restores the browser fallback.

### [Phase 8C] F05 Size the remember-me hit area
- **Before:** The visible login label was 20px high.
- **After:** The label has a 40px minimum height.
- **Why:** The associated label is the effective hit area for the checkbox.
- **Files:** resources/views/auth/login.blade.php.
- **Verified by:** The touch guard measured the visible label with no flags at 360, 390 or 768px.
- **Risk & rollback:** The checkbox's value and submission are unchanged.

### [Phase 8C] F06 Size the verify-email logout action
- **Before:** The text logout action was about 31px wide.
- **After:** The button has a 40px minimum width and remains in its POST form.
- **Why:** The touch target needs usable width even with a short label.
- **Files:** resources/views/auth/verify-email.blade.php.
- **Verified by:** The verify-email captures passed the touch guard at all three widths.
- **Risk & rollback:** The existing POST action and token remain in place.

### [Phase 8C] F07 Increase map attribution hit areas
- **Before:** Attribution links were about 14px high.
- **After:** Links use inline flex and a 24px minimum height, increased to 40px on touch layouts.
- **Why:** The attribution must remain visible and usable as a link.
- **Files:** resources/css/map.css.
- **Verified by:** The registration, home, settings and map captures passed the touch and overflow guards.
- **Risk & rollback:** Long custom attributions may wrap within the map.

### [Phase 8C] F08 Size zoom controls on coarse pointers
- **Before:** The 40px rule stopped below 768px.
- **After:** The rule also applies to coarse pointers, including the 768px tablet layout.
- **Why:** A tablet can use touch even at the desktop layout breakpoint.
- **Files:** resources/css/map.css.
- **Verified by:** The 768px touch captures passed with both zoom controls.
- **Risk & rollback:** Map height retains its existing width breakpoint.

### [Phase 8C] F09 Expand the picker marker hit area
- **Before:** The draggable icon was only 25px wide.
- **After:** The picker uses a 40×41px image box. Object fit preserves the pin proportions and a 19.5px horizontal anchor accounts for the added side space.
- **Why:** The draggable area can grow without moving the visible tip away from its coordinate.
- **Files:** resources/js/building/map.js, resources/css/map.css.
- **Verified by:** Picker captures passed the touch guard and retained the visible pin at the mapped point.
- **Risk & rollback:** The larger hit area overlaps a few more pixels of map around the pin.

### [Phase 8C] F10 Make display markers non-interactive
- **Before:** The read-only pin used the default interactive marker options.
- **After:** Display markers disable keyboard and pointer interaction. The map region announces the building or request address.
- **Why:** A display pin has no action and should not be exposed as a button.
- **Files:** resources/js/building/map.js and the complex map component and callers.
- **Verified by:** Home and display captures passed touch checks. The display marker has no button role and the map keeps an accessible region name.
- **Risk & rollback:** Zoom controls remain available on the read-only map.

### [Phase 8C] F11 Measure labelled touch targets
- **Before:** The guard flagged the clipped chat file input instead of the visible attachment control.
- **After:** The shared browser guard uses a visible associated label for a visually hidden input, checkbox or radio when the label handles activation.
- **Why:** The measurement should describe the area a user can tap.
- **Files:** tests/Browser/touch-targets.js, visual.spec.js, docs/VISUAL_QA.md.
- **Verified by:** The chat attachment label and remember-me row passed the touch guard.
- **Risk & rollback:** Inputs without a visible label still use their own box.

### [Phase 8C] F12 Show when the payments table can scroll
- **Before:** The report clipped later columns inside its scroll container without a visible cue.
- **After:** A localized hint appears when the table overflows. A non-interactive edge fade disappears at the far edge. Both are hidden in print.
- **Why:** Users need a cue that more columns are available.
- **Files:** building/payments.blade.php, resources/js/building/payments.js, resources/css/panels.css, findings.spec.js.
- **Verified by:** The mobile browser checked the hint, fade and far-edge state. Print media kept the report rows and hid the cue.
- **Risk & rollback:** The existing report table and print action remain in place.

### [Phase 8C] F13 Serve chat images through complex authorization
- **Before:** The public storage link served a copied chat attachment URL without a session.
- **After:** Chat stores images under the private path and presents an authenticated URL keyed by message. Active members of the message's complex or global admins can read it. The move command preserves legacy filenames and reports conflicts.
- **Why:** Chat files need the same membership boundary as the conversation. The response uses the detected image type and private cache headers.
- **Files:** ChatAttachmentController, ComplexPolicy, ChatService, MessagePresenter, MoveChatAttachments, DemoSeeder, README and chat tests.
- **Verified by:** The anonymous and foreign-complex tests received no attachment. Member and admin tests received the expected PNG bytes. The local move reported one found and moved, with zero duplicates, conflicts or failures. A repeat found zero files. Existing chat, seeder and presenter assertions now check private paths or scoped URLs.
- **Risk & rollback:** Move legacy files before serving chat and resolve any reported conflicts. Announcement and incidental-cost images stay on their existing public path. Reverting code alone does not move private files back.

### [Phase 8D] P2 Normalize numeric form inputs
- **Before:** Validated numbers from form submissions remained strings. Poll creation and coordinate handling converted their own inputs, and authorization looked up some resources using raw form IDs.
- **After:** NumericFormRequest normalizes digit scripts and trims declared fields before validation. It returns typed quantities after validation without adding absent keys. TypedInput is also used by AccessMiddleware before resource lookup and by the mock callback before its existing amount verification.
- **Why:** A FormRequest declaration keeps typing next to each endpoint's validation. The shared parser lets early authorization see the same ID without casting a malformed value to a valid resource ID.
- **Inventory:** The numeric payload fields and empty-value rules are listed below. Required fields keep their validation. Missing optional keys stay absent and explicitly blank optional values become null.

| Endpoint | Fields | Empty value |
| --- | --- | --- |
| Create charge | Float `chargeAmount`; integer `dayStart`, `monthStart`, `yearStart`, `dayEnd`, `monthEnd`, `yearEnd` | Required. The date parts still form Jalali date strings. |
| Create incidental cost | Floats `total_amount`, `share_amount` | Required. |
| Create poll | Integers `endMinutes`, `endHours`, `endDays` | Each omitted or blank part contributes zero. At least one part is still required. |
| Add unit | Integers `complexId`, `unitNumber`, `floorNumber`; string identifier `zipCode` | Complex ID must resolve for authorization. Missing or blank unit details are null. |
| Update unit | Integers `complexId`, `currentUnitNumber`, `newUnitNumber`, `floorNumber`; string identifier `zipCode` | Same as add. The current number uses the existing unit lookup. |
| Register building | Integers `floors`, `units`; floats `latitude`, `longitude` | Counts are required. Omitted or blank coordinates leave a null pair on a new request. |
| Update building location | Floats `latitude`, `longitude` | Omit both to keep the pair. A blank pair clears it. |
| Update profile | String identifiers `ncode`, `phoneNumber` | Omit to keep the corresponding value. Blank clears it. |
| Pay charge manually | Integers `unitNumber`, `complexId`, `monthlyChargeId` | Unit number is required. Payable and complex IDs must resolve and match for authorization. |
| Pay cost manually | Integers `unitNumber`, `complexId`, `incidentalCostId` | Same as manual charge. Both actions use stored amounts. |
| Initiate online payment | Integers `payable_id`, `unitNumber` | Required. |
| Mock payment callback | Decimal `amount` | Missing or blank fails the existing amount check. The precision check still runs before conversion for comparison. |
| Cast vote | Integer `voteRadio` | Missing or blank means no option selected. |
| Read poll results | Integer `poll_id` | Must resolve for authorization. |
| Read payments report | Integer `complex_id` | Must resolve for authorization. |
| Send chat message | Integer `complexId` | Must resolve for authorization. |
| Edit or mark chat message seen | Integer `messageId` | Must resolve for authorization. |
| Read message viewers | Integer `message_id` | Must resolve for authorization. |

- **Scope:** Numeric route parameters keep their existing bindings. Payment gateway tokens and statuses, signed verification links, and join tokens stay opaque. Postal, national and phone numbers are digit-normalized text so leading zeroes survive.
- **Files:** TypedInput, NumericFormRequest and its endpoint requests, AccessMiddleware, PollService, MockGateway, TypedInputTest, ARCHITECTURE.md.
- **Verified by:** TypedInputTest covers ASCII, whitespace, zero, negative, a large integer, both local digit scripts, blank and absent values, and a fractional float.
- **Risk & rollback:** Nullable ID and unit fields now reject non-integers. The shared boundary replaces three poll duration casts, two coordinate casts and the payable complex check's integer cast. Coordinate rounding, opaque gateway verification and persisted field formats stay in place. Revert the request and authorization changes together.

### [Phase 8D] P1 Check browser and typed payload parity
- **Before:** Most endpoint tests used native PHP numbers. The poll regression covered form-encoded durations without comparing the resulting state to a typed request.
- **After:** Paired URL-encoded and JSON requests cover charge and cost creation, poll creation, unit add and update, building registration and location, profile update, both manual payments, online payment initiation and mock callback, voting, results, report, and chat send, edit, seen and viewers.
- **Why:** The same fixtures and clock expose differences in response type, status, redirect, flash message and saved state. Separate database savepoints let both requests start from the same records.
- **Files:** tests/Feature/Backend/FormEncodingParityTest.php.
- **Verified by:** The pairs include Persian digits in coordinates, the current unit lookup and the national code. Blank and omitted profile and settings fields run as separate cases.
- **Risk & rollback:** Test-only. The earlier browser poll regression remains unchanged.

### [Phase 8D] P3 Record scoped audit events
- **Before:** Role changes, membership decisions and join-link rotations had no shared history.
- **After:** AuditLogger appends `role.upgraded`, `role.downgraded`, `membership.approved`, `membership.rejected` and `join_link.rotated` after the domain write. Admins see the latest 100 across all complexes. Active managers see up to 100 for their own complex in settings. Residents see no list.
- **Why:** The actions know when the change succeeded and who made it. The subject morph supports both users and complexes. Rejections retain the requested name with a null complex ID. Rotation has null metadata so no link material reaches the event.
- **Files:** create_audit_events_table migration, AuditEvent, AuditLogger, AuditEventPolicy, RoleController, AdminController, audit-events component and translation, AuditEventsTest, ARCHITECTURE.md.
- **Verified by:** Thirteen tests cover the five writes, five injected write failures, repeated decisions, admin limit and manager scoping. The rotation test scans the stored row for both the original and replacement links. The migration has no update timestamp and the app exposes no edit or purge action.
- **Risk & rollback:** A failed append leaves the original action in place and emits a warning. Actor and complex foreign keys protect the references while the rows are retained. The migration rollback drops the audit history.

### [Phase 8D] P4 Pin query budgets as the fixture grows
- **Before:** The comparison audit measured the three actions at 2, 8 and 32 units without making those counts part of the suite.
- **After:** QueryBudget uses a query listener around each action. Each fixture includes one user, one poll with two options and two payments per unit. Tests assert zero query growth and the original caps.
- **Why:** A single fixture size cannot distinguish a fixed query cost from a query per unit.
- **Files:** tests/Support/QueryBudget.php, tests/Feature/Backend/QueryBudgetTest.php.
- **Verified by:** At 2, 8 and 32 units, the payments report used 6/6/6 queries, the building dashboard used 11/11/11 and the user dashboard used 2/2/2. These are controller action counts with fixtures, authorization, route binding and Blade rendering outside the window, as in the comparison audit. A separate render of the manager dashboard with an empty audit list measured 13/13/13 including the action.
- **Risk & rollback:** Test-only. The new audit list runs its permission check and retrieval when Blade renders it. The original action caps are unchanged.

### [Phase 9] Run browser journeys with isolated fixtures
- **Before:** Browser checks depended on a manually seeded server, opt-in capture specs and separate audit scripts.
- **After:** `npm run test:e2e` builds the assets and starts two loopback Artisan servers on ports 8001 and 8002. The harness assembles a temporary application root for each mode and supplies explicit SQLite, session and storage settings. Global setup migrates and seeds both with E2ESeeder. Only port 8002 enables mock payments and the SMS log driver. Both use the log broadcaster and a local blank map tile.
- **Why:** Manual and online scenarios need different flags, and each run needs fixtures that can be reset without reading the checkout environment or writing to its database or upload paths. The existing opt-in visual command keeps its own config.
- **Files:** Playwright configs, npm scripts and lockfile, E2ESeeder, tests/e2e, four baseline PNGs, .gitignore, README and VISUAL_QA.md.
- **Verified by:** PHP passed 447 tests and 1,414 assertions. The browser run had 38 passes, one expected failure and one pending case. The 250-capture sweep and 25 axe scans passed. Four refreshed comparisons had zero changed pixels. Build, Pint and the staged style scan passed.
- **Risk & rollback:** The runner removes both temporary roots at exit. The browser findings record the logout redirect to the public home page. The reload-based chat check passes with the log broadcaster. Live delivery without a reload stays pending until a real broadcaster is available.

### [Phase 10] Verify and release the refactor baseline
- **Before:** The browser suite treated the public logout destination as an expected failure. The dependency refresh, clean-room setup and MySQL attempt were outstanding.
- **After:** The browser checks assert the approved public logout destination, the redirect from a protected page and signing in again. The release includes the building permission boundaries, private chat attachments, payment integrity checks, audit events, responsive layouts, maps and optional integrations. The README uses the npm lockfile for installation.
- **Why:** The logout test now checks the reviewed contract. A clean checkout exercises the documented path and locked dependencies without using the development database.
- **Files:** tests/e2e/auth.spec.js, composer.lock, README, CHANGELOG, ARCHITECTURE.md, FINDINGS.md and VISUAL_QA.md.
- **Verified by:** PHP 8.3.36 and Laravel 12.72.0 passed 447 PHP tests with 1,414 assertions. The clean-room browser run passed 39 cases with one pending live-push case and zero expected failures. Composer and npm audits reported zero advisories. The frontend build and JavaScript syntax checks passed. Both Pint versions reported the same 48 pre-existing PHP formatting findings.
- **Dependency review:** Seven Composer packages moved within their existing major versions. The prior optional metadata was retained for three unchanged Spatie packages. The npm audit fix left package versions unchanged.
- **MySQL attempt:** A portable MySQL 8.4.13 instance on loopback port 33316 passed migrate, rollback, migrate:fresh and DemoSeeder in a scratch schema. The full test command used a separate schema. Its 10 unit cases passed, and the bootstrap guard rejected all 437 feature cases because the suite requires in-memory SQLite. The scratch server shut down cleanly.
- **Risk & rollback:** The normal installation retains disabled-by-default payment and SMS settings. The clean-room run used a local clone at `8310571` and isolated browser databases. The release notes list the remaining coverage and attachment scope.

#### Open items

- Live Pusher delivery was pending at release and is closed by the activation check below.
- Full MySQL test coverage remains open. MySQL 8.4.13 passed migration, one rollback step, fresh migration and demo seeding. The test harness requires in-memory SQLite and stopped all 437 feature cases during bootstrap.
- Announcement and incidental-cost images still use public storage. This is accepted scope. Chat attachments require authorization.
- Post-refactor backlog is tracked in the items marked Later. [Proposals](PROPOSALS.md)

### [Attachment privacy] Protect announcement and incidental-cost images
- **Before:** The building previews used public storage URLs. Someone who obtained a URL could read the upload without a session or building role.
- **After:** Announcement and cost uploads use private storage and their previews use authenticated routes keyed by the owning record. The routes share the chat membership check and image response, including detected content types and private cache headers. This closes the attachment privacy item in the 1.0.0 release notes.
- **Why:** A copied image URL must enforce the same complex boundary as the building page. An active resident or manager of the complex, or a global admin, can read the attachment.
- **Files:** BuildingAttachmentController, ChatAttachmentController, ComplexPolicy, PrivateImage, the upload controllers and models, building preview views, MoveBuildingImages, AttachmentMover, E2ESeeder, tests, README, ARCHITECTURE.md and CHANGELOG.md.
- **Verified by:** The PHP suite passed 465 cases. Each image type has guest, foreign resident, inactive member, resident, manager, global admin, missing file and invalid content coverage. The upload tests assert private paths and verify the rendered preview URLs. The move tests cover filenames, identical copies, conflicts and clean reruns. The browser suite passed 41 cases with one pending realtime case. Both image cases checked resident access, guest and foreign-resident denial and the absence of legacy upload URLs in the rendered page. All 250 visual captures and the fresh SQLite demo seed passed.
- **Local move:** `attachments:move-building-images` found and moved one announcement image. It found zero incidental-cost images. Both types reported zero duplicates, conflicts and failures. The private file matched the original checksum and the public junction path was gone. A repeat found zero files in either directory.
- **Risk & rollback:** Run the move before reopening web traffic and resolve any reported conflicts or failures. Coordinate the storage layout with the code if reverting a deployment. The stock placeholder remains public.

### [Pusher activation] Verify live private chat delivery
- **Before:** The browser suite checked that a second session could see a message after a reload. Live delivery remained open in the release notes.
- **After:** Credentials set in the ignored local environment. The local app uses Pusher. The live browser case checks both private-channel subscriptions, delivery without navigation and HTTP 403 for a resident of a different complex. The case runs on explicit opt-in. Default PHP and browser tests use the log broadcaster.
- **Why:** A reload proves that the message was stored. Two subscribed sessions verify that the broadcaster delivers the stored message to an authorized reader without a reload.
- **Files:** playwright.config.js, tests/e2e/environment.js, tests/e2e/realtime.spec.js, phpunit.xml, README.md, VISUAL_QA.md, ARCHITECTURE.md and CHANGELOG.md.
- **Verified by:** The live browser case passed twice. The receiving session displayed the message in about five seconds on the first run and about two seconds on the rerun. The unauthorized channel request returned HTTP 403 both times. The captures are in the ignored QA output directory. The PHP suite passed 465 cases with 1,526 assertions. The default browser suite passed 41 cases with the live check skipped by design. This closes the live delivery item in the 1.0.0 release notes.
- **Risk & rollback:** Use a test broadcasting app for the opt-in check. It sends seeded chat content to real channels. Traces are disabled for the live run to keep channel signatures out of test artifacts. To return the local app to the log broadcaster, set `BROADCAST_CONNECTION=log` and clear the config cache.

### [Phase 11 discovery] Review feature gaps and opened interactions
- **Before:** The existing sweep checked page width, console output and target sizing at baseline states. It did not inventory chat and building capabilities or exercise dropdown placement and keyboard focus in opened states.
- **After:** The audit classifies 43 items and compares all nine Later proposals. It records seven findings with proposed scope and unresolved policy choices. The new evidence includes newest-message menu clipping, drawer focus entering the background, landscape composer and dialog behavior, and a mismatch between the viewer menu and endpoint audiences.
- **Why:** The owner can separate missing features from interaction defects before choosing another development round.
- **Files:** docs/FEATURE_AUDIT.md and 106 seeded browser captures under docs/audit-11.
- **Verified by:** Chrome click-throughs in disposable SQLite sessions at 390px and 1440px in both themes, plus landscape and a shortened chat viewport. Route and test review confirmed the viewer audience and the three authorized attachment routes. The PHP suite passed 465 tests with 1,526 assertions. The build and default browser suite passed with 41 browser cases and one expected skip for the opt-in live check.
- **Risk & rollback:** The audit changes documentation and evidence only. The shortened viewport does not establish physical keyboard or IME behavior. Revert the audit commit to remove the report and captures.

### [Phase 11A, F11-01] Keep message actions reachable
- **Before:** The chat list clipped the menu on the newest own message, hiding Edit, Delete and Viewers.
- **After:** The menu opens in the browser top layer and uses the trigger and available viewport space for placement.
- **Why:** Stacking the menu within the scroll box could not move it outside the clipping boundary.
- **Files:** resources/js/building/chat.js, resources/css/chat.css, resources/css/panels.css, tests/e2e/chat.spec.js and the audit closure evidence.
- **Verified by:** Four Playwright cases hit-test and click each action at 390 and 1440 in both themes.
- **Risk & rollback:** Menu placement now uses the browser's Popover API. Revert the placement and interaction changes together if restoring the native-only disclosure.

### [Phase 11A, F11-02] Dismiss message menus and restore focus
- **Before:** More than one disclosure could stay open, and Escape or an outside click left it open.
- **After:** The chat keeps one open menu. Escape and outside clicks close it and return focus to its trigger.
- **Why:** Native disclosures do not supply outside dismissal or Escape handling.
- **Files:** resources/js/building/chat.js, tests/e2e/chat.spec.js and the audit closure evidence.
- **Verified by:** The browser opens two menus in sequence, then checks both dismissal methods and focus return.
- **Risk & rollback:** Reverting this entry restores the old dismissal behavior. Menu placement is independent of the dismissal handlers.

### [Phase 11A, F11-03] Keep keyboard focus in the open drawer
- **Before:** Tab reached the dashboard join field while the drawer covered it.
- **After:** The header and content behind the drawer become inert. Tab and Shift+Tab wrap inside the drawer. Closing clears inert state and restores the opener.
- **Why:** Moving focus on open left the rest of the page in the tab order.
- **Files:** resources/js/building/navigation.js, tests/e2e/chat.spec.js and the audit closure evidence.
- **Verified by:** The 390px browser check verifies inert state, traversal in both directions and focus return.
- **Risk & rollback:** Navigation and drawer share the handler. Reverting removes both inert state and the boundary.

### [Phase 11A, F11-04] Fit chat into short landscape screens
- **Before:** At 844 × 390 the desktop sidebar and minimum chat height pushed the composer below the viewport.
- **After:** The short-landscape breakpoint uses the drawer, and the chat panel allocates the remaining height to the scrollable message list.
- **Why:** The width breakpoint and minimum block size did not account for short phone screens.
- **Files:** resources/css/panels.css, resources/css/chat.css, tests/e2e/chat.spec.js and the audit closure evidence.
- **Verified by:** The landscape case checks the heading, paging, message list and composer in the same viewport and confirms that focusing the composer keeps the heading visible.
- **Risk & rollback:** This breakpoint also changes navigation on short landscape screens outside chat. Revert the landscape media rules together to restore the earlier layout.

### [Phase 11A, F11-05] Keep the close control in tall forms
- **Before:** Scrolling the charge form to Submit moved its Close control out of reach.
- **After:** A toolbar keeps Close at the top of the scrolling form panel, with the panel background behind the control.
- **Why:** Close used to scroll with the long form fields.
- **Files:** resources/css/panels.css, the four building creation views, tests/e2e/chat.spec.js and the audit closure evidence.
- **Verified by:** The landscape charge case scrolls to Submit, checks Close is still visible, and verifies Close and Escape return focus.
- **Risk & rollback:** The sticky toolbar takes one row of the dialog's visible height. Revert the toolbar markup and styling together to restore the previous layout.

### [Phase 11A, F11-07] Scope access to message viewer names
- **Before:** The menu showed Viewers for the author's own messages, but the endpoint permitted any active member of the complex.
- **After:** The endpoint allows the message author or an active manager of that complex. The route and author menu stay as before.
- **Why:** The owner chose author and complex-manager access to named receipts.
- **Files:** app/Http/Controllers/GroupController.php, tests/Feature/Building/ChatTest.php, tests/Feature/Backend/FormEncodingParityTest.php, tests/e2e/chat.spec.js and the audit closure evidence. Pint also normalized the controller's existing JSON response docblocks.
- **Verified by:** PHP and browser cases cover the author, manager, non-author resident and foreign manager. The named ChatTest expectation now requires 403 for a non-author resident. The viewer parity case checks the same denial for JSON and form bodies and retains manager success.
- **Risk & rollback:** Reverting reopens named receipts to all active complex members. Keep the policy and its test expectations together.

### [Phase 11B, A05] Add named chat reactions
- **Before:** Chat had no stored reactions, picker or counts.
- **After:** Active members can toggle any of seven palette emoji. The unique message, user and emoji key allows different reactions while preventing duplicate rows. A quick control and the message menu open the same picker. Counts highlight the viewer's reactions and open the reactor list.
- **Why:** Each viewer needs the same counts and names but a different selected state. The private-channel event sends the full summary with reactor IDs, and the browser derives its own selected state. History and mutation responses can include that state directly. A version prevents a late update from replacing a newer summary.
- **Files:** The chat social migration, MessageReaction, ChatReactionRequest, GroupController, ChatSocialService, MessagePresenter, ChatReactionsChanged, chat-social.js, chat views and styles, and the chat tests.
- **Verified by:** Feature cases cover scope, palette validation, the unique key, reversible toggles, independent emoji and the named event payload. The browser matrix checks both picker entry points, 40px palette targets, highlighting and reactor names at 390 and 1440 in both themes. The opt-in live run verified the reaction summary and per-viewer highlight in a second session, with no history or pin refetch.
- **Risk & rollback:** The shared summary grows with the number of reactors. The migration uses binary emoji comparison on MySQL-compatible engines so distinct emoji do not collide under the default collation. Rolling it back removes stored reactions.

### [Phase 11B, A06] Reply to a message and jump to it
- **Before:** A new message could only carry text or an attachment.
- **After:** A reply may reference a live message in the exact group where the reply is sent. The composer previews the author and a short snippet and can cancel without clearing the draft. Clicking a quote focuses the original and loads its page when necessary. Sending returns to the latest page, and editing returns to the edited row.
- **Why:** The prior read marker could point to an old page after a jump. The quote follows the current parent, and both history and delayed new-message broadcasts replace a deleted parent's author and text with a tombstone reference.
- **Files:** The chat social migration, Message, ChatStoreRequest, ChatService, MessagePresenter, NewChatMessage, chat.js, chat markup and styles, and the chat tests.
- **Verified by:** PHP rejects foreign, same-complex different-group and deleted parents. It compares the live quote and paged quote before and after deletion. The browser follows a quote to an older page and checks focus and redaction. A live second session received the quote and its later redaction without reloading.
- **Risk & rollback:** The self-reference is nullable and clears if the referenced row is physically removed. Rolling back the migration drops the reply references.

### [Phase 11B, A07] Pin messages with manager approval
- **Before:** Chat had no pinned strip or pin history.
- **After:** Active managers can pin and unpin multiple messages. The strip orders pins by newest timestamp and then message ID, and each item jumps to its message. Pin changes publish to the private complex channel and record the actor and message in the scoped audit log. Deletion clears a pin and records the reason.
- **Why:** Pin permissions are separate from chat membership. The explicit set or clear operation preserves the pinner and timestamp when the requested state is already set.
- **Files:** The chat social migration, ChatPinRequest, GroupController, ChatSocialService, MessagePresenter, ChatPinChanged, audit translations, chat-social.js, chat markup and styles, and the chat tests.
- **Verified by:** PHP checks manager permissions, several pins, order, removal, audit scope and the private event. The browser pins an older message, jumps from the strip and verifies cleanup after deletion. In the live run the second session saw the pin and its removal without refetching.
- **Risk & rollback:** The strip adds a read when the chat panel opens. Rolling back the migration removes pin state. The audit records remain in the existing append-only table.

### [Phase 11B, A02] Keep deleted messages as tombstones
- **Before:** Deletion removed a message from history and the live thread. Its attachment route already denied access after deletion.
- **After:** History and live deletion preserve the row and its cursor while redacting text, attachment and quote content. The deletion event carries the ID and kind only. The same renderer can display an actor-specific label from the deletion kind. Attachment denial remains in force.
- **Why:** Quotes, cursor jumps and adjacent messages need a stable target after deletion.
- **Files:** The chat social migration, GroupController, ChatService, ChatSocialService, MessagePresenter, DeleteChatMessage, EditChatMessage, chat.js, chat styles and the chat tests.
- **Verified by:** PHP checks history, broadcast redaction and the previously readable attachment becoming unavailable. Updated deletion expectations cover the cursor page in ChatServiceTest, the delete event in MessagePresenterTest, and the deleted row and latest-page anchor in chat.spec.js. The approved 11A reachability and other-author menu assertions in chat.spec.js now check the expanded menu and preserve resident and ownership restrictions.
- **Risk & rollback:** Existing deleted rows render with the author label. Reverting the feature hides those rows from history again.

#### Phase 11B verification

The PHP suite passed 498 cases with 1,677 assertions, an increase of 29 cases over Phase 11A. The browser suite passed 54 cases with zero failures and its one default opt-in live skip, an increase of four cases. The live two-context social flow passed separately with no history or pin refetch on the receiving client. Fresh SQLite migration and DemoSeeder, one rollback step, re-migration, the frontend build, Pint and JavaScript syntax checks passed. The 250-state browser sweep passed. Captures for the four features at 390 and 1440 in both themes are under the ignored `storage/app/test-screenshots/phase-11b` directory.

### [Phase 11C, A04] Delete messages as a complex manager
- **Before:** A manager could delete only their own messages.
- **After:** An active manager can delete another author's message with a required reason, capped at 240 characters. The new migration adds the deletion actor, reason and time. The tombstone renders the manager kind, and a scoped audit event stores the message subject and reason.
- **Why:** Moderation needs accountability without exposing the removed body or internal reason in member-facing history and broadcasts.
- **Files:** ChatDeleteRequest, GroupController, ChatSocialService, Message, MessagePresenter, the deletion-details migration, audit translations, chat views and modules, ChatPowerTest and chat-power.spec.js.
- **Verified by:** PHP covers resident, foreign-manager and inactive-manager denial, required and bounded reasons, audit scope, metadata and retry behavior. Browser flows cover the reason dialog and manager tombstone at 390 and 1440 in both themes.
- **Risk & rollback:** Historical deletions keep null actor, reason and time. Rolling back the migration removes the new details. The retained bodies remain inaccessible through chat history and attachments.

### [Phase 11C, A09] Select and delete a batch of messages
- **Before:** Chat could delete one message at a time.
- **After:** A menu action or touch hold starts selection. Checkboxes, a live count and Cancel remain available while paging. One request accepts at most 50 distinct IDs from the selected complex's group. Each item follows author or manager deletion rules, with one shared moderation reason. Results state success or denial per item, and one private event carries the resulting tombstones.
- **Why:** A mixed batch needs to make progress without granting access to foreign messages or hiding individual denials.
- **Files:** ChatBulkDeleteRequest, GroupController, ChatSocialService, ChatMessagesDeleted, chat-power.js, chat.js, views and CSS, and the new PHP and browser cases.
- **Verified by:** PHP tests mixed own/other/foreign IDs, partial success, missing moderation reason, duplicate IDs, the 50/51 boundary and retries. Browser flows select three messages, including other authors, and check the shared reason and counts. The existing chat.spec.js menu checks now include Select while retaining reachability and ownership checks.
- **Risk & rollback:** Each row commits separately, so retrying a partially completed request may report already-deleted rows. The response identifies those rows explicitly. Revert the endpoint and client selection controls together.

### [Phase 11C, A10] Search current chat text
- **Before:** Chat history could be paged by ID but had no text search.
- **After:** A scoped endpoint returns 20 matches at a time, with author, Jalali date and a snippet. Search reuses the typed boundary's digit replacements and adds the requested letter variants. The same replacements run on the stored text side of the query. Tombstones are excluded, edits are read from the current body, and result clicks jump and briefly highlight the original row.
- **Why:** Search must match Persian and Arabic spellings without rewriting historical bodies or exposing deleted content.
- **Files:** TypedInput, ChatSearchRequest, ChatSearchService, GroupController, routes, chat-power.js, chat.js, views, styles and tests.
- **Verified by:** PHP covers both directions of the letter variants, Arabic-Indic and Persian digits, literal wildcard characters, pagination, edited and deleted content, group authorization and the paging target. The browser matrix searches an older message and checks focus and highlighting after the jump. The 390px search controls have opened-state hit checks.
- **Risk & rollback:** The portable SQL replacements scan matching text within the indexed group scope. Search is ordered by descending message ID. Remove the route and controls together to roll back.

### [Phase 11C, A11] Signal typing over the private channel
- **Before:** Chat had no typing signal.
- **After:** The Pusher client sends client-typing whispers containing an ID, bounded name and active flag. Active signals are throttled to 1.5 seconds. Send, clear and leaving the panel stop immediately, and peer names expire after five seconds. The log broadcaster uses a no-op handler. No server typing route, event class or storage was added.
- **Why:** Typing is short-lived client state. It should expire even when a peer disconnects without a final stop signal.
- **Files:** chat-typing.js, chat.js, the building config and typing element, client unit tests, the live branch of chat-power.spec.js, README and ARCHITECTURE.md.
- **Verified by:** Six Node tests cover payload shape, no-op use, throttling, immediate stop, expiry, malformed input and subscription readiness. Route snapshots show only the new search and bulk routes. Initial live retries reached both private subscriptions but the provider rejected whispers until Client Events was enabled on the configured app. After the owner saved that setting, a fresh two-context browser run passed: the peer saw the typing name without reload, clearing hid it promptly, and an idle signal expired within five seconds. The same live run passed moderation, bulk deletion and search. Evidence is typing-live-390-light.png in the phase capture directory. No credential or broadcaster configuration changed.
- **Risk & rollback:** The broadcasting app must enable Client Events. Typing names are advisory client signals. Revert the module and template element together to remove the indicator.

#### Phase 11C verification

The PHP suite passed 521 cases with 1,805 assertions, an increase of 23 cases over Phase 11B. Six client typing unit tests passed. The default browser suite passed 58 cases with its one opt-in live skip, an increase of four cases. The 250-state sweep passed overflow, console, image, touch-target and accessibility guards. The four new browser flows cover 390 and 1440 in both themes. Captures are under the ignored `storage/app/test-screenshots/phase-11c` directory; visual review caught and corrected a clipped search Close control and a cramped selection count on the phone layout. Fresh SQLite migration and DemoSeeder, one rollback step, re-migration, the frontend build, Pint, JavaScript syntax checks and the staged style check passed. The only existing test file edited was chat.spec.js: its two exact-menu assertions now include Select, with the existing reachability and ownership restrictions retained. No existing PHP test changed.

### [Phase 11D, A16 and F11-06] Open authorized images in one viewer
- **Before:** Chat images were plain inline images. Announcement and cost previews used separate dialogs without gallery navigation or zoom.
- **After:** Chat image buttons open a shared modal viewer, also used by announcement and invoice controls. It supports keyboard, wheel and pinch zoom, panning, Escape, a visible Close, touch swipe-down, focus containment and restoration, and download through the existing authorized delivery URL. A scoped media endpoint pages ten non-deleted attachments at a time in message order, independent of the loaded history page.
- **Why:** Inline dimensions prevented inspection, and the gallery must not stop at the chat history cursor or expose deleted attachments.
- **Files:** ChatMediaRequest, ChatMediaService, GroupController, routes, media-viewer.js, chat.js, announcement and payment modules, chat styles, the two image preview views, E2EMediaSeeder and the new media/seen tests.
- **Verified by:** PHP covers both cursor directions across three pages, foreign and inactive membership denial, tombstone and foreign-image exclusion, cursor validation and authorized URL shape. Browser checks cover both widths and themes, a second gallery page, keyboard navigation and zoom, wheel and pan, mobile pinch and swipe, download, announcement and cost reuse, and focus restoration. Boundary loading and Shift+Tab checks caught focus loss; the viewer now restores the navigation control after loading and explicitly wraps Tab within its controls. The old preview class names remain context aliases on the shared dialog so existing preview checks continue unchanged.
- **Risk & rollback:** The gallery lists only metadata; the existing delivery routes still authorize every byte request. A failed or revoked image shows an error state. Remove the media route and viewer integration together to return to the previous inline presentation. No schema change is required.

### [Phase 11D, A14] Show aggregate seen receipts
- **Before:** Seen rows and the authorized names dialog existed, but authors had no inline receipt or live aggregate update.
- **After:** Authors see a compact count beside their own timestamps, and local managers see counts on all local messages. Zero is hidden. A scoped, bounded counts endpoint supports initial rendering. New non-author receipts publish only message_id and count on the private complex channel; repeats and author receipts publish nothing. The client updates the label without rerendering the thread or disrupting focus.
- **Why:** Receipts need to match the existing detail list while names stay behind its author/manager authorization. Counting and viewport semantics remain unchanged.
- **Files:** ChatSeenCountsRequest, ChatSeenCountChanged, ChatService, GroupController, routes, chat.js, chat styles, and the new media/seen tests.
- **Verified by:** PHP checks author exclusion, equality with viewer detail counts, the author/manager/resident audience, foreign and deleted row exclusion, inactive and foreign membership denial, duplicate seen requests and the event's exact two payload keys. Browser checks cover quiet zero counts and the audience. The live three-context run passed: author and manager received increases without reload, another resident had no inline count, and every captured count event had exactly message_id and count. Live captures are seen-live-author.png and seen-live-manager.png in the ignored phase-11d capture directory. The local single-process server needed a longer initial history-load wait with synchronous live broadcasts; the new test allows 45 seconds for that load.
- **Risk & rollback:** Counts are aggregate channel data, never names. Clients keep the larger count if an older response arrives after a live update. Revert the counts endpoint, event and inline labels together; existing seen rows and the viewers dialog remain intact.

#### Phase 11D verification

The PHP suite passed 532 cases with 1,939 assertions, up from 521 and 1,805. All eleven new PHP cases are in ChatMediaSeenTest.php. The default browser suite passed 63 cases with its one existing opt-in live skip in 12.4 minutes, up from 58 passed. The five new cases in media-seen.spec.js cover the four width/theme combinations and the receipt audience. Downloads matched the source bytes, and loading and denied-image states passed. The separate live receipt case passed in 59.7 seconds, including author and manager updates without reload and exact event keys. No existing test file was edited. All 250 responsive sweep states passed. Fresh SQLite migration and DemoSeeder ran clean in a separate scratch database; no development data was replaced. The frontend build, Pint, JavaScript syntax and staged style and credential checks passed. Fifteen captures are retained in the ignored phase-11d directory. The only added routes are the two read-only, member-scoped media and count endpoints; existing URLs, verbs and names are unchanged. No migration or dependency change was needed.

### [Phase 11E, B01] Pin announcements
- **Before:** Announcements could be published or archived but not pinned.
- **After:** Active local managers can pin several notices and unpin them explicitly. Active notices render pinned first in ascending pin time and then ID. Each transition records a scoped announcement audit event with the actor and subject. Repeating the same state does not reorder a pin or add another event.
- **Why:** The pin belongs to the announcement and needs a stable order and accountable changes. Pin timestamps retain microseconds so two actions within one second keep their order. Archive/restore retains pin state; it does not silently unpin a notice.
- **Files:** The new announcement/poll controls migration, Announcement, AnnouncementService, AnnouncementPinRequest, AnnouncementController, routes, card partial, audit translations, styles and the new pack tests.
- **Verified by:** PHP covers resident, foreign and inactive manager denial, several pins within one second, ordering, preserved actor/time on retry and scoped pin/unpin events. Browser flows verify the highlighted card at both widths and themes.
- **Risk & rollback:** Rollback removes pin metadata. Revert controls and migration together. Existing announcements begin unpinned.

### [Phase 11E, B02 and B03] Search and restore the archive
- **Before:** Cancellation set the archive flag and hid the row, with no way to browse or restore it.
- **After:** Managers can search active, archived or all notices in their complex, with a result empty state and restore action. Members receive active notices only and no archive/search controls. Search normalizes Persian/Arabic letters and digits on both stored text and query. Blade renders and escapes the returned card fragment.
- **Why:** Archived notices need a scoped recovery path. The owner-approved archive is manager-only. This closes the Later archive proposal under that scope; date filtering was not part of the approval.
- **Files:** AnnouncementService, AnnouncementSearchRequest, AnnouncementController, BuildingDashboardController, routes, announcement views/module, styles, PROPOSALS.md and the new tests.
- **Verified by:** PHP covers state filters, normalization in both directions, foreign rows, empty results, the archive/restore round trip and member invisibility. Browser flows archive with confirmation, search the archive and restore without losing the pin. Existing preview tests pass unchanged.
- **Risk & rollback:** Restore makes the notice visible again. Reverting the new search/restore routes leaves the existing archive flag intact.

### [Phase 11E, B04] Track announcement reads
- **Before:** Only chat messages had per-user seen records.
- **After:** The new announcement_reads table has a unique announcement/user pair and a creation timestamp. An observer marks a notice when it enters the visible announcement panel. Members see a quiet unread badge until acknowledgement. Managers see distinct-reader counts and can open names and reading times. Managers reading their own notices count as members too.
- **Why:** Read tracking needs user-level idempotency rather than one row per unit role, and reader names must stay behind the complex manager policy.
- **Files:** The new migration, AnnouncementRead, Announcement::reads, AnnouncementController, announcement card partial/module, routes, styles and tests.
- **Verified by:** PHP checks duplicate marking, foreign/inactive/guest denial, archived marking rejection and resident denial on the names endpoint. Browser checks hold the read request to inspect the unread badge, then release it and open the manager's reader list. Initial counts and own read state use subqueries, preserving the dashboard query budget.
- **Risk & rollback:** Rollback removes read history. Counts are snapshots, not live broadcasts. The archive remains manager-only.

### [Phase 11E, B05] Close polls early
- **Before:** A poll closed only when its deadline passed.
- **After:** Local managers can confirm irreversible early closure. The poll records closer and time, renders closed with results and rejects subsequent form or direct votes. Repeated closure preserves the original metadata.
- **Why:** Closure, voting and editing now lock the same poll row inside transactions so a concurrent vote cannot bypass closure or the edit lock. Manual closure stays set even if a poll without votes is edited to a later deadline.
- **Files:** The new migration, Poll, PollService, PollController, BuildingDashboardController, poll view/module, routes and tests.
- **Verified by:** PHP checks scoped manager permissions, actor/time, repeated closure, blocked votes and preserved closure after a permitted pre-vote deadline edit. Browser flows confirm closure, check the closed card and open results. Existing expiry and change-vote tests pass unchanged.
- **Risk & rollback:** Dropping the closure columns loses manual closure history. Reverting the feature can reopen manually closed polls whose deadline is still in the future.

### [Phase 11E, B07] Restrict named ballots
- **Before:** Only aggregate option counts were available.
- **After:** A separate manager-only endpoint returns voter names per option after deadline or manual closure. While open, it rejects managers too. The aggregate endpoint retains its original shape and counts. Names list each person once per option; underlying aggregates remain per-role. Mismatched poll/option rows and foreign-complex roles are excluded from named payloads.
- **Why:** Naming ballots is a separate permission and timing decision, not an addition to member-visible aggregate results.
- **Files:** PollService, PollController, routes, poll results view/module and tests.
- **Verified by:** PHP checks open-poll denial, both closure modes, resident/foreign/inactive denial, aggregate payload shape and distinct/scoped names. The browser checks an empty named area while open and the named roster after closure in both themes and widths. Results discard stale responses when another poll opens, and the new reader/edit/result headers reuse the sticky close controls.
- **Risk & rollback:** Managers can see historical choices once closed. Revert the named endpoint and roster controls together; aggregate results are independent.

### [Phase 11E, B08 and B06] Edit before voting and preselect the current choice
- **Before:** Poll text/deadline could not be edited, and the vote dialog always opened with no choice selected.
- **After:** Local managers can change title, question and deadline before any vote, with options fixed. The UI explains the lock after a vote; the service enforces it under the row lock. The deadline input uses the application's Tehran time and retains seconds, so a text-only edit does not shorten the deadline. Invalid submissions reopen the correct dialog with field errors and entered values. A returning voter sees their current option selected.
- **Why:** The first ballot fixes the question being answered. The current-choice subquery reuses already-loaded role IDs and adds no dashboard query. The dashboard action signature stays unchanged for existing callers.
- **Files:** PollUpdateRequest, PollService, PollController, BuildingDashboardController, poll view/module, routes and tests.
- **Verified by:** PHP checks successful edits, ignored option tampering, invalid dates, locked edits and current-choice markup. Browser flows edit the question before voting, verify the saved choice on reopen, then check the manager's lock explanation. No existing test file changed.
- **Risk & rollback:** Reverting the edit route removes editing but keeps saved text/deadlines. Preselection uses the latest stored ballot among the member's roles; the existing vote action still applies the chosen option to those roles.

#### Phase 11E verification

The PHP suite passed 569 cases with 2,078 assertions, up from 532 and 1,939. The 37 new cases in AnnouncementPollPackTest.php include the management authorization matrix, pin order/audit, archive/search, read tracking, closure, ballot privacy and edit/current-choice rules. Existing query-budget tests passed unchanged. The full browser suite passed 67 cases with its one existing opt-in live skip in 13.0 minutes, up from 63 passed. The four new cases in announce-polls.spec.js cover all six items at 390 and 1440 in both themes. All 250 sweep states passed. A final focused rerun passed all four cases in 1.6 minutes after the dialog changes, including invalid-deadline recovery, retained values and the sticky results Close at 390px height. No existing test file was edited.

Fresh SQLite migration and DemoSeeder, one rollback step and re-migration passed against a separate scratch database. The local environment was separately verified as local SQLite before applying its pending migrations: six older migrations (request coordinates, transaction authority index, queue jobs, audit events, chat social fields and deletion details) and the new announcement/poll migration were applied. No development database reset or demo reseed was used. Migration status is now fully applied. The frontend build, Pint, JavaScript syntax, whitespace and staged style/credential checks passed. Twenty feature captures are under the ignored storage/app/test-screenshots/phase-11e directory. Existing route names and verbs remain unchanged; eight scoped routes were added. Existing migration files were not changed.
