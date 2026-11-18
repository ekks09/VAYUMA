# VAYUMA M-Pesa Daraja Setup

VAYUMA now uses Safaricom Daraja STK Push for real M-Pesa payments.

## 1. Database
Run `database/migrations/mpesa_payments.sql` once in phpMyAdmin against `vayuma_db`.

## 2. Daraja app
Create a Daraja app in the Safaricom Developer Portal and use the Sandbox environment first.

Do not put secrets in source control. The app reads these environment variables when available:

- `MPESA_ENV=sandbox` or `production`
- `MPESA_CONSUMER_KEY`
- `MPESA_CONSUMER_SECRET`
- `MPESA_SHORTCODE` — the Lipa na M-Pesa Online shortcode
- `MPESA_PARTY_B` — the PayBill that receives the payment (same as the shortcode for a normal PayBill)
- `MPESA_PASSKEY`
- `MPESA_TRANSACTION_TYPE=CustomerPayBillOnline`
- `MPESA_CALLBACK_URL=https://YOUR_PUBLIC_DOMAIN/mpesa_callback.php`

If XAMPP does not expose environment variables, copy `config/mpesa.local.example.php` to `config/mpesa.local.php` and fill the values there. Never upload real credentials to GitHub or share them in chat.

## 3. Callback URL
Safaricom must be able to reach `mpesa_callback.php`. `localhost` is not publicly reachable.

For local testing, expose the VAYUMA folder through a secure HTTPS tunnel (for example, a configured Cloudflare Tunnel or ngrok HTTPS tunnel) and set `MPESA_CALLBACK_URL` to the public HTTPS URL ending in `/mpesa_callback.php`.

## 4. Payment flow
Marketplace: `my_orders.php` -> `payment.php` (enter phone) -> `mpesa_stkpush.php` -> Daraja PayBill STK Push -> `mpesa_callback.php` -> `payments` + `orders.status='paid'`.

Subscriptions: `subscription.php` (enter phone) -> `subscription_stkpush.php` -> the same PayBill STK Push -> `mpesa_callback.php` -> `subscription_payments` + active membership.

The callback is authoritative. The waiting page also asks Daraja for the STK result if the callback is delayed. The browser never marks a payment successful just because Safaricom accepted the prompt request.

`transaction_type` stays `CustomerPayBillOnline`. `party_b` is the PayBill that receives the money. For a standard PayBill it matches `shortcode`.

## 5. Security behavior
- Order ownership is checked before initiating payment.
- Only accepted orders can be paid.
- A second pending STK request for the same order is blocked.
- Phone numbers are normalized to `2547XXXXXXXX` / `2541XXXXXXXX`.
- Callback amount is compared with the stored order amount.
- Duplicate callbacks are safe: an already-paid payment is not processed again.
- M-Pesa receipt number is stored as `transaction_reference`.

## 6. Bank transfer payments
VAYUMA also supports manual bank transfers alongside M-Pesa.

Run `database/migrations/bank_payments.sql` once in phpMyAdmin against `vayuma_db`.

Configure the receiving account in `config/bank.php` locally, or use these environment variables:

- `VAYUMA_BANK_NAME`
- `VAYUMA_BANK_ACCOUNT_NAME`
- `VAYUMA_BANK_ACCOUNT_NUMBER`
- `VAYUMA_BANK_BRANCH`
- `VAYUMA_BANK_SWIFT` (optional)
- `VAYUMA_BANK_REFERENCE_PREFIX`

The buyer selects **Bank Transfer**, sees the VAYUMA receiving account, enters the bank transaction/reference number and uploads a JPG, PNG, WEBP or PDF proof (maximum 5 MB). The payment stays `pending` until a designated VAYUMA administrator approves it from the Admin Verification Centre. Only approval changes the order to `paid`.

Bank transfer is intentionally manual at this stage. It does not require a bank API or online-banking credentials. A future phase can add direct bank/API integrations where a specific Kenyan bank and supported API are selected.
