<?php
session_start();
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !vayuma_csrf_valid()) {
    http_response_code(403);
    exit('Your session expired. Refresh the profile form and try again.');
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: ../login.php');
    exit;
}
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'transport_provider') {
    header('Location: ../role.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../transport_profile.php');
    exit;
}

$uid = (int) $_SESSION['user_id'];
$get = static function(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
};
$business_name = $get('business_name');
$phone = $get('phone');
$county = $get('county');
$vehicle_type = $get('vehicle_type');
$registration_number = strtoupper($get('registration_number'));
$vehicle_capacity = $get('vehicle_capacity');
$operating_area = $get('operating_area');
$service_type = $get('service_type');

if ($business_name === '' || $phone === '' || $county === '' || $vehicle_type === '' ||
    $vehicle_capacity === '' || $operating_area === '' || $service_type === '') {
    $_SESSION['transport_profile_error'] = 'Please complete all required fields.';
    header('Location: ../transport_profile.php');
    exit;
}

// Confirm the submitted county is one of VAYUMA's configured Kenyan counties.
$countyGroups = require __DIR__ . '/../config/kenya_counties.php';
$validCounties = [];
foreach ($countyGroups as $items) {
    foreach ($items as $item) {
        if (!empty($item['name'])) $validCounties[] = $item['name'];
    }
}
$validCounties[] = 'Marsabit';
$countyMatch = null;
foreach ($validCounties as $validCounty) {
    if (strcasecmp($county, $validCounty) === 0) { $countyMatch = $validCounty; break; }
}
if ($countyMatch === null) {
    $_SESSION['transport_profile_error'] = 'Please select a valid county from the list.';
    header('Location: ../transport_profile.php');
    exit;
}
$county = $countyMatch;

// The transport_profiles table and unique user_id key already exist in the supplied VAYUMA schema.
// Keep the existing verification flag untouched when updating a profile.
$sql = "INSERT INTO transport_profiles
    (user_id, business_name, county, vehicle_type, vehicle_capacity,
     registration_number, operating_area, service_type, phone,
     vehicle_registration, load_capacity)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      business_name = VALUES(business_name),
      county = VALUES(county),
      vehicle_type = VALUES(vehicle_type),
      vehicle_capacity = VALUES(vehicle_capacity),
      registration_number = VALUES(registration_number),
      operating_area = VALUES(operating_area),
      service_type = VALUES(service_type),
      phone = VALUES(phone),
      vehicle_registration = VALUES(vehicle_registration),
      load_capacity = VALUES(load_capacity)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('VAYUMA transport profile prepare failed: ' . $conn->error);
    $_SESSION['transport_profile_error'] = 'The transport profile could not be prepared. Please contact the administrator.';
    header('Location: ../transport_profile.php');
    exit;
}
$stmt->bind_param(
    'issssssssss',
    $uid, $business_name, $county, $vehicle_type, $vehicle_capacity,
    $registration_number, $operating_area, $service_type, $phone,
    $registration_number, $vehicle_capacity
);
if (!$stmt->execute()) {
    error_log('VAYUMA transport profile save failed: ' . $stmt->error);
    $stmt->close();
    $_SESSION['transport_profile_error'] = 'Your transport profile could not be saved. Please try again.';
    header('Location: ../transport_profile.php');
    exit;
}
$stmt->close();
$_SESSION['profile_completed'] = true;
header('Location: ../transport_dashboard.php');
exit;
