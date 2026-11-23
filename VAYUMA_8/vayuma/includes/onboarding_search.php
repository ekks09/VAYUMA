<?php
// Search stays inside a CSRF-protected POST, never in URLs or logs.
function vayuma_onboarding_search_tokens(string $query): array {
    $query = trim($query);
    if (strlen($query) > 200) throw new DomainException('Search is too long.');
    $parts = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($parts) > 12) throw new DomainException('Use at most 12 search terms.');
    $tokens = [];
    foreach ($parts as $index => $part) {
        $digits = preg_replace('/\D/', '', $part);
        $numeric = $digits !== '' && preg_match('/^[+\d().-]+$/', $part);
        // A separately typed country code is a prefix, not a search term.
        if ($numeric && $digits === '254' && isset($parts[$index + 1]) && preg_match('/^[+\d().-]+$/', $parts[$index + 1])) continue;
        if ($numeric && strlen($digits) > 3 && str_starts_with($digits, '254')) $digits = substr($digits, 3);
        elseif ($numeric && strlen($digits) > 3 && str_starts_with($digits, '0')) $digits = substr($digits, 1);
        $tokens[] = [$numeric, $numeric ? $digits : (function_exists('mb_strtolower') ? mb_strtolower($part, 'UTF-8') : strtolower($part))];
    }
    return $tokens;
}
function vayuma_onboarding_matches(array $row, array $tokens): bool {
    $fields = ['full_name','county','farmer_location','location','stakeholder_role','current_role','activity','farm_size','farm_size_unit','preferred_language','farm_name','sub_county'];
    $text = implode(' ', array_map(static fn($key) => (string)($row[$key] ?? ''), $fields));
    $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $phone = preg_replace('/\D/', '', (string)($row['phone_identity'] ?? $row['phone'] ?? ''));
    if (str_starts_with($phone, '254')) $phone = substr($phone, 3);
    elseif (str_starts_with($phone, '0')) $phone = substr($phone, 1);
    foreach ($tokens as [$numeric, $token]) {
        if (!str_contains($text, $token) && (!$numeric || !str_contains($phone, $token))) return false;
    }
    return true;
}
