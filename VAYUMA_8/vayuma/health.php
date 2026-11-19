<?php
// Liveness only: intentionally does not expose configuration or depend on TiDB.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo json_encode(['status' => 'ok']);
