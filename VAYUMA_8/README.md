# VAYUMA

VAYUMA is a PHP and MySQL agribusiness platform that connects farmers, buyers, cooperatives, agricultural service providers, and other stakeholders. It brings together produce listings and orders, stakeholder profiles, subscriptions, payment workflows, and agricultural advice.

## Features

- Role-based registration, stakeholder profiles, and dashboards
- Produce marketplace, orders, and delivery workflows
- Historical M-Pesa, Paystack, and bank-transfer integrations (payment processing is currently locked)
- Subscription management and administration
- Climate, crop, disease, and farming-advice tools
- SMS and USSD integrations

## Technology

- PHP
- MySQL
- HTML, CSS, and JavaScript
- Optional integrations: Safaricom Daraja, Paystack, and Africa's Talking

## Run locally

The application source is in the [`vayuma/`](./vayuma/) directory.

1. Install a local PHP/MySQL stack such as XAMPP.
2. Copy the `vayuma` directory into XAMPP's `htdocs` directory.
3. Start Apache and MySQL.
4. Import [`vayuma/database/fresh_start.sql`](./vayuma/database/fresh_start.sql) using phpMyAdmin. It creates a local starter database; it does not restore production data.
5. Configure the database connection in `vayuma/config/db.php` for your local environment.
6. Apply any additional database migrations required by the features you want to test. See [`VAYUMA_MASTER_INSTALL.md`](./vayuma/VAYUMA_MASTER_INSTALL.md) and the integration guides below.
7. Open `http://localhost/vayuma/` in your browser.

Historical integration guides are listed below. Payment processing is currently hard locked; do not use live callbacks or credentials. Messaging integrations require separate configuration and security review:

- [M-Pesa setup](./vayuma/MPESA_SETUP.md)
- [Paystack setup](./vayuma/PAYSTACK_SETUP.md)
- [SMS and USSD setup](./vayuma/SMS_USSD_SETUP.md)

## Install as a Progressive Web App

VAYUMA remains a PHP/MySQL website. When served over HTTPS (or localhost for development), open `welcome.php` in a supported browser and use **Install app** / **Add to Home screen** from the browser menu. On iPhone or iPad, use Safari’s Share menu and choose **Add to Home Screen**. Browser install prompts and availability vary by device. The manifest uses relative paths, so the app can also be hosted in a subdirectory; ensure Apache serves `.webmanifest` as `application/manifest+json` and serves `sw.js` without long-lived caching.

An initial online visit is required. The service worker provides a simple offline message and can reuse an explicit set of public static assets. Account pages, API responses, payment data, uploads and POST requests are never stored for offline use or queued; signing in, viewing live data and making changes or payments require a connection. Offline mode is not a substitute for server backups or payment reconciliation.

## Staging deployment planning

The Render Blueprint is for staging only. Use an isolated TiDB database and private environment credentials; verify TLS, schema compatibility, backups and restore before use. The SQL files in `staging/sql/` require review and must not be applied blindly. `/health.php` reports liveness only; `/health.php?check=db` performs an on-demand connection and query check, returning only a generic status. Keep Render's health-check path at `/health.php`: the database check is not a schema, authorization or transaction test, and a public probe can generate database traffic.

For temporary debugging only, set `DB_DIAGNOSTICS=1` with `APP_ENV=staging` and trigger the database readiness check. On failure, private server logs report whether DB variables are set, whether the selected CA is readable, and the mysqli error code, SQLSTATE and message when the driver provides them. Configured connection values are redacted, but driver messages may still contain sensitive information: restrict log access and inspect before sharing. No details are returned to the browser. Remove `DB_DIAGNOSTICS` after debugging; this option is ignored outside staging. `DB_SSL_CA` is optional and defaults to the container's system CA bundle; a custom value must be a readable runtime file.

Payment initiation and settlement are hard locked and cannot be enabled with environment variables. Do not point live callbacks here or use real credentials. Safe activation requires authenticated provider reconciliation, idempotent settlement, handling ambiguous transactions, sandbox tests and independent security review. Historical payment setup documents do not describe the current lock.

Avatar uploads remain disabled unless durable storage is explicitly configured and verified. An opt-in TiDB backend for compressed profile thumbnails is prepared: review and manually apply `staging/sql/09_avatar_storage_REVIEW.sql`, then set `VAYUMA_AVATAR_STORAGE=tidb`. Uploads are limited to 1 MB before processing and stored as cropped JPEGs of at most 80 KiB; access is restricted to the signed-in owner. This backend does not migrate existing filesystem photos. Check TiDB capacity, quotas, backups, and restore before use. It has not been tested against hosted TiDB. Local Render files and default sessions are ephemeral; durable sessions and durable storage for produce images are not implemented. Before enabling profile edits or uploads, audit existing identities, review the manual schema migrations and verify storage, permissions and restart recovery. The MariaDB identity reference in `staging/sql/05_identity_unique_MANUAL.sql` is not TiDB-compatible and must not be applied to TiDB; a TiDB-specific migration and integration test are still required. Test real TiDB login and all roles, Android/iOS installation and narrow-screen layouts before deployment.

## Protect private data

Do not commit API keys, passwords, `.env` files, local config overrides, uploaded user files, or backups. The `.gitignore` excludes common local files, but it does not remove files that are already tracked or erase them from Git history. Check the Git history and current changes before publishing this project.

## License

No license is currently specified. Choose and add a license before presenting the project as open source.
