# VAYUMA USSD and SMS setup

## Apply the database migration

In phpMyAdmin, select vayuma_db, open the SQL tab, and run database/migrations/ussd_sms_system.sql.
This creates SMS delivery logs, subscriptions, and USSD audit logs. It does not change existing users, orders, or produce listings.

## Add Africa's Talking credentials

Copy config/africas_talking.local.example.php to config/africas_talking.local.php. Set your Africa's Talking username and API key there. Use sandbox until testing succeeds, then change environment and base_url to production.

Never upload africas_talking.local.php or share its API key.

## Configure Africa's Talking callbacks

The URLs must be public HTTPS URLs; localhost cannot receive callbacks.

- USSD callback: https://YOUR-DOMAIN/ussd.php
- Incoming SMS callback: https://YOUR-DOMAIN/sms_inbound.php?token=YOUR_INBOUND_CALLBACK_TOKEN
- SMS delivery callback: https://YOUR-DOMAIN/sms_callback.php

For local testing, publish the XAMPP site through a secure tunnel such as ngrok, then use its HTTPS URL temporarily.

## Test

1. Start Apache and MySQL in XAMPP.
2. Run the migration.
3. Use Africa's Talking sandbox simulator to call the USSD callback.
4. Test Crop Advice, including More crops and Send Advice by SMS.
5. Send HELP, CROP, MARKET, and STOP to the Africa's Talking SMS number.
6. Check sms_messages and ussd_requests in phpMyAdmin.

## Included service flows

- Weather by region and county using Open-Meteo
- Crop advice, weather suitability, crop information, and SMS delivery
- Correct second-page crop selection
- Available produce and SMS market-price alert subscriptions
- Disease-help and crop-calendar guidance
- Phone-linked produce and order summaries
- Incoming SMS commands and opt-out
- Africa's Talking delivery callback logging
