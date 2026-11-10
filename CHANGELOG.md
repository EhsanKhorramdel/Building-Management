# Changelog

See [REFACTOR_LOG.md](docs/REFACTOR_LOG.md) for the engineering record and verification details.

## [1.0.0] — post-refactor baseline (2026-11-10)

### Added

- Local demo data for administrators, managers and residents.
- A printable payment report, install metadata and clearer empty and loading states.
- Optional map locations during building registration, admin review, and manager settings.
- Optional online payment checkout and SMS notifications. Both ship disabled.
- Recent activity for global administrators and building managers, including role changes, membership decisions and invite rotations.
- One command runs the browser checks against fresh test fixtures, including mock payments, chat, polls and map settings.

### Changed

- Phone layouts use a drawer, fit forms and dialogs to the viewport and keep the chat composer in reach.
- Dark mode covers the building, chat, account and admin screens while keeping existing preferences.
- Payment reports use fewer queries as complexes grow. Poll results are counted in the database.
- Forms show a shared error and success display, preserve input on validation errors and confirm destructive actions.
- The application runs on Laravel 12.72 and PHP 8.3 or a newer PHP 8.x release. The building workflows have regression coverage.
- Numeric forms accept Persian and Arabic-Indic digits and preserve leading zeroes in identifiers. Signing out takes users to the public home page.

### Fixed

- Expired polls reject votes, and blank options no longer hide later options.
- Empty chat opens normally, and profile updates preserve omitted optional fields.
- Charge payments can be recorded again in a new Jalali month, while duplicate payments in the same period are blocked. Building approval creates its records together.
- Chat can return to the latest message after paging back. Payment reports show when more columns are available on a phone.

### Security

- Building pages, actions and live chat enforce membership in the target complex. Managers can rotate an invite to invalidate the previous one.
- Payment amounts come from stored charges and costs. Chat rejects unsupported or oversized image uploads.
- Destructive actions require protected requests. The chat viewer list renders names as text.
- Chat attachments require authorization even when someone opens the image URL directly.

### Open items

- Live Pusher delivery test is pending a configured broadcasting app.
- Full MySQL test coverage remains open. MySQL 8.4.13 passed migration, one rollback step, fresh migration and demo seeding. The test harness requires in-memory SQLite and stopped all 437 feature cases during bootstrap.
- Announcement and incidental-cost images still use public storage. This is accepted scope. Chat attachments require authorization.
- Post-refactor backlog is tracked in the items marked Later. [Proposals](docs/PROPOSALS.md)
