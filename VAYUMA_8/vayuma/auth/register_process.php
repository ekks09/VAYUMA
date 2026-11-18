<?php

session_start();

require_once "../config/db.php";
require_once __DIR__ . '/../includes/csrf.php';


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

$full_name = trim($_POST["full_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";
$terms = $_POST["terms"] ?? "";


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

if (strlen($full_name) < 2) {

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

$phone_clean = preg_replace("/[\s\-\(\)]/", "", $phone);

if (!preg_match("/^\+?[0-9]{9,15}$/", $phone_clean)) {

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

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

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

if (strlen($password) < 8) {

    $_SESSION["register_error"] =
        "Password must contain at least 8 characters.";

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
    "SELECT id FROM users WHERE phone = ? LIMIT 1"
);

$phone_check->bind_param("s", $phone_clean);

$phone_check->execute();

$phone_result = $phone_check->get_result();

if ($phone_result->num_rows > 0) {

    $phone_check->close();

    $_SESSION["register_error"] =
        "An account with this phone number already exists.";

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
        "SELECT id FROM users WHERE email = ? LIMIT 1"
    );

    $email_check->bind_param("s", $email);

    $email_check->execute();

    $email_result = $email_check->get_result();

    if ($email_result->num_rows > 0) {

        $email_check->close();

        $_SESSION["register_error"] =
            "An account with this email already exists.";

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

if (!$stmt->execute()) {

    $_SESSION["register_error"] =
        "Unable to create your account. Please try again.";

    $stmt->close();

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Save newly created user ID
|--------------------------------------------------------------------------
*/

$user_id = $stmt->insert_id;

$stmt->close();


/*
|--------------------------------------------------------------------------
| Save user information in session
|--------------------------------------------------------------------------
*/

$_SESSION["user_id"] = $user_id;

$_SESSION["full_name"] = $full_name;

$_SESSION["phone"] = $phone_clean;

$_SESSION["email"] = $email;


/*
|--------------------------------------------------------------------------
| Redirect to role selection
|--------------------------------------------------------------------------
*/

header("Location: ../role.php");

exit;

?>