# Verification evidence

Date: 23 October 2026. Local PHP 8.5.4, Laravel 12.70.1, Filament 3.3.57, MariaDB 11.8.6.

## Automated suite

30 tests, 170 assertions passed on SQLite and on a dedicated MariaDB database. Tests cover public/admin rendering, Livewire create actions, POS, appointment transitions, business/branch/customer restrictions, disabled modules, insufficient stock, overpayment rollback, payment idempotency, full/partial returns, refund limits, purchasing, payroll/attendance locks, gifts, loyalty/coupons, catalog drafts/SSRF rejection, package/membership issuance, attendance-device simulation/deduplication, cash register accounting, and settings dependency validation.

Tests use one seeded baseline with transactional isolation. The baseline is seeded outside individual test transactions to avoid MySQL auto-increment rollback differences. Never run against a production database.

## Concurrent process verification

`scripts/check-concurrency.php` was run against a dedicated MariaDB database named `haircut_concurrency_test` with two independent PHP processes competing for:

- One remaining product unit: exactly one sale succeeded; one was rejected; remaining stock was zero.
- The same stylist/time: exactly one appointment succeeded; one was rejected.

This verifies those representative races, not general load/soak behavior or all possible deadlock scenarios.

## UI and documents

Owner sign-in and representative screens were inspected using the browser: public landing/storefront, dashboard, POS draft, bookings, sales/orders, module settings, reporting, shop, and Bengali mode. The initial narrow browser view and desktop view were inspected; this is not a full device/accessibility test matrix.

Actual screenshots are in `docs/screenshots/`. A real Bengali/English invoice was generated with Unicode PDF, rendered with Poppler, and visually inspected. The compact invoice layout was revised after inspection. Sample output: `docs/sample-receipt.pdf`.

## Build and static checks

The production Vite build was completed. PHP source was formatted with Pint. Final check output is summarized in the delivery message; GitHub CI is supplied for repeatability but has not been run on a remote GitHub repository in this session.

## Not live-tested / not implemented

No merchant payment gateway, real wallet refund, SMS provider, live AI provider, or real attendance machine was contacted. Mail uses the local log/simulated test transport. Custom domains, offline POS, statutory payroll/VAT, advanced resource scheduling, and the other roadmap items in README are not represented as complete.

The broader original implementation brief is a product vision; this evidence describes the actual current release.
