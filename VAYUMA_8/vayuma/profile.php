<?php
session_start();
header('Cache-Control: private, no-store, max-age=0');
require_once __DIR__ . '/includes/role_routes.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: login.php'); exit; }
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/user_menu_data.php';
require_once __DIR__ . '/includes/avatar_storage.php';
require_once __DIR__ . '/includes/identity_errors.php';
require_once __DIR__ . '/includes/onboarding_policy.php';
require_once __DIR__ . '/includes/login_identity.php';
$dashboard = vayuma_dashboard_return_route();
// Accept only subscription or the logged-in user's mapped dashboard.
$returnTo = vayuma_profile_hub_return_route($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['return_to'] ?? null) : ($_GET['return_to'] ?? null));
$transactionStarted = false; $avatarLock = null; $avatarBackup = null; $avatarTarget = null; $avatarChanged = false;
$error = ''; $notice = (string)($_SESSION['profile_hub_notice'] ?? ''); unset($_SESSION['profile_hub_notice']);
$id = (int)$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!vayuma_csrf_valid()) throw new VayumaProfileError('Your session expired. Refresh and try again.');
        $name = trim((string)($_POST['full_name'] ?? ''));
        $phone = vayuma_onboarding_phone((string)($_POST['phone'] ?? ''));
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        if ($name === '' || mb_strlen($name, 'UTF-8') > 100) throw new VayumaProfileError('Enter a name of up to 100 characters.');
        if ($phone === null) throw new VayumaProfileError('Enter a valid phone number.');
        if (strlen($email) > 100 || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) throw new VayumaProfileError('Enter a valid email address.');
        $existsStmt = $conn->prepare('SELECT id, phone FROM users WHERE id=? LIMIT 1');
        $existsStmt->bind_param('i', $id); $existsStmt->execute(); $userExists = $existsStmt->get_result()->fetch_assoc(); $existsStmt->close();
        if (!$userExists) throw new VayumaProfileError('Your account could not be found. Please sign in again.');
        // A user-editable phone must not be used to acquire or transfer staff access.
        $oldPhone = vayuma_onboarding_phone((string)($userExists['phone'] ?? ''));
        $staffPhones = (string)(getenv('VAYUMA_ONBOARDING_PHONES') ?: '');
        if ($phone !== $oldPhone && (vayuma_onboarding_phone_allowed($phone, $staffPhones) || vayuma_onboarding_phone_allowed((string)($userExists['phone'] ?? ''), $staffPhones))) {
            throw new VayumaProfileError('Staff phone changes require support.');
        }
        [$phone1, $phone2, $phone3] = vayuma_login_phone_variants($phone);
        $stmt = $conn->prepare("SELECT id FROM users WHERE id<>? AND (phone_identity=? OR REPLACE(phone_identity, '.', '') IN (?, ?, ?) OR (email_identity IS NOT NULL AND email_identity=?)) LIMIT 1");
        $stmt->bind_param('isssss',$id,$phone,$phone1,$phone2,$phone3,$email); $stmt->execute(); $duplicate = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($duplicate) throw new VayumaProfileError('That phone number or email is already in use.');
        $avatarBytes = null; $dir = null;
        if (($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if (vayuma_avatar_uses_tidb()) {
                if (!vayuma_avatar_tidb_ready($conn)) throw new VayumaProfileError('Photo storage is unavailable.');
            } else {
                $dir = vayuma_avatar_directory();
                if (!$dir) throw new VayumaProfileError('Photo uploads are disabled until durable storage is configured.');
                $avatarTarget = $dir . '/avatar_' . $id . '.jpg';
                $avatarLock = fopen($dir . '/.avatar_' . $id . '.lock', 'c');
                if (!$avatarLock || !flock($avatarLock, LOCK_EX)) throw new VayumaProfileError('Photo storage is unavailable.');
                if (is_link($avatarTarget)) throw new VayumaProfileError('Photo storage is unavailable.');
                if (is_file($avatarTarget)) {
                    $avatarBackup = file_get_contents($avatarTarget);
                    if ($avatarBackup === false) throw new VayumaProfileError('Photo storage is unavailable.');
                }
            }
            $avatarBytes = vayuma_avatar_compress($_FILES['avatar']);
        }
        $conn->begin_transaction(); $transactionStarted = true;
        if (getenv('VAYUMA_ONBOARDING_ENABLED') === '1' || (is_string(getenv('VAYUMA_STAFF_ONBOARD_KEY')) && getenv('VAYUMA_STAFF_ONBOARD_KEY') !== '')) {
            require_once __DIR__ . '/includes/onboarding_lock.php';
            vayuma_onboarding_lock_phone($conn, $phone);
            // Do not let a profile edit take a phone reserved by someone else's intake.
            $reserved = $conn->prepare('SELECT id FROM onboarding_records WHERE phone_identity=? AND (claimed_by_user_id IS NULL OR claimed_by_user_id<>?) LIMIT 1 FOR UPDATE');
            if (!$reserved) throw new RuntimeException('Onboarding check unavailable');
            $reserved->bind_param('si', $phone, $id);
            if (!$reserved->execute()) throw new RuntimeException('Onboarding check unavailable');
            $record = $reserved->get_result()->fetch_assoc(); $reserved->close();
            if ($record) throw new VayumaProfileError('Unable to update the profile with those details. Contact support.');
        }
        $emailDb = $email !== '' ? $email : null;
        if ($avatarBytes !== null) {
            $key = 'avatar_' . $id . '.jpg';
            $stmt = $conn->prepare('UPDATE users SET full_name=?, phone=?, email=?, avatar_url=? WHERE id=?');
            $stmt->bind_param('ssssi',$name,$phone,$emailDb,$key,$id);
        } else {
            $stmt = $conn->prepare('UPDATE users SET full_name=?, phone=?, email=? WHERE id=?');
            $stmt->bind_param('sssi',$name,$phone,$emailDb,$id);
        }
        if (!$stmt->execute()) {
            if ($stmt->errno === 1062) throw new VayumaProfileError('That phone number or email is already in use.');
            throw new RuntimeException('Database update failed');
        }
        $stmt->close();
        if ($avatarBytes !== null && vayuma_avatar_uses_tidb()) {
            vayuma_avatar_tidb_put($conn, $id, $avatarBytes);
        } elseif ($avatarBytes !== null) {
            $temporary = tempnam($dir, '.avatar-');
            if (!$temporary || file_put_contents($temporary, $avatarBytes, LOCK_EX) !== strlen($avatarBytes) || !@rename($temporary, $dir . '/' . $key)) {
                if ($temporary && is_file($temporary)) unlink($temporary);
                throw new VayumaProfileError('Photo could not be saved.');
            }
            $avatarChanged = true;
            @chmod($dir . '/' . $key, 0600);
        }
        $conn->commit(); $transactionStarted = false;
        if ($avatarLock) { flock($avatarLock, LOCK_UN); fclose($avatarLock); $avatarLock = null; }
        $_SESSION['full_name'] = $name; $_SESSION['phone'] = $phone; $_SESSION['email'] = $email;
        $_SESSION['profile_hub_notice'] = 'Your profile has been updated.';
        header('Location: profile.php?return_to=' . rawurlencode($returnTo)); exit;
    } catch (Throwable $e) {
        if ($transactionStarted) { try { $conn->rollback(); } catch (Throwable $ignored) {} }
        if ($avatarChanged && $avatarTarget) {
            if ($avatarBackup === null) @unlink($avatarTarget);
            else { $restore = tempnam($dir, '.avatar-'); if ($restore && file_put_contents($restore, $avatarBackup, LOCK_EX) === strlen($avatarBackup)) @rename($restore, $avatarTarget); elseif ($restore) @unlink($restore); }
        }
        if ($avatarLock) { flock($avatarLock, LOCK_UN); fclose($avatarLock); }
        $error = vayuma_is_duplicate_key($e) ? 'That phone number or email is already in use.' : ($e instanceof VayumaProfileError ? $e->getMessage() : 'Unable to update your profile. Please try again.');
        error_log('VAYUMA profile update failed.');
    }
}
[$user, $plan] = vayuma_menu_data($conn);
[$status, $statusClass, $date] = vayuma_menu_plan($plan);
$h = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$storageReady = (vayuma_avatar_uses_tidb() ? vayuma_avatar_tidb_ready($conn) : vayuma_avatar_directory() !== null) && function_exists('imagecreatefromstring') && function_exists('imagejpeg');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>My profile | VAYUMA TRIX</title>
<link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/components.css"><link rel="stylesheet" href="frontend/css/profile-hub.css"><?php require __DIR__ . '/includes/pwa.php'; ?></head><body>
<header class="vy-profile__header"><a href="<?= $h($returnTo) ?>">← <?= $returnTo === 'role.php' ? 'Back to category selection' : ($returnTo === 'subscription.php' ? 'Back to subscription' : 'Back to dashboard') ?></a><?php require __DIR__ . '/includes/header_user_menu.php'; ?></header>
<main class="vy-profile"><h1>My profile</h1><p>Manage your personal details and profile photo.</p>
<?php if ($error): ?><p class="vy-profile__message" role="alert"><?= $h($error) ?></p><?php endif; ?><?php if ($notice): ?><p class="vy-profile__message" role="status"><?= $h($notice) ?></p><?php endif; ?>
<section class="vy-profile__card"><h2>Personal details</h2><form method="post" enctype="multipart/form-data" id="vy-profile-form"><?= vayuma_csrf_field() ?><input type="hidden" name="return_to" value="<?= $h($returnTo) ?>">
<label for="full_name">Full name</label><input id="full_name" name="full_name" required maxlength="100" value="<?= $h($user['full_name'] ?? '') ?>">
<label for="phone">Phone number</label><input id="phone" name="phone" type="tel" required maxlength="20" value="<?= $h($user['phone'] ?? '') ?>">
<label for="email">Email address (optional)</label><input id="email" name="email" type="email" maxlength="100" value="<?= $h($user['email'] ?? '') ?>">
<label for="avatar">Profile photo</label><input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" <?= $storageReady ? '' : 'disabled' ?>><small><?= $storageReady ? 'JPEG, PNG or WebP. Photos are cropped and compressed before saving.' : 'Photo uploads are unavailable until durable storage and image processing are configured.' ?></small><p id="vy-photo-status" role="status"></p>
<button type="submit">Save changes</button></form></section>
<section class="vy-profile__card"><h2>Subscription</h2><p><?= $h($plan['stakeholder_name'] ?? 'Plan unavailable') ?> · <?= $h($status) ?></p><?php if ($date): ?><p><?= $statusClass === 'grace' ? 'Grace ends' : 'Expiry' ?>: <?= $h($date) ?></p><?php endif; ?><a href="subscription.php">Manage subscription →</a></section>
<section class="vy-profile__card"><a href="<?= $h((vayuma_role_route((string)($_SESSION['role'] ?? ''), 'profile') ?? 'role.php') . '?from=' . rawurlencode($returnTo)) ?>">Edit stakeholder profile →</a><br><a href="role.php">Change account category →</a><br><a href="auth/logout.php">Sign out</a></section></main><script src="frontend/js/profile-photo.js" defer></script></body></html>
