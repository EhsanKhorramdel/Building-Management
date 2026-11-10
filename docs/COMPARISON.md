# Original and current application

This round compares `master` at `aeef0a90c40cf6570979ebb6ca056ec5fa029a26` with the accepted Phase 8 tip `c4e327c2b610bb3b57204ab80d4634e2692f8622`. The original ran in a detached checkout. Browser writes went to disposable SQLite fixtures.

## Stack and coverage

| Measure | Original | Current |
| --- | --- | --- |
| PHP requirement | ^8.1 | ^8.3 |
| Laravel and Sanctum | 10.14.1 and 3.2.5 | 12.71.0 and 4.3.3 |
| Carbon and Jalali library | 2.68.1 and 3.4.1 | 3.15.1 and 3.5.0 |
| Pest and PHPUnit | 2.8.2 and 10.2.3 | 3.8.7 and 11.5.56 |
| Vite and Tailwind | 4.3.9 and 3.3.2 in the lockfile. The building also loaded Tailwind CDN variants. | 6.4.5 and 3.4.19 in the build. |
| Charting and maps | Chart.js 2.9.4 loaded externally. No map dependency. | Chart.js 4.6.1 and Leaflet 1.9.4 bundled. |
| Browser test tooling | No Playwright spec | Playwright 1.65.0 and three tracked specs for the visual baseline, maps and mock checkout |
| PHP suite | 24 cases, 17 passed, 7 failed, 0 skipped, 43 assertions | 387 cases, 387 passed, 0 failed, 0 skipped, 1,102 assertions |
| PHP test files | Eight feature files and one unit file. Auth and the stock profile tests dominate. | 44 feature files and one unit file. Coverage includes complex roles, browser-facing endpoints, Jalali payment periods, constraints, services, map approval, payments and SMS. |

Both audit test runs used PHP 8.3.36 and both builds used Node 22.17.0. The original lock rejected installation on PHP 8.3 because its locked Nette packages cap PHP below 8.3. The second install used Composer's `--ignore-platform-req=php+` in the detached checkout to preserve its dependency versions. The original failures reproduced for login and registration redirect expectations and five tests against the unregistered `/profile` endpoints. The current suite still uses in-memory SQLite.

## Security

| Surface | Original | Current |
| --- | --- | --- |
| Mutation verbs | Seven state-changing GET entries. | Role upgrade and downgrade, announcement cancellation and admin accept and reject use POST. Chat deletion uses DELETE. The GET logout alias is gone and the POST action serves logout. |
| Complex authorization | The access middleware looked up the role on the user without a complex scope. | Active manager and resident roles are checked against the target complex. Admin access is global and separate. The authorization matrix passed 168 cases and 242 assertions in this round. |
| Realtime chat | The three message events used a shared public `group` channel. The browser script contained a literal realtime client key. | The events use a private channel per complex and the server checks membership before authorizing a subscription. The browser gets the key from rendered configuration. Attachment URLs still use public storage. See F13. |
| Uploads | Announcement and cost uploads already had file constraints. The old chat store path lacked equivalent file and size validation. | Chat image uploads are restricted to GIF, JPG, WebP or PNG, up to 5,120 KB. A message needs text or an image. |
| Stored XSS | The chat viewers script interpolated a viewer's name into `innerHTML`. | Viewer content is inserted as text. |
| Payments and tokens | Payment rows could use browser-supplied amounts. `remember_token` was mass assignable. | Both amounts come from the payable. The persistent login token is removed from mass assignment. |
| Online endpoints | Absent. | Authenticated POST initiation, a driver callback over GET or POST, and conditional GET mock checkout. Only the callback skips CSRF middleware. Mock POST still checks the session token and payer. |

The route-list comparison returned 54 original and 58 current entries. Five entries were added for join-link rotation, map settings, payment initiation, the callback and conditional mock checkout. The GET logout alias was removed. The audit scanned tracked app, config, view, script, public and example environment files for private-key markers, common token shapes, assigned literal secrets and a literal realtime client key. The original browser script matched. The current scan returned zero matches and Git lists only `.env.example` among environment files. This is a pattern scan of the current tree.

## Database and architecture

| Surface | Original | Current |
| --- | --- | --- |
| Migration files | 19 | 25. The original migration files compare unchanged. |
| Business uniqueness | Join links and identity constraints existed, with no compound rules for voting, read receipts, units, roles or payment records. | Seven new compound unique indexes cover votes, read receipts, unit number per complex, unit membership, one-off cost payment, Jalali-month charge payment and gateway authority. Nullable unit-role assignments retain SQL NULL behavior. |
| Lookup indexes | The original table and foreign-key definitions. | Three added hot-path indexes on messages, roles and read receipts, plus the transaction morph and queue indexes. |
| Atomic operations | Building approval wrote the complex, role and group separately. | Approval commits all three and the request status together. Online settlement commits its payment row and transaction status together. |
| Tables and fields | Manual payments and text location. | Existing text location plus optional coordinates on complexes and pending requests, and new transaction, SMS log and job tables. |
| Controllers and services | 15 controller files and no `app/Services` files. `BuildingController` was 771 lines and `GroupController` was 396. | 23 controller files and 15 service files. Building actions are split by domain. The dashboard controller is 123 lines and the group controller is 115. Chat cursors and presentation, polls, uploads, payment recording and integration drivers sit outside the controllers. |

### SQL counts

The same fixture creates one role and one poll per unit, two options per poll, one announcement, and two payments per unit. Counts include the action and its view render where applicable. They exclude request middleware, fixture creation and route binding.

| Units | Payment rows | Payments report old → current | Building dashboard old → current | User dashboard old → current |
| ---: | ---: | ---: | ---: | ---: |
| 2 | 4 | 12 → 6 | 12 → 11 | 3 → 2 |
| 8 | 16 | 42 → 6 | 18 → 11 | 3 → 2 |
| 32 | 64 | 162 → 6 | 42 → 11 | 3 → 2 |

The old report looked up each unit's role, payments and payer in loops. The new report eager-loads them. Poll options were loaded per poll in the original building dashboard and are now eager-loaded. The counts are SQLite measurements with payments and SMS off. They do not represent latency or the cost of an enabled online history.

## Frontend

| Measure | Original | Current |
| --- | --- | --- |
| Asset loading | Dashboard and building used static CSS and JS plus external scripts. The building loaded three Tailwind CDN assets and external charting, color and realtime scripts. | Pages use Vite for application CSS and JS. The building entry is 21 lines and delegates to modules. The old building script and dashboard stylesheet were 1,495 and 1,028 lines. |
| Local CSS bytes | 77,326 across the original Vite output and tracked static CSS | 72,674 across the built application and map CSS |
| Local JS bytes | 129,597 across the original Vite output and tracked static JS | 495,699 across the built chunks, including bundled dependencies |
| Cold building-page transfer | 4,323,657 encoded bytes across 16 requests. Seven external requests accounted for 4,064,606 bytes. | 716,950 encoded bytes across 12 local requests. No external HTTP requests in this fixture. |
| Responsive layout | Dashboard and building lacked viewport metadata. The original document width probe stayed within the desktop-emulated widths on its three pages, which does not check internal panes or phone scaling. | Viewport metadata, drawer navigation below 768px and internally scrolling tables. All 250 current captures stayed within their document widths from 360px to 1440px. Touch findings are listed separately. |
| Dark coverage | The persisted dashboard and building toggle was present. The old dashboard stylesheet has nine dark-selector lines. | The persisted preference now reaches all 25 captured states. All 125 dark captures had the dark class. |

The transfer probe used the same two-unit fixture on each revision, Chromium with cache disabled and HTTP response bytes from the network log. It excludes socket traffic and does not load map tiles because the fixture had no coordinates. Browser transfer is distinct from the sum of files on disk.

## Capabilities added or expanded

- Demo data, expanded PHP coverage and repeatable browser screenshot guards.
- Scoped building permissions, private chat subscriptions and manager-controlled invite rotation.
- Jalali payment-period enforcement, durable uniqueness and atomic approval.
- Phone drawer navigation, full-theme styling, shared notices, destructive-action confirmations, empty states, install metadata and payment-report printing.
- Optional location selection during building registration and in manager settings, a read-only admin map, and a home map with an external directions link.
- Optional mock and configurable HTTP checkout, payer history, and queued log or HTTP SMS. Both integrations ship disabled.

## Original page controls and their disposition

The inventory below comes from both view trees, including shared layouts, and the scripts that bind interactive behavior. Hidden transport inputs are mentioned where their removal changes the trust boundary.

| Original page or panel | Controls on master | Current controls and changes |
| --- | --- | --- |
| Landing page | Register and sign-in links inside button markup. | Direct links to the same destinations and a theme toggle. |
| Login | Email, password, reveal, remember, submit, reset link and registration link. | Same sign-in actions with labelled controls and a theme toggle. |
| Register | Name, email, both password fields, reveal controls, submit and sign-in link. | Same registration actions with labelled controls and theme toggle. |
| Forgot password | Email and send-link button. | Same send-link flow in the shared asset setup. |
| Reset password | Email, new password, confirmation and submit, with a hidden reset token and linked logo from the guest layout. | Same form and linked logo. |
| Confirm password | Password, submit and linked logo. | Same actions. |
| Verify email | Resend link, log out and linked logo. | Same actions, with POST logout. |
| Dashboard home | Join link input and submit, list/grid, building detail links, unit add/edit menus and forms, theme, profile-looking header button, navigation and logout. | Join, list/grid, building links, unit actions, theme and navigation remain. The header button had no binding and is now a name label. Logout uses POST. |
| Dashboard registration | Building name, address, floors, units and submit. | Same fields with the optional location picker and coordinate inputs. |
| Dashboard settings | Profile name, email, national ID, phone and save, plus password change fields and save. | Same forms. Optional identity fields can be omitted on update. |
| Building shell and home | Tabs, theme, profile-looking header button, create action for the selected tab, logout, residents list/grid and role promotion. | Tabs, theme, create actions and resident controls remain. The unbound header button is a name label. Logout and promotion use POST. Home adds address and optional map. |
| Announcements | Cancel, open and close image preview, open and close creation form, text, attachment, remove attachment and submit. | Same operations. Cancellation asks for confirmation and uses POST. |
| Polls | Vote radio and submit, view/close result, create/close, title, question, three duration fields, add/remove options and submit. | Same controls, chart bundled locally. The browser found a create failure despite the controls being present. See F01. |
| Incidental costs | Invoice preview and close, unit number, pay and cancel, and manager form with title, description, invoice, total and share. | Same controls. The amount displayed comes from the server. The browser-controlled hidden amount is removed. Optional online checkout uses the same pay entry. |
| Monthly charges | Unit number, pay and cancel, and manager form with amount and start/end date parts. | Same controls. The browser-controlled hidden amount is removed. Optional online checkout uses the same pay entry. |
| Payments report | Open report tab and read payment rows. | Readable table and Print. Online mode adds the payer's attempts and charge and cost shortcuts. |
| Chat | Compose, attach, send, copy, edit, delete, viewers, scroll-driven older paging and a jump-to-bottom button. | Compose and message actions remain, with delete confirmation. Explicit older/newer buttons replace scroll-driven paging. The jump-to-bottom control is removed. See F02. |
| Building settings | Five share links, invite text and copy, and manager step-down. | All five links and copy remain. Step-down uses POST and confirmation. Invite rotation and map editing are added. |
| Admin | Pending, accepted and rejected tabs, accept, reject and logout. | Same actions. All three writes use POST, rejection confirms, and request details can open a read-only map. |
| Stock profile scaffold | Profile update, password update and account deletion exist as Blade partials. The edit and delete routes were commented out. | Profile partials compare unchanged. The dashboard profile update remains registered. The scaffold still has no standalone route. |

## Evidence from this round

- Stack, file and asset counts come from `git ls-tree`, `git show`, both lockfiles, and byte counts after each `npm run build`. The recorded results are in `phase8b-source.json` in the audit scratch directory, `D:/Building-Management-work`.
- Test and route counts come from `artisan test`, the authorization filter and `artisan route:list --json` at both revisions. The route delta and credential scan are in `phase8b-route-diff.txt` and `phase8b-security.txt` in the same directory.
- SQL and network measurements are in `phase8b-queries-old.jsonl`, `phase8b-queries-new.jsonl` and `phase8b-weight.json`. The viewport probe is in `phase8b-responsive.json`.
- Current browser captures and `guards.json` are in `storage/app/test-screenshots/phase-8b`. The original and current view and script comparisons use `git diff master HEAD` and the two view trees.

## Owner summary

The building app keeps its everyday workflows and adds maps, better phone layouts and optional integrations. Complex permissions are tighter, and payment reports do far less database work as buildings grow. The audit found a poll creation error and a lost chat shortcut, along with smaller accessibility and touch issues. The Findings document records what needs a decision.
