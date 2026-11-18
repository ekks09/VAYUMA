# VAYUMA Master Project — Sandbox Ready

This is the consolidated VAYUMA build through the M-Pesa Daraja Sandbox stage.

## Included
- 19 VAYUMA account roles with protected routing, including Strategic Partner.
- Monthly subscriptions with database-managed stakeholder pricing, M-Pesa checkout, receipts and access gating.
- Dedicated onboarding/profile pages for the 10 stakeholder roles that previously shared a generic page.
- Dedicated stakeholder dashboards for those 10 roles.
- Stakeholder directory, offers and request/connection workflow.
- Farmer produce marketplace integration remains in place.
- Marketplace buyer eligibility expanded for relevant business roles.
- Delivery request workflow foundation for customers and transport providers.
- Verification/admin foundation.
- M-Pesa payment schema aligned with the callback code, including receipt and callback timestamps.
- Existing Daraja STK Push flow retained.

## Database setup order
Run these in phpMyAdmin against `vayuma_db` as needed:
1. `database/migrations/stakeholder_profiles.sql`
2. `database/migrations/orders_marketplace.sql` (only if marketplace columns are not already present)
3. `database/migrations/stakeholder_offers.sql`
4. `database/migrations/delivery_workflow.sql`
5. `database/migrations/mpesa_payments.sql`
6. `database/migrations/subscriptions.sql`

The M-Pesa migration is designed to add the missing callback fields to an existing `payments` table rather than replacing your existing payment records.

## M-Pesa Sandbox
- `config/mpesa.php` supports environment variables and local sandbox configuration.
- Never publish consumer secret or passkey values.
- Daraja must be able to reach `mpesa_callback.php` through a public HTTPS callback URL.
- Localhost alone cannot receive the Safaricom callback.
- The browser does not mark an order as paid; the callback does.

## Admin verification
Set `VAYUMA_ADMIN_USER_IDS` in the PHP/Apache environment to the numeric user IDs that should access `admin.php`, for example `1,7`.

## First run
1. Extract this project into your XAMPP `htdocs` folder.
2. Confirm Apache and MySQL are running.
3. Confirm `config/db.php` points to `vayuma_db`.
4. Apply the required migrations above.
5. Open `index.php`.
6. Test registration → role selection → stakeholder profile → dashboard.
7. Test marketplace → order → accept → payment.
8. When ready for M-Pesa, configure a public HTTPS callback URL and test Daraja Sandbox.
