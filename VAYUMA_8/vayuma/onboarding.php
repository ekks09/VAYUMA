<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
require_once __DIR__ . '/includes/onboarding_policy.php';
require_once __DIR__ . '/includes/farmer_identity.php';
require_once __DIR__ . '/includes/login_identity.php';
if (($_SESSION['logged_in'] ?? false) !== true || !isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$staffId = (int)$_SESSION['user_id'];
$vayumaDbReadinessProbe = true;
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/onboarding_lock.php';
// Unlock is scoped to the signed-in account and invalidated if the configured key changes.
$staffKey = getenv('VAYUMA_STAFF_ONBOARD_KEY');
$keyFingerprint = is_string($staffKey) && $staffKey !== '' ? hash('sha256', $staffKey) : '';
unset($staffKey);
if (($_SESSION['onboarding_unlocked_user_id'] ?? null) !== $staffId || $keyFingerprint === '' || !is_string($_SESSION['onboarding_key_fingerprint'] ?? null) || !hash_equals($keyFingerprint, $_SESSION['onboarding_key_fingerprint'])) {
    unset($_SESSION['onboarding_unlocked_user_id'], $_SESSION['onboarding_key_fingerprint']);
    $unlockError = '';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!vayuma_csrf_valid()) { http_response_code(403); $unlockError = 'Your session expired. Refresh and try again.'; }
        else {
            $passphrase = is_string($_POST['passphrase'] ?? null) ? $_POST['passphrase'] : '';
            try {
                $result = vayuma_onboarding_unlock_attempt($conn, $staffId, $passphrase);
                unset($passphrase, $_POST['passphrase']);
                if ($result === 'ok') {
                    if (!session_regenerate_id(true)) throw new RuntimeException('Session unavailable');
                    $_SESSION['onboarding_unlocked_user_id'] = $staffId;
                    $_SESSION['onboarding_key_fingerprint'] = $keyFingerprint;
                    header('Location: onboarding.php'); exit;
                }
                if ($result === 'limited') { http_response_code(429); $unlockError = 'Too many attempts. Try again after 15 minutes.'; }
                else { http_response_code(403); $unlockError = 'Passphrase not accepted.'; }
            } catch (Throwable $e) { unset($passphrase, $_POST['passphrase']); error_log('VAYUMA staff unlock unavailable.'); http_response_code(503); $unlockError = 'Staff access is temporarily unavailable.'; }
        }
    }
    $h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    ?><!doctype html><html lang="<?= $h($current_language) ?>"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover, interactive-widget=resizes-content"><title>Staff Access | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/onboarding.css?v=7"></head><body><?php vayuma_language_selector(); ?><main class="onboard onboard--unlock"><?php require __DIR__ . '/includes/workspace_back.php'; ?><header class="onboard-header"><div><span class="onboard-eyebrow">VAYUMA TRIX · STAFF ACCESS</span><h1>Staff Access</h1><p>For authorized onboarding staff</p></div></header><section class="onboard-card"><h2>Unlock onboarding</h2><p>Enter the staff passphrase to unlock this session. Your existing account remains signed in.</p><?php if ($unlockError): ?><p class="onboard-error" role="alert"><?= $h($unlockError) ?></p><?php endif; ?><form method="post"><?= vayuma_csrf_field() ?><label for="staff-passphrase">Staff passphrase<input id="staff-passphrase" name="passphrase" type="password" autocomplete="off" maxlength="256" required></label><button type="button" class="onboard-show-key" id="onboard-show-key" aria-controls="staff-passphrase" aria-pressed="false" hidden>Show passphrase</button><p><button type="submit">Unlock staff access</button></p></form></section></main><script src="assets/js/onboarding-workspace.js?v=5" defer></script></body></html><?php
    exit;
}
// Staff can lock the portal without signing out of their personal account.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'lock_staff') {
    if (!vayuma_csrf_valid()) { http_response_code(403); exit('Your session expired. Refresh and try again.'); }
    unset($_SESSION['onboarding_unlocked_user_id'], $_SESSION['onboarding_key_fingerprint'], $_SESSION['onboarding_claim_code'], $_SESSION['onboarding_claim_name']);
    header('Location: onboarding.php'); exit;
}
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$sw = $current_language === 'sw';
$tr = static fn($en, $kiswahili) => $sw ? $kiswahili : $en;
$roles = ['farmer','consumer','buyer','transport_provider','input_supplier','manufacturer','warehouse_provider','retailer','agribusiness','cooperative','researcher','extension_officer','veterinary_officer','financial_institution','insurance_provider','government_officer','ngo_partner','agricultural_service_provider','strategic_partner'];
unset($_SESSION['onboarding_claim_code'], $_SESSION['onboarding_claim_name']);
$error = ''; $notice = (string)($_SESSION['onboarding_notice'] ?? ''); unset($_SESSION['onboarding_notice']);
$old = []; $searchPhone = null;
try {
    $countyResult = $conn->query("SELECT name FROM administrative_units WHERE unit_type='county' ORDER BY name");
    if (!$countyResult) throw new RuntimeException('Counties unavailable');
    $counties = [];
    while ($row = $countyResult->fetch_assoc()) $counties[] = (string)$row['name'];
    if (!$counties) throw new RuntimeException('Counties unavailable');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $old = $_POST; unset($old['farmer_id']);
        if (!vayuma_csrf_valid()) throw new DomainException('Your session expired. Refresh and try again.');
        if (in_array(($_POST['action'] ?? ''), ['reset_claim', 'update_farmer_id'], true)) {
            $recordText = $_POST['record_id'] ?? '';
            if (!is_string($recordText) || !ctype_digit($recordText) || (int)$recordText < 1) throw new DomainException('Choose a valid onboarding record.');
            $recordId = (int)$recordText;
            if (($_POST['action'] ?? '') === 'update_farmer_id') {
                $newId = is_string($_POST['farmer_id'] ?? null) ? trim($_POST['farmer_id']) : '';
                if (!vayuma_farmer_id_valid($newId)) throw new DomainException('Enter a valid farmer ID number (5 to 20 digits).');
                $cipher = vayuma_farmer_id_encrypt($newId); unset($newId);
                $update = $conn->prepare("UPDATE onboarding_records SET farmer_id_ciphertext=?, claim_attempts=0 WHERE id=? AND stakeholder_role='farmer' AND claimed_by_user_id IS NULL");
                if (!$update) throw new RuntimeException('Record unavailable');
                $update->bind_param('si', $cipher, $recordId);
            } else {
                $update = $conn->prepare("UPDATE onboarding_records SET claim_attempts=0 WHERE id=? AND stakeholder_role='farmer' AND claimed_by_user_id IS NULL");
                if (!$update) throw new RuntimeException('Record unavailable');
                $update->bind_param('i', $recordId);
            }
            if (!$update->execute()) throw new RuntimeException('Record unavailable');
            $changed = $update->affected_rows; $update->close();
            // A reset of an already unlocked record may change no rows; do not
            // infer that a matching record exists or that a claim succeeded.
            $_SESSION['onboarding_notice'] = $changed === 1 ? 'Record updated.' : 'No change was made. Check that the record is unclaimed.';
            header('Location: onboarding.php#records'); exit;
        }
        if (($_POST['action'] ?? '') === 'find_record') {
            $old = [];
            $searchPhone = vayuma_onboarding_phone(is_string($_POST['search_phone'] ?? null) ? $_POST['search_phone'] : '');
            if ($searchPhone === null) throw new DomainException('Enter a valid phone number to find a record.');
        } else {
        [$data, $validationError] = vayuma_onboarding_validate($_POST, $counties);
        if ($validationError !== null) throw new DomainException($validationError);
        if (!$conn->begin_transaction()) throw new RuntimeException('Transaction unavailable');
        try {
            vayuma_onboarding_lock_phone($conn, $data['phone']);
            // Locking reads use the latest committed data after the shared phone lock.
            [$phone1, $phone2, $phone3] = vayuma_login_phone_variants($data['phone']);
            $check = $conn->prepare("SELECT id FROM users WHERE phone_identity=? OR REPLACE(phone_identity, '.', '') IN (?, ?, ?) LIMIT 1 FOR UPDATE");
            $check->bind_param('ssss', $data['phone'], $phone1, $phone2, $phone3); $check->execute(); $account = $check->get_result()->fetch_assoc(); $check->close();
            $check = $conn->prepare('SELECT r.id, u.full_name AS staff_name FROM onboarding_records r LEFT JOIN users u ON u.id=r.onboarded_by_user_id WHERE r.phone_identity=? LIMIT 1 FOR UPDATE');
            $check->bind_param('s', $data['phone']); $check->execute(); $existing = $check->get_result()->fetch_assoc(); $check->close();
            if ($existing) throw new DomainException(($account ? 'An account already uses this phone number. ' : '') . 'This phone number was already recorded by ' . (trim((string)($existing['staff_name'] ?? '')) ?: 'an onboarding staff member') . '.');
            if ($account) throw new DomainException('An account already uses this phone number. No onboarding record was created.');
            $cipher = $data['role'] === 'farmer' ? vayuma_farmer_id_encrypt($data['farmer_id']) : null;
            $stmt = $conn->prepare('INSERT INTO onboarding_records (full_name,phone_identity,county,stakeholder_role,activity,farm_size,farm_size_unit,preferred_language,onboarded_by_user_id,farmer_id_ciphertext,farmer_location) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('ssssssssiss', $data['full_name'], $data['phone'], $data['county'], $data['role'], $data['activity'], $data['farm_size'], $data['farm_size_unit'], $data['preferred_language'], $staffId, $cipher, $data['farmer_location']);
            if (!$stmt->execute()) throw new RuntimeException('Save failed');
            $stmt->close();
            if (!$conn->commit()) throw new RuntimeException('Commit failed');
            $_SESSION['onboarding_notice'] = 'Record saved. This is not yet a VAYUMA TRIX account.';
            header('Location: onboarding.php#records'); exit;
        } catch (Throwable $e) { try { $conn->rollback(); } catch (Throwable $ignored) {} throw $e; }
        }
    }
} catch (DomainException $e) { $error = $e->getMessage(); }
catch (Throwable $e) { error_log('VAYUMA onboarding unavailable.'); http_response_code(503); $error = 'Onboarding is temporarily unavailable. Please try again.'; $counties = $counties ?? []; }
$view = $searchPhone !== null ? 'records' : (($_GET['view'] ?? '') === 'farmers' ? 'farmers' : 'records');
$pageInput = $_GET['page'] ?? '1';
$page = is_string($pageInput) && ctype_digit($pageInput) ? min(max((int)$pageInput, 1), 1000000) : 1;
$offset = ($page - 1) * 50;
$rows = []; $more = false; $staffCounts = []; $recordsAvailable = true; $staffCountsAvailable = true;
try {
    if ($view === 'farmers') {
        $stmt = $conn->prepare("SELECT u.full_name,u.phone,(SELECT fp.county FROM farmer_profiles fp WHERE fp.user_id=u.id ORDER BY fp.id LIMIT 1) AS county, (SELECT fp.farm_location FROM farmer_profiles fp WHERE fp.user_id=u.id ORDER BY fp.id LIMIT 1) AS farmer_location FROM users u WHERE LOWER(TRIM(u.role))='farmer' ORDER BY u.id DESC LIMIT 51 OFFSET ?");
    } elseif ($searchPhone !== null) {
        $stmt = $conn->prepare('SELECT r.full_name,r.phone_identity,r.county,r.farmer_location,r.stakeholder_role,r.activity,r.farm_size,r.farm_size_unit,r.preferred_language,r.created_at,r.id,r.claimed_by_user_id,u.full_name AS staff_name FROM onboarding_records r LEFT JOIN users u ON u.id=r.onboarded_by_user_id WHERE r.phone_identity=? LIMIT 2');
    } else {
        $stmt = $conn->prepare('SELECT r.full_name,r.phone_identity,r.county,r.farmer_location,r.stakeholder_role,r.activity,r.farm_size,r.farm_size_unit,r.preferred_language,r.created_at,r.id,r.claimed_by_user_id,u.full_name AS staff_name FROM onboarding_records r LEFT JOIN users u ON u.id=r.onboarded_by_user_id ORDER BY r.id DESC LIMIT 51 OFFSET ?');
    }
    if (!$stmt) throw new RuntimeException('Records unavailable');
    if ($searchPhone !== null) $stmt->bind_param('s', $searchPhone); else $stmt->bind_param('i', $offset);
    if (!$stmt->execute()) throw new RuntimeException('Records unavailable');
    $result = $stmt->get_result();
    if (!$result) throw new RuntimeException('Records unavailable');
    while ($row = $result->fetch_assoc()) $rows[] = $row; $stmt->close();
    $more = count($rows) > 50; if ($more) array_pop($rows);
    $result = $conn->query('SELECT u.full_name,COUNT(*) AS total FROM onboarding_records r LEFT JOIN users u ON u.id=r.onboarded_by_user_id GROUP BY r.onboarded_by_user_id,u.full_name ORDER BY total DESC');
    if ($result) while ($row = $result->fetch_assoc()) $staffCounts[] = $row;
    else { $staffCountsAvailable = false; error_log('VAYUMA onboarding staff report unavailable.'); }
} catch (Throwable $e) { $recordsAvailable = false; $staffCountsAvailable = false; error_log('VAYUMA onboarding list unavailable.'); if (!$error) { http_response_code(503); $error = 'Onboarding records are temporarily unavailable.'; } }
$registeredFarmerCount = null; $onboardedFarmerCount = null;
try {
    $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE LOWER(TRIM(role))='farmer'");
    if (!$result || !($row = $result->fetch_assoc())) throw new RuntimeException('Farmer count unavailable');
    $registeredFarmerCount = (int)$row['total'];
} catch (Throwable $e) { error_log('VAYUMA registered farmer count failed: '.$e->getMessage()); }
try {
    $result = $conn->query("SELECT COUNT(*) AS total FROM onboarding_records WHERE stakeholder_role='farmer'");
    if (!$result || !($row = $result->fetch_assoc())) throw new RuntimeException('Onboarding count unavailable');
    $onboardedFarmerCount = (int)$row['total'];
} catch (Throwable $e) { error_log('VAYUMA onboarded farmer count failed: '.$e->getMessage()); }
$val = static fn($key) => is_string($old[$key] ?? null) ? $old[$key] : '';
?><!doctype html><html lang="<?= $h($current_language) ?>"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover, interactive-widget=resizes-content"><title>Onboarding | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/onboarding.css?v=7"></head><body>
<?php vayuma_language_selector(); ?>
<main class="onboard"><?php require __DIR__ . '/includes/workspace_back.php'; ?><header class="onboard-header onboard-header--workspace"><div><span class="onboard-eyebrow">VAYUMA TRIX · STAFF WORKSPACE</span><h1><?= $h($tr('Onboarding workspace','Eneo la usajili')) ?></h1><p><?= $h($tr('For authorized onboarding staff','Kwa wafanyakazi walioidhinishwa')) ?></p></div><div class="onboard-actions"><form method="post"><?= vayuma_csrf_field() ?><input type="hidden" name="action" value="lock_staff"><button type="submit"><?= $h($tr('Lock staff access','Funga ufikiaji wa wafanyakazi')) ?></button></form><a href="auth/logout.php"><?= $h($tr('Sign out','Toka')) ?></a></div></header>
<p class="onboard-note"><?= $h($tr('An internet connection is required to save records and download CSV files, including when the app is installed. Nothing is queued for offline submission.','Mtandao unahitajika kuhifadhi rekodi na kupakua CSV, hata programu ikiwa imesakinishwa. Hakuna taarifa zinazohifadhiwa kutumwa bila mtandao.')) ?></p><p id="onboard-offline" class="onboard-error" role="status" hidden><?= $h($tr('You appear to be offline. Reconnect before saving or downloading; nothing has been submitted.','Inaonekana huna mtandao. Unganisha kabla ya kuhifadhi au kupakua; hakuna kilichotumwa.')) ?></p>
<p class="onboard-note"><?= $h($tr('Only collect information with the person’s permission. A record is not a login account; it does not grant access or activate a subscription.','Kusanya taarifa kwa ruhusa ya mhusika. Rekodi si akaunti ya kuingia wala haiwashi usajili.')) ?></p>
<?php if ($notice): ?><p class="onboard-notice" role="status"><?= $h($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="onboard-error" role="alert"><?= $h($error) ?></p><?php endif; ?>
<section class="onboard-card" aria-labelledby="farmer-count-title"><h2 id="farmer-count-title">Farmer totals</h2><p><strong>Registered farmer accounts:</strong> <?= $registeredFarmerCount === null ? 'Unavailable' : number_format($registeredFarmerCount) ?></p><p><strong>Staff onboarding farmer records:</strong> <?= $onboardedFarmerCount === null ? 'Unavailable' : number_format($onboardedFarmerCount) ?></p><p>These are different, overlapping groups: an onboarding record is not a registered account, and a claimed record may appear in both totals. Counts include all staff and all registered farmer accounts.</p><div class="onboard-actions"><a href="onboarding.php?view=farmers#records">View registered farmers</a><form action="farmer_export.php" method="post"><?= vayuma_csrf_field() ?><button type="submit">Download farmer CSV</button></form></div><p><small>Contains confidential ID numbers and contact details. Download only to a trusted device and protect or delete the file when no longer needed. Phone and ID values use a leading apostrophe to help spreadsheets preserve leading zeros; some CSV viewers display it.</small></p></section>
<nav class="onboard-steps" aria-label="<?= $h($tr('Workspace sections','Sehemu za kazi')) ?>"><a href="#add-person"><span>01</span><strong><?= $h($tr('Add a person','Ongeza mtu')) ?></strong><small><?= $h($tr('Save their details','Hifadhi taarifa')) ?></small></a><a href="#records"><span>02</span><strong><?= $h($tr('Find a record','Tafuta rekodi')) ?></strong><small><?= $h($tr('Search or browse','Tafuta au angalia')) ?></small></a><a href="#staff-report"><span>03</span><strong><?= $h($tr('Staff report','Ripoti ya wafanyakazi')) ?></strong><small><?= $h($tr('Review attribution','Angalia rekodi')) ?></small></a></nav>
<section class="onboard-card" id="add-person"><h2><?= $h($tr('Add a person','Ongeza mtu')) ?></h2><p><?= $h($tr('Save the farmer’s correct phone and ID. The farmer can claim using those details on their own device or a separate signed-out browser.','Hifadhi nambari sahihi ya simu na kitambulisho cha mkulima. Mkulima anaweza kuunganisha akaunti kwenye kifaa chake au kivinjari tofauti ambacho hakijaingia.')) ?></p><form method="post" id="onboarding-form"><?= vayuma_csrf_field() ?><div class="onboard-grid">
<label><?= $h($tr('Full name','Jina kamili')) ?><input name="full_name" maxlength="100" required autocomplete="name" value="<?= $h($val('full_name')) ?>"></label>
<label><?= $h($tr('Phone number','Nambari ya simu')) ?><input name="phone" type="tel" required autocomplete="tel" value="<?= $h($val('phone')) ?>"></label>
<label><?= $h($tr('County','Kaunti')) ?><select name="county" required><option value=""><?= $h($tr('Select county','Chagua kaunti')) ?></option><?php foreach ($counties as $county): ?><option value="<?= $h($county) ?>" <?= $val('county') === $county ? 'selected' : '' ?>><?= $h($county) ?></option><?php endforeach; ?></select></label>
<label><?= $h($tr('Stakeholder category','Aina ya mdau')) ?><select name="role" id="onboarding-role" required><?php foreach ($roles as $role): ?><option value="<?= $h($role) ?>" <?= $val('role') === $role ? 'selected' : '' ?>><?= $h(ucwords(str_replace('_',' ',$role))) ?></option><?php endforeach; ?></select></label>
<label data-farmer-field><?= $h($tr('National ID number','Nambari ya kitambulisho')) ?><input name="farmer_id" type="text" inputmode="numeric" pattern="[0-9]{5,20}" maxlength="20" autocomplete="off"><small><?= $h($tr('Required for farmers; use digits only','Inahitajika kwa wakulima; tumia tarakimu pekee')) ?></small></label>
<label data-farmer-field><?= $h($tr('Village or farm location','Kijiji au eneo la shamba')) ?><input name="farmer_location" maxlength="255" value="<?= $h($val('farmer_location')) ?>"></label>
<label data-farmer-field><?= $h($tr('Primary crops or livestock','Mazao au mifugo mikuu')) ?><input name="activity" maxlength="255" value="<?= $h($val('activity')) ?>"><small><?= $h($tr('Required for farmers','Inahitajika kwa wakulima')) ?></small></label>
<label data-farmer-field><?= $h($tr('Farm size','Ukubwa wa shamba')) ?><input name="farm_size" inputmode="decimal" type="number" min="0.01" max="1000000" step="0.01" value="<?= $h($val('farm_size')) ?>"><small><?= $h($tr('Required for farmers','Inahitajika kwa wakulima')) ?></small></label>
<label data-farmer-field><?= $h($tr('Size unit','Kipimo cha ukubwa')) ?><select name="farm_size_unit"><option value=""><?= $h($tr('Select unit','Chagua kipimo')) ?></option><option value="acres" <?= $val('farm_size_unit') === 'acres' ? 'selected' : '' ?>>Acres</option><option value="hectares" <?= $val('farm_size_unit') === 'hectares' ? 'selected' : '' ?>>Hectares</option></select></label>
<label><?= $h($tr('Preferred language','Lugha inayopendelewa')) ?><select name="preferred_language" required><option value="en" <?= $val('preferred_language') === 'en' ? 'selected' : '' ?>>English</option><option value="sw" <?= $val('preferred_language') === 'sw' ? 'selected' : '' ?>>Kiswahili</option></select></label>
</div><label class="onboard-consent"><input type="checkbox" name="consent" value="yes" required> <?= $h($tr('The person has agreed to VAYUMA TRIX storing these details, including their ID, for onboarding and authorized staff reports.','Mhusika amekubali VAYUMA TRIX ihifadhi taarifa hizi, pamoja na kitambulisho, kwa usajili na ripoti za wafanyakazi walioidhinishwa.')) ?></label><button type="submit"><?= $h($tr('Save onboarding record','Hifadhi rekodi')) ?></button></form></section>
<section class="onboard-card" id="records"><h2><?= $h($tr('People and records','Watu na rekodi')) ?></h2><nav class="onboard-tabs"><a href="onboarding.php" <?= $view === 'records' ? 'aria-current="page"' : '' ?>><?= $h($tr('Onboarding records','Rekodi za usajili')) ?></a><a href="onboarding.php?view=farmers" <?= $view === 'farmers' ? 'aria-current="page"' : '' ?>><?= $h($tr('Registered farmers','Wakulima waliojisajili')) ?></a></nav>
<p><?= $h($view === 'farmers' ? $tr('Registered farmer accounts, including those without a completed farm profile.','Akaunti za wakulima, hata kama wasifu haujakamilika.') : $tr('Records are not claimed accounts.','Rekodi si akaunti zilizounganishwa.')) ?></p>
<?php if ($view === 'records'): ?><form method="post" action="onboarding.php#records" class="onboard-search"><?= vayuma_csrf_field() ?><input type="hidden" name="action" value="find_record"><label for="search-phone"><?= $h($tr('Find an onboarding record by phone','Tafuta rekodi kwa nambari ya simu')) ?><input id="search-phone" name="search_phone" type="tel" inputmode="tel" autocomplete="off" required value="<?= $h($searchPhone ?? '') ?>"></label><button type="submit"><?= $h($tr('Find record','Tafuta rekodi')) ?></button><?php if ($searchPhone !== null): ?><a href="onboarding.php#records"><?= $h($tr('Show all records','Onyesha rekodi zote')) ?></a><?php endif; ?></form><p><small><?= $h($tr('Search stays in this request, not the address bar. Records are private to authorized staff.','Utafutaji hauonekani kwenye anwani ya ukurasa. Rekodi ni za wafanyakazi walioidhinishwa tu.')) ?></small></p><?php if (getenv('VAYUMA_ONBOARDING_ENABLED') !== '1' || getenv('VAYUMA_CLAIM_ENABLED') !== '1'): ?><p class="onboard-note"><?= $h($tr('Farmer claiming is currently disabled.','Kuunganisha akaunti za wakulima kumezimwa kwa sasa.')) ?></p><?php endif; ?><?php endif; ?>
<?php if ($recordsAvailable && !$rows): ?><p><?= $h($searchPhone !== null ? $tr('No onboarding record found for this number.','Hakuna rekodi ya usajili kwa nambari hii.') : $tr('No records found.','Hakuna rekodi zilizopatikana.')) ?></p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="onboard-person"><strong><?= $h($row['full_name']) ?></strong><span><?= $h($row['phone_identity'] ?? $row['phone'] ?? '') ?> · <?= $h($row['county'] ?: $tr('County not provided','Kaunti haijawekwa')) ?><?php if (!empty($row['farmer_location'])): ?> · <?= $h($row['farmer_location']) ?><?php endif; ?></span><?php if ($view === 'records'): ?><span><?= $h(ucwords(str_replace('_',' ',(string)$row['stakeholder_role']))) ?> · <?= $h($row['activity'] ?: '—') ?><?php if ($row['farm_size'] !== null): ?> · <?= $h($row['farm_size'].' '.$row['farm_size_unit']) ?><?php endif; ?></span><small><?= $h($tr('Recorded by','Ameandika')) ?> <?= $h($row['staff_name'] ?: $tr('Staff member','Mfanyakazi')) ?> · <?= $h($row['created_at']) ?></small><small><?= $h($row['claimed_by_user_id'] !== null ? $tr('Claimed','Imeunganishwa') : $tr('Not claimed','Haijaunganishwa')) ?></small><?php if (getenv('VAYUMA_ONBOARDING_ENABLED') === '1' && getenv('VAYUMA_CLAIM_ENABLED') === '1' && $row['stakeholder_role'] === 'farmer' && $row['claimed_by_user_id'] === null): ?><form method="post"><?= vayuma_csrf_field() ?><input type="hidden" name="action" value="reset_claim"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><button type="submit"><?= $h($tr('Reset claim attempts','Fungua majaribio ya kuunganisha')) ?></button></form><details><summary><?= $h($tr('Add or correct farmer ID','Ongeza au rekebisha kitambulisho')) ?></summary><form method="post"><?= vayuma_csrf_field() ?><input type="hidden" name="action" value="update_farmer_id"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><label><?= $h($tr('Farmer ID number','Nambari ya kitambulisho')) ?><input name="farmer_id" type="text" inputmode="numeric" pattern="[0-9]{5,20}" maxlength="20" autocomplete="off" required></label><button type="submit"><?= $h($tr('Save ID and reset attempts','Hifadhi kitambulisho na ufungue majaribio')) ?></button></form></details><?php endif; ?><?php endif; ?></article><?php endforeach; ?>
<?php if ($searchPhone === null): ?><nav class="onboard-pages" aria-label="Pagination"><?php if ($page > 1): ?><a href="?view=<?= $h($view) ?>&amp;page=<?= $page-1 ?>">← <?= $h($tr('Previous','Iliyotangulia')) ?></a><?php endif; ?><span><?= $h($tr('Page','Ukurasa')) ?> <?= $page ?></span><?php if ($more): ?><a href="?view=<?= $h($view) ?>&amp;page=<?= $page+1 ?>"><?= $h($tr('Next','Inayofuata')) ?> →</a><?php endif; ?></nav><?php endif; ?></section>
<section class="onboard-card onboard-report" id="staff-report"><h2><?= $h($tr('Records by staff','Rekodi kwa mfanyakazi')) ?></h2><p><?= $h($tr('Saved onboarding records attributed to each staff account.','Rekodi zilizohifadhiwa kwa kila akaunti ya mfanyakazi.')) ?></p><?php if (!$staffCountsAvailable): ?><p class="onboard-error" role="status"><?= $h($tr('Staff report is temporarily unavailable.','Ripoti ya wafanyakazi haipatikani kwa sasa.')) ?></p><?php elseif (!$staffCounts): ?><p><?= $h($tr('No records yet.','Bado hakuna rekodi.')) ?></p><?php endif; ?><?php foreach ($staffCounts as $count): ?><p><?= $h($count['full_name'] ?: $tr('Staff member','Mfanyakazi')) ?>: <?= (int)$count['total'] ?></p><?php endforeach; ?></section>
</main><script src="assets/js/onboarding-workspace.js?v=5" defer></script></body></html>
