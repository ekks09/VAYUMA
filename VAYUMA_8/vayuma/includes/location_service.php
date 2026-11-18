<?php
/**
 * Save a user's registered location after validating the county/sub-county/ward chain.
 * Call after profile data has passed validation:
 * vayuma_save_registered_location($conn, $userId, $_POST);
 */
function vayuma_save_registered_location(mysqli $conn, int $userId, array $input): void {
    $countyId = (int)($input['county_id'] ?? 0);
    $subCountyId = (int)($input['sub_county_id'] ?? 0);
    $wardId = (int)($input['ward_id'] ?? 0);
    $village = trim((string)($input['village_estate'] ?? ''));

    if ($userId < 1 || !$countyId || !$subCountyId || !$wardId) {
        throw new InvalidArgumentException('Please select your county, sub-county and ward.');
    }

    $check = $conn->prepare(
        "SELECT c.id
         FROM administrative_units c
         INNER JOIN administrative_units s ON s.parent_id=c.id AND s.unit_type='sub_county'
         INNER JOIN administrative_units w ON w.parent_id=s.id AND w.unit_type='ward'
         WHERE c.id=? AND c.unit_type='county'
           AND s.id=? AND w.id=?
           AND c.is_active=1 AND s.is_active=1 AND w.is_active=1"
    );
    if (!$check) throw new RuntimeException('Location validation could not be prepared.');
    $check->bind_param('iii', $countyId, $subCountyId, $wardId);
    $check->execute();
    $valid = $check->get_result()->fetch_assoc();
    $check->close();
    if (!$valid) throw new InvalidArgumentException('The selected county, sub-county and ward do not match.');

    $stmt = $conn->prepare(
        "INSERT INTO user_locations (user_id,county_id,sub_county_id,ward_id,village_estate)
         VALUES (?,?,?,?,?)
         ON DUPLICATE KEY UPDATE county_id=VALUES(county_id),
           sub_county_id=VALUES(sub_county_id), ward_id=VALUES(ward_id),
           village_estate=VALUES(village_estate)"
    );
    if (!$stmt) throw new RuntimeException('Location save could not be prepared.');
    $stmt->bind_param('iiiis', $userId, $countyId, $subCountyId, $wardId, $village);
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Location could not be saved: '.$error);
    }
    $stmt->close();
}
