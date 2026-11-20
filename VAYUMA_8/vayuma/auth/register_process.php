<?php

session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/onboarding_policy.php';
require_once __DIR__ . '/../includes/login_identity.php';


/*
|--------------------------------------------------------------------------
| Only allow POST requests
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../register.php");
    exit;

}

if (!vayuma_csrf_valid()) {
    $_SESSION["register_error"] = "Your session expired. Refresh the form and try again.";
    header("Location: ../register.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get submitted data
|--------------------------------------------------------------------------
*/

$full_name = is_string($_POST["full_name"] ?? null) ? trim($_POST["full_name"]) : "";
$phone = is_string($_POST["phone"] ?? null) ? trim($_POST["phone"]) : "";
$email = is_string($_POST["email"] ?? null) ? strtolower(trim($_POST["email"])) : "";
$password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";
$confirm_password = is_string($_POST["confirm_password"] ?? null) ? $_POST["confirm_password"] : "";
$terms = is_string($_POST["terms"] ?? null) ? $_POST["terms"] : "";
$_SESSION["register_phone"] = $phone;


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if (
    empty($full_name) ||
    empty($phone) ||
    empty($password) ||
    empty($confirm_password)
) {

    $_SESSION["register_error"] =
        "Please fill in all required fields.";

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Validate name
|--------------------------------------------------------------------------
*/

if (strlen($full_name) < 2 || mb_strlen($full_name, "UTF-8") > 100) {

    $_SESSION["register_error"] =
        "Please enter a valid full name.";

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Validate phone
|--------------------------------------------------------------------------
*/

$phone_clean = vayuma_onboarding_phone($phone);

if ($phone_clean === null) {

    $_SESSION["register_error"] =
        "Please enter a valid phone number.";

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Validate email if provided
|--------------------------------------------------------------------------
*/

if (!empty($email) && (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {

    $_SESSION["register_error"] =
        "Please enter a valid email address.";

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Password validation
|--------------------------------------------------------------------------
*/

if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {

    $_SESSION["register_error"] =
        "Password must be between 8 and 72 bytes.";

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Confirm password
|--------------------------------------------------------------------------
*/

if ($password !== $confirm_password) {

    $_SESSION["register_error"] =
        "Passwords do not match.";

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Terms
|--------------------------------------------------------------------------
*/

if ($terms !== "accepted") {

    $_SESSION["register_error"] =
        "You must accept the Terms of Service and Privacy Policy.";

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Check duplicate phone
|--------------------------------------------------------------------------
*/

$phone_check = $conn->prepare(
    "SELECT id FROM users WHERE phone_identity = ? OR REPLACE(phone_identity, '.', '') IN (?, ?, ?) LIMIT 1"
);

[$phone1, $phone2, $phone3] = vayuma_login_phone_variants($phone_clean);
$phone_check->bind_param("ssss", $phone_clean, $phone1, $phone2, $phone3);

$phone_check->execute();

$phone_result = $phone_check->get_result();

if ($phone_result->num_rows > 0) {

    $phone_check->close();

    $_SESSION["register_error"] =
        "Unable to create your account. Please sign in or try again later.";

    header("Location: ../register.php");
    exit;

}

$phone_check->close();


/*
|--------------------------------------------------------------------------
| Check duplicate email
|--------------------------------------------------------------------------
*/

if (!empty($email)) {

    $email_check = $conn->prepare(
        "SELECT id FROM users WHERE email_identity = ? LIMIT 1"
    );

    $email_check->bind_param("s", $email);

    $email_check->execute();

    $email_result = $email_check->get_result();

    if ($email_result->num_rows > 0) {

        $email_check->close();

        $_SESSION["register_error"] =
            "Unable to create your account. Please sign in or try again later.";

        header("Location: ../register.php");
        exit;

    }

    $email_check->close();

}


/*
|--------------------------------------------------------------------------
| Hash password
|--------------------------------------------------------------------------
*/

$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);


/*
|--------------------------------------------------------------------------
| Create user
|--------------------------------------------------------------------------
|
| Role is intentionally NULL.
| The user chooses their role in the next step.
|
|--------------------------------------------------------------------------
*/

if (empty($email)) {

    $stmt = $conn->prepare(
        "INSERT INTO users
        (full_name, phone, email, password, role)
        VALUES (?, ?, NULL, ?, NULL)"
    );

    $stmt->bind_param(
        "sss",
        $full_name,
        $phone_clean,
        $hashed_password
    );

} else {

    $stmt = $conn->prepare(
        "INSERT INTO users
        (full_name, phone, email, password, role)
        VALUES (?, ?, ?, ?, NULL)"
    );

    $stmt->bind_param(
        "ssss",
        $full_name,
        $phone_clean,
        $email,
        $hashed_password
    );

}


/*
|--------------------------------------------------------------------------
| Execute registration
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/identity_errors.php';
$registrationTx = false;
try {
    // Do not report success until the exact password hash can be read back and
    // verified. Roll back on any failure, even when staff onboarding is off.
    if (!$conn->begin_transaction()) throw new RuntimeException('Transaction unavailable');
    $registrationTx = true;
    if (getenv('VAYUMA_ONBOARDING_ENABLED') === '1') {
        require_once __DIR__ . '/../includes/onboarding_lock.php';
        vayuma_onboarding_lock_phone($conn, $phone_clean);
        // A staff record reserves its phone regardless of stakeholder type or claim flag.
        $claimCheck = $conn->prepare('SELECT id FROM onboarding_records WHERE phone_identity=? LIMIT 1 FOR UPDATE');
        if (!$claimCheck) throw new RuntimeException('Onboarding check unavailable');
        $claimCheck->bind_param('s', $phone_clean);
        if (!$claimCheck->execute()) throw new RuntimeException('Onboarding check unavailable');
        $reserved = $claimCheck->get_result()->fetch_assoc(); $claimCheck->close();
        if ($reserved) {
            $conn->rollback(); $registrationTx = false; $stmt->close();
            $_SESSION['register_error'] = 'Unable to create your account. Please sign in or try again later.';
            $_SESSION['register_phone'] = $phone;
            header('Location: ../register.php'); exit;
        }
    }
    if (!$stmt->execute()) throw new RuntimeException('Registration insert unavailable');
    $newUserId = (int)$stmt->insert_id;
    if ($newUserId <= 0 || !is_string($hashed_password)) throw new RuntimeException('Registration verification unavailable');
    $verifyStmt = $conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
    if (!$verifyStmt) throw new RuntimeException('Registration verification unavailable');
    $verifyStmt->bind_param('i', $newUserId);
    if (!$verifyStmt->execute()) { $verifyStmt->close(); throw new RuntimeException('Registration verification unavailable'); }
    $verifyResult = $verifyStmt->get_result();
    if (!$verifyResult) { $verifyStmt->close(); throw new RuntimeException('Registration verification unavailable'); }
    $stored = $verifyResult->fetch_assoc(); $verifyStmt->close();
    $storedHash = $stored['password'] ?? null;
    if (!is_string($storedHash) || !hash_equals($hashed_password, $storedHash) || !password_verify($password, $storedHash)) {
        // Categorical only: never log the submitted password, hash or identity.
        error_log('VAYUMA registration password integrity check failed.');
        throw new RuntimeException('Registration verification unavailable');
    }
    unset($storedHash, $stored);
    if (!$conn->commit()) throw new RuntimeException('Transaction commit unavailable');
    $registrationTx = false;
} catch (Throwable $e) {
    if ($registrationTx) { try { $conn->rollback(); } catch (Throwable $ignored) {} }
    $_SESSION['register_error'] = 'Unable to create your account. Please sign in or try again later.';
    $stmt->close();
    header('Location: ../register.php');
    exit;
}


/* Account creation is not authentication: ask the user to sign in before
 * choosing a role. Keep language and any allowlisted feature intent only. */
$stmt->close();
$language = $_SESSION['vayuma_language'] ?? 'en';
$pending = $_SESSION['pending_feature'] ?? '';
$_SESSION = [];
if (in_array($language, ['en', 'sw'], true)) $_SESSION['vayuma_language'] = $language;
if (is_string($pending) && in_array($pending, ['climate', 'soil', 'ai'], true)) $_SESSION['pending_feature'] = $pending;
session_regenerate_id(true);
$_SESSION['registration_success'] = true;
header('Location: ../login.php');
exit;
