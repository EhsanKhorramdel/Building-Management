# Architecture

The application uses Laravel 12, Blade, and Vite. The Laravel 11 and 12 upgrades kept the existing HTTP kernel and service providers, which still register middleware, routes, and broadcast authorization.

## Domain map

| Entity | Relationships |
| --- | --- |
| Complex | Owns units, user roles, announcements, monthly charges, incidental costs, polls and chat groups. |
| User and user role | A user can hold roles in multiple complexes. Each scoped role belongs to a complex and can point to a unit. Votes and both kinds of payment point to a role. A site administrator holds a global role. |
| Membership request | Belongs to the applicant. Approval of a pending request creates the complex, its initial manager role and its chat group in one transaction. Any submitted coordinates are copied to the complex at approval. |
| Announcement | Belongs to a complex and its authoring manager. Cancellation archives it. |
| Monthly charge and charge payment | A charge belongs to a complex and its author. A payment links the charge to the paying role and a Jalali month. |
| Transaction | An online attempt belongs to a payer and role and links to a charge or incidental cost. |
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
| Add a unit | No | Yes | Yes | No |
| Edit a unit | No | Any unit in the complex | Unit tied to the resident's active role | No |
| Record payments | No | Own unit | Own unit | No |
| Use group chat and view read receipts | No | Yes | Yes | No |
| Edit or delete a chat message | No | Own message | Own message | No |

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

## Real-time chat

1. Each complex uses a private Laravel channel named `group.{complexId}`. The server derives the complex from the message's group.
2. The web client subscribes to `private-group.{complexId}` when Pusher is configured. It uses the rendered public key and cluster.
3. The client authorizes the subscription through `/broadcasting/auth` with its session and CSRF token. The callback in `routes/channels.php` permits active residents and managers of that complex.
4. `NewChatMessage`, `EditChatMessage` and `DeleteChatMessage` deliver changes on that channel. The client applies them by message ID.
5. The HTTP endpoints load message history around the last seen message or page forward and backward by ID.

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
| Online payment verified | The payer. Replaying a callback does not enqueue a second receipt. |
| Membership request approved | The new manager |

The action queues work after saving. A dedicated database worker processes the `sms` queue even when the rest of the app uses the synchronous queue connection. Users without phone numbers are skipped and logged without creating an SMS row. The log driver records the message locally. The HTTP driver records the response status in `sms_logs.meta` and marks non-2xx responses as failed. The worker checks the flag before processing.

## Testing

- PHP unit and feature tests run with `php artisan test` on in-memory SQLite. Feature coverage includes auth, building workflows, payment period boundaries, schema constraints and domain services.
- The authorization matrix checks guests, admins, scoped managers and residents, and access from a different complex. Broadcast tests cover the channel callback and event channel names.
- `npm run test:e2e` starts isolated SQLite servers for manual and mock-payment flows. Playwright checks the browser journeys and captures seeded screens in light and dark across five widths. See [visual QA](VISUAL_QA.md).
- Logout ends the session and redirects to the public home page. Authentication middleware sends subsequent requests for protected pages to sign-in.

## Deployment

Use PHP 8.3 or a newer PHP 8.x release, and MySQL 8. Install the locked PHP dependencies, build assets with `npm run build`, run migrations and point the web root at `public`. Persist `storage/app/public` and `storage/app/private/chats` across releases. Run `php artisan storage:link` for public announcement and cost images. Chat attachments are served through the complex membership check, with a global admin exception. Keep the app key and production credentials in the deployment environment and leave demo seeding for local use.

The template uses a synchronous default queue and logs broadcasts. Configure Pusher for live updates. When SMS is enabled, run `php artisan queue:work database --queue=sms` for its dedicated queue. Serve production traffic over HTTPS.

Migration, one rollback step, fresh migration and demo seeding passed on scratch MySQL 8.4.13. The feature suite's bootstrap enforces in-memory SQLite, so full MySQL test coverage remains open. Live Pusher delivery is pending a configured broadcasting app.

## Numeric form inputs

Numeric form requests normalize Persian and Arabic-Indic digits to ASCII and trim whitespace before validation. Validated quantities and IDs become integers or floats before reaching controllers and services. Complex authorization uses the same integer parser for IDs it needs before validation.

Blank optional values become null. Missing keys stay absent, so profile numbers and a saved coordinate pair are kept unless the form explicitly clears them. A missing or blank poll duration part contributes zero. Postal, national and phone numbers stay strings to retain leading zeroes.

## Audit events

`audit_events` records the actor, optional complex, event name, polymorphic subject, optional JSON metadata and creation time. Role changes capture the previous and new roles. Membership decisions capture the requested complex name. A rejection has no complex ID. Join-link rotation stores no metadata, and neither the previous nor the new link is passed to the logger.

Events are append-only. There is no update or purge action. The admin panel shows the latest 100 events across complexes to global admins. Building settings show up to 100 events for that complex to its active managers. The lists show the event, actor, complex and time. Actions record events after the domain write, and audit storage failures are logged without interrupting the action.
