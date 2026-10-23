# AI coding agent prompt: Bangladesh salon and barbershop business platform

You are the lead engineer responsible for building and validating a complete, runnable business application in this repository. Implement the application; do not stop at architecture, mockups, generated CRUD, or a plan. Work incrementally until the acceptance criteria below pass. Record any genuine external dependency blockers accurately. Never describe incomplete, simulated, or untested functionality as production ready.

## 1. Business goal

Build a configurable Laravel and Filament business management platform for Bangladeshi haircut businesses: single-chair barbers, neighborhood salons, men's grooming shops, women's beauty parlors, unisex salons, premium spas, bridal studios, home-service operators, and multi-branch chains. A small shop must be able to operate with only services and POS, while a larger business can enable appointments, ecommerce, inventory, HR, payroll, and other modules.

Deliver three connected experiences:
1. A polished public business website and online store for each business.
2. A customer account for bookings, orders, payments, and loyalty.
3. Filament administration for owners, branch teams, and platform administrators.

Support multiple independent businesses and multiple branches within each business. A business with one branch must have a simple interface. Use fictional businesses and customers in demos.

## 2. Execution and dependency rules

- First inspect repository instructions, current files, runtime, and existing dependencies. Preserve existing user work.
- Use PHP, Laravel, Filament, Blade/Livewire, Tailwind, and a relational database. Select mutually compatible maintained versions after checking official documentation and Composer constraints. Document the exact versions selected and commit dependency lockfiles.
- Prefer a modular monolith with clear domain services, policies, jobs, events, and transaction boundaries. Do not create unnecessary microservices.
- Use MySQL/MariaDB for the documented primary deployment. Run critical concurrency and database behavior checks against the primary database engine. Lightweight local alternatives must be explicitly documented and tested separately.
- Use queues and scheduled jobs for reminders, reports, imports, integrations, and synchronization. Provide a local setup that works without paid services.
- Before coding, write a concise implementation checklist and an entity relationship diagram in docs/. Continue implementation after planning.
- Build complete vertical workflows, then broaden coverage. Update the checklist with evidence, not assumptions.
- If tools or context limits interrupt work, save an accurate continuation document with completed work, remaining tasks, commands, and blockers. Do not quietly reduce scope.

Evaluate and integrate these requested packages using their actual current documentation and source:

| Package | Intended use |
| --- | --- |
| https://packagist.org/packages/imrandevbd/drutogoti | Development/runtime acceleration where appropriate and measurable |
| https://packagist.org/packages/imrandevbd/laravel-admin-panel | Dashboard/admin capabilities compatible with the Filament architecture |
| https://packagist.org/packages/imrandevbd/laravel-ai-catalog | Assisted product catalog import and generation from permitted sources |
| https://packagist.org/packages/imrandevbd/laravel-ai-hub | AI provider abstraction and optional AI features |
| https://packagist.org/packages/imrandevbd/laravel-attendance-hub | Attendance device integration |
| https://packagist.org/packages/imrandevbd/laravel-unicode-pdf | Bengali and multilingual invoices, receipts, and reports |
| https://packagist.org/packages/imrandevbd/laravel-universal-slug | Bengali/English Unicode slugs |

For every package, verify availability, license, release stability, PHP/Laravel/Filament compatibility, real APIs, configuration, and security implications. Do not invent facade names or assume these packages are Filament plugins. Keep Filament as the primary business admin UI; integrate laravel-admin-panel only where it is compatible and serves a clear purpose. Avoid parallel conflicting admin/auth systems. Document that decision. Do not lower the entire dependency tree to unstable releases just to install one package. Isolate any unavoidable development release, pin its revision, and record the risk. If a package is unavailable or incompatible, retain the required business capability through a clean adapter and document the fallback and reason. Never claim the unavailable package is integrated.

Benchmark any claimed performance improvement. Avoid caching financial balances or availability in ways that allow stale writes.

## 3. Bangladesh localization

- English and Bengali UI, public content, validation, notifications, and printed documents; persistent language preference and translation-ready strings.
- BDT currency, configurable display numerals, Asia/Dhaka default timezone, configurable per business, UTC timestamps internally, and timezone-aware scheduling.
- Normalize Bangladeshi mobile numbers to a consistent international format while accepting common local input. Do not treat phone number alone as proof of identity.
- Addresses with division, district, upazila/thana, area, postcode, and delivery instructions. Use a documented, licensed data source or clearly labeled sample data.
- Configurable holidays, weekly closures, prayer/break times, and special opening hours.
- Bengali fonts that correctly shape text in browser and PDF, plus A4 invoices and 58/80mm thermal receipts with print styles.
- Configurable tax/VAT, service charge, and invoice numbering. Verify legal assumptions against current authoritative sources; do not hardcode an unverified rate or claim regulatory certification.
- Payment architecture for cash, bank transfer, bKash, Nagad, Rocket, and a suitable online gateway such as SSLCOMMERZ, depending on actual provider documentation and merchant availability. Show exact supported integrations and setup requirements.

## 4. Businesses, branches, security, and permissions

- Business onboarding, business profile, branding, slug, contact details, operating model, defaults, and first branch.
- Branch address, opening hours, managers, staff assignments, service availability/prices, stock locations, cash registers, appointment capacity, and receipt settings.
- Branch switcher with authorized scope; consolidated owner reporting and branch comparison.
- Roles: platform administrator, business owner, business administrator, branch manager, receptionist, cashier, barber/stylist/beautician, inventory officer, accountant, HR/payroll manager, and customer. Support custom roles and granular permissions.
- Staff may work in multiple branches with effective assignments and schedules; prevent contradictory bookings.
- Enforce business and branch authorization in database queries, policies, APIs, route binding, Livewire actions, exports, files, queued jobs, and notifications. Never trust IDs from the browser. Customers see only their own records.
- Platform support access must be explicit, limited, and audited. No silent unrestricted impersonation.
- Audit sensitive actions: settings, role/module changes, price overrides, discounts, voids, refunds, stock changes, payroll, and exports.

## 5. Configurable modules

Provide a real module registry and business settings UI with dependencies, defaults, permission requirements, and feature checks. Support business-level toggles and branch overrides where meaningful; a branch cannot override a business-level prohibition.

Modules: public website, services, appointments, walk-in queue, POS, product catalog, ecommerce, inventory, purchasing, suppliers, CRM, loyalty, memberships/packages, gift cards, expenses, accounting, HR, attendance, payroll, commissions/tips, marketing, reviews, reports, AI tools, home services, and platform subscriptions.

- Enforce enabled state on routes, actions, jobs, APIs, and UI; hiding navigation alone is insufficient.
- Preview dependencies and operational impact before changing module settings.
- Disable preserves historical data, financial records, exports, and already-created obligations. Define how existing bookings/orders are completed or refunded. Prevent new activity after disablement.
- Provide presets: basic barber, salon with appointments, beauty/bridal studio, retail salon, home-service business, and multi-branch chain.
- Keep operational module switches separate from SaaS plan entitlements and user permissions; all must pass.

## 6. Public website and customer portal

Create real responsive pages with seeded content and functional navigation:
- Home with business branding, services, prices, branch highlights, gallery, reviews, and booking/shop calls to action.
- About, team, individual staff profiles, service categories, service details, packages, memberships, product categories, product details, offers, gallery, FAQ, blog/news, contact, branch listing, and branch detail.
- Booking flow, cart, checkout, payment result, order tracking, customer registration/login, password reset, account settings, booking history, order history, invoices, loyalty, memberships, and support requests.
- Privacy, terms, cancellations/refunds, delivery, and cookie information where applicable; label editable policy templates for owner review.
- Public sitemap, metadata, canonical URLs, social previews, structured data where appropriate, accessible forms, useful empty/error states, and optimized images.
- Tenant-aware routing that cannot confuse identical product/service slugs from different businesses. Custom domains may be optional, but document DNS, verification, TLS, and tenant resolution before claiming support.
- Published content only; drafts and private business data must never appear publicly.
- Reviews must originate from eligible completed transactions or an explicitly moderated process. Demo reviews must be labeled as sample content.

## 7. Services, appointments, and walk-ins

- Service categories, duration, cleanup/buffer time, base and branch prices, add-ons, staff eligibility, required chairs/rooms/equipment, gender-neutral configurable suitability labels, and optional deposit.
- Single and multi-service appointments, staff preference or automatic assignment, branch selection, availability calendar, booking limits, lead time, blackout dates, shifts, breaks, leave, and resource capacity.
- Atomic slot reservation with expiration and final revalidation; prevent overlapping staff/resource assignments under concurrent requests. Define no-preference assignment behavior and multi-service sequencing.
- Lifecycle: requested, awaiting deposit, confirmed, checked in, in service, completed, cancelled, and no-show. Enforce allowed transitions and audit reasons.
- Rescheduling, cancellation deadlines, deposit application/refund, reminders, customer notes, and appointment history.
- Walk-in tickets, estimated waiting time, priority with reason, staff assignment, queue display with masked names/tokens, and conversion to service/POS sale.
- Staff calendar/day view and reception board; completed service must link to a sale without duplicate billing.
- Home services: service zones, travel fees/time, address, assignment, safety/contact notes, and appointment capacity including travel buffers.

## 8. POS and financial transaction integrity

- Fast barcode/search POS combining services, products, packages, discounts, deposits, memberships, gift cards, and loyalty redemption.
- Walk-in or known customer, staff assignment per service, tax and service charges, tips, split tender, partial payments, due balances, and receipts.
- Register opening float, cash in/out with reasons, shift close, expected versus counted cash, variance, and manager approval where configured.
- Draft/held sale, completed sale, void before completion, partial/full return and refund, original transaction reference, reasons, and approval limits.
- Discounts by amount/percent with caps and permissions; server-side total calculation and documented stacking/rounding rules.
- Store money using integer minor units or exact decimals, never binary floating point. Preserve price/tax snapshots on finalized lines.
- Transactional posting: sale, payments, stock consumption, package redemption, loyalty, accounting, and commissions must remain consistent. Use idempotency keys, row locks/constraints where needed, and an outbox/retry strategy for external side effects.
- Refunds cannot exceed captured/refundable amounts or quantities. Refund reversals must correctly adjust stock eligibility, loyalty, commissions, package use, and accounting.
- Distinguish order status, payment status, fulfillment status, and refund status. A redirect or customer-entered transaction ID does not prove payment.

## 9. Products, inventory, purchasing, and ecommerce

- Products, variants/SKUs, barcodes, brands, categories, images, purchase cost, retail price, unit, reorder threshold, supplier, optional batch/expiry, and publication state.
- Per-branch stock ledger: opening balances, goods received, sales, service consumption recipes, returns, wastage/damage, adjustments, counts, transfers, and reservations.
- Separate retail inventory from salon consumables. Deduct consumables from completed services using configurable recipes and unit conversion.
- Purchase orders, partial receipt, supplier bills, payments, returns, and supplier balances. A purchase order itself must not increase stock.
- Inter-branch transfers: requested, approved, dispatched, partially received/received, discrepancy, cancelled; represent goods in transit and preserve auditability.
- Consistent stock costing method, stock valuation, low-stock and expiry alerts; disallow negative stock by default with a controlled override if configured.
- Storefront filters, variants, availability, cart, checkout, guest/account orders, pickup, local delivery, branch fulfillment, delivery zone/rate, coupons, and inventory reservation expiry.
- Operational order board: pending, awaiting payment, confirmed, processing, ready for pickup/dispatched, delivered/collected, cancelled, returned. Track partial fulfillment when supported.
- Handle failed/abandoned payment, reservation release, customer cancellation, returns, refunds, and duplicate webhook delivery.
- Prevent overselling across simultaneous POS and online checkout. Stock must not be deducted twice when reserved orders are fulfilled.

## 10. CRM, retention, packages, and marketing

- Customer profiles, visit history, purchases, preferences, consent, service notes, birthday/anniversary with optional fields, segmentation, and outstanding balance.
- Restrict sensitive skin/allergy or treatment notes to authorized staff, collect only necessary data, and keep consent/access history.
- Loyalty earn/redeem rules, expiry, adjustment ledger, reversal on refunds, and fraud limits.
- Prepaid service packages with validity, remaining uses, branch/staff restrictions, redemption ledger, and refund policy.
- Memberships with benefits, renewal/expiry, pause/cancel rules, and payment history. Do not imply automatic recurring billing unless a supported gateway implements it.
- Gift cards with secure codes, liability balance, expiry policy, partial redemption, and issuance/redemption audit.
- Coupons, offers, referral rewards, consent-based email/SMS campaigns, templates in Bengali/English, opt-out handling, delivery logs, and spending/rate limits.
- Support requests and complaint resolution with assignment and history.

## 11. Employees, attendance, payroll, commissions

- Employee profiles, role/branch assignments, skills, shifts, leave, attendance corrections, overtime rules, advances, salary configuration, and staff portal.
- Attendance Hub adapter for documented devices/protocols; device-to-business/branch mapping, employee mapping, connection health, push/pull synchronization, raw event preservation, duplicate event protection, and timezone handling.
- Real local simulator/manual attendance workflow when hardware is absent. Label device simulation clearly. Do not claim every machine is supported or scan networks automatically.
- Protect device credentials and biometric data; avoid collecting/storing biometric templates unless necessary and explicitly enabled.
- Commission rules by service/product/staff/branch with effective dates, tiers if configured, shared service allocation, tips distribution, refunds, and settlement history. Snapshot the applicable rule.
- Payroll draft, review, approval, payment, payslip, lock, and correction workflow; include salary, commissions, tips, allowances, overtime, deductions, leave effects, and advances without double counting.
- Attendance corrections after payroll lock require an adjustment workflow. Payroll access must be separate from general staff access.

## 12. Expenses, accounting, and reporting

- Expense categories, recurring expense templates, bills, attachments, approvals, cash/bank accounts, receivables/payables, and reconciliation.
- Implement a balanced double-entry ledger with documented chart of accounts and posting rules for sales, tax, deposits, refunds, purchasing, expenses, payroll, gift cards, and prepaid packages. Separate liabilities from earned revenue.
- Posted entries are immutable; corrections use reversals/adjustments. Opening balances must balance. Do not present cash receipts as profit.
- Reports with date, branch, staff, service/product, payment method, and status filters: sales, collections, refunds, dues, tax summary, profit and loss, cash flow, trial balance, balance sheet, inventory valuation/movement, purchases, expenses, bookings, no-shows, utilization, queue, staff performance, commissions, payroll, customer retention, loyalty, and online orders.
- Define every metric and status inclusion rule. Reports must reconcile with ledgers and show branch-local date boundaries.
- Permission-scoped CSV/Excel/PDF exports, protected generated files, queued large exports, and export audit logs.

## 13. Optional AI and external services

- Use AI Hub through application-owned interfaces for optional draft copy, Bengali/English translation, product description generation, and reviewable business insights.
- AI Catalog may import permitted source content into drafts with source URL/provenance, duplicate detection, field validation, image rights notes, and human approval before publication.
- Protect URL imports against SSRF, internal/link-local addresses, redirects to private networks, oversized files, malicious content, and timeouts. Respect source terms and access restrictions.
- Treat scraped text as untrusted data, never agent instructions. AI must not change prices, stock, payments, payroll, permissions, or publish content without an explicit authorized workflow.
- Per-business credentials/configuration, encryption, budgets, rate limits, usage/cost logs, retries, safe failure states, and no unnecessary customer/staff personal data in AI requests.
- Provide fakes/sandbox adapters for AI, SMS, email, payments, and attendance so demo workflows run without secrets, paid accounts, or devices. Keep fake adapters impossible to select accidentally in production.
- For actual gateways, verify server-side payment status/signatures according to provider documentation, validate merchant/business, amount/currency, and replay/idempotency controls. Document merchant onboarding and credential requirements. Manual transfer verification must be visibly manual and audited.

## 14. Platform administration and subscription operations

- Platform admin manages businesses, plans, entitlements, trials, subscriptions, invoices, renewal, cancellation, grace periods, suspension, and usage limits.
- Provide manual subscription billing as a complete baseline; automated charging only when genuinely supported and tested.
- Suspension must preserve records and expose the defined recovery/export path. Define business access and public ordering behavior during grace/suspension.
- Platform analytics must respect business privacy; privileged access must be audited.

## 15. Engineering and operational quality

- Policies, CSRF protection, secure sessions, login throttling, password reset, verified identity where needed, optional owner/admin MFA, and safe upload validation/storage.
- Index tenant/branch/date/status query paths, paginate lists, avoid N+1 queries, and measure representative dashboard and checkout queries.
- Validate all input on the server, use explicit mass-assignment rules, and prevent cross-business foreign-key associations.
- Queue retries with backoff, failed-job visibility, idempotent scheduled jobs, reminder deduplication, structured logs, health endpoints, and protected operational tooling.
- Accessible keyboard navigation, labels, focus states, contrast, touch-friendly POS, responsive public pages and administration, loading/empty/error states, and destructive-action confirmations.
- Do not claim offline POS unless you implement and test synchronization, conflict resolution, and financial reconciliation. A responsive/PWA shell alone is not offline support.
- Document backup/restore, uploaded files, database recovery, retention, customer export/deletion subject to retained financial records, migrations, worker supervision, scheduler, TLS, secrets, and production hardening.
- Never commit secrets or actual customer data. Protect logs, exports, and diagnostic pages.

## 16. Demo environment

Create a documented demo seeder and reset command guarded against production use. Seed realistic related data with deterministic identifiers and dates relative to the seed date:
- Business A: Dhaka Grooming & Salon, branches in Dhanmondi, Mirpur, and Uttara, with most modules enabled.
- Business B: Chattogram Classic Barber, one branch with basic services/POS, to prove isolation and module disabling.
- Business C: fictional bridal/beauty studio with packages, resource-based bookings, and home services.
- Bengali and English names/content, staff shifts, services, stock, suppliers, customers, upcoming/past bookings, walk-in queue, paid/pending/partial orders, purchases, transfers, expenses, loyalty, gift cards, attendance, and draft/paid payroll.
- Financial seed records must use the same domain posting services as normal operations and reconcile; do not insert contradictory totals for screenshots.

Demo accounts:
| Role | Email |
| --- | --- |
| Platform administrator | platform@example.test |
| Multi-branch owner | owner@example.test |
| Branch manager | manager@example.test |
| Receptionist | reception@example.test |
| Cashier | cashier@example.test |
| Stylist | stylist@example.test |
| Inventory officer | inventory@example.test |
| Accountant | accounts@example.test |
| HR/payroll manager | hr@example.test |
| Customer | customer@example.test |
| Second business owner | owner2@example.test |

Use a clearly documented local-demo-only password, hashed normally, configurable with DEMO_PASSWORD. Do not create demo accounts by default in production. Display an obvious demo badge and simulated provider indicators. Ensure all role accounts actually have useful scoped data and working login paths.

Provide a demo walkthrough: customer books a service, receptionist checks in, stylist completes, cashier sells service plus product, records split payment, prints Bengali receipt, owner reviews reconciled reports; then place and fulfill an online order, refund a line, transfer stock, and process an attendance-to-payroll example.

## 17. Documentation deliverables

Write a comprehensive README.md as the product and developer entry point:
- Product purpose, business types, screenshots of the running application, and public/admin/customer URLs.
- Every implemented module and business workflow, plus presets and how owners enable/disable modules.
- Exact requirements, install/build commands, database setup, .env.example, migrations, demo seed/reset, worker/scheduler, tests, and production deployment.
- Demo credentials and walkthrough; distinguish simulated versus real providers.
- Package integration table with versions, actual usage, configuration, compatibility decisions, and fallback status.
- Roles/permissions, multi-business and multi-branch behavior, localization, payments, taxes, imports/exports, backup/restore, and troubleshooting.
- Explicit implementation status: complete, partial, external setup required, or not implemented. No aspirational feature described as complete.

Also create docs/architecture.md, docs/data-model.md (Mermaid ER diagram), docs/modules.md, docs/permissions.md, docs/workflows.md, docs/integrations.md, docs/deployment.md, docs/demo-walkthrough.md, and docs/acceptance.md. Include an operations runbook and meaningful API/webhook documentation for any exposed endpoints.

## 18. Tests and acceptance gates

Write meaningful unit, feature, integration, and browser tests. Verify, at minimum:
1. Fresh install, migrations, build, demo seed, role logins, public pages, and main navigation.
2. Business/branch isolation including forged IDs, exports, attachments, background jobs, and role restrictions.
3. Module disable blocks direct requests and new jobs while preserving history and defined existing obligations.
4. Concurrent bookings cannot double-assign staff/resources; cancelled/expired holds release capacity.
5. Concurrent POS/online purchases cannot oversell; expired reservations release stock exactly once.
6. POS totals, rounding, deposits, split/partial payment, register close, refunds, due balances, and printed receipts.
7. Duplicate/reordered payment callbacks do not duplicate payment or fulfillment; invalid callbacks cannot mark an order paid.
8. Partial receiving/transfers/returns, consumable deduction, stock counts, and valuation reconcile with stock ledgers.
9. Package, gift-card, loyalty, commission, and payroll calculations and reversal behavior.
10. Ledger balances, tax/deposit handling, report reconciliation, and prevention of double posting.
11. Attendance deduplication, timezone/overnight shifts, correction approvals, and payroll locking.
12. Bengali browser and rendered PDF text, thermal print layouts, English/Bengali toggles, mobile layouts, and keyboard flows.
13. Integration failure/retry behavior, import SSRF protection, upload validation, and demo/production separation.
14. SaaS trial/grace/suspension and plan entitlement behavior.

Run formatting/static analysis appropriate to the selected stack, automated tests, frontend production build, and browser smoke tests. Inspect the running app at desktop and mobile widths. Save actual screenshots and test evidence. Do not mark browser/PDF checks passed without performing them. If a service cannot be tested without credentials, test its adapter contract with fakes and explicitly record the live verification requirement.

## 19. Delivery standard

A feature is complete only when its migrations, domain behavior, authorization, UI, validation, empty/error states, audit effects, tests, demo data, and documentation are present as applicable. No placeholder buttons, disconnected forms, fabricated metrics, or TODO-based business logic in a feature marked complete.

At completion provide:
- What was implemented and where to access it.
- Exact setup/start/test commands and demo accounts.
- Tests/checks actually run and their results.
- Screenshots/artifact paths.
- Any remaining limitations or external merchant/device/AI configuration.

Start by inspecting the repository and validating the dependency matrix. Then implement the complete application in coherent, testable increments. Keep working until the acceptance checklist is satisfied, or record the specific blockers and continuation work honestly.
