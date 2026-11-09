# Changelog

See [REFACTOR_LOG.md](docs/REFACTOR_LOG.md) for the engineering record and verification details.

## [Unreleased] — internal refactor

### Added

- Local demo data for administrators, managers and residents.
- A printable payment report, install metadata and clearer empty and loading states.
- Optional map locations during building registration, admin review, and manager settings.

### Changed

- Phone layouts use a drawer, fit forms and dialogs to the viewport and keep the chat composer in reach.
- Dark mode covers the building, chat, account and admin screens while keeping existing preferences.
- Payment reports use fewer queries as complexes grow. Poll results are counted in the database.
- Forms show a shared error and success display, preserve input on validation errors and confirm destructive actions.
- The application runs on Laravel 12 and PHP 8.3. The existing building workflows have regression coverage.

### Fixed

- Expired polls reject votes, and blank options no longer hide later options.
- Empty chat opens normally, and profile updates preserve omitted optional fields.
- Charge payments can be recorded again in a new Jalali month, while duplicate payments in the same period are blocked. Building approval creates its records together.

### Security

- Building pages, actions and live chat enforce membership in the target complex. Managers can rotate an invite to invalidate the previous one.
- Payment amounts come from stored charges and costs. Chat rejects unsupported or oversized image uploads.
- Destructive actions require protected requests. The chat viewer list renders names as text.
