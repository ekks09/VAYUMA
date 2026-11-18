<?php

session_start();
require_once __DIR__ . '/subscription_guard.php';
require_once __DIR__ . '/includes/role_routes.php';

if (!isset($_SESSION["user_id"]) || ($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}

$role = strtolower(trim($_SESSION["role"] ?? ""));
$dashboard = vayuma_role_route($role, 'dashboard');

if ($dashboard === null) {
    session_unset();
    session_destroy();
    header("Location: login.php?error=profile");
    exit;
}

header("Location: " . $dashboard);
exit;
