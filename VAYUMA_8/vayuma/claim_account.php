<?php
session_start();
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow');
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/onboarding_policy.php';
require_once __DIR__ . '/includes/login_identity.php';
require_once __DIR__ . '/includes/farmer_identity.php';
if (($_SESSION['logged_in'] ?? false) === true) { header('Location: dashboard.php'); exit; }
$enabled = getenv('VAYUMA_ONBOARDING_ENABLED') === '1' && getenv('VAYUMA_CLAIM_ENABLED') === '1';
$error = ''; $phoneValue = ''; $emailValue = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$enabled) { http_response_code(503); $error = 'Account claiming is not available yet.'; }
    elseif (!vayuma_csrf_valid()) { http_response_code(403); $error = 'Your session expired. Refresh and try again.'; }
    else {
        $phoneValue = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : '';
        $emailValue = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
        $farmerId = is_string($_POST['farmer_id'] ?? null) ? trim($_POST['farmer_id']) : '';
        $claimPassword = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        $phone = vayuma_onboarding_phone($phoneValue);
        if ($phone === null || !vayuma_farmer_id_valid($farmerId)) $error = 'Unable to claim this account. Check your details or contact staff.';
        elseif (strlen($emailValue) > 100 || !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) $error = 'Enter a valid email address.';
        elseif (strlen($claimPassword) < 8 || strlen($claimPassword) > 72 || str_contains($claimPassword, "\0")) $error = 'Choose a password between 8 and 72 bytes.';
        elseif ($claimPassword !== $confirm) $error = 'Passwords do not match.';
        else {
            require_once __DIR__ . '/config/db.php';
            require_once __DIR__ . '/includes/onboarding_lock.php';
            $transaction = false;
            try {
                $hash = password_hash($claimPassword, PASSWORD_DEFAULT);
                if (!is_string($hash)) throw new RuntimeException('Password hashing failed');
                if (!$conn->begin_transaction()) throw new RuntimeException('Transaction unavailable');
                $transaction = true;
                vayuma_onboarding_lock_phone($conn, $phone);
                $stmt = $conn->prepare("SELECT id,full_name,farmer_id_ciphertext,claim_attempts,claimed_by_user_id FROM onboarding_records WHERE phone_identity=? AND stakeholder_role='farmer' LIMIT 1 FOR UPDATE");
                $stmt->bind_param('s', $phone); $stmt->execute(); $record = $stmt->get_result()->fetch_assoc(); $stmt->close();
                // An ID and phone are identifiers, not proof of phone ownership. This
                // flow is explicitly selected by the operator despite that risk.
                $storedId = ($record && !empty($record['farmer_id_ciphertext'])) ? vayuma_farmer_id_decrypt($record['farmer_id_ciphertext']) : '';
                $valid = $record && $record['claimed_by_user_id'] === null && (int)$record['claim_attempts'] < 5 && $storedId !== '' && hash_equals($storedId, $farmerId);
                unset($storedId, $farmerId);
                if (!$valid) {
                    if ($record && $record['claimed_by_user_id'] === null && (int)$record['claim_attempts'] < 5) {
                        $id = (int)$record['id']; $stmt = $conn->prepare('UPDATE onboarding_records SET claim_attempts=claim_attempts+1 WHERE id=?');
                        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
                    }
                    if (!$conn->commit()) throw new RuntimeException('Commit failed');
                    $transaction = false;
                    $error = 'Unable to claim this account. Check your details or contact staff.';
                } else {
                    [$phone1, $phone2, $phone3] = vayuma_login_phone_variants($phone);
                    $stmt = $conn->prepare("SELECT id FROM users WHERE phone_identity=? OR REPLACE(phone_identity, '.', '') IN (?, ?, ?) OR email_identity=? LIMIT 1 FOR UPDATE");
                    $stmt->bind_param('sssss', $phone, $phone1, $phone2, $phone3, $emailValue); $stmt->execute(); $existing = $stmt->get_result()->fetch_assoc(); $stmt->close();
                    if ($existing) throw new DomainException('Unable to complete the claim. Sign in or contact authorized staff.');
                    $name = (string)$record['full_name'];
                    $stmt = $conn->prepare("INSERT INTO users (full_name,phone,email,password,role) VALUES (?,?,?,?,'farmer')");
                    $stmt->bind_param('ssss', $name, $phone, $emailValue, $hash);
                    if (!$stmt->execute()) throw new RuntimeException('Account creation failed');
                    $userId = (int)$stmt->insert_id; $stmt->close();
                    if ($userId < 1) throw new RuntimeException('Account creation failed');
                    $recordId = (int)$record['id'];
                    $stmt = $conn->prepare('UPDATE onboarding_records SET claimed_by_user_id=?, claimed_at=UTC_TIMESTAMP(), claim_code_hash=NULL, claim_expires_at=NULL WHERE id=? AND claimed_by_user_id IS NULL');
                    $stmt->bind_param('ii', $userId, $recordId); $stmt->execute(); $changed = $stmt->affected_rows; $stmt->close();
                    if ($changed !== 1) throw new RuntimeException('Claim failed');
                    if (!$conn->commit()) throw new RuntimeException('Commit failed');
                    $transaction = false;
                    session_regenerate_id(true);
                    $_SESSION['claim_success'] = true;
                    header('Location: login.php'); exit;
                }
            } catch (DomainException $e) {
                if ($transaction) { try { $conn->rollback(); } catch (Throwable $ignored) {} }
                $error = $e->getMessage();
            } catch (Throwable $e) {
                if ($transaction) { try { $conn->rollback(); } catch (Throwable $ignored) {} }
                error_log('VAYUMA account claim unavailable.'); http_response_code(503);
                $error = 'Unable to complete the claim right now. Please try again later.';
            }
        }
    }
}
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$sw = $current_language === 'sw';
$tr = static fn($en,$kiswahili) => $sw ? $kiswahili : $en;
?><!doctype html><html lang="<?= $h($current_language) ?>"><head><?php require __DIR__ . '/includes/pwa.php'; ?><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover, interactive-widget=resizes-content"><title>Claim account | VAYUMA TRIX</title><link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/onboarding.css?v=6"></head><body><?php vayuma_language_selector(); ?><main class="onboard"><header class="onboard-header"><div><a href="login.php">← <?= $h($tr('Sign in','Ingia')) ?></a><h1><?= $h($tr('Claim your farmer account','Unganisha akaunti yako ya mkulima')) ?></h1></div></header><section class="onboard-card"><p><?= $h($tr('Enter the phone number and national ID recorded by staff, then choose your email and password.','Ingiza nambari ya simu na kitambulisho vilivyosajiliwa na mfanyakazi, kisha chagua barua pepe na nenosiri lako.')) ?></p><p class="onboard-note"><?= $h($tr('Your staff record is not yet a login. After claiming, sign in and complete your profile. A claim does not activate a subscription.','Rekodi ya mfanyakazi bado si akaunti ya kuingia. Baada ya kuunganisha, ingia na ukamilishe wasifu. Kuunganisha hakuwashi usajili.')) ?></p><?php if (!$enabled): ?><p class="onboard-error" role="status"><?= $h($tr('Account claiming is not available yet.','Kuunganisha akaunti bado hakupatikani.')) ?></p><?php endif; ?><?php if ($error): ?><p class="onboard-error" role="alert"><?= $h($error) ?></p><?php endif; ?><form method="post"><?= vayuma_csrf_field() ?><div class="onboard-grid"><label><?= $h($tr('Phone number','Nambari ya simu')) ?><input type="tel" id="claim-phone" name="phone" inputmode="tel" autocomplete="tel" required value="<?= $h($phoneValue) ?>"></label><label><?= $h($tr('National ID number','Nambari ya kitambulisho')) ?><input type="text" name="farmer_id" inputmode="numeric" pattern="[0-9]{5,20}" minlength="5" maxlength="20" autocomplete="off" required></label><label><?= $h($tr('Email address','Barua pepe')) ?><input type="email" name="email" maxlength="100" autocomplete="email" required value="<?= $h($emailValue) ?>"></label><label><?= $h($tr('Choose password','Chagua nenosiri')) ?><input type="password" name="password" minlength="8" autocomplete="new-password" required></label><label><?= $h($tr('Confirm password','Thibitisha nenosiri')) ?><input type="password" name="confirm_password" minlength="8" autocomplete="new-password" required></label></div><p><?= $h($tr('Five incorrect attempts lock this claim. Contact staff if your details need correcting or your claim is locked. Never share your ID or password.','Majaribio matano yasiyo sahihi hufunga akaunti hii. Wasiliana na wafanyakazi kurekebisha taarifa au kufungua akaunti. Usishiriki kitambulisho au nenosiri lako.')) ?></p><button type="submit" <?= $enabled ? '' : 'disabled' ?>><?= $h($tr('Create my account','Fungua akaunti yangu')) ?></button></form></section></main><script src="assets/js/claim-phone.js?v=1" defer></script></body></html>
