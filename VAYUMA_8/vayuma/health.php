<?php
// Keep Render's default liveness check independent of database availability.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (($_GET['check'] ?? '') !== 'db') {
    echo json_encode(['status' => 'ok', 'database' => 'not_checked']);
    exit;
}

// Optional readiness check uses exactly the application's verified TLS path.
// It returns only generic state; connection details and exceptions stay private.
$vayumaDbReadinessProbe = true;
$connected = (require __DIR__ . '/config/db.php') !== false;
if (!$connected) {
    http_response_code(503);
    echo json_encode(['status' => 'unavailable', 'database' => 'unavailable']);
    exit;
}
try {
    $result = $conn->query('SELECT 1');
    if (!$result || !$result->fetch_row()) throw new RuntimeException('Database readiness query failed');
    $result->free();
    $conn->close();
    echo json_encode(['status' => 'ok', 'database' => 'connected']);
} catch (Throwable $exception) {
    error_log('VAYUMA database readiness query failed.');
    http_response_code(503);
    echo json_encode(['status' => 'unavailable', 'database' => 'unavailable']);
}
