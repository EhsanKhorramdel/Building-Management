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
Status for all: OPEN — closed by the phase noted, which adds its own entries above this section.

### [Baseline] Cross-complex authorization missing
- **Before:** Controllers check only `auth`; `AccessMiddleware` checks "is manager anywhere" (no complex_id). Chat read/write endpoints have no membership check at all.
- **Why it matters:** any logged-in user can read/send in another building's chat and a manager of one complex can manage others — data leak + unauthorized actions.
- **Cause:** authorization was implemented as a global role flag instead of per-resource policy.
- **Closed by:** Phase 2.

### [Baseline] Real-time chat is public
- **Before:** all chat events broadcast on one public `Channel('group')` shared by every complex; Pusher key hardcoded in `public/js/building.js`.
- **Why it matters:** anyone with the (public) key receives every building's messages live.
- **Cause:** private channels + channel authorization were never wired (`routes/channels.php` has only the default user channel; Echo config is commented out).
- **Closed by:** Phase 2.

### [Baseline] State changes via GET
- **Before:** delete message, upgrade/downgrade role, cancel announcement, admin accept/reject, logout are `Route::get`.
- **Why it matters:** no CSRF protection on mutations; a link can trigger them.
- **Cause:** routes were written for link-click convenience, not HTTP semantics.
- **Closed by:** Phase 2.

### [Baseline] Chat image upload unvalidated
- **Before:** `GroupController@store` stores any uploaded file with no mime/size validation; other uploads in the app do validate.
- **Why it matters:** arbitrary/oversized file upload.
- **Cause:** validation rules were only added to the later upload features.
- **Closed by:** Phase 2.

### [Baseline] Predictable join links
- **Before:** `join_link` generated with `uniqid()` in `AdminController@accept`.
- **Why it matters:** time-based and guessable — anyone can join any complex as resident.
- **Cause:** `uniqid()` used as if it were a random token.
- **Closed by:** Phase 2.

### [Baseline] Client-set payment amounts
- **Before:** charge/cost payment endpoints accept `Amount` from the request and only block double payment.
- **Why it matters:** a 1-unit payment records as fully paid.
- **Cause:** amounts were treated as user input instead of being read from the charge record.
- **Closed by:** Phase 2 (server-side amounts) + Phase 8 (verified gateway flow).

### [Baseline] Stored XSS in message-viewers list
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
