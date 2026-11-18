# VAYUMA Paystack Subscription Setup

## Overview

VAYUMA monthly subscriptions now support two payment methods:
- **M-Pesa** (STK Push via Safaricom Daraja) — existing
- **Paystack** (card-based recurring subscription) — new

Prices are managed in **Admin > Subscription & Pricing** (`admin_subscriptions.php`), not hardcoded.

## Database migration

Run once in phpMyAdmin against `vayuma_db`, in order:

```sql
database/migrations/paystack_subscriptions.sql
database/migrations/paystack_provider_separation.sql
```

`paystack_subscriptions.sql` safely adds:
- `subscription_plans.paystack_plan_code`, `billing_interval`, `currency`
- `user_subscriptions.paystack_customer_code`, `paystack_subscription_code`, `next_billing_date`, `grace_period_ends_at`, `auto_renew`, `billing_interval`
- `subscription_payments.payment_method` (ENUM mpesa/paystack), `paystack_transaction_id`, `paystack_customer_code`, `paystack_subscription_code`, `channel`, `raw_payload`
- `payment_events` table (webhook audit + idempotency)
- `subscription_status_history` table (audit trail)

`paystack_provider_separation.sql` adds:
- `user_subscriptions.payment_provider` (ENUM 'mpesa','paystack') — records which provider owns the active subscription
- Index on `payment_provider`

All `ALTER TABLE` statements use `INFORMATION_SCHEMA` guards, so re-running is safe.

### Provider separation rule (documented)

A provider's webhook may update `user_subscriptions` only when:
- `payment_provider IS NULL` — first-ever activation
- `payment_provider = 'this provider'` — same provider renewing
- `status = 'expired'` — expired subscriptions can switch providers

When activating, the provider sets `payment_provider = 'itself'`. On Paystack `subscription.disable`, `payment_provider` is released to `NULL`.

This prevents M-Pesa from overwriting an active Paystack subscription and vice versa.

## Paystack configuration

Edit `config/paystack.php` or set environment variables:

| Variable | Description | Example |
|---|---|---|
| `PAYSTACK_SECRET_KEY` | Paystack secret key (test or live) | `sk_test_xxx` |
| `PAYSTACK_PUBLIC_KEY` | Paystack public key | `pk_test_xxx` |
| `PAYSTACK_ENV` | `test` or `live` | `test` |
| `PAYSTACK_CURRENCY` | Settlement currency | `KES` |

Never commit live secret keys. Use `YOUR_` placeholders in source control.

## Paystack dashboard setup

1. Create a **Plan** in Paystack for each paid stakeholder type (e.g. Farmer monthly KSh 299).
2. Copy each plan's `PLN_xxx` code.
3. In **Admin > Subscription & Pricing**, paste the plan code into the matching stakeholder row.
4. Register the webhook URL in Paystack dashboard:
   ```
   https://YOUR_DOMAIN/paystack_subscription_webhook.php
   ```
   Must be public HTTPS. Use ngrok or Cloudflare Tunnel for local testing — localhost cannot receive Paystack webhooks.

## User flow

1. User goes to **Choose Stakeholder Type** (`choose_stakeholder.php`)
2. Selects a stakeholder type (Farmer, Consumer, Buyer, etc.)
3. System displays the applicable subscription from the admin pricing table
4. User pays via **M-Pesa** or **Paystack card**
5. Webhook confirms payment → membership set to `active` → expiry date set
6. User sees receipt with reference number and expiry date

## Payment statuses

| Status | Meaning |
|---|---|
| `active` | Valid subscription, access granted |
| `payment_due` | Payment required or renewal failed |
| `expired` | No valid payment, access revoked |
| `free_account` | Free stakeholder type, no payment needed |
| `negotiated_plan` | Custom/admin-controlled arrangement |

## Webhook events handled

- `charge.success` — first payment succeeded → activate membership
- `invoice.payment_success` — renewal succeeded → extend expiry
- `invoice.payment_failed` — renewal failed → `payment_due` + 7-day grace
- `subscription.create` — store subscription code
- `subscription.disable` → `expired`
- `subscription.not_renew` → `payment_due`, auto-renew off

Events are signature-verified (`hash_hmac` sha512 vs `x-paystack-signature`) and deduplicated via `payment_events`.

## Files

| File | Purpose |
|---|---|
| `choose_stakeholder.php` | Step 1: pick stakeholder type, view pricing from DB |
| `subscription.php` | Step 2-6: show plan, pay, status, expiry, receipt |
| `subscription_stkpush.php` | M-Pesa STK push initiation |
| `subscription_pending.php` | M-Pesa pending check page |
| `subscription_receipt.php` | Receipt after confirmed payment |
| `paystack_subscribe.php` | Paystack: create customer → subscription → redirect |
| `paystack_subscription_webhook.php` | Paystack webhook: signature verify, dedupe, status sync |
| `lib/paystack.php` | Paystack API client (initialize, verify) |
| `lib/paystack_subscription.php` | Paystack subscription helpers |
| `config/paystack.php` | Paystack config (env-var-backed keys) |
| `admin_subscriptions.php` | Admin pricing management |
| `lib/subscription_lib.php` | Subscription business logic |

## Testing

1. Run the migration.
2. Set Paystack test keys in `config/paystack.php`.
3. Create Paystack plans in test mode; paste plan codes into `admin_subscriptions.php`.
4. Register test webhook URL.
5. Subscribe as Farmer with a Paystack test card.
6. Confirm webhook fires `charge.success` → membership → `active`.
7. Test `invoice.payment_failed` → `payment_due`.
8. Switch to live keys only after test webhooks confirm end-to-end.

PHP is not installed on this agent machine — run `php -l` on every changed file before deploying.
