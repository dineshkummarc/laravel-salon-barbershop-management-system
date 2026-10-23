# Demo walkthrough

Use the README demo accounts and local-only password. Enable `SALON_DEMO=true`; mail should remain `log` and live AI should remain disabled.

1. Open `/b/dhaka-grooming`. Browse services and products; switch between English and Bengali.
2. Sign in through `/b/dhaka-grooming/login` as `customer@example.test`.
3. Book a service at Dhanmondi with its assigned stylist. Choose a future time between branch opening/closing, or use available-time lookup.
4. Sign out of the customer account. Sign into `/admin/login` as `owner@example.test` or reception.
5. Open Bookings. Find the new customer/time. Check in, start service, then complete.
6. As owner/cashier, open Bill in POS. Confirm customer, stylist, original service price, payment, and receipt. The same appointment cannot be billed twice.
7. Inspect receipt, thermal toggle, and PDF. All payment records are local demo/manual records.
8. As the customer, add products to the bag and place a pickup order. Re-enter the owner workspace, find its number in Sales, collect payment, process, mark ready, then collected.
9. As owner, return a quantity with a reason. Inspect Credit Notes, Stock Movements, due/refunded values, and ledger entries. You must handle actual external money movement in a real business.
10. Receive part of a purchase. Create and dispatch a transfer, then receive it while viewing all branches.
11. Issue a package or gift card only after the corresponding demo cash collection. Try package redemption against a completed eligible appointment.
12. Open Attendance Devices and sync the fake terminal twice. Raw events remain deduplicated. Inspect attendance; generate payroll for an ungenerated employee/month, review, then pay/lock.
13. Inspect Reports, trial balance, and CSV export. Use Business Settings to compare module presets; save only valid dependencies/plan entitlements.
14. Sign into the basic barber as `owner2@example.test`. Its simpler modules and separate business data demonstrate isolation.

Resetting with `salon:demo-reset --force` deletes the configured non-production database. It is not a general production maintenance command.
