# Deployment

## Prerequisites

PHP 8.3+, compatible extensions (`composer check-platform-reqs`), Composer, Node 22.12+, a dedicated MySQL/MariaDB database, HTTPS web server, queue supervisor, and scheduler. SQLite is suitable for the local demo; use the documented relational server engine for a business rollout.

## Install

1. Check out the application and configure `.env` from `.env.example`.
2. Set `APP_ENV=production`, `APP_DEBUG=false`, `SALON_DEMO=false`, a real `APP_URL`, secure sessions, database credentials, and your mail transport.
3. Run `composer install --no-dev --optimize-autoloader` and `npm ci && npm run build`.
4. Run `php artisan key:generate` for a new installation only. Never replace a live key without a rotation plan: encrypted credentials and sessions depend on it.
5. Run `php artisan migrate --force`. Do not run the demo seeder.
6. On the first empty installation, run `php artisan salon:install` to create a real business/owner.
7. Serve only the `public/` directory. Ensure `storage/` and `bootstrap/cache/` are writable by the application user.
8. Configure workers and cron, then run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`.
9. Configure services/content, policies, opening balances, role accounts, backups, and business-specific rules; pilot before live trading.

## Example Nginx location

```nginx
root /var/www/groomos/public;
index index.php;
location / { try_files $uri $uri/ /index.php?$query_string; }
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php-fpm.sock;
}
location ~ /\. { deny all; }
```

Adapt the PHP-FPM socket, TLS settings, upload limits, and host rules to your server. Do not expose `.env`, application source, logs, database dumps, or storage directories as public web roots.

## Processes

Queue: `php artisan queue:work --sleep=3 --tries=1 --timeout=90` under a supervisor. Restart workers after deploying code: `php artisan queue:restart`.

Cron: `* * * * * cd /var/www/groomos && php artisan schedule:run >> /dev/null 2>&1`.

Health endpoint: `/up`. It is a Laravel health check, not proof that every external provider or financial invariant is healthy. Add operator monitoring appropriate to your infrastructure.

## Upgrade

Back up database, files, and encrypted-key configuration. Restore-test the backup. Install locked dependencies, run migrations, rebuild assets, restart workers, clear/rebuild caches, run smoke checks and `salon:check`. Review development-release package changes before updating the lockfile.

The baseline has no public self-service platform-admin provisioning flow. The deployment operator must provision a trusted platform account if operating multiple businesses; never turn a public registration form into a platform-role creator.
