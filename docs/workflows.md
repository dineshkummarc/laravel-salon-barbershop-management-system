# Operational workflows

## Appointment to bill

Customer/reception chooses branch, service, stylist, and time. Branch hours, stylist shift/day/leave, module state, and overlap are checked while holding a branch lock. Check in → in service → complete. Bill the completed appointment in POS. The original booking price is used and the unique booking link prevents duplicate billing. Package redemption blocks subsequent POS billing of the same visit.

Single-service bookings and fixed home travel buffers are the current scope. A separate automatic walk-in token/wait-estimation system is not implemented.

## POS / online checkout

The server re-reads active catalog records and prices. Applicable membership/coupon discount and loyalty redemption are calculated, followed by tax. Tips are a payable. A sale creates item snapshots, deducts stock/recipes, posts journals, records rewards, and records supplied payments in one transaction. Any insufficient stock/invalid payment rolls everything back.

Online orders deduct stock at placement. Order fulfillment and payment are distinct. Collected/delivered status requires no remaining due. There is no live gateway or expiring reservation subsystem.

## Returns and credit notes

Select eligible item quantities and a reason. Proportional revenue/discount/tax allocations use cumulative integer arithmetic. A credit note reduces the invoice balance. Only excess net money collected over the remaining invoice is refunded. Original cash/bank/gift payments are allocated explicitly. Retail stock is restored; consumed service materials are not restored by a service refund. Commissions and rewards are reversed proportionally; paid payroll receives a future commission adjustment record.

Tips are credited when the entire remaining sale is reversed. External cash/bank/mobile-wallet refund movement is the operator's responsibility. The app records the result; it cannot perform an unconfigured merchant refund.

## Inventory

Create a purchase, receive some/all units, then inspect stock movements. A purchase order alone adds no stock. Standard cost is used for inventory; differences in supplier price post to purchase-price variance.

Transfer: request → dispatch (source stock leaves and value goes into transit) → receipt (destination stock enters; branch-clearing entries balance consolidation). The baseline receives the whole transfer.

Adjustments require a reason and explicit posting. Service recipes consume whole product stock units at sale/redemption time.

## Packages, memberships, gifts

Collect cash before issuance. Packages credit a liability, then recognize service revenue at redemption. Gift cards credit a liability; tender debits that liability. Membership fees are recognized at issue and benefits expire with the term. Membership/coupon percentages do not stack. These policies are application defaults, not accounting/legal advice.

## Attendance and payroll

Manual attendance or device sync produces daily attendance. Device raw-event hashes prevent duplicate ingestion. Sync uses explicit employee mappings and approved hosts. Payroll is generated for employee/month from salary, un-reversed sales commission, and explicit allowance/deduction. Pay locks the payroll and related month's attendance corrections. Attendance-based overtime and statutory rules are not automatic.

## Cash register

Open a register with a physical float. Cash-affecting journal postings by that user and branch after opening contribute to expected cash. Close with the physical count; the difference is audited. Opening float is not itself treated as business revenue. Do not run overlapping registers for one user.

## Campaigns and reminders

Campaign dispatch creates customer delivery records only for consenting customers with email addresses. Workers re-check consent and module state. Signed unsubscribe links show a confirmation form. Reminder records prevent repeating the same appointment reminder. `sent` means accepted by the configured transport; uncertain outcomes require review.
