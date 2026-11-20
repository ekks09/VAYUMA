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

// Never log driver text: it may contain passwords, connection strings or SQL.
function vayuma_db_connection_stage(int $code): string
{
    if (in_array($code, [1044, 1045, 1698], true)) return 'authentication_or_database_access';
    if ($code === 2026) return 'tls_handshake';
    if (in_array($code, [2002, 2003, 2005, 2006, 2013], true)) return 'tcp_or_connection';
    return 'connection_or_handshake';
}
function vayuma_db_log_driver_error(int $code, string $sqlstate, string $message = ''): void
{
    $sqlstate = preg_match('/^[A-Z0-9]{5}$/', $sqlstate) ? $sqlstate : 'unknown';
    error_log('VAYUMA staging DB driver error: code=' . $code . ' SQLSTATE=' . $sqlstate);
}
