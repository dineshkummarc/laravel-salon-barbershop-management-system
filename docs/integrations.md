# Integrations

Use `composer.lock` as the authoritative installed dependency list. Verified 23 October 2026: Laravel 12.70.1, Filament 3.3.57, AI Hub 1.6.0. The six other requested packages had only development releases available and are locked by revision:

| Package | Locked revision / version |
| --- | --- |
| drutogoti | 6c5b4c9 |
| laravel-admin-panel | 6730519 |
| laravel-ai-catalog | 07b5783 |
| laravel-ai-hub | v1.6.0 |
| laravel-attendance-hub | 7bd5a7b |
| laravel-unicode-pdf | bc0288d |
| laravel-universal-slug | 9fdcf16 |

Package licenses were inspected as MIT. Re-check upstream licensing and advisories when upgrading. No global minimum-stability downgrade was made.

## Registration strategy

- Filament remains the one business admin/auth UI.
- Admin Panel's palette definitions are reused; its separate provider/routes are excluded.
- AI Catalog's data classes and hardened URL reader are used through the app's import page; its standalone API/Studio is excluded.
- Attendance Hub manager/DTOs/drivers are called by the app-owned tenant-scoped adapter. Package discovery/network scanning/push routes are not registered.
- AI Hub is registered, but its installation-wide settings UI is disabled in config.
- Unicode PDF and Unicode Slug are registered and used.
- Drutogoti provider is explicitly registered; capture defaults to disabled. Its schema/config can be published for controlled profiling. No speedup was benchmarked or claimed.

## Provider configuration

AI Hub: `SALON_AI_LIVE`, `AI_HUB_PROVIDER`, a valid provider model, and provider credential such as `OPENAI_API_KEY`. Demo generation uses the package fake and labels output. No live provider call was made during validation.

Catalog: `SALON_IMPORT_HOSTS` contains exact permitted public HTTPS hosts. URLs are DNS-validated and pinned, with redirects blocked and HTML response size/time limits. Import saves an inactive product. Verify copy, product facts, claims, and image rights manually.

Attendance: `SALON_ATTENDANCE_HOSTS` contains exact approved device hosts. Configure provider, port, encrypted credentials, and external-user/employee mappings. Fake devices require demo mode. Real machines and firmware were not tested; the simulator/deduplication workflow was tested.

Mail: `MAIL_MAILER=log` for local demo. Configure your real Laravel transport, from address, DNS authentication, and queue supervision before sending real campaigns. No SMS/WhatsApp integration is implemented.

Payments: cash and manually verified transfer records. There are no live gateway callbacks or automated external refunds; do not mistake a stored transfer reference for a provider confirmation.

PDF: local Noto Bengali fonts are configured. A real PDF was rendered and inspected. The native engine is used; arbitrary-language layout beyond the current Bengali/English invoice needs separate verification.
