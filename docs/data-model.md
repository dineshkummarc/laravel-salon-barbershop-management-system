# Data model

All operational entities carry `business_id`; branch-specific entities also carry `branch_id`. Monetary columns store integer paisa. Posted financial data is corrected with new journal/credit records, rather than UI deletion.

```mermaid
erDiagram
    Business ||--o{ Branch : operates
    Business ||--o{ User : authorizes
    Branch ||--o{ Employee : assigns
    Business ||--o{ Customer : serves
    User o|--o| Customer : identifies
    Customer ||--o{ Booking : books
    Service ||--o{ Booking : schedules
    Employee ||--o{ Booking : performs
    Booking o|--o| Sale : bills
    Customer o|--o{ Sale : purchases
    Branch ||--o{ Sale : handles
    Sale ||--|{ SaleItem : contains
    Sale ||--o{ Payment : receives
    Sale ||--o{ CreditNote : credits
    Product o|--o{ SaleItem : sells
    Service o|--o{ SaleItem : supplies
    Product ||--o{ Stock : locates
    Branch ||--o{ Stock : holds
    Product ||--o{ StockMovement : traces
    Supplier ||--o{ Purchase : supplies
    Product ||--o{ Purchase : orders
    Branch ||--o{ Transfer : dispatches
    Service ||--o{ ServiceRecipe : consumes
    Product ||--o{ ServiceRecipe : provides
    Journal ||--|{ LedgerEntry : balances
    Employee ||--o{ Attendance : records
    Employee ||--o{ Payroll : earns
    AttendanceDevice ||--o{ AttendanceEvent : captures
    PackagePlan ||--o{ CustomerPackage : issues
    CustomerPackage ||--o{ PackageRedemption : redeems
    Customer ||--o{ Membership : joins
    MembershipPlan ||--o{ Membership : defines
    GiftCard o|--o{ Payment : tenders
    Customer ||--o{ LoyaltyEntry : earns
    Customer ||--o{ Review : writes
    Campaign ||--o{ MessageDelivery : sends
    Business ||--o{ PlatformInvoice : subscribes
```

## Important uniqueness constraints

- Business slug; tenant-local product SKU/slug and content/service slugs.
- Branch/product stock row.
- Sale idempotency key and receipt token; one sale per booking.
- Payment idempotency key; credit-note idempotency key.
- Journal event key; one package redemption per booking.
- Employee/date attendance; raw attendance event hash; employee/month payroll.
- Tenant coupon code; gift code; review per sale; campaign/customer delivery.

## Snapshots and policies

Sales retain item names, unit prices, standard costs, tax/discount totals, and commissions. Bookings retain service price. Memberships retain benefit percentage and price. Issued packages retain price/remaining uses; service/use-count changes on plans with issued packages are blocked. Product standard cost cannot change while stock is held.

The implementation does not model variants, batch/expiry inventory, multi-line purchase orders, or equipment capacities. Introduce those with explicit migrations and workflow tests rather than overloading existing columns.
