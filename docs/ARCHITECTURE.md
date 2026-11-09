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

`transactions` and `sms_logs` are schema scaffolding for planned capabilities. Online payment processing and SMS sending are currently disabled. The existing payment screens record amounts inside the app.

## Real-time chat

1. Each complex uses a private Laravel channel named `group.{complexId}`. The server derives the complex from the message's group.
2. The web client subscribes to `private-group.{complexId}` when Pusher is configured. It uses the rendered public key and cluster.
3. The client authorizes the subscription through `/broadcasting/auth` with its session and CSRF token. The callback in `routes/channels.php` permits active residents and managers of that complex.
4. `NewChatMessage`, `EditChatMessage` and `DeleteChatMessage` deliver changes on that channel. The client applies them by message ID.
5. The HTTP endpoints load message history around the last seen message or page forward and backward by ID.

## Testing

- PHP unit and feature tests run with `php artisan test` on in-memory SQLite. Feature coverage includes auth, building workflows, payment period boundaries, schema constraints and domain services.
- The authorization matrix checks guests, admins, scoped managers and residents, and access from a different complex. Broadcast tests cover the channel callback and event channel names.
- Playwright captures seeded screens in light and dark across five widths, with checks for overflow and browser errors. See [visual QA](VISUAL_QA.md).

## Deployment

Use PHP 8.3 or later and MySQL 8. Install the locked PHP dependencies, build assets with `npm run build`, run migrations and point the web root at `public`. Persist `storage/app/public` across releases and run `php artisan storage:link` to expose uploads. Keep the app key and production credentials in the deployment environment and leave demo seeding for local use.

The template runs queued work synchronously and logs broadcasts. Configure Pusher for live updates and run a queue worker if the selected queue connection is asynchronous. Serve production traffic over HTTPS.

Live MySQL migration and rollback verification is pending a MySQL environment. The end-to-end live Pusher check is pending a configured Pusher environment.
