# Modules, presets, and plans

Business Settings contains the module registry and presets. Branches can additionally list disabled modules. A branch cannot enable a business-disabled or plan-disallowed module. Resource permissions and relevant domain methods enforce modules, not just navigation.

| Module | Dependencies |
| --- | --- |
| Appointments | Services, HR |
| Ecommerce | Website, products, inventory |
| Inventory | Products |
| Purchasing | Inventory, products |
| Attendance | HR |
| Payroll | HR, accounting |
| Packages | Services, CRM, accounting |
| Memberships / gift cards | Accounting, CRM |
| Loyalty / marketing / reviews | CRM |
| Home services | Appointments |

Basic plan: website, services, POS, CRM, HR, content, reports. Professional/enterprise: full module registry. Subscription charging is manual; plan labels do not imply usage metering or automatic collection.

Presets: Basic barber; appointments salon; retail salon; full studio/chain. A preset changes the selection; the user still saves and dependency validation runs. Select only modules allowed by the plan.

Disabling preserves stored records. Re-enable a module to complete its related outstanding actions. Sales history remains accessible where the user has order permission; payment/refund operations still check the relevant sales module. Underlying financial records are never deleted by a toggle. The public website switch blocks public browsing workflows while historical customer account access remains separately authorized.
