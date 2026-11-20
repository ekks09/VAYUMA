<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$identifier = is_string($_POST["identifier"] ?? null) ? trim($_POST["identifier"]) : '';
$password = is_string($_POST["password"] ?? null) ? $_POST["password"] : '';
if ($identifier === "" || $password === "") {
    header("Location: ../login.php?error=empty");
    exit;
}

require_once __DIR__ . '/../includes/login_identity.php';
require_once __DIR__ . '/../config/db.php';
[$phone1, $phone2, $phone3] = vayuma_login_phone_variants($identifier);
try {
    $stmt = $conn->prepare("SELECT id, full_name, phone, email, password, role FROM users WHERE email = ? OR phone IN (?, ?, ?) LIMIT 1");
    if (!$stmt) throw new RuntimeException('Login query unavailable');
    $stmt->bind_param("ssss", $identifier, $phone1, $phone2, $phone3);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    $stmt->close();
} catch (Throwable $e) {
    error_log('VAYUMA login query failed.');
    http_response_code(503);
    exit('VAYUMA is temporarily unavailable. Please try again later.');
}

if (!$user || !is_string($user['password'] ?? null) || !password_verify($password, $user['password'])) {
    header("Location: ../login.php?error=invalid");
    exit;
}

$role = strtolower(trim($user["role"] ?? ""));
$allowed_roles = [
    "farmer", "buyer", "consumer", "transport_provider", "input_supplier",
    "manufacturer", "warehouse_provider", "retailer", "agribusiness", "cooperative",
    "researcher", "extension_officer", "veterinary_officer", "financial_institution",
    "insurance_provider", "government_officer", "ngo_partner", "agricultural_service_provider",
    "strategic_partner",
];

// Newly registered accounts have no role until the user completes onboarding.
if ($role !== '' && !in_array($role, $allowed_roles, true)) {
    header("Location: ../login.php?error=profile");
    exit;
}

session_regenerate_id(true);
$_SESSION["user_id"] = (int) $user["id"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["phone"] = $user["phone"];
$_SESSION["email"] = $user["email"];
$_SESSION["role"] = $role;
$_SESSION["logged_in"] = true;

$pending = $_SESSION['pending_feature'] ?? '';
if ($role !== '' && is_string($pending) && in_array($pending, ['climate', 'soil', 'ai'], true)) {
    header('Location: ../feature_access.php?feature=' . $pending);
} else {
    header('Location: ' . ($role === '' ? '../role.php' : '../dashboard.php'));
}
exit;
