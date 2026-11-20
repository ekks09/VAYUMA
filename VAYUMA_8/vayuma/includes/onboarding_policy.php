<?php
/** Policy helpers for a future, separately authorized onboarding workspace.
 * A record is not an account, and a phone number is not proof of ownership.
 */
function vayuma_onboarding_authorized(int $userId, string $allowedIds): bool
{
    if ($userId <= 0) return false;
    foreach (explode(',', $allowedIds) as $candidate) {
        $candidate = trim($candidate);
        if ($candidate !== '' && ctype_digit($candidate) && (int)$candidate === $userId) return true;
    }
    return false;
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
