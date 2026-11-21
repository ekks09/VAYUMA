<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
require_once __DIR__ . '/includes/onboarding_policy.php';
require_once __DIR__ . '/includes/login_identity.php';
if (($_SESSION['logged_in'] ?? false) !== true || !isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$staffId = (int)$_SESSION['user_id'];
if (getenv('VAYUMA_ONBOARDING_ENABLED') !== '1') {
    http_response_code(403); exit('Onboarding access is not enabled for this account.');
}
require_once __DIR__ . '/config/db.php';
if (!vayuma_onboarding_authorized($conn ?? null, $staffId, (string)(getenv('VAYUMA_ONBOARDING_PHONES') ?: ''))) {
    unset($_SESSION['onboarding_unlocked_user_id']);
    http_response_code(403); exit('Onboarding access is not enabled for this account.');
}
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/onboarding_lock.php';
// The passphrase supplements, never replaces, the account allowlist above.
if (($_SESSION['onboarding_unlocked_user_id'] ?? null) !== $staffId) {
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
                    header('Location: onboarding.php'); exit;
                }
                if ($result === 'limited') { http_response_code(429); $unlockError = 'Too many attempts. Try again after 15 minutes.'; }
                else { http_response_code(403); $unlockError = 'Passphrase not accepted.'; }
            } catch (Throwable $e) { unset($passphrase, $_POST['passphrase']); error_log('VAYUMA staff unlock unavailable.'); http_response_code(503); $unlockError = 'Staff access is temporarily unavailable.'; }
        }
    }
    $h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    ?><!doctype html><html lang="<?= $h($current_language) ?>"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Staff Access | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/onboarding.css?v=3"></head><body><?php vayuma_language_selector(); ?><main class="onboard"><header class="onboard-header"><div><a href="dashboard.php">← Dashboard</a><h1>Staff Access</h1><p>For authorized onboarding staff</p></div></header><section class="onboard-card"><h2>Unlock onboarding</h2><p>Enter the staff passphrase to unlock this session. Your existing account remains signed in.</p><?php if ($unlockError): ?><p class="onboard-error" role="alert"><?= $h($unlockError) ?></p><?php endif; ?><form method="post"><?= vayuma_csrf_field() ?><label for="staff-passphrase">Staff passphrase<input id="staff-passphrase" name="passphrase" type="password" autocomplete="off" maxlength="256" required></label><p><button type="submit">Unlock staff access</button></p></form></section></main><script src="assets/js/onboarding-workspace.js?v=1" defer></script></body></html><?php
    exit;
}
$h = static fn($value) => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$sw = $current_language === 'sw';
$tr = static fn($en, $kiswahili) => $sw ? $kiswahili : $en;
$roles = ['farmer','consumer','buyer','transport_provider','input_supplier','manufacturer','warehouse_provider','retailer','agribusiness','cooperative','researcher','extension_officer','veterinary_officer','financial_institution','insurance_provider','government_officer','ngo_partner','agricultural_service_provider','strategic_partner'];
$claimCode = $_SESSION['onboarding_claim_code'] ?? ''; unset($_SESSION['onboarding_claim_code']);
$claimName = $_SESSION['onboarding_claim_name'] ?? ''; unset($_SESSION['onboarding_claim_name']);
$error = ''; $notice = (string)($_SESSION['onboarding_notice'] ?? ''); unset($_SESSION['onboarding_notice']);
$old = []; $searchPhone = null;
try {
    $countyResult = $conn->query("SELECT name FROM administrative_units WHERE unit_type='county' ORDER BY name");
    if (!$countyResult) throw new RuntimeException('Counties unavailable');
    $counties = [];
    while ($row = $countyResult->fetch_assoc()) $counties[] = (string)$row['name'];
    if (!$counties) throw new RuntimeException('Counties unavailable');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $old = $_POST;
        if (!vayuma_csrf_valid()) throw new DomainException('Your session expired. Refresh and try again.');
        if (($_POST['action'] ?? '') === 'issue_code') {
            if (getenv('VAYUMA_CLAIM_ENABLED') !== '1') throw new DomainException('Claiming is not enabled.');
            $recordText = $_POST['record_id'] ?? '';
            if (!is_string($recordText) || !ctype_digit($recordText) || (int)$recordText < 1) throw new DomainException('Choose a valid onboarding record.');
            $recordId = (int)$recordText;
            $target = $conn->prepare("SELECT full_name FROM onboarding_records WHERE id=? AND stakeholder_role='farmer' AND claimed_by_user_id IS NULL LIMIT 1");
            if (!$target) throw new RuntimeException('Record unavailable');
            $target->bind_param('i', $recordId);
            if (!$target->execute()) throw new RuntimeException('Record unavailable');
            $targetResult = $target->get_result();
            if (!$targetResult) throw new RuntimeException('Record unavailable');
            $targetRow = $targetResult->fetch_assoc(); $target->close();
            if (!$targetRow) throw new DomainException('This record is unavailable or already claimed.');
            $newCode = bin2hex(random_bytes(16)); $newHash = hash('sha256', $newCode);
            $issue = $conn->prepare("UPDATE onboarding_records SET claim_code_hash=?, claim_expires_at=DATE_ADD(UTC_TIMESTAMP(), INTERVAL 7 DAY), claim_attempts=0 WHERE id=? AND stakeholder_role='farmer' AND claimed_by_user_id IS NULL");
            if (!$issue) throw new RuntimeException('Claim code unavailable');
            $issue->bind_param('si', $newHash, $recordId);
            if (!$issue->execute()) throw new RuntimeException('Claim code unavailable');
            $changed = $issue->affected_rows; $issue->close();
            if ($changed !== 1) throw new DomainException('This record is unavailable or already claimed.');
            $_SESSION['onboarding_claim_code'] = $newCode;
            $_SESSION['onboarding_claim_name'] = (string)($targetRow['full_name'] ?? '');
            header('Location: onboarding.php#claim-code'); exit;
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
            $stmt = $conn->prepare('INSERT INTO onboarding_records (full_name,phone_identity,county,stakeholder_role,activity,farm_size,farm_size_unit,preferred_language,onboarded_by_user_id) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->bind_param('ssssssssi', $data['full_name'], $data['phone'], $data['county'], $data['role'], $data['activity'], $data['farm_size'], $data['farm_size_unit'], $data['preferred_language'], $staffId);
            if (!$stmt->execute()) throw new RuntimeException('Save failed');
            $stmt->close();
            if (!$conn->commit()) throw new RuntimeException('Commit failed');
            $_SESSION['onboarding_notice'] = 'Record saved. This is not yet a VAYUMA account.';
            header('Location: onboarding.php#records'); exit;
        } catch (Throwable $e) { try { $conn->rollback(); } catch (Throwable $ignored) {} throw $e; }
        }
    }
} catch (DomainException $e) { $error = $e->getMessage(); }
catch (Throwable $e) { error_log('VAYUMA onboarding unavailable.'); http_response_code(503); $error = 'Onboarding is temporarily unavailable. No record was saved.'; $counties = $counties ?? []; }
$view = $searchPhone !== null ? 'records' : (($_GET['view'] ?? '') === 'farmers' ? 'farmers' : 'records');
$pageInput = $_GET['page'] ?? '1';
$page = is_string($pageInput) && ctype_digit($pageInput) ? min(max((int)$pageInput, 1), 1000000) : 1;
$offset = ($page - 1) * 50;
$rows = []; $more = false; $staffCounts = [];
try {
    if ($view === 'farmers') {
        $stmt = $conn->prepare("SELECT u.full_name,u.phone,(SELECT fp.county FROM farmer_profiles fp WHERE fp.user_id=u.id ORDER BY fp.id LIMIT 1) AS county FROM users u WHERE LOWER(TRIM(u.role))='farmer' ORDER BY u.id DESC LIMIT 51 OFFSET ?");
    } elseif ($searchPhone !== null) {
        $stmt = $conn->prepare('SELECT r.full_name,r.phone_identity,r.county,r.stakeholder_role,r.activity,r.farm_size,r.farm_size_unit,r.preferred_language,r.created_at,r.id,r.claimed_by_user_id,u.full_name AS staff_name FROM onboarding_records r LEFT JOIN users u ON u.id=r.onboarded_by_user_id WHERE r.phone_identity=? LIMIT 2');
    } else {
        $stmt = $conn->prepare('SELECT r.full_name,r.phone_identity,r.county,r.stakeholder_role,r.activity,r.farm_size,r.farm_size_unit,r.preferred_language,r.created_at,r.id,r.claimed_by_user_id,u.full_name AS staff_name FROM onboarding_records r LEFT JOIN users u ON u.id=r.onboarded_by_user_id ORDER BY r.id DESC LIMIT 51 OFFSET ?');
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
} catch (Throwable $e) { error_log('VAYUMA onboarding list unavailable.'); if (!$error) { http_response_code(503); $error = 'Onboarding records are temporarily unavailable.'; } }
$val = static fn($key) => is_string($old[$key] ?? null) ? $old[$key] : '';
?><!doctype html><html lang="<?= $h($current_language) ?>"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Onboarding | VAYUMA</title><link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/onboarding.css?v=3"></head><body>
<?php vayuma_language_selector(); ?>
<main class="onboard"><header class="onboard-header"><div><a href="dashboard.php">← <?= $h($tr('Dashboard','Dashibodi')) ?></a><h1><?= $h($tr('Onboarding workspace','Eneo la usajili')) ?></h1><p><?= $h($tr('For authorized onboarding staff','Kwa wafanyakazi walioidhinishwa')) ?></p></div><a href="auth/logout.php"><?= $h($tr('Sign out','Toka')) ?></a></header>
<p class="onboard-note"><?= $h($tr('Only collect information with the person’s permission. A record is not a login account; it does not grant access or activate a subscription.','Kusanya taarifa kwa ruhusa ya mhusika. Rekodi si akaunti ya kuingia wala haiwashi usajili.')) ?></p>
<?php if ($notice): ?><p class="onboard-notice" role="status"><?= $h($notice) ?></p><?php endif; ?>
<?php if (is_string($claimCode) && $claimCode !== ''): ?><section class="onboard-card onboard-claim" id="claim-code" aria-labelledby="claim-code-title"><h2 id="claim-code-title"><?= $h($tr('Private claim code','Msimbo wa siri wa kuunganisha')) ?></h2><p><?= $h($tr('For','Kwa')) ?> <strong><?= $h($claimName ?: $tr('the selected farmer','mkulima aliyechaguliwa')) ?></strong></p><p><?= $h($tr('Give this code privately to the farmer. It is shown only once, expires after seven days and can be used once. Replacing it invalidates the previous code.','Mpe mkulima msimbo huu kwa siri. Unaonyeshwa mara moja, huisha baada ya siku saba na hutumika mara moja. Msimbo mpya huzima wa awali.')) ?></p><code id="staff-claim-code" class="onboard-code"><?= $h($claimCode) ?></code><p><button type="button" id="copy-claim-code" data-copied="<?= $h($tr('Copied.','Imenakiliwa.')) ?>" data-failed="<?= $h($tr('Copy failed; select the code manually.','Kunakili kumeshindikana; chagua msimbo mwenyewe.')) ?>" hidden><?= $h($tr('Copy code','Nakili msimbo')) ?></button> <span id="copy-claim-status" role="status"></span></p><p><small><?= $h($tr('Only copy on a trusted device; clipboard contents may be visible to other apps. VAYUMA does not send SMS.','Nakili kwenye kifaa unachoamini tu; programu nyingine zinaweza kuona kilichonakiliwa. VAYUMA haitumi SMS.')) ?></small></p><p><?= $h($tr('The farmer opens the claim page, enters their phone and code, and chooses their own email and password. Claiming does not activate a subscription.','Mkulima afungue ukurasa wa kuunganisha, aweke simu na msimbo, na achague barua pepe na nenosiri lake. Kuunganisha hakuwashi usajili.')) ?> <a href="claim_account.php"><?= $h($tr('Open claim page','Fungua ukurasa wa kuunganisha')) ?></a></p></section><?php endif; ?><?php if ($error): ?><p class="onboard-error" role="alert"><?= $h($error) ?></p><?php endif; ?>
<nav class="onboard-steps" aria-label="<?= $h($tr('Workspace sections','Sehemu za kazi')) ?>"><a href="#add-person"><?= $h($tr('1. Add a person','1. Ongeza mtu')) ?></a><a href="#records"><?= $h($tr('2. Find a record','2. Tafuta rekodi')) ?></a></nav>
<section class="onboard-card" id="add-person"><h2><?= $h($tr('Add a person','Ongeza mtu')) ?></h2><p><?= $h($tr('For a farmer, save the record first, then find it below to issue a private claim code.','Kwa mkulima, hifadhi rekodi kwanza, kisha itafute hapa chini kutoa msimbo wa siri.')) ?></p><form method="post" id="onboarding-form"><?= vayuma_csrf_field() ?><div class="onboard-grid">
<label><?= $h($tr('Full name','Jina kamili')) ?><input name="full_name" maxlength="100" required autocomplete="name" value="<?= $h($val('full_name')) ?>"></label>
<label><?= $h($tr('Phone number','Nambari ya simu')) ?><input name="phone" type="tel" required autocomplete="tel" value="<?= $h($val('phone')) ?>"></label>
<label><?= $h($tr('County','Kaunti')) ?><select name="county" required><option value=""><?= $h($tr('Select county','Chagua kaunti')) ?></option><?php foreach ($counties as $county): ?><option value="<?= $h($county) ?>" <?= $val('county') === $county ? 'selected' : '' ?>><?= $h($county) ?></option><?php endforeach; ?></select></label>
<label><?= $h($tr('Stakeholder category','Aina ya mdau')) ?><select name="role" id="onboarding-role" required><?php foreach ($roles as $role): ?><option value="<?= $h($role) ?>" <?= $val('role') === $role ? 'selected' : '' ?>><?= $h(ucwords(str_replace('_',' ',$role))) ?></option><?php endforeach; ?></select></label>
<label data-farmer-field><?= $h($tr('Primary crops or livestock','Mazao au mifugo mikuu')) ?><input name="activity" maxlength="255" value="<?= $h($val('activity')) ?>"><small><?= $h($tr('Required for farmers','Inahitajika kwa wakulima')) ?></small></label>
<label data-farmer-field><?= $h($tr('Farm size','Ukubwa wa shamba')) ?><input name="farm_size" inputmode="decimal" type="number" min="0.01" max="1000000" step="0.01" value="<?= $h($val('farm_size')) ?>"><small><?= $h($tr('Required for farmers','Inahitajika kwa wakulima')) ?></small></label>
<label data-farmer-field><?= $h($tr('Size unit','Kipimo cha ukubwa')) ?><select name="farm_size_unit"><option value=""><?= $h($tr('Select unit','Chagua kipimo')) ?></option><option value="acres" <?= $val('farm_size_unit') === 'acres' ? 'selected' : '' ?>>Acres</option><option value="hectares" <?= $val('farm_size_unit') === 'hectares' ? 'selected' : '' ?>>Hectares</option></select></label>
<label><?= $h($tr('Preferred language','Lugha inayopendelewa')) ?><select name="preferred_language" required><option value="en" <?= $val('preferred_language') === 'en' ? 'selected' : '' ?>>English</option><option value="sw" <?= $val('preferred_language') === 'sw' ? 'selected' : '' ?>>Kiswahili</option></select></label>
</div><label class="onboard-consent"><input type="checkbox" name="consent" value="yes" required> <?= $h($tr('The person has agreed to VAYUMA storing these details for onboarding.','Mhusika amekubali VAYUMA ihifadhi taarifa hizi kwa usajili.')) ?></label><button type="submit"><?= $h($tr('Save onboarding record','Hifadhi rekodi')) ?></button></form></section>
<section class="onboard-card" id="records"><h2><?= $h($tr('People and records','Watu na rekodi')) ?></h2><nav class="onboard-tabs"><a href="onboarding.php" <?= $view === 'records' ? 'aria-current="page"' : '' ?>><?= $h($tr('Onboarding records','Rekodi za usajili')) ?></a><a href="onboarding.php?view=farmers" <?= $view === 'farmers' ? 'aria-current="page"' : '' ?>><?= $h($tr('Registered farmers','Wakulima waliojisajili')) ?></a></nav>
<p><?= $h($view === 'farmers' ? $tr('Registered farmer accounts, including those without a completed farm profile.','Akaunti za wakulima, hata kama wasifu haujakamilika.') : $tr('Records are not claimed accounts.','Rekodi si akaunti zilizounganishwa.')) ?></p>
<?php if ($view === 'records'): ?><form method="post" action="onboarding.php#records" class="onboard-search"><?= vayuma_csrf_field() ?><input type="hidden" name="action" value="find_record"><label for="search-phone"><?= $h($tr('Find an onboarding record by phone','Tafuta rekodi kwa nambari ya simu')) ?><input id="search-phone" name="search_phone" type="tel" inputmode="tel" autocomplete="off" required value="<?= $h($searchPhone ?? '') ?>"></label><button type="submit"><?= $h($tr('Find record','Tafuta rekodi')) ?></button><?php if ($searchPhone !== null): ?><a href="onboarding.php#records"><?= $h($tr('Show all records','Onyesha rekodi zote')) ?></a><?php endif; ?></form><p><small><?= $h($tr('Search stays in this request, not the address bar. Records are private to authorized staff.','Utafutaji hauonekani kwenye anwani ya ukurasa. Rekodi ni za wafanyakazi walioidhinishwa tu.')) ?></small></p><?php if (getenv('VAYUMA_CLAIM_ENABLED') !== '1'): ?><p class="onboard-note"><?= $h($tr('Farmer claiming is currently disabled. No claim codes can be issued.','Kuunganisha akaunti za wakulima kumezimwa kwa sasa. Misimbo haiwezi kutolewa.')) ?></p><?php endif; ?><?php endif; ?>
<?php if (!$rows): ?><p><?= $h($searchPhone !== null ? $tr('No onboarding record found for this number.','Hakuna rekodi ya usajili kwa nambari hii.') : $tr('No records found.','Hakuna rekodi zilizopatikana.')) ?></p><?php endif; ?>
<?php foreach ($rows as $row): ?><article class="onboard-person"><strong><?= $h($row['full_name']) ?></strong><span><?= $h($row['phone_identity'] ?? $row['phone'] ?? '') ?> · <?= $h($row['county'] ?: $tr('County not provided','Kaunti haijawekwa')) ?></span><?php if ($view === 'records'): ?><span><?= $h(ucwords(str_replace('_',' ',(string)$row['stakeholder_role']))) ?> · <?= $h($row['activity'] ?: '—') ?><?php if ($row['farm_size'] !== null): ?> · <?= $h($row['farm_size'].' '.$row['farm_size_unit']) ?><?php endif; ?></span><small><?= $h($tr('Recorded by','Ameandika')) ?> <?= $h($row['staff_name'] ?: $tr('Staff member','Mfanyakazi')) ?> · <?= $h($row['created_at']) ?></small><small><?= $h($row['claimed_by_user_id'] !== null ? $tr('Claimed','Imeunganishwa') : $tr('Not claimed','Haijaunganishwa')) ?></small><?php if (getenv('VAYUMA_CLAIM_ENABLED') === '1' && $row['stakeholder_role'] === 'farmer' && $row['claimed_by_user_id'] === null): ?><form method="post"><?= vayuma_csrf_field() ?><input type="hidden" name="action" value="issue_code"><input type="hidden" name="record_id" value="<?= (int)$row['id'] ?>"><button type="submit"><?= $h($tr('Issue or replace claim code','Toa au badilisha msimbo')) ?></button></form><?php endif; ?><?php endif; ?></article><?php endforeach; ?>
<?php if ($searchPhone === null): ?><nav class="onboard-pages" aria-label="Pagination"><?php if ($page > 1): ?><a href="?view=<?= $h($view) ?>&amp;page=<?= $page-1 ?>">← <?= $h($tr('Previous','Iliyotangulia')) ?></a><?php endif; ?><span><?= $h($tr('Page','Ukurasa')) ?> <?= $page ?></span><?php if ($more): ?><a href="?view=<?= $h($view) ?>&amp;page=<?= $page+1 ?>"><?= $h($tr('Next','Inayofuata')) ?> →</a><?php endif; ?></nav><?php endif; ?></section>
<section class="onboard-card"><h2><?= $h($tr('Records by staff','Rekodi kwa mfanyakazi')) ?></h2><?php if (!$staffCounts): ?><p><?= $h($tr('No records yet.','Bado hakuna rekodi.')) ?></p><?php endif; ?><?php foreach ($staffCounts as $count): ?><p><?= $h($count['full_name'] ?: $tr('Staff member','Mfanyakazi')) ?>: <?= (int)$count['total'] ?></p><?php endforeach; ?></section>
</main><script src="assets/js/onboarding-workspace.js?v=1" defer></script></body></html>
