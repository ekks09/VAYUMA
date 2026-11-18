<?php

session_start();

require_once "../config/db.php";
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/role_routes.php';

if (!isset($_SESSION["user_id"])) {
    header("Location: ../register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../role.php");
    exit;
}
if (!vayuma_csrf_valid()) {
    $_SESSION["role_error"] = "Your session expired. Refresh the page and try again.";
    header("Location: ../role.php");
    exit;
}

$role = trim($_POST["role"] ?? "");
$user_id = (int) $_SESSION["user_id"];

$allowed_roles = [
    "farmer",
    "buyer",
    "consumer",
    "transport_provider",
    "input_supplier",
    "manufacturer",
    "warehouse_provider",
    "retailer",
    "agribusiness",
    "cooperative",
    "researcher",
    "extension_officer",
    "veterinary_officer",
    "financial_institution",
    "insurance_provider",
    "government_officer",
    "ngo_partner",
    "agricultural_service_provider",
    "strategic_partner",
];

if (!in_array($role, $allowed_roles, true)) {
    $_SESSION["role_error"] = "Please select a valid VAYUMA role.";
    header("Location: ../role.php");
    exit;
}

$currentRoleStmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
if (!$currentRoleStmt) {
    error_log("Unable to check current stakeholder role: " . $conn->error);
    $_SESSION["role_error"] = "Unable to update your stakeholder type right now.";
    header("Location: ../role.php");
    exit;
}
$currentRoleStmt->bind_param("i", $user_id);
$currentRoleStmt->execute();
$currentRoleRow = $currentRoleStmt->get_result()->fetch_assoc();
$currentRoleStmt->close();
if (!$currentRoleRow) {
    $_SESSION["role_error"] = "Your account could not be found. Please sign in again.";
    header("Location: ../login.php");
    exit;
}
$currentRole = strtolower(trim((string)($currentRoleRow["role"] ?? "")));
if ($currentRole !== "" && $currentRole !== $role) {
    $activeSubscriptionStmt = $conn->prepare(
        "SELECT expires_at FROM user_subscriptions
         WHERE user_id = ? AND status = 'active' AND expires_at > NOW()
         LIMIT 1"
    );
    if (!$activeSubscriptionStmt) {
        error_log("Unable to check active subscription before stakeholder change: " . $conn->error);
        $_SESSION["role_error"] = "Unable to update your stakeholder type right now.";
        header("Location: ../role.php");
        exit;
    }
    $activeSubscriptionStmt->bind_param("i", $user_id);
    $activeSubscriptionStmt->execute();
    $activeSubscription = $activeSubscriptionStmt->get_result()->fetch_assoc();
    $activeSubscriptionStmt->close();
    if ($activeSubscription) {
        $expiry = date("d M Y", strtotime($activeSubscription["expires_at"]));
        $_SESSION["role_error"] = "Your paid subscription is active until " . $expiry . ". Change your stakeholder type after it expires.";
        header("Location: ../role.php");
        exit;
    }
}

$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
if ($stmt === false) {
    $_SESSION["role_error"] = "Unable to save your role. Please try again.";
    header("Location: ../role.php");
    exit;
}

$stmt->bind_param("si", $role, $user_id);

if (!$stmt->execute()) {
    $_SESSION["role_error"] = "Unable to save your role. Please try again.";
    $stmt->close();
    header("Location: ../role.php");
    exit;
}

$stmt->close();
$_SESSION["role"] = $role;
/* Registration has already authenticated this session for onboarding. */
$_SESSION["logged_in"] = true;

$next_page = vayuma_role_route($role, 'profile') ?? 'stakeholder_profile.php';

header("Location: ../" . $next_page);
exit;
