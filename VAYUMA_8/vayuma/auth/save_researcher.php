<?php

session_start();
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !vayuma_csrf_valid()) {
    http_response_code(403);
    exit('Your session expired. Refresh the profile form and try again.');
}

require_once __DIR__ . "/../config/db.php";

/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| REQUIRE RESEARCHER ROLE
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'researcher'
) {
    header("Location: ../role.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ONLY ACCEPT POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../researcher_profile.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$institution = trim($_POST['institution'] ?? '');

$research_area = trim($_POST['research_area'] ?? '');

$qualification = trim($_POST['qualification'] ?? '');

$location = trim($_POST['location'] ?? '');

$bio = trim($_POST['bio'] ?? '');


/*
|--------------------------------------------------------------------------
| VALIDATE REQUIRED FIELDS
|--------------------------------------------------------------------------
*/

if (
    $institution === '' ||
    $research_area === '' ||
    $qualification === '' ||
    $location === ''
) {
    die("Please fill in all required researcher fields.");
}


/*
|--------------------------------------------------------------------------
| CHECK IF PROFILE ALREADY EXISTS
|--------------------------------------------------------------------------
*/

try {
$check = $conn->prepare(
    "SELECT id FROM researcher_profiles WHERE user_id = ?"
);

$check->bind_param("i", $user_id);

$check->execute();

$result = $check->get_result();


/*
|--------------------------------------------------------------------------
| UPDATE EXISTING PROFILE
|--------------------------------------------------------------------------
*/

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();

    $profile_id = (int) $row['id'];

    $update = $conn->prepare(
        "UPDATE researcher_profiles
         SET institution = ?,
             research_area = ?,
             qualification = ?,
             location = ?,
             bio = ?
         WHERE id = ? AND user_id = ?"
    );

    $update->bind_param(
        "sssssii",
        $institution,
        $research_area,
        $qualification,
        $location,
        $bio,
        $profile_id,
        $user_id
    );

    if (!$update->execute()) {
        throw new RuntimeException('Profile write failed');
    }

    $update->close();

}


/*
|--------------------------------------------------------------------------
| CREATE NEW PROFILE
|--------------------------------------------------------------------------
*/

else {

    $insert = $conn->prepare(
        "INSERT INTO researcher_profiles
        (
            user_id,
            institution,
            research_area,
            qualification,
            location,
            bio
        )
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    $insert->bind_param(
        "isssss",
        $user_id,
        $institution,
        $research_area,
        $qualification,
        $location,
        $bio
    );

    if (!$insert->execute()) {
        throw new RuntimeException('Profile write failed');
    }

    $insert->close();
}


/*
|--------------------------------------------------------------------------
| REDIRECT TO RESEARCHER DASHBOARD
|--------------------------------------------------------------------------
*/

 } catch (Throwable $e) {
    // Driver messages can contain private values. Keep only safe class/code context.
    error_log('VAYUMA researcher profile save failed. code=' . (int)$e->getCode());
    http_response_code(500);
    exit('Unable to save your profile. Please try again or contact support.');
}

header("Location: ../researcher_dashboard.php");
exit;

?>