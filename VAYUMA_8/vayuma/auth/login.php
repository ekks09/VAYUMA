<?php

session_start();
require_once "../config/db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$identifier = trim($_POST["identifier"] ?? "");
$password = $_POST["password"] ?? "";
if ($identifier === "" || $password === "") {
    header("Location: ../login.php?error=empty");
    exit;
}

$stmt = $conn->prepare("SELECT id, full_name, phone, email, password, role FROM users WHERE email = ? OR phone = ? LIMIT 1");
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    header("Location: ../login.php?error=invalid");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
if (!password_verify($password, $user["password"])) {
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

if (!in_array($role, $allowed_roles, true)) {
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

header("Location: ../dashboard.php");
exit;
