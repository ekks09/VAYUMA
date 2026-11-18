<?php

session_start();

/* ==============================
   DATABASE CONNECTION
   ============================== */

require_once __DIR__ . '/../config/db.php';


/* ==============================
   CHECK LOGIN
   ============================== */

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];


/* ==============================
   GET FORM DATA
   ============================== */

$farm_name = trim($_POST['farm_name'] ?? '');
$county = trim($_POST['county'] ?? '');
$sub_county = trim($_POST['sub_county'] ?? '');
$farm_location = trim($_POST['farm_location'] ?? '');
$farm_size = trim($_POST['farm_size'] ?? '');
$farm_size_unit = trim($_POST['farm_size_unit'] ?? '');
$farming_experience = trim($_POST['farming_experience'] ?? '');
$main_crops = trim($_POST['main_crops'] ?? '');
$soil_type = trim($_POST['soil_type'] ?? '');
$irrigation = trim($_POST['irrigation'] ?? '');
$secondary_crop = trim($_POST['secondary_crop'] ?? '');


/* ==============================
   VALIDATE REQUIRED FIELDS
   ============================== */

if (
    $farm_name === '' ||
    $county === '' ||
    $sub_county === '' ||
    $farm_location === '' ||
    $farm_size === '' ||
    $farm_size_unit === '' ||
    $farming_experience === '' ||
    $main_crops === '' ||
    $soil_type === '' ||
    $irrigation === ''
) {
    die("Please fill in all required farm profile fields.");
}


/* ==============================
   SAVE FARM PROFILE
   ============================== */

$sql = "INSERT INTO farmer_profiles
        (
            user_id,
            farm_name,
            county,
            sub_county,
            farm_location,
            farm_size,
            farm_size_unit,
            farming_experience,
            main_crops,
            soil_type,
            irrigation,
            secondary_crop
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}


/* ==============================
   BIND VALUES
   ============================== */

$stmt->bind_param(
    "isssssssssss",
    $user_id,
    $farm_name,
    $county,
    $sub_county,
    $farm_location,
    $farm_size,
    $farm_size_unit,
    $farming_experience,
    $main_crops,
    $soil_type,
    $irrigation,
    $secondary_crop
);


/* ==============================
   EXECUTE
   ============================== */

if ($stmt->execute()) {

    $stmt->close();

    /*
     * Farm profile saved successfully.
     * Send farmer to dashboard.
     */

    header("Location: ../farmer_dashboard.php");
    exit;

} else {

    die("Failed to save farm profile: " . $stmt->error);
}

?>