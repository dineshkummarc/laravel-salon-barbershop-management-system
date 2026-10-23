# Architecture

GroomOS is a Laravel 12 / Filament 3 modular monolith. The public experience uses Blade and local CSS/JavaScript. Filament resources provide scoped administration; custom pages implement POS, reporting, settings, catalog import, and journal posting.

## Request context

`BusinessContext` is a scoped container service. Public routes resolve a business slug and expose only explicitly public records. Authenticated administration derives business membership from the user, with audited platform workspace selection. Branch-bound users cannot widen their branch scope through a submitted ID. Owners can optionally filter the workspace to one branch.

`TenantModel` applies fail-closed business scope, applicable branch scope, and stylist booking scope. It rejects cross-business creation and immutable business ownership changes. Controllers enforce customer ownership on account/order/receipt endpoints. Do not use `withoutGlobalScopes()` for ordinary business queries. The platform workspace branch selector is a deliberate exception with an explicit business filter.

## Domains

| Service | Responsibility |
| --- | --- |
| Appointments / Availability | Appointment validation, hours, leave, overlap protection, transitions, available times |
| Checkout | Server pricing, sales, payments, credits/refunds, order fulfillment |
| Inventory / StockControl | Stock movements, receiving, transfers, recipes and adjustments |
| Ledger | Balanced, idempotent journal posting |
| Operations | Packages, attendance, payroll, register closing, expenses |
| Retention | Gift issuance, membership issuance, coupons, reviews |
| DeviceSync | Allowlisted Attendance Hub adapter and raw-event deduplication |
| Messaging | Consent-aware campaign dispatch |
| Audit | Sensitive workflow history |

## Transactions and locks

Booking and checkout obtain a branch row lock before checking availability/stock, serializing competing work in that branch. Stock balance rows and financial records are locked when changed. Payment keys and other unique database constraints provide a second layer of duplicate protection. Refunds credit remaining quantities, allocate against original payment records, and cannot exceed eligible money/quantities.

The core state changes and ledger/stock/reward updates run within database transactions. Email happens through queued jobs after commit. Mail transport delivery is not exactly-once; uncertain outcomes are retained for review. There is no generalized external-payment outbox because live gateway settlement is not implemented.

## Runtime

- Relational database: SQLite demo; MySQL/MariaDB deployment.
- Queue: Laravel database queue by default.
- Scheduler: appointment reminders, subscription expiry, failed-job pruning.
- Money: integer minor units (paisa), with explicit decimal-input parsing.
- Times: UTC storage; Asia/Dhaka UI/report boundaries. Other per-business timezones need broader UI testing.
- Standard product costing; purchase price differences use a variance account.

## Limits

This is not an offline transaction engine, financial certification, live merchant gateway, or universal attendance hardware implementation. See the README release-boundary table before designing a production rollout.
