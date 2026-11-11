# Architecture

The application uses Laravel 12, Blade, and Vite. The Laravel 11 and 12 upgrades kept the existing HTTP kernel and service providers, which still register middleware, routes, and broadcast authorization.

## Domain map

| Entity | Relationships |
| --- | --- |
| Complex | Owns units, user roles, announcements, monthly charges, incidental costs, polls and chat groups. |
| User and user role | A user can hold roles in multiple complexes. Each scoped role belongs to a complex and can point to a unit. Votes and both kinds of payment point to a role. A site administrator holds a global role. |
| Membership request | Belongs to the applicant. Approval of a pending request creates the complex, its initial manager role and its chat group in one transaction. Any submitted coordinates are copied to the complex at approval. |
| Announcement and read | Belongs to a complex and its authoring manager. Cancellation archives it. Pins retain their actor and time; reads link one user to one announcement. |
| Monthly charge and charge payment | A charge belongs to a complex and its author. A payment links the charge to the paying role and a Jalali month. |
| Transaction and transition | An online attempt belongs to a payer and role, links to a charge or cost, and stores the completed payment ID. Transition rows record state, actor and time. Callback issue rows flag mismatches and replays. |
| SMS log | Records the recipient, template, driver and delivery outcome of an SMS attempt. |
| Incidental cost and additional cost payment | A cost belongs to a complex. Its payment links the cost to the paying role. |
| Poll, option and vote | A poll belongs to a complex and owns numbered options. A vote links a role to one option in that poll. |
| Group, message and seen receipt | A chat group belongs to a complex. Messages belong to the group and their authors. A seen receipt links a message to a user in that group. |

## Roles and permissions

Admin is global. Manager and resident permissions require an active role in the target complex. An admin account needs a separate scoped role to act as a building member.

| Capability | Admin role | Manager in complex | Resident in complex | Role in a different complex |
| --- | --- | --- | --- | --- |
| Review, approve or reject building requests | Yes | No | No | No |
| View a building dashboard and payment report | No | Yes | Yes | No |
| Promote residents, step down, rotate a join link | No | Yes | No | No |
| Set or clear the saved map location | No | Yes | No | No |
| Publish or archive announcements, create charges or incidental costs | No | Yes | No | No |
| Create polls, vote while open and view results | No | Yes | Yes | No |
| Pin announcements, search/restore the archive and view reader names | No | Yes | No | No |
| Close or edit a poll | No | Yes; edits lock after the first vote | No | No |
| View named ballots | No | Only after deadline or manual closure | No | No |
| Add a unit | No | Yes | Yes | No |
| Edit a unit | No | Any unit in the complex | Unit tied to the resident's active role | No |
| Record payments | No | Own unit | Own unit | No |
| View payment receipts | All | Complex payments | Own payments | Own payments only |
| Review online payment attempts | All | Own complex | No | No |
| Use group chat | No | Yes | Yes | No |
| View named read receipts | No | Any message in the complex | Own message | No |
| Edit a chat message | No | Own message | Own message | No |
| Delete a chat message | No | Own message, or another author's message with a reason | Own message | No |

Signed-in users can request a new complex or join an existing one with its current invite. Rotating an invite invalidates the old value. Web chat and the private broadcast channel share the complex membership policy.

## Data and dates

A charge payment is unique by role, charge, Jalali year and Jalali month. The same role can pay the same charge in a later month. The period fields are written from the current Jalali date and historical rows were backfilled from their creation timestamps. An incidental cost payment is unique by role and cost across all dates. Both payment amounts come from their respective charge or cost records.

| Stored fields | Convention |
| --- | --- |
| `monthly_charges.start_date` and `end_date` | Jalali date strings stored in date columns. |
| `charge_payments.period_year` and `period_month` | Numeric Jalali year and month. |
| `polls.start_date` and `end_date` | Datetimes cast by the model and compared against the current time. |
| `user_roles.start_date` and `end_date` | Gregorian dates. |
| Eloquent timestamps, including payment and seen-receipt `created_at` | Datetimes. The report converts payment dates to Jalali for display. |

`transactions` and `sms_logs` store the two optional integrations. Both integrations are disabled by default.

## Announcements and polls

Active announcements render pinned first in ascending pin time, then by ID. Pin times retain microseconds, repeated pin requests do not reorder them, and unpinning is explicit. Archiving retains a pin but removes the notice from member panels; managers can search active, archived or all notices and restore them. Text search uses the shared Persian/Arabic letter and digit substitutions on both query and stored text. Only managers receive the archive and its search controls.

A read is recorded when a notice enters the member's visible panel. The unique announcement/user pair makes repeated requests idempotent. Counts are distinct people and include managers reading their own notices. Manager-only endpoints return reader names; members receive only acknowledgement of their own read.

Poll closure records the manager and time and cannot be reversed. Vote, edit and close operations lock the poll row in a transaction. Editing changes title, question and deadline only, before the first vote; options remain unchanged. A member's current option is selected when the vote dialog opens. Aggregate results retain their previous shape and per-role counts. A separate endpoint authorizes managers and requires a closed poll before loading named ballots. Names are listed once per person per option, while ballots belonging to a different poll or complex are excluded from that payload.
## Real-time chat

Chat, announcement and invoice images share an accessible viewer. The chat gallery pages non-deleted attachments by message ID, ten at a time, within the authorized complex. Image and download requests use the existing private delivery routes; gallery metadata never grants access to file bytes. Keyboard, wheel and touch zoom share one transform, with panning, visible Close, Escape and touch swipe-down. Closing restores focus to the image control.

Seen counts exclude the author, matching the viewer detail list. Authors see their own inline counts; active managers see counts across their complex. The scoped counts endpoint filters the audience on the server. A new receipt emits only message_id and count on the private complex channel, while names remain behind the existing author/manager detail endpoint. The client marks messages by viewport intersection as before. Counts update without rebuilding the thread, so keyboard focus is preserved.

1. Each complex uses a private Laravel channel named `group.{complexId}`. The server derives the complex from the message's group.
2. The web client subscribes to `private-group.{complexId}` when Pusher is configured. It uses the rendered public key and cluster.
3. The client authorizes the subscription through `/broadcasting/auth` with its session and CSRF token. The callback in `routes/channels.php` permits active residents and managers of that complex.
4. Message, reaction, pin and deletion events deliver changes on that channel. A bulk deletion emits one `ChatMessagesDeleted` event containing IDs and tombstone kinds. The client applies them by message ID.
5. The HTTP endpoints load message history around the last seen message or page forward and backward by ID.

Managers can delete another author's message with a required reason of at most 240 characters. The message retains its body for storage, but history, quotes and events expose only a tombstone. The row records the deletion actor, reason and time. The scoped audit entry contains the reason and message subject, without copying the message body or attachment path. Bulk requests accept up to 50 distinct IDs from the route's group and return a result for each ID, including denials and previously deleted rows.

Search uses the digit mapping from the typed input boundary plus Arabic/Persian letter variants. SQL applies the same replacements to the current stored body. Results exclude deleted messages, use an ID cursor with 20 matches per page, and link to the existing history paging route. Stored bodies retain their original spelling.

Typing uses `client-typing` whispers on the private channel, throttled to one active signal per 1.5 seconds. Stops bypass that throttle. Peers expire five seconds after the latest signal. Client events require the broadcasting app's Client Events setting. Names are advisory client data rendered as text, and no typing state is stored by the server.

## Reports and payment records

The report covers completed payments. Filters combine a Jalali charge year and month, an inclusive Jalali paid-at range, unit number and payment type. Selecting a charge period restricts the report to charges. The first role per unit remains the report's selection rule. Both its JSON and CSV use the same scoped row generator. Screen queries use eager loading, while the CSV streams in batches of up to 100 units. Print includes the complex and active filter labels.

Receipt routes authorize the original payer, an active manager of the owning complex or a global admin. The receipt number combines the payment type and ID. Charge receipts display the stored Jalali period. Cost receipts use the paid-at month. Successful online callbacks save the payment ID with the paid state. The migration links historical rows when one payment and one paid attempt match by role, item, amount and the attempt's lifetime. Ambiguous historical matches display an unknown method and omit the tracking code.

The payment service inserts the initial pending transition and appends each subsequent state change in the same database transaction as the state write. Model guards and SQLite and MySQL triggers reject transition updates and deletes. The actor field holds the payer for initiation and authenticated mock callbacks. Gateway callbacks and system failures use a null actor. Duplicate callback issues record the first occurrence per kind for a transaction. Historical transactions begin with their existing state and an empty timeline.

Staff reconciliation shows failed, expired and overdue pending attempts and attempts with callback issues. It displays the last four characters of long references and masks shorter values in full. The timeline uses the stored transitions. Global admins can review all complexes and managers are scoped to the payable's complex, with the stored role supplying the scope when the item is unavailable. Payer history uses descending transaction IDs with a strict before-ID cursor and 15 rows per page.
## Online payment lifecycle

Initiation checks the active role and unit in the payable's complex, reads the amount from the charge or cost, and creates a pending transaction. A second active attempt for that role and payable is refused. The `gateway` column identifies the configured driver. Its combination with the authority token is unique.

The callback looks up the driver and authority, refuses expired attempts and serializes verification on the matching row. The generic HTTP driver checks status, amount and reference on the server. Only the callback route is exempt from CSRF because an external service cannot provide a session token. Mock callbacks require the payer's session and its CSRF token.

A verified callback rechecks the current Jalali period for charges and lifetime uniqueness for incidental costs. The existing payment writer inserts the payment, and the same database transaction marks the attempt paid and saves the reference. Failed, cancelled and expired attempts are terminal. Replay returns the stored result. If the payable amount changed or a payment already exists, the attempt records a conflict and keeps the returned reference for reconciliation.

Online forms and payer-scoped history appear only when payments are enabled. In that mode the old manual write endpoints refuse requests. Turning the flag off restores the manual forms and payment endpoints.

## SMS triggers

| Trigger | Recipients |
| --- | --- |
| Announcement published | Active residents and managers in the complex |
| Monthly charge created | Active members assigned to units in that complex. The message includes the amount and Jalali start month. |
| Poll opened | Active residents and managers in the complex. |
| Payment recorded or verified | The payer. Replaying an online callback does not enqueue a second receipt. |
| Membership request approved | The new manager |

The action queues work after saving. A dedicated database worker processes the `sms` queue even when the rest of the app uses the synchronous queue connection. Users without phone numbers are skipped and logged without creating an SMS row. The log driver records the message locally. The HTTP driver records the response status in `sms_logs.meta` and marks non-2xx responses as failed. The worker checks the flag before processing. The existing membership approval SMS uses the global flag and is separate from the four profile categories.

## Account and building preferences

Avatar thumbnails are stored on the private disk under storage/app/avatars. The authenticated avatar route checks the owner, an active shared-complex role or global admin access before streaming a thumbnail. The preference matrix is per account and defaults each channel to on. An account can turn off the in-app record and SMS independently. The SMS flag gates queueing and delivery globally, and the worker checks the recipient's preference at delivery. Tab and unit layout preferences are keyed by account and complex. A panel unavailable to the current role falls back to Home.

Join links may be reusable, time-limited or single-use. A transaction ties single-use redemption to membership creation. The scheduler or the first expired redemption attempt records expiry once. Audit views allow-list detail fields and never render invite values. Membership submissions are visible in the global audit detail view only.

## Testing

- PHP unit and feature tests run with `php artisan test` on in-memory SQLite. Feature coverage includes auth, building workflows, payment period boundaries, schema constraints and domain services.
- The authorization matrix checks guests, admins, scoped managers and residents, and access from a different complex. Broadcast tests cover the channel callback and event channel names.
- `npm run test:e2e` starts isolated SQLite servers for manual and mock-payment flows. Playwright checks the browser journeys and captures seeded screens in light and dark across five widths. See [visual QA](VISUAL_QA.md).
- Logout ends the session and redirects to the public home page. Authentication middleware sends subsequent requests for protected pages to sign-in.

## Deployment

Use PHP 8.3 or a newer PHP 8.x release, and MySQL 8. Install the locked PHP dependencies, build assets with `npm run build`, run migrations and point the web root at `public`. Keep the app key and production credentials in the deployment environment and leave demo seeding for local use.

Persist `storage/app/private` and `storage/app/public` across releases. Chat, announcement and incidental-cost attachments use private storage. Their routes check active membership in the owning complex or a global admin role before streaming image bytes. The `php artisan storage:link` command exposes stock assets such as the placeholder. When upgrading from public attachments, run the two move commands in the README and resolve any reported conflicts before reopening traffic.

The template uses a synchronous default queue and logs broadcasts. Configure Pusher for live updates. When SMS is enabled, run `php artisan queue:work database --queue=sms` for its dedicated queue. Serve production traffic over HTTPS.

Migration, one rollback step, fresh migration and demo seeding passed on scratch MySQL 8.4.13. The feature suite's bootstrap enforces in-memory SQLite, so full MySQL test coverage remains open. Two signed-in browser sessions verified live Pusher delivery and a third session verified the cross-complex authorization boundary. See [visual QA](VISUAL_QA.md#live-chat-delivery).

## Numeric form inputs

Numeric form requests normalize Persian and Arabic-Indic digits to ASCII and trim whitespace before validation. Validated quantities and IDs become integers or floats before reaching controllers and services. Complex authorization uses the same integer parser for IDs it needs before validation.

Blank optional values become null. Missing keys stay absent, so profile numbers and a saved coordinate pair are kept unless the form explicitly clears them. A missing or blank poll duration part contributes zero. Postal, national and phone numbers stay strings to retain leading zeroes.

## Audit events

`audit_events` records the actor, optional complex, event name, polymorphic subject, optional JSON metadata and creation time. Role changes capture the previous and new roles. Membership decisions capture the requested complex name. A rejection has no complex ID. Join-link rotation stores no metadata, and neither the previous nor the new link is passed to the logger.

Events are append-only. There is no update or purge action. The admin panel shows the latest 100 events across complexes to global admins. Building settings show up to 100 events for that complex to its active managers. The lists show the event, actor, complex and time. Actions record events after the domain write, and audit storage failures are logged without interrupting the action.
