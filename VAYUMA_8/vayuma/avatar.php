<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { http_response_code(401); exit; }
require_once __DIR__ . '/includes/avatar_storage.php';
if (vayuma_avatar_uses_tidb()) {
    require_once __DIR__ . '/config/db.php';
    $bytes = vayuma_avatar_tidb_get($conn, (int)$_SESSION['user_id']);
    if ($bytes === null) { http_response_code(404); exit; }
    header('Content-Type: image/jpeg');
    echo $bytes;
    exit;
}
$dir = vayuma_avatar_directory();
$file = $dir ? $dir . '/avatar_' . (int)$_SESSION['user_id'] . '.jpg' : '';
if (!$file || !is_file($file) || is_link($file) || filesize($file) > 81920) { http_response_code(404); exit; }
header('Content-Type: image/jpeg');
readfile($file);
