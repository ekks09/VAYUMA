<?php
/** Staging-only, opt-in diagnostics. Never send these details to the browser. */
function vayuma_db_diagnostics_enabled(string $environment): bool
{
    return $environment === 'staging' && getenv('DB_DIAGNOSTICS') === '1';
}

function vayuma_db_log_environment(): void
{
    $presence = [];
    foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_SSL_CA'] as $key) {
        $value = getenv($key);
        $presence[] = $key . '=' . ($value !== false && trim($value) !== '' ? 'set' : 'unset');
    }
    $customCa = getenv('DB_SSL_CA');
    $ca = $customCa ?: '/etc/ssl/certs/ca-certificates.crt';
    $presence[] = 'CA_source=' . ($customCa ? 'custom' : 'system');
    $presence[] = 'CA_readable=' . (is_file($ca) && is_readable($ca) ? 'yes' : 'no');
    error_log('VAYUMA staging DB environment: ' . implode(' ', $presence));
}

function vayuma_db_log_driver_error(int $code, string $sqlstate, string $message): void
{
    // Driver messages can include identity or connection information. Redact all
    // configured DB values and common encodings before logging; keep logs private.
    $values = [];
    foreach (['DB_PASSWORD', 'DB_USER', 'DB_HOST', 'DB_NAME', 'DB_SSL_CA'] as $key) {
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            $values[] = $value;
            $values[] = rawurlencode($value);
            $values[] = urlencode($value);
        }
    }
    usort($values, static function ($a, $b) { return strlen($b) <=> strlen($a); });
    $message = str_ireplace($values, '[redacted]', $message);
    $message = preg_replace('/[\x00-\x1f\x7f]+/', ' ', $message);
    $message = substr($message, 0, 1000);
    $sqlstate = preg_match('/^[A-Z0-9]{5}$/', $sqlstate) ? $sqlstate : 'unknown';
    error_log('VAYUMA staging DB driver error: code=' . $code . ' SQLSTATE=' . $sqlstate . ' message=' . $message);
}
