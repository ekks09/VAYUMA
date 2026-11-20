<?php
/**
 * PHP 8.3 mysqlnd has a verification connect flag even on builds where
 * mysqli::options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true) returns false.
 * mysqlnd defines CLIENT_SSL_VERIFY_SERVER_CERT as bit 30 and handles it in
 * mysqlnd_commands.c before setting both verify_peer and verify_peer_name.
 * Restrict this fallback to the audited driver and PHP minor version. Unknown
 * implementations fail closed rather than silently weakening TLS.
 */
function vayuma_verified_mysqlnd_flags(string $clientInfo, int $versionId, bool $opensslAvailable): int
{
    if (!str_starts_with(strtolower($clientInfo), 'mysqlnd ') ||
        $versionId < 80300 || $versionId >= 80400 || !$opensslAvailable ||
        !defined('MYSQLI_CLIENT_SSL')) {
        throw new RuntimeException('Unsupported secure database driver');
    }
    // Internal mysqlnd CLIENT_SSL_VERIFY_SERVER_CERT, not the DONT_VERIFY flag.
    $verifyServerCertificate = 1 << 30;
    return MYSQLI_CLIENT_SSL | $verifyServerCertificate;
}
