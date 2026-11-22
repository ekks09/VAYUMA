<?php

session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$identifier = is_string($_POST["identifier"] ?? null) ? trim($_POST["identifier"]) : '';
$submittedPassword = is_string($_POST["password"] ?? null) ? $_POST["password"] : '';
if ($identifier === "" || $submittedPassword === "") {
    header("Location: ../login.php?error=empty");
    exit;
}

require_once __DIR__ . '/../includes/login_identity.php';
require_once __DIR__ . '/../config/db.php';
[$phone1, $phone2, $phone3] = vayuma_login_phone_variants($identifier);
$emailIdentity = strtolower($identifier);
try {
    $stmt = $conn->prepare("SELECT id, full_name, phone, email, password, role FROM users WHERE email_identity = ? OR phone_identity = ? OR REPLACE(phone_identity, '.', '') IN (?, ?, ?) LIMIT 2");
    if (!$stmt) throw new RuntimeException('Login query unavailable');
    $stmt->bind_param("sssss", $emailIdentity, $phone1, $phone1, $phone2, $phone3);
    if (!$stmt->execute()) throw new RuntimeException('Login query unavailable');
    $result = $stmt->get_result();
    if (!$result) throw new RuntimeException('Login query unavailable');
    $user = $result->fetch_assoc();
    // Never authenticate an ambiguous identity.
    $ambiguous = $user && $result->fetch_assoc();
    if ($ambiguous) $user = null;
    $stmt->close();
} catch (Throwable $e) {
    error_log('VAYUMA login query failed.');
    http_response_code(503);
    exit('VAYUMA is temporarily unavailable. Please try again later.');
}

$submittedPasswordMatches = $user && is_string($user['password'] ?? null) && password_verify($submittedPassword, $user['password']);
if (!$submittedPasswordMatches) {
    // Temporary opt-in operational diagnostic: no identities, passwords, hashes,
    // request metadata or user-supplied values are written to the log.
    if (getenv('VAYUMA_AUTH_DIAGNOSTICS') === '1') {
        $stage = $ambiguous ? 'ambiguous' : (!$user ? 'no_match' : 'password_verification_failed');
        error_log('VAYUMA login diagnostic: ' . $stage);
    }
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
$_SESSION["vayuma_auth_fingerprint"] = hash('sha256', 'vayuma-session-password-v1:' . $user['password']);

// Claimed farmers complete their own profile before subscription/dashboard routing.
if ($role === 'farmer' && getenv('VAYUMA_ONBOARDING_ENABLED') === '1' && getenv('VAYUMA_CLAIM_ENABLED') === '1') {
    try {
        $claimUserId = (int)$user['id'];
        $claimStmt = $conn->prepare('SELECT r.id FROM onboarding_records r WHERE r.claimed_by_user_id=? AND NOT EXISTS (SELECT 1 FROM farmer_profiles fp WHERE fp.user_id=r.claimed_by_user_id) LIMIT 1');
        $claimStmt->bind_param('i', $claimUserId); $claimStmt->execute(); $needsProfile = $claimStmt->get_result()->fetch_assoc(); $claimStmt->close();
        if ($needsProfile) { header('Location: ../profile_setup.php'); exit; }
    } catch (Throwable $e) { error_log('VAYUMA claimed profile lookup unavailable.'); http_response_code(503); exit('VAYUMA is temporarily unavailable. Please try again later.'); }
}

$pending = $_SESSION['pending_feature'] ?? '';
if (is_string($pending) && ($pending === 'ai' || ($role !== '' && in_array($pending, ['climate', 'soil'], true)))) {
    header('Location: ../feature_access.php?feature=' . $pending);
} else {
    header('Location: ' . ($role === '' ? '../role.php' : '../dashboard.php'));
}
exit;
