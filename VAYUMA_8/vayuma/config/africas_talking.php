<?php

/*
 * Africa's Talking settings.
 *
 * Keep API keys out of this file. Add them as Apache/XAMPP environment
 * variables (AFRICASTALKING_USERNAME and AFRICASTALKING_API_KEY), or create
 * config/africas_talking.local.php from the example below. The local file is
 * intentionally ignored by source control and must never be shared.
 */

$localConfig = __DIR__ . '/africas_talking.local.php';
if (is_file($localConfig)) {
    $local = require $localConfig;
    if (is_array($local)) {
        return $local;
    }
}

$environment = getenv('AFRICASTALKING_ENVIRONMENT') ?: 'sandbox';

return [
    'environment' => $environment,
    'username' => getenv('AFRICASTALKING_USERNAME') ?: '',
    'api_key' => getenv('AFRICASTALKING_API_KEY') ?: '',
    'from' => getenv('AFRICASTALKING_SENDER_ID') ?: '',
    'delivery_callback_url' => getenv('AFRICASTALKING_DELIVERY_CALLBACK_URL') ?: '',
    'inbound_callback_token' => getenv('AFRICASTALKING_INBOUND_CALLBACK_TOKEN') ?: '',
    'base_url' => $environment === 'sandbox'
        ? 'https://api.sandbox.africastalking.com'
        : 'https://api.africastalking.com',
];
