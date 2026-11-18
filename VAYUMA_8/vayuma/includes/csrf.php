<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['vayuma_csrf_token'])) {
    $_SESSION['vayuma_csrf_token'] = bin2hex(random_bytes(32));
}
function vayuma_csrf_field(): string {
    $token = htmlspecialchars($_SESSION['vayuma_csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}
function vayuma_csrf_valid(): bool {
    $sent = (string)($_POST['csrf_token'] ?? '');
    $saved = (string)($_SESSION['vayuma_csrf_token'] ?? '');
    return $sent !== '' && $saved !== '' && hash_equals($saved, $sent);
}
