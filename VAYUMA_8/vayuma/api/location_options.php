<?php
// GET /api/location_options.php?type=county
// GET /api/location_options.php?type=sub_county&parent_id=...
// GET /api/location_options.php?type=ward&parent_id=...
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

$type = $_GET['type'] ?? '';
$parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

if (!in_array($type, ['county', 'sub_county', 'ward'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid location type.']);
    exit;
}

if ($type === 'county') {
    $stmt = $conn->prepare("SELECT id, name FROM administrative_units WHERE unit_type='county' AND parent_id IS NULL AND is_active=1 ORDER BY name");
} else {
    if ($parentId < 1) {
        echo json_encode(['success' => true, 'items' => []]);
        exit;
    }
    $expectedParentType = $type === 'sub_county' ? 'county' : 'sub_county';
    $check = $conn->prepare("SELECT id FROM administrative_units WHERE id=? AND unit_type=? AND is_active=1");
    $check->bind_param('is', $parentId, $expectedParentType);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parent location.']);
        exit;
    }
    $stmt = $conn->prepare("SELECT id, name FROM administrative_units WHERE unit_type=? AND parent_id=? AND is_active=1 ORDER BY name");
    $stmt->bind_param('si', $type, $parentId);
}
if (!$stmt || !$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load locations.']);
    exit;
}
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = ['id' => (int)$row['id'], 'name' => $row['name']];
}
echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
