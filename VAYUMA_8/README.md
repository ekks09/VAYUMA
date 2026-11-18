# VAYUMA

VAYUMA is a PHP and MySQL agribusiness platform that connects farmers, buyers, cooperatives, agricultural service providers, and other stakeholders. It brings together produce listings and orders, stakeholder profiles, subscriptions, payment workflows, and agricultural advice.

## Features

- Role-based registration, stakeholder profiles, and dashboards
- Produce marketplace, orders, and delivery workflows
- M-Pesa, Paystack, and manual bank-transfer payment flows
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

Payment and messaging integrations need their own credentials and publicly reachable HTTPS callback URLs. Follow the relevant setup guide and use sandbox/test credentials first:

- [M-Pesa setup](./vayuma/MPESA_SETUP.md)
- [Paystack setup](./vayuma/PAYSTACK_SETUP.md)
- [SMS and USSD setup](./vayuma/SMS_USSD_SETUP.md)

## Protect private data

Do not commit API keys, passwords, `.env` files, local config overrides, uploaded user files, or backups. The `.gitignore` excludes common local files, but it does not remove files that are already tracked or erase them from Git history. Check the Git history and current changes before publishing this project.

## License

No license is currently specified. Choose and add a license before presenting the project as open source.
