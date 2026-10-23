# Operations runbook

## Daily

Review pending online orders, failed/review-required deliveries, branch cash variances, stock shortages, and relevant audit records. Collect/verify transfer payments before marking them paid. Resolve returns with credit notes and actual external money movement.

Run `php artisan salon:check` to detect unbalanced journals. This verifies debit/credit equality, not complete financial correctness. Investigate ledger/stock/customer balance differences using original transaction references rather than editing posted rows.

## Workers and schedules

Keep queue workers and scheduler supervised. Use `php artisan queue:failed` to inspect failed jobs. Campaign and reminder jobs use one attempt and explicit delivery status; review uncertain transport results before resending. They do not prove inbox delivery. Scheduled tasks include reminders, subscription expiry, and old failed-job pruning.

## Backup and restore

Back up the database consistently, `storage/app` data, any future uploaded files, deployment configuration, and the encryption key through a protected secret-management process. Keep encrypted, access-controlled off-site copies according to your retention policy. Test restoring into an isolated environment with outbound mail/AI/device connections disabled.

For MySQL/MariaDB, use your provider's consistent snapshot or a correctly configured transaction-safe dump. Restore into a new database, check migration/version alignment, run integrity checks, compare record counts and balances, and test sign-in and representative receipts before switching traffic. This repository does not perform automatic hosted backups.

## Common incidents

| Symptom | Check |
| --- | --- |
| Module missing / 403 | User role, business plan, business module, branch override, subscription status |
| Can't transfer to another branch | Owner workspace should be set to all branches for receipt |
| Booking rejected | Branch/stylist match, working days/hours, leave, overlap, future time |
| Product sale rejected | Active product, inventory module, stock at selected branch |
| Device won't connect | Approved host, provider/port, employee map, network reachability, actual model support |
| Mail not received | Mail transport, queue worker, delivery status, recipient consent, local log mode |
| AI import fails | Provider key/model, response JSON, host allowlist, source HTML/size/redirect policy |
| Bengali PDF issue | Bundled fonts/config, package revision, native engine output, actual rendered document |
| Unexpected cash variance | Correct user/branch register, all cash postings, actual physical count |

## Privacy and access

Use least-privileged role/branch accounts. Customer notes should contain only information necessary for service. Do not store biometric templates or sensitive identity documents in this baseline. Data export/deletion and retention requests require operator review; there is no automated customer erasure workflow. Retained financial records may have different obligations from marketing data.

Subscription suspension preserves records. A trusted platform operator can confirm renewal/reactivate or assist with authorized data access; owners do not receive an automatic suspended-account export portal in this release.
