<?php
/** Until an assignment system exists, access is limited to farmers who sent this
 * officer an accepted, in-progress or completed extension support request. */
function vayuma_extension_farmers($conn, int $officer, string $search = '', int $offset = 0): array {
    $sql = "SELECT u.id,u.full_name,fp.farm_name,fp.county,fp.sub_county,fp.farm_location,fp.main_crops FROM users u LEFT JOIN farmer_profiles fp ON fp.id=(SELECT MIN(fp2.id) FROM farmer_profiles fp2 WHERE fp2.user_id=u.id) WHERE LOWER(TRIM(u.role))='farmer' AND EXISTS (SELECT 1 FROM extension_support_requests r WHERE r.provider_id=? AND r.requester_id=u.id AND r.status IN ('accepted','in_progress','completed')) AND (u.full_name LIKE ? OR fp.farm_name LIKE ? OR fp.main_crops LIKE ? OR fp.farm_location LIKE ?) ORDER BY u.full_name,u.id LIMIT 51 OFFSET ?";
    $like='%'.$search.'%'; $st=$conn->prepare($sql);
    if (!$st) throw new RuntimeException('Farmer lookup prepare failed');
    $st->bind_param('issssi',$officer,$like,$like,$like,$like,$offset);
    if (!$st->execute() || !($result=$st->get_result())) throw new RuntimeException('Farmer lookup failed');
    $rows=$result->fetch_all(MYSQLI_ASSOC); $st->close(); return $rows;
}
function vayuma_extension_farmer_name($conn, int $officer, int $farmer): ?string {
    $st=$conn->prepare("SELECT u.full_name FROM users u WHERE u.id=? AND LOWER(TRIM(u.role))='farmer' AND EXISTS (SELECT 1 FROM extension_support_requests r WHERE r.provider_id=? AND r.requester_id=u.id AND r.status IN ('accepted','in_progress','completed')) LIMIT 1");
    if (!$st) throw new RuntimeException('Farmer authorization prepare failed');
    $st->bind_param('ii',$farmer,$officer);
    if (!$st->execute() || !($result=$st->get_result())) throw new RuntimeException('Farmer authorization failed');
    $row=$result->fetch_assoc(); $st->close(); return $row ? (string)$row['full_name'] : null;
}
function vayuma_extension_visit_matches($conn, int $officer, int $visit, int $farmer): bool {
    $st=$conn->prepare('SELECT farmer_id FROM extension_field_visits WHERE id=? AND extension_officer_id=? LIMIT 1');
    if (!$st) throw new RuntimeException('Visit authorization prepare failed');
    $st->bind_param('ii',$visit,$officer);
    if (!$st->execute() || !($result=$st->get_result())) throw new RuntimeException('Visit authorization failed');
    $row=$result->fetch_assoc(); $st->close();
    return $row !== null && (int)($row['farmer_id'] ?? 0) === $farmer;
}
