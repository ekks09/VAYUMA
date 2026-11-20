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

if (strtolower(trim($_SESSION['role'] ?? '')) !== 'financial_institution') {
    http_response_code(403);
    exit('Access denied. This page is only for Financial Institutions.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../finance_profile.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$institution_name = trim($_POST['institution_name'] ?? '');
$institution_type = trim($_POST['institution_type'] ?? '');
$registration_number = trim($_POST['registration_number'] ?? '');
$location = trim($_POST['location'] ?? '');
$services = trim($_POST['services'] ?? '');
$contact_person = trim($_POST['contact_person'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($institution_name === '' || $institution_type === '' || $location === '' || $contact_person === '') {
    http_response_code(422);
    exit('Please complete the required institution name, type, location and contact person fields.');
}

/*
 * The database export contains `financial_profiles`, not `finance_profiles`.
 * The form fields are mapped to columns that already exist in financial_profiles.
 * Existing records are updated; no profile data is deleted.
 */
try {
$check = $conn->prepare('SELECT id FROM financial_profiles WHERE user_id = ? LIMIT 1');
if (!$check) {
    error_log('VAYUMA finance profile lookup failed.');
    http_response_code(500);
    exit('The financial profile could not be loaded. Please contact VAYUMA support.');
}
$check->bind_param('i', $user_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if ($existing) {
    $profile_id = (int) $existing['id'];
    $stmt = $conn->prepare(
        'UPDATE financial_profiles
         SET institution_name = ?, institution_type = ?, registration_number = ?,
             location = ?, services = ?, contact_person = ?, description = ?
         WHERE id = ? AND user_id = ?'
    );
    if (!$stmt) {
        error_log('VAYUMA finance profile update prepare failed.');
        http_response_code(500);
        exit('The financial profile could not be prepared for updating.');
    }
    $stmt->bind_param(
        'sssssssii',
        $institution_name,
        $institution_type,
        $registration_number,
        $location,
        $services,
        $contact_person,
        $description,
        $profile_id,
        $user_id
    );
} else {
    $stmt = $conn->prepare(
        'INSERT INTO financial_profiles
         (user_id, institution_name, institution_type, registration_number,
          location, services, contact_person, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        error_log('VAYUMA finance profile insert prepare failed.');
        http_response_code(500);
        exit('The financial profile could not be prepared for saving.');
    }
    $stmt->bind_param(
        'isssssss',
        $user_id,
        $institution_name,
        $institution_type,
        $registration_number,
        $location,
        $services,
        $contact_person,
        $description
    );
}

if (!$stmt->execute()) {
    error_log('VAYUMA finance profile save failed.');
    $stmt->close();
    http_response_code(500);
    exit('Unable to save the financial institution profile. Please try again.');
}
$stmt->close();

} catch (Throwable $e) {
    error_log('VAYUMA finance profile save failed. code=' . (int)$e->getCode());
    http_response_code(500);
    exit('Unable to save the financial institution profile. Please try again.');
}

$_SESSION['profile_completed'] = true;
header('Location: ../finance_dashboard.php');
exit;
