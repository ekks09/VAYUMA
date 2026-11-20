<?php
session_start();
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !vayuma_csrf_valid()) {
    http_response_code(403);
    exit('Your session expired. Refresh the profile form and try again.');
}
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: ../login.php'); exit;
}
if (($_SESSION['role'] ?? '') !== 'farmer') { http_response_code(403); exit('Access denied.'); }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: ../profile_setup.php'); exit; }
require_once __DIR__ . '/../config/db.php';

$limits = ['farm_name'=>100, 'county'=>100, 'sub_county'=>100, 'farm_location'=>255,
    'farm_size'=>50, 'farm_size_unit'=>50, 'farming_experience'=>100,
    'main_crops'=>255, 'soil_type'=>100, 'irrigation'=>100, 'secondary_crop'=>50];
$data = [];
foreach ($limits as $key => $max) {
    $value = $_POST[$key] ?? '';
    $data[$key] = is_string($value) ? trim($value) : '';
    if (($key !== 'secondary_crop' && $data[$key] === '') || mb_strlen($data[$key], 'UTF-8') > $max) {
        $_SESSION['farm_profile_error'] = 'Please complete all required fields within their length limits.';
        $_SESSION['farm_profile_old'] = $data + array_intersect_key($_POST, $limits);
        header('Location: ../profile_setup.php'); exit;
    }
}
$userId = (int) $_SESSION['user_id'];
try {
    $conn->begin_transaction();
    // Serialize saves for this user even though the attached schema lacks a unique user_id index.
    $lock = $conn->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');
    $lock->bind_param('i', $userId); $lock->execute();
    $user = $lock->get_result()->fetch_assoc(); $lock->close();
    if (!$user) throw new RuntimeException('User unavailable');
    $lookup = $conn->prepare('SELECT id FROM farmer_profiles WHERE user_id=? ORDER BY id LIMIT 2');
    $lookup->bind_param('i', $userId); $lookup->execute();
    $result = $lookup->get_result(); $existing = $result->fetch_assoc(); $duplicate = $result->fetch_assoc(); $lookup->close();
    if ($duplicate) {
        $conn->rollback();
        $_SESSION['farm_profile_error'] = 'Multiple farm profiles were found. Please contact support before editing.';
        header('Location: ../profile_setup.php'); exit;
    }
    if ($existing) {
        $stmt = $conn->prepare('UPDATE farmer_profiles SET farm_name=?, county=?, sub_county=?, farm_location=?, farm_size=?, farm_size_unit=?, farming_experience=?, main_crops=?, soil_type=?, irrigation=?, secondary_crop=? WHERE id=? AND user_id=?');
        $id = (int)$existing['id'];
        $stmt->bind_param('sssssssssssii', $data['farm_name'], $data['county'], $data['sub_county'], $data['farm_location'], $data['farm_size'], $data['farm_size_unit'], $data['farming_experience'], $data['main_crops'], $data['soil_type'], $data['irrigation'], $data['secondary_crop'], $id, $userId);
    } else {
        $stmt = $conn->prepare('INSERT INTO farmer_profiles (user_id,farm_name,county,sub_county,farm_location,farm_size,farm_size_unit,farming_experience,main_crops,soil_type,irrigation,secondary_crop) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->bind_param('isssssssssss', $userId, $data['farm_name'], $data['county'], $data['sub_county'], $data['farm_location'], $data['farm_size'], $data['farm_size_unit'], $data['farming_experience'], $data['main_crops'], $data['soil_type'], $data['irrigation'], $data['secondary_crop']);
    }
    if (!$stmt->execute()) throw new RuntimeException('Profile save failed');
    $stmt->close(); $conn->commit();
    unset($_SESSION['farm_profile_old']);
    header('Location: ../farmer_dashboard.php'); exit;
} catch (Throwable $e) {
    try { $conn->rollback(); } catch (Throwable $ignored) {}
    error_log('VAYUMA farm profile save failed.');
    $_SESSION['farm_profile_error'] = 'Unable to save your farm profile. Please try again.';
    $_SESSION['farm_profile_old'] = $data;
    header('Location: ../profile_setup.php'); exit;
}
