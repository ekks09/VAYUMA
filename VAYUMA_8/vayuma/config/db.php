<?php
/** MySQL/TiDB connection. Non-loopback connections always verify TLS. */
$environment = strtolower(trim((string)(getenv('APP_ENV') ?: 'development')));
$host = trim((string)(getenv('DB_HOST') ?: 'localhost'));
$portText = (string)(getenv('DB_PORT') ?: '3306');
$userEnv = getenv('DB_USER');
$passwordEnv = getenv('DB_PASSWORD');
$nameEnv = getenv('DB_NAME');
$isLocal = in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
$isDevelopment = in_array($environment, ['development', 'test'], true);

require_once __DIR__ . '/db_diagnostics.php';
require_once __DIR__ . '/db_tls.php';
$diagnosticsEnabled = vayuma_db_diagnostics_enabled($environment);
$driverErrorLogged = false;
$failureStage = 'configuration';
try {
    if (!in_array($environment, ['development', 'test', 'staging', 'production'], true)) {
        throw new RuntimeException('Invalid application environment');
    }
    if (!ctype_digit($portText) || (int)$portText < 1 || (int)$portText > 65535 || $host === '') {
        throw new RuntimeException('Invalid database address');
    }
    if (!$isDevelopment && (getenv('DB_HOST') === false || trim((string)getenv('DB_HOST')) === '')) {
        throw new RuntimeException('Database host must be configured');
    }
    if ((!$isDevelopment || !$isLocal) &&
        ($userEnv === false || $userEnv === '' || $passwordEnv === false || $passwordEnv === '' || $nameEnv === false || $nameEnv === '')) {
        throw new RuntimeException('Database credentials must be configured');
    }
    // Included files execute in their caller's scope. Never overwrite common
    // application variables such as $user or $password with DB credentials.
    $dbConnectionUser = $userEnv === false ? 'root' : $userEnv;
    $dbConnectionPassword = $passwordEnv === false ? '' : $passwordEnv;
    $database = $nameEnv === false ? 'vayuma_db' : $nameEnv;
    if ($dbConnectionUser === '' || $database === '') {
        throw new RuntimeException('Invalid database configuration');
    }
    // Staging/production and all non-loopback connections must use a CA and
    // hostname verification; there is no insecure remote opt-out.
    $tls = !$isLocal || !$isDevelopment || getenv('DB_TLS') === '1';
    $failureStage = 'initialization';
    $conn = mysqli_init();
    if (!$conn) throw new RuntimeException('Database initialization failed');
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $flags = 0;
    if ($tls) {
        $failureStage = 'ca_file_check';
        $ca = (string)(getenv('DB_SSL_CA') ?: '/etc/ssl/certs/ca-certificates.crt');
        if (!is_file($ca) || !is_readable($ca)) throw new RuntimeException('Database CA bundle unavailable');
        $failureStage = 'tls_driver_check';
        $flags = vayuma_verified_mysqlnd_flags(mysqli_get_client_info(), PHP_VERSION_ID, extension_loaded('openssl'));
        $failureStage = 'tls_setup';
        if (!$conn->ssl_set(null, null, $ca, null, null)) {
            throw new RuntimeException('Database TLS configuration failed');
        }
    }
    $failureStage = 'connection';
    if (!$conn->real_connect($host, $dbConnectionUser, $dbConnectionPassword, $database, (int)$portText, null, $flags)) {
        $failureStage = vayuma_db_connection_stage((int)mysqli_connect_errno());
        if ($diagnosticsEnabled) {
            vayuma_db_log_driver_error(mysqli_connect_errno(), '', '');
            $driverErrorLogged = true;
        }
        throw new RuntimeException('Database connection failed');
    }
    if ($tls) {
        $failureStage = 'tls_verification';
        $cipherResult = $conn->query("SHOW STATUS LIKE 'Ssl_cipher'");
        $cipher = $cipherResult ? $cipherResult->fetch_row() : null;
        if (!$cipher || empty($cipher[1])) throw new RuntimeException('Database TLS was not negotiated');
    }
    $failureStage = 'charset';
    $conn->set_charset('utf8mb4');
} catch (Throwable $exception) {
    if ($failureStage === 'connection' && $exception instanceof mysqli_sql_exception) {
        $failureStage = vayuma_db_connection_stage((int)$exception->getCode());
    }
    error_log('VAYUMA database connection or configuration failed. Stage: ' . $failureStage);
    if ($diagnosticsEnabled) {
        vayuma_db_log_environment();
        if (!$driverErrorLogged && $exception instanceof mysqli_sql_exception) {
            vayuma_db_log_driver_error((int) $exception->getCode(), $exception->getSqlState(), $exception->getMessage());
        }
    }
    // A readiness probe handles its own generic response without exposing details.
    if (($vayumaDbReadinessProbe ?? false) === true) {
        return false;
    }
    http_response_code(503);
    exit('VAYUMA is temporarily unavailable. Please try again later.');
}
