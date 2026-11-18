<?php

session_start();

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
| REQUIRE COOPERATIVE ROLE
|--------------------------------------------------------------------------
*/

$role = strtolower(trim($_SESSION['role'] ?? ''));

if ($role !== 'cooperative') {
    header("Location: ../role.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| ONLY ACCEPT POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../cooperative_profile.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| USER ID
|--------------------------------------------------------------------------
*/

$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| FORM DATA
|--------------------------------------------------------------------------
*/

$cooperative_name =
    trim($_POST['cooperative_name'] ?? '');

$registration_number =
    trim($_POST['registration_number'] ?? '');

$cooperative_type =
    trim($_POST['cooperative_type'] ?? '');

$location =
    trim($_POST['location'] ?? '');

$member_count =
    (int)($_POST['member_count'] ?? 0);

$contact_person =
    trim($_POST['contact_person'] ?? '');

$description =
    trim($_POST['description'] ?? '');


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $cooperative_name === '' ||
    $cooperative_type === '' ||
    $location === '' ||
    $contact_person === ''
) {
    die("Please fill in all required cooperative fields.");
}


if ($member_count < 0) {
    $member_count = 0;
}


/*
|--------------------------------------------------------------------------
| CHECK EXISTING PROFILE
|--------------------------------------------------------------------------
*/

$check = $conn->prepare(
    "SELECT id
     FROM cooperative_profiles
     WHERE user_id = ?"
);

$check->bind_param(
    "i",
    $user_id
);

$check->execute();

$result = $check->get_result();


/*
|--------------------------------------------------------------------------
| UPDATE EXISTING PROFILE
|--------------------------------------------------------------------------
*/

if ($result->num_rows > 0) {

    $row = $result->fetch_assoc();

    $profile_id = (int)$row['id'];


    $update = $conn->prepare(
        "UPDATE cooperative_profiles
         SET cooperative_name = ?,
             registration_number = ?,
             cooperative_type = ?,
             location = ?,
             member_count = ?,
             contact_person = ?,
             description = ?
         WHERE id = ?"
    );


    $update->bind_param(
        "ssssissi",
        $cooperative_name,
        $registration_number,
        $cooperative_type,
        $location,
        $member_count,
        $contact_person,
        $description,
        $profile_id
    );


    if (!$update->execute()) {

        die(
            "Failed to update cooperative profile: "
            . $update->error
        );

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
        "INSERT INTO cooperative_profiles
        (
            user_id,
            cooperative_name,
            registration_number,
            cooperative_type,
            location,
            member_count,
            contact_person,
            description
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    $insert->bind_param(
        "issssiss",
        $user_id,
        $cooperative_name,
        $registration_number,
        $cooperative_type,
        $location,
        $member_count,
        $contact_person,
        $description
    );


    if (!$insert->execute()) {

        die(
            "Failed to save cooperative profile: "
            . $insert->error
        );

    }


    $insert->close();

}


/*
|--------------------------------------------------------------------------
| REDIRECT TO DASHBOARD
|--------------------------------------------------------------------------
*/

header("Location: ../cooperative_dashboard.php");

exit;

?>