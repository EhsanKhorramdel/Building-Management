# Refactor Log — Building-Management

This file is the single record of EVERY change made during the refactor.
One entry per distinct change. No entry = the change did not happen.
Codex appends entries in every phase; the reviewer checks this file against the diff.

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
- **Before:** building + dashboard pages have no viewport meta; dashboard CSS (1028 lines) has 5 media queries and fixed widths up to 1800px; three conflicting Tailwind versions load on one page (2.2.15 CDN, 0.3 unpkg, Play CDN) on top of hand-written CSS; app pages bypass Vite so assets are unminified/unhashed.
- **Why it matters:** mobile layout is broken/unpredictable — the main user device for residents is a phone.
- **Cause:** pages grew as static files outside the build pipeline; Tailwind was added three times at three moments.
- **Closed by:** Phase 5.

### [Baseline] Frontend monolith + duplication
- **Before:** `public/js/building.js` is 1495 lines covering chat/polls/payments/uploads; the CSRF-token block repeats 14×; PHP builds the same message array 7×; the random upload-filename block repeats 6×; `BuildingController` is 771 lines / 13 responsibilities.
- **Why it matters:** every fix must be made in several places; regressions hide easily.
- **Cause:** features were appended to the same files without extraction points.
- **Closed by:** Phases 4–5.

### [Baseline] N+1 query patterns
- **Before:** building dashboard resolves users/units inside nested loops; payments report queries per unit and per payment (`User::findOrFail` inside loops); vote results counted in PHP; last-seen scans all seen-rows.
- **Why it matters:** page cost grows with residents × payments; will crawl on a real building.
- **Cause:** collection processing in PHP instead of eager loading / aggregate queries.
- **Closed by:** Phase 4.

### [Baseline] No uniqueness in the database
- **Before:** no unique constraints on votes, seen_messages, units (per complex), payments; integrity kept only by in-code checks.
- **Why it matters:** concurrent requests create duplicates the UI assumes impossible.
- **Cause:** constraints were left to application code.
- **Closed by:** Phase 3.

### [Baseline] Dark mode does not exist
- **Before:** light theme only; no `dark` variant anywhere, despite Tailwind being present.
- **Why it matters:** standard expectation for a resident-facing app used at night; also part of the owner's design goals.
- **Cause:** never implemented — recorded here as a missing capability, not a bug.
- **Closed by:** Phase 5 (toggle + system preference + full-screen audit, both themes in screenshot QA).

### [Baseline] Mixed/absent UX states
- **Before:** fetch calls largely without error handling (1 try/catch for 11 calls), no loading/empty states system, destructive actions without confirm, alerts inconsistent.
- **Why it matters:** failures are silent for users; misclicks are unrecoverable.
- **Cause:** happy-path-first UI.
- **Closed by:** Phase 5.

### [Baseline] Tests do not cover the app
- **Before:** only default Breeze tests exist; ProfileTest targets routes that are commented out, so part of the stock suite fails; no factories except User; no seeders.
- **Why it matters:** no change can be proven safe.
- **Cause:** tests were never adopted as part of the workflow.
- **Closed by:** Phase 1 (+ Phase 9 for browser/E2E).

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

(Codex appends here, one entry per change, newest phase last.)


### [Phase 0] Audit inventory and verified baseline
- **Before:** The supplied baseline had no checked-in inventory or measured test result. Its profile-route failure was known, but two additional redirect failures and several model, tenant-boundary, date, reporting, deployment and UI defects were not recorded.
- **After:** Added docs/REFACTOR_AUDIT.md with every controller/model/route/migration/Blade/JS/CSS path, exact runtime and locked package versions, per-test before/after outcomes, source-specific findings grouped by area, and dependency advisory references. Copied this log unchanged before appending this one entry. No application behavior changed.
- **Why:** Confirmed additional findings include: profile optional-field 500 (B07); wrong incidental-payment/user-vote relations and missing group_user pivot (B02-B04); invalid date ordering and migration-time role default (B10/D03); monthly rather than lifetime charge duplicate protection (B11); omitted role payment history (B12); nontransactional/repeatable approval and inconsistent reject state (B13); unvalidated unit/cost fields and no-op unit edit rejection (B14-B15); duplicate logout names breaking route caching (B20); unknown date fillable column (B21); unscoped poll/results/payments and unit mutations, membership creation without a join token, cross-complex payable references and inactive-role access (S01-S02/S07-S08); payment modals retaining the final card amount, lost form values, cursor retry suppression, malformed/duplicate markup and incomplete keyboard/theme handling (F03-F15). Dependency scans match 61 Composer advisories across 17 packages and 17 affected npm packages (S11). See the audit for further locations, invariant gaps and verification limits.
- **Baseline corrections:** Dark mode already exists partially; building has three Tailwind sources while dashboard has one; cited 1800/480 widths are maxima and 520 is a breakpoint; there are 7 repeated CSRF blocks and 3 upload blocks; dashboard resident scans use loaded collections, with poll options providing the per-poll queries; DatabaseSeeder exists but is a no-op. Suite result is 24 tests / 17 passed / 7 failed / 43 assertions: all five ProfileTest cases plus successful-login and registration redirect assertions. The charge-payment rule currently permits another payment in a later month, so a lifetime unique pair would change behavior. Supplied baseline entries remain intact.
- **Files:** docs/REFACTOR_AUDIT.md; docs/REFACTOR_LOG.md. Local ignored .env/key, SQLite file, dependencies, caches and frontend build are environment setup only; manifests, lockfiles and application files are unchanged.
- **Verified by:** Locked Composer install and npm ci; 19 SQLite migrations; identical full-suite before/final-after results with names recorded in the audit; Vite build; 160 tracked *.php syntax checks; Composer platform check; route-list and duplicate-name route-cache check (cache cleared afterward); read-only dependency audits; synthetic in-memory relation/profile/empty-chat probes; docs-only diff and byte-preserved source-log prefix checks.
- **Risk & rollback:** Documentation only; pre-existing failures and vulnerabilities remain open. Tests used process-level in-memory SQLite overrides because phpunit.xml inherits the dev DB otherwise. Revert this phase's single docs commit to remove the inventory/log import; local ignored setup is outside the commit. No later phase was started.
