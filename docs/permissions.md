# Permissions

The baseline role matrix is implemented in `App\Models\User::allows()`. A custom role editor is not part of this release.

| Role | Baseline access |
| --- | --- |
| Platform | Business management, explicit business workspace selection, subscription payment confirmation, authorized business operations |
| Owner / business admin | All business abilities; business/branch scope still enforced |
| Manager | Catalog, appointments, POS/orders, inventory/purchasing, CRM, expenses/reports, HR/attendance, packages/marketing, register |
| Reception | Appointments, CRM, orders |
| Cashier | POS, orders, CRM, cash register |
| Stylist | Appointments for linked employee |
| Inventory officer | Catalog, inventory, purchasing |
| Accountant | Expenses, accounting, reports, payroll |
| HR | Employees, attendance, payroll |
| Customer | Own public account records, orders, bookings, and authorized receipts |

Manual discounts and refunds are owner/admin/platform abilities in the baseline. Creating and managing staff access is a settings ability. Team access editing cannot change the current operator's own account through that resource.

Branch-assigned users cannot bypass assignment through forms or forged IDs. Owners can select a branch filter; switch back to all branches for cross-branch transfer receiving. Gift cards are tenant-wide bearer/named tender; custody and code exposure require care. Customer packages remain branch-scoped in the baseline.

Platform workspace selection records an audit entry. The platform role is highly privileged; provision it only for the application operator. No silent impersonation is implemented.
