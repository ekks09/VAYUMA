<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/farmer_identity.php';
$staffId = (int)($_SESSION['user_id'] ?? 0);
$key = getenv('VAYUMA_STAFF_ONBOARD_KEY');
$fingerprint = is_string($key) && $key !== '' ? hash('sha256', $key) : '';
unset($key);
if (($_SESSION['logged_in'] ?? false) !== true || $staffId <= 0 ||
    ($_SESSION['onboarding_unlocked_user_id'] ?? null) !== $staffId || $fingerprint === '' ||
    !is_string($_SESSION['onboarding_key_fingerprint'] ?? null) ||
    !hash_equals($fingerprint, $_SESSION['onboarding_key_fingerprint'])) {
    http_response_code(403); exit('Unlock Staff Access to download this report.');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !vayuma_csrf_valid()) {
    http_response_code(403); exit('Request could not be verified.');
}
require_once __DIR__ . '/config/db.php';
// Prefix spreadsheet formula-like cells, including whitespace/control-prefixed
// formulae. Prefix phone/ID explicitly so leading zeroes survive spreadsheet use.
function vayuma_csv_cell($value, bool $text = false): string {
    $value = (string)($value ?? '');
    if ($value !== '' && ($text || preg_match('/^[\x00-\x20]*[=+@\-\t\r]/u', $value))) return "'" . $value;
    return $value;
}
try {
    // A claimed onboarding record is merged into its registered account.
    // Unclaimed staff records remain visible and are labelled, not described as accounts.
    $sql = "SELECT 'Registered account' AS record_type,u.id AS account_id,u.full_name,u.phone,u.email,u.created_at,
       COALESCE(NULLIF(f.county,''),r.county) county, f.sub_county,
       COALESCE(NULLIF(f.farm_location,''),r.farmer_location) location,
       COALESCE(f.farmer_id_ciphertext,r.farmer_id_ciphertext) id_cipher,
       f.farm_name, COALESCE(NULLIF(f.main_crops,''),r.activity) activity,
       COALESCE(f.farm_size,r.farm_size) farm_size, COALESCE(f.farm_size_unit,r.farm_size_unit) farm_size_unit,
       r.preferred_language,r.created_at AS onboarded_at, staff.full_name AS onboarded_by,
       f.farming_experience,f.soil_type,f.irrigation,f.secondary_crop,u.role AS current_role
      FROM users u
      LEFT JOIN farmer_profiles f ON f.id=(SELECT fp.id FROM farmer_profiles fp WHERE fp.user_id=u.id ORDER BY fp.id LIMIT 1)
      LEFT JOIN onboarding_records r ON r.claimed_by_user_id=u.id AND r.stakeholder_role='farmer'
      LEFT JOIN users staff ON staff.id=r.onboarded_by_user_id
      WHERE LOWER(TRIM(u.role))='farmer' OR r.id IS NOT NULL OR f.id IS NOT NULL
      UNION ALL
      SELECT 'Staff record not claimed',NULL,r.full_name,r.phone_identity,NULL,NULL,r.county,NULL,r.farmer_location,
       r.farmer_id_ciphertext,NULL,r.activity,r.farm_size,r.farm_size_unit,r.preferred_language,r.created_at,staff.full_name,NULL,NULL,NULL,NULL,NULL
      FROM onboarding_records r LEFT JOIN users staff ON staff.id=r.onboarded_by_user_id
      WHERE r.stakeholder_role='farmer' AND r.claimed_by_user_id IS NULL";
    $result = $conn->query($sql);
    if (!$result) throw new RuntimeException('Report unavailable');
    $out = fopen('php://memory', 'w+');
    if (!$out) throw new RuntimeException('Report unavailable');
    fputcsv($out, ['Record type','Account ID','Full name','Phone','Email','Account created','County','Sub-county','Farm or village location','National ID number','Farm name','Crops or livestock','Farm size','Size unit','Preferred language','Onboarded at','Onboarded by','Farming experience','Soil type','Irrigation','Secondary crop','Current role'], ',', '"', '');
    while ($row = $result->fetch_assoc()) {
        $id = vayuma_farmer_id_decrypt($row['id_cipher']);
        $values = [$row['record_type'],$row['account_id'],$row['full_name'],$row['phone'],$row['email'],$row['created_at'],$row['county'],$row['sub_county'],$row['location'],$id,$row['farm_name'],$row['activity'],$row['farm_size'],$row['farm_size_unit'],$row['preferred_language'],$row['onboarded_at'],$row['onboarded_by'],$row['farming_experience'],$row['soil_type'],$row['irrigation'],$row['secondary_crop'],$row['current_role']];
        foreach ($values as $i => $value) $values[$i] = vayuma_csv_cell($value, $i === 3 || $i === 9);
        if (fputcsv($out, $values, ',', '"', '') === false) throw new RuntimeException('Report unavailable');
    }
    rewind($out);
    error_log('VAYUMA farmer CSV prepared for staff account ' . $staffId);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="vayuma-farmers.csv"');
    echo "\xEF\xBB\xBF";
    fpassthru($out); fclose($out);
} catch (Throwable $e) {
    error_log('VAYUMA farmer CSV export unavailable.');
    http_response_code(503); exit('Farmer export is temporarily unavailable. No file was prepared. Please return to Staff Access and try again. <a href="onboarding.php#records">Back to Staff Access</a>');
}
