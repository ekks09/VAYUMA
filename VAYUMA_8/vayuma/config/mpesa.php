<?php
/**
 * VAYUMA M-Pesa Daraja configuration.
 *
 * Lipa na M-Pesa Online (STK Push) against a PayBill.
 * TransactionType CustomerPayBillOnline sends a PIN prompt to the phone
 * the customer enters. PartyB is the PayBill number.
 *
 * Prefer environment variables. For XAMPP, copy config/mpesa.local.example.php
 * to config/mpesa.local.php and fill the sandbox or production values there.
 * Do not commit real consumer secrets or the production passkey.
 */
$localConfig = [];
$localConfigPath = __DIR__ . '/mpesa.local.php';
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (!is_array($loadedConfig)) {
        throw new RuntimeException('config/mpesa.local.php must return an array.');
    }
    $localConfig = $loadedConfig;
}

$setting = static function (string $environmentVariable, string $configKey, string $default = '') use ($localConfig): string {
    $environmentValue = getenv($environmentVariable);
    if ($environmentValue !== false && $environmentValue !== '') {
        return (string)$environmentValue;
    }
    $localValue = $localConfig[$configKey] ?? '';
    return is_string($localValue) && $localValue !== '' ? $localValue : $default;
};

$shortcode = $setting('MPESA_SHORTCODE', 'shortcode', '174379');

return [
    'environment' => $setting('MPESA_ENV', 'environment', 'sandbox'),
    'consumer_key' => $setting('MPESA_CONSUMER_KEY', 'consumer_key', 'YOUR_CONSUMER_KEY'),
    'consumer_secret' => $setting('MPESA_CONSUMER_SECRET', 'consumer_secret', 'YOUR_CONSUMER_SECRET'),
    'shortcode' => $shortcode,
    'party_b' => $setting('MPESA_PARTY_B', 'party_b', $shortcode),
    'passkey' => $setting('MPESA_PASSKEY', 'passkey', 'YOUR_PASSKEY'),
    'transaction_type' => $setting('MPESA_TRANSACTION_TYPE', 'transaction_type', 'CustomerPayBillOnline'),
    'callback_url' => $setting('MPESA_CALLBACK_URL', 'callback_url', ''),
    'account_reference_prefix' => $setting('MPESA_ACCOUNT_REFERENCE_PREFIX', 'account_reference_prefix', 'VAYUMA'),
    'transaction_desc' => $setting('MPESA_TRANSACTION_DESC', 'transaction_desc', 'VAYUMA Payment'),
];
