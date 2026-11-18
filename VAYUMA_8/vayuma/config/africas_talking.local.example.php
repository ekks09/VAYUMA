<?php

/* Copy this file to africas_talking.local.php and add real credentials. */
return [
    'environment' => 'sandbox', // Change to production only after approval.
    'username' => 'sandbox',
    'api_key' => 'PASTE_YOUR_AFRICAS_TALKING_API_KEY_HERE',
    'from' => '', // Optional: approved Sender ID for production.
    'delivery_callback_url' => 'https://your-domain.example/sms_callback.php',
    'inbound_callback_token' => 'CHANGE_TO_A_LONG_RANDOM_SECRET',
    'base_url' => 'https://api.sandbox.africastalking.com',
];
