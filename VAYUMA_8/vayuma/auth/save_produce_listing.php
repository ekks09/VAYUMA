<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../add_produce.php');
    exit;
}

function vayuma_fail_produce_listing(string $message): void
{
    $_SESSION['produce_listing_error'] = $message;
    header('Location: ../add_produce.php');
    exit;
}

if (!vayuma_csrf_valid()) {
    vayuma_fail_produce_listing('Your session expired. Refresh the form and try again.');
}

$farmerId = (int)$_SESSION['user_id'];
$postString = static function (string $key): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};
$produceName = $postString('produce_name');
$categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$quantityInput = $postString('quantity');
$unit = $postString('unit');
$priceInput = $postString('price_per_unit');
$location = $postString('location');
$harvestDate = $postString('harvest_date');
$description = $postString('description');

if ($produceName === '' || $categoryId === false || $categoryId === null || $quantityInput === '' || $unit === '' || $priceInput === '' || $location === '') {
    vayuma_fail_produce_listing('Complete all required fields before publishing your listing.');
}

if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $quantityInput) || (float)$quantityInput <= 0) {
    vayuma_fail_produce_listing('Enter a quantity greater than zero with no more than two decimal places.');
}

if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $priceInput)) {
    vayuma_fail_produce_listing('Enter a valid price with no more than two decimal places.');
}

if ($harvestDate !== '') {
    $date = DateTime::createFromFormat('!Y-m-d', $harvestDate);
    if (!$date || $date->format('Y-m-d') !== $harvestDate) {
        vayuma_fail_produce_listing('Choose a valid harvest date.');
    }
} else {
    $harvestDate = null;
}

try {
    $categoryStmt = $conn->prepare('SELECT name FROM produce_categories WHERE id = ? AND is_active = 1 LIMIT 1');
    if (!$categoryStmt) {
        throw new mysqli_sql_exception('Unable to prepare active category lookup.');
    }
    $categoryStmt->bind_param('i', $categoryId);
    $categoryStmt->execute();
    $categoryName = (string)($categoryStmt->get_result()->fetch_assoc()['name'] ?? '');
    $categoryStmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Produce category validation failed: ' . $exception->getMessage());
    vayuma_fail_produce_listing('We could not verify the selected category. Please try again.');
}

if ($categoryName === '') {
    vayuma_fail_produce_listing('Choose an active produce category.');
}

$imagePath = null;
$uploadedFile = null;
if (isset($_FILES['image'])) {
    if (!is_array($_FILES['image']) || !isset($_FILES['image']['error'])) {
        vayuma_fail_produce_listing('The image upload is invalid. Choose the file again and retry.');
    }
    $file = $_FILES['image'];
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        $file = null;
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        vayuma_fail_produce_listing('The image could not be uploaded. Choose it again and retry.');
    }
    if ($file !== null && (!isset($file['tmp_name'], $file['size']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name']))) {
        vayuma_fail_produce_listing('The image upload is invalid. Choose the file again and retry.');
    }
    if ($file !== null && (int)$file['size'] > 5 * 1024 * 1024) {
        vayuma_fail_produce_listing('The produce image must be 5 MB or smaller.');
    }

    if ($file !== null) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!is_string($mime) || !isset($allowedTypes[$mime])) {
            vayuma_fail_produce_listing('Use a JPG, PNG, or WEBP image.');
        }

        $uploadDirectory = dirname(__DIR__) . '/uploads/produce/';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            error_log('Unable to create produce upload directory.');
            vayuma_fail_produce_listing('The image could not be saved right now. Please try again without the image or retry later.');
        }

        $filename = 'produce_' . $farmerId . '_' . bin2hex(random_bytes(12)) . '.' . $allowedTypes[$mime];
        $uploadedFile = $uploadDirectory . $filename;
        if (!move_uploaded_file($file['tmp_name'], $uploadedFile)) {
            vayuma_fail_produce_listing('The image could not be saved. Choose it again and retry.');
        }
        $imagePath = 'uploads/produce/' . $filename;
    }
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO produce_listings
            (farmer_id, produce_name, category, category_id, quantity, unit, price_per_unit,
             location, harvest_date, description, image, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')"
    );
    if (!$stmt) {
        throw new mysqli_sql_exception('Unable to prepare produce listing insert.');
    }

    $quantity = (float)$quantityInput;
    $pricePerUnit = (float)$priceInput;
    $stmt->bind_param(
        'issidsdssss',
        $farmerId,
        $produceName,
        $categoryName,
        $categoryId,
        $quantity,
        $unit,
        $pricePerUnit,
        $location,
        $harvestDate,
        $description,
        $imagePath
    );
    $stmt->execute();
    $stmt->close();
} catch (mysqli_sql_exception $exception) {
    error_log('Produce listing insert failed: ' . $exception->getMessage());
    if ($uploadedFile !== null && is_file($uploadedFile)) {
        unlink($uploadedFile);
    }
    vayuma_fail_produce_listing('Your listing could not be saved. Please review the details and try again.');
}

$_SESSION['produce_listing_success'] = true;
header('Location: ../my_produce.php');
exit;
