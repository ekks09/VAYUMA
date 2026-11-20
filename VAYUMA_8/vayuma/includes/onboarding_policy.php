<?php
/** Policy helpers for a future, separately authorized onboarding workspace.
 * A record is not an account, and a phone number is not proof of ownership.
 */
// A phone is an allowlist identifier, not proof of phone ownership. The account
// must also sign in and unlock with the separate staff passphrase.
function vayuma_onboarding_phone_allowed(string $phone, string $allowedPhones): bool
{
    $normalized = vayuma_onboarding_phone($phone);
    if ($normalized === null) return false;
    foreach (explode(',', $allowedPhones) as $candidate) {
        $allowed = vayuma_onboarding_phone(trim($candidate));
        if ($allowed !== null && hash_equals($allowed, $normalized)) return true;
    }
    return false;
}

/** Always look up the current account in the database; never trust a session phone. */
function vayuma_onboarding_authorized($conn, int $userId, string $allowedPhones): bool
{
    if ($userId <= 0 || $allowedPhones === '' || !$conn) return false;
    try {
        $stmt = $conn->prepare('SELECT phone FROM users WHERE id=? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('i', $userId);
        if (!$stmt->execute()) { $stmt->close(); return false; }
        $result = $stmt->get_result();
        if (!$result) { $stmt->close(); return false; }
        $row = $result->fetch_assoc(); $stmt->close();
        $phone = is_string($row['phone'] ?? null) ? $row['phone'] : '';
        if (!vayuma_onboarding_phone_allowed($phone, $allowedPhones)) return false;
        // Fail closed if a legacy equivalent phone belongs to another account.
        require_once __DIR__ . '/login_identity.php';
        [$phone1, $phone2, $phone3] = vayuma_login_phone_variants($phone);
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone_identity=? OR REPLACE(phone_identity, '.', '') IN (?, ?, ?) LIMIT 2");
        if (!$stmt) return false;
        $stmt->bind_param('ssss', $phone1, $phone1, $phone2, $phone3);
        if (!$stmt->execute()) { $stmt->close(); return false; }
        $result = $stmt->get_result();
        if (!$result) { $stmt->close(); return false; }
        $owner = $result->fetch_assoc(); $second = $result->fetch_assoc(); $stmt->close();
        return $owner && (int)$owner['id'] === $userId && !$second;
    } catch (Throwable $ignored) {
        error_log('VAYUMA staff authorization unavailable.');
        return false;
    }
}

/** Normalize phone numbers in the same way as the users.phone_identity index. */
function vayuma_onboarding_phone(string $raw): ?string
{
    $phone = preg_replace('/[\s.\-()]/', '', trim($raw));
    if (!is_string($phone) || !preg_match('/^\+?[0-9]{9,15}$/D', $phone)) return null;
    if (preg_match('/^\+?254([0-9]{9})$/D', $phone, $match)) return '0' . $match[1];
    return $phone;
}

/** Validation does not grant consent or create a VAYUMA login. */
function vayuma_onboarding_validate(array $input, array $counties): array
{
    $get = static fn(string $key): string => is_string($input[$key] ?? null) ? trim($input[$key]) : '';
    $name = $get('full_name');
    $phone = vayuma_onboarding_phone($get('phone'));
    $county = $get('county');
    $activity = $get('activity');
    $size = $get('farm_size');
    $unit = $get('farm_size_unit');
    $language = $get('preferred_language');
    $role = $get('role');
    require_once __DIR__ . '/role_routes.php';
    if ($name === '' || mb_strlen($name, 'UTF-8') > 100 || mb_strlen($name, 'UTF-8') < 2) return [null, 'Enter a full name of 2 to 100 characters.'];
    if ($phone === null) return [null, 'Enter a valid phone number.'];
    if (!in_array($county, $counties, true)) return [null, 'Select a valid county.'];
    if (vayuma_role_route($role, 'dashboard') === null) return [null, 'Select a valid stakeholder category.'];
    if (!in_array($language, ['en', 'sw'], true)) return [null, 'Select a preferred language.'];
    if ($get('consent') !== 'yes') return [null, 'Confirm the person agreed to this record.'];
    if (mb_strlen($activity, 'UTF-8') > 255) return [null, 'Crops or livestock must be 255 characters or less.'];
    if ($role === 'farmer' && $activity === '') return [null, 'Enter primary crops or livestock.'];
    if ($role === 'farmer' && ($size === '' || $unit === '')) return [null, 'Enter farm size and unit.'];
    if (($size === '') !== ($unit === '')) return [null, 'Provide both farm size and unit.'];
    if ($size !== '' && (!preg_match('/^(?:0|[1-9][0-9]{0,6})(?:\.[0-9]{1,2})?$/D', $size) || (float)$size <= 0 || (float)$size > 1000000 || !in_array($unit, ['acres', 'hectares'], true))) return [null, 'Enter a valid farm size and unit.'];
    return [['full_name'=>$name, 'phone'=>$phone, 'county'=>$county, 'role'=>$role, 'activity'=>$activity,
        'farm_size'=>$size !== '' ? $size : null, 'farm_size_unit'=>$unit !== '' ? $unit : null,
        'preferred_language'=>$language], null];
}
