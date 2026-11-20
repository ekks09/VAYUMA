<?php
/** Match Kenyan phone numbers entered in local or international form. */
function vayuma_login_phone_variants(string $identifier): array
{
    $phone = preg_replace('/[\s.\-\(\)]/', '', $identifier);
    if (!preg_match('/^\+?[0-9]{9,15}$/', $phone)) return [null, null, null];
    $local = $phone;
    if (preg_match('/^\+?254([0-9]{9})$/', $phone, $m)) $local = '0' . $m[1];
    if (preg_match('/^0([0-9]{9})$/', $local, $m)) return [$local, '254' . $m[1], '+254' . $m[1]];
    return [$phone, $phone, $phone];
}
