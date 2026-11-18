<?php
/**
 * VAYUMA Paystack configuration.
 *
 * Use test keys first. Never commit real (live) secret keys to source control.
 * For production, move these values to environment variables/server secrets.
 *
 * Required:
 *   PAYSTACK_SECRET_KEY   sk_test_... / sk_live_...
 *   PAYSTACK_PUBLIC_KEY   pk_test_... / pk_live_...
 *   PAYSTACK_WEBHOOK_SECRET whsec_... (or PAYSTACK_WEBHOOK_SECRETS during key rotation)
 *   PAYSTACK_CALLBACK_URL https://YOUR_DOMAIN/paystack_verify.php
 *   PAYSTACK_SUBSCRIPTION_CALLBACK_URL https://YOUR_DOMAIN/paystack_subscription_return.php
 *   PAYSTACK_WEBHOOK_URL  https://YOUR_DOMAIN/paystack_webhook.php (register once in Paystack dashboard)
 *
 * Local XAMPP users can set these in config/paystack.local.php. Keep that file
 * private and use Paystack test keys until the full flow has been verified.
 */
$localConfig = [];
$localConfigPath = __DIR__ . '/paystack.local.php';
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (!is_array($loadedConfig)) {
        throw new RuntimeException('config/paystack.local.php must return an array.');
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

$callbackUrl = $setting(
    'PAYSTACK_CALLBACK_URL',
    'callback_url'
);
$subscriptionCallbackUrl = $setting('PAYSTACK_SUBSCRIPTION_CALLBACK_URL', 'subscription_callback_url');
if ($subscriptionCallbackUrl === '') {
    $subscriptionCallbackUrl = preg_replace(
        '~/paystack_verify\.php$~',
        '/paystack_subscription_return.php',
        $callbackUrl
    ) ?: '';
}
$webhookSecrets = array_filter(
    array_map('trim', explode(',', $setting('PAYSTACK_WEBHOOK_SECRETS', 'webhook_secrets')))
);
$localWebhookSecrets = $localConfig['webhook_secrets'] ?? [];
if (is_array($localWebhookSecrets)) {
    $webhookSecrets = array_merge($webhookSecrets, array_filter(array_map('trim', $localWebhookSecrets)));
}
$singleWebhookSecret = trim($setting('PAYSTACK_WEBHOOK_SECRET', 'webhook_secret'));
if ($singleWebhookSecret !== '') {
    $webhookSecrets[] = $singleWebhookSecret;
}

return [
    // test | live
    'environment' => $setting('PAYSTACK_ENV', 'environment', 'test'),

    // Paystack dashboard keys
    'secret_key'  => $setting('PAYSTACK_SECRET_KEY', 'secret_key', 'YOUR_PAYSTACK_SECRET_KEY'),
    'public_key'  => $setting('PAYSTACK_PUBLIC_KEY', 'public_key', 'YOUR_PAYSTACK_PUBLIC_KEY'),
    // Modern Paystack-Signature signing secrets; comma-separate during rotation.
    'webhook_secrets' => array_values(array_unique($webhookSecrets)),

    // Publicly reachable HTTPS URLs. Paystack redirects here after payment and posts webhooks here.
    'callback_url' => $callbackUrl,
    // Keep this separate from marketplace order verification.
    'subscription_callback_url' => $subscriptionCallbackUrl,
    'webhook_url'  => $setting('PAYSTACK_WEBHOOK_URL', 'webhook_url'),

    // All Paystack transaction channels VAYUMA supports
    'channels' => ['card','bank','ussd','mobile_money','qr','bank_account','paylater'],

    // Settlement currency for your Paystack account country (KES for Kenya, NGN for Nigeria, GHS for Ghana, etc.)
    'currency' => $setting('PAYSTACK_CURRENCY', 'currency', 'KES'),

    // Mobile money providers supported by Paystack (adjust to your account's country)
    'mobile_money_providers' => ['MPESA','AIRTEL_MONEY','T_KASH'],
];
