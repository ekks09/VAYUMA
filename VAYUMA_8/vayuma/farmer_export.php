<?php
ob_start();
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/farmer_identity.php';
require_once __DIR__ . '/includes/onboarding_search.php';
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
// Protect spreadsheet consumers and preserve leading zeroes in phone and ID fields.
function vayuma_csv_cell($value, bool $text = false): string {
    $value = (string)($value ?? '');
    if ($value !== '' && ($text || preg_match('/^[\x00-\x20]*[=+@\-\t\r]/u', $value))) return "'" . $value;
    return $value;
}
$stage = 'search';
$out = null;
try {
    $search = is_string($_POST['search_query'] ?? null) ? $_POST['search_query'] : '';
    $tokens = vayuma_onboarding_search_tokens($search);
    $preview = ($_POST['mode'] ?? '') === 'preview';
    // Separate statements avoid UNION type/collation coercion across legacy
    // profile and onboarding columns. A query failure is never an empty report.
    $queries = [
      "SELECT 'Registered account' AS record_type,u.id AS account_id,u.full_name,u.phone,u.email,u.created_at,
       COALESCE(NULLIF(f.county,''),r.county) AS county, f.sub_county,
       COALESCE(NULLIF(f.farm_location,''),r.farmer_location) AS location,
       COALESCE(f.farmer_id_ciphertext,r.farmer_id_ciphertext) AS id_cipher,
       f.farm_name, COALESCE(NULLIF(f.main_crops,''),r.activity) AS activity,
       COALESCE(f.farm_size,r.farm_size) AS farm_size, COALESCE(f.farm_size_unit,r.farm_size_unit) AS farm_size_unit,
       r.preferred_language,r.created_at AS onboarded_at, staff.full_name AS onboarded_by,
       f.farming_experience,f.soil_type,f.irrigation,f.secondary_crop,u.role AS current_role
       FROM users u LEFT JOIN farmer_profiles f ON f.user_id=u.id
       LEFT JOIN onboarding_records r ON r.claimed_by_user_id=u.id AND r.stakeholder_role='farmer'
       LEFT JOIN users staff ON staff.id=r.onboarded_by_user_id
       WHERE LOWER(TRIM(u.role))='farmer' OR r.id IS NOT NULL OR f.id IS NOT NULL",
      "SELECT 'Staff record not claimed' AS record_type,NULL AS account_id,r.full_name,r.phone_identity AS phone,
       NULL AS email,NULL AS created_at,r.county,NULL AS sub_county,r.farmer_location AS location,
       r.farmer_id_ciphertext AS id_cipher,NULL AS farm_name,r.activity,r.farm_size,r.farm_size_unit,
       r.preferred_language,r.created_at AS onboarded_at,staff.full_name AS onboarded_by,
       NULL AS farming_experience,NULL AS soil_type,NULL AS irrigation,NULL AS secondary_crop,NULL AS current_role
       FROM onboarding_records r LEFT JOIN users staff ON staff.id=r.onboarded_by_user_id
       WHERE r.stakeholder_role='farmer' AND r.claimed_by_user_id IS NULL"
    ];
    $stage = 'prepare_report';
    $out = fopen('php://temp', 'w+');
    if (!$out) throw new RuntimeException('Report stream unavailable');
    $headers = ['Record type','Account ID','Full name','Phone','Email','Account created','County','Sub-county','Farm or village location','National ID number','ID status','Farm name','Crops or livestock','Farm size','Size unit','Preferred language','Onboarded at','Onboarded by','Farming experience','Soil type','Irrigation','Secondary crop','Current role'];
    if (fputcsv($out, $headers, ',', '"', '') === false) throw new RuntimeException('Report write failed');
    $total = 0; $unavailableIds = 0;
    foreach ($queries as $index => $sql) {
        $stage = $index === 0 ? 'registered_query' : 'staff_records_query';
        $result = $conn->query($sql);
        if (!$result) throw new RuntimeException('Report query failed');
        while ($row = $result->fetch_assoc()) {
            if ($tokens && !vayuma_onboarding_matches($row, $tokens)) continue;
            $id = ''; $idStatus = 'Not recorded';
            if (!empty($row['id_cipher'])) {
                try { $id = vayuma_farmer_id_decrypt($row['id_cipher']); $idStatus = 'Available'; }
                catch (Throwable $idError) { $idStatus = 'Unavailable'; $unavailableIds++; }
            }
            $values = [$row['record_type'],$row['account_id'],$row['full_name'],$row['phone'],$row['email'],$row['created_at'],$row['county'],$row['sub_county'],$row['location'],$id,$idStatus,$row['farm_name'],$row['activity'],$row['farm_size'],$row['farm_size_unit'],$row['preferred_language'],$row['onboarded_at'],$row['onboarded_by'],$row['farming_experience'],$row['soil_type'],$row['irrigation'],$row['secondary_crop'],$row['current_role']];
            foreach ($values as $i => $value) $values[$i] = vayuma_csv_cell($value, $i === 3 || $i === 9);
            $stage = 'write_report';
            if (fputcsv($out, $values, ',', '"', '') === false) throw new RuntimeException('Report write failed');
            $total++;
        }
        if (method_exists($result, 'free')) $result->free();
    }
    $stage = 'deliver';
    rewind($out);
    error_log('VAYUMA farmer CSV prepared; unavailable ID count: ' . $unavailableIds);
    if (headers_sent()) throw new RuntimeException('Headers already sent');
    while (ob_get_level() > 0) ob_end_clean();
    if (!$preview) {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="vayuma-farmers.csv"');
        echo "\xEF\xBB\xBF";
        fpassthru($out); fclose($out); exit;
    }
    header('Content-Type: text/html; charset=UTF-8');
    $h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    $head = fgetcsv($out, 0, ',', '"', '');
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Farmer CSV preview | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/onboarding.css?v=12"><style>.export-preview{overflow:auto;max-width:100%}.export-preview table{border-collapse:collapse;font-size:14px}.export-preview th,.export-preview td{padding:8px;border:1px solid #dceae3;min-width:110px;text-align:left;vertical-align:top}.export-preview th{background:#edf7f0}.export-preview-page .onboard-actions{margin:16px 0}</style></head><body><main class="onboard export-preview-page"><section class="onboard-card"><h1>Farmer CSV preview</h1><p><?= (int)$total ?> matching farmer record<?= $total === 1 ? '' : 's' ?>. Showing up to 50 rows; the download includes all matches.</p><?php if ($unavailableIds): ?><p class="onboard-error" role="status"><?= (int)$unavailableIds ?> national ID value(s) could not be decrypted. The report marks them as unavailable; contact an administrator to check the encryption configuration.</p><?php endif; ?><p><small>Confidential personal data. Use a trusted device. The downloaded report is prepared again and may reflect newer changes.</small></p><div class="onboard-actions"><form method="post" action="farmer_export.php"><?= vayuma_csrf_field() ?><input type="hidden" name="search_query" value="<?= $h($search) ?>"><input type="hidden" name="mode" value="download"><button type="submit">Download CSV</button></form><a href="onboarding.php#records">Back to records</a></div><?php if ($total === 0): ?><p>No matching farmer records.</p><?php else: ?><div class="export-preview"><table><thead><tr><?php foreach ($head as $cell): ?><th scope="col"><?= $h($cell) ?></th><?php endforeach; ?></tr></thead><tbody><?php for ($i=0; $i<50 && ($cells=fgetcsv($out, 0, ',', '"', '')) !== false; $i++): ?><tr><?php foreach ($cells as $cell): ?><td><?= $h($cell) ?></td><?php endforeach; ?></tr><?php endfor; ?></tbody></table></div><?php endif; ?></section></main></body></html><?php
    fclose($out);
} catch (Throwable $e) {
    if (is_resource($out)) fclose($out);
    // Stage and exception class are useful for an administrator without logging
    // SQL, credentials, farmer details, or ciphertext.
    $errorCode = (int)$e->getCode();
    $sqlState = ($e instanceof mysqli_sql_exception) ? $e->getSqlState() : '';
    if (!is_string($sqlState) || !preg_match('/^[A-Z0-9]{5}$/D', $sqlState)) $sqlState = '';
    error_log('VAYUMA farmer CSV export failed at ' . $stage . ' (' . get_class($e) . ', code ' . $errorCode . ($sqlState !== '' ? ', SQLSTATE ' . $sqlState : '') . ').');
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(503); exit('Farmer export is temporarily unavailable. No file was prepared. Please return to Staff Access and try again. <a href="onboarding.php#records">Back to Staff Access</a>');
}
