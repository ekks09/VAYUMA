<?php
/** Only fixed, caller-selected tables are permitted. Refuse ambiguous records. */
function vayuma_existing_profile($conn, string $table, int $userId): array
{
    if (!in_array($table, ['cooperative_profiles', 'researcher_profiles', 'financial_profiles'], true)) {
        throw new InvalidArgumentException('Unsupported profile');
    }
    $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id=? ORDER BY id LIMIT 2");
    if (!$stmt) throw new RuntimeException('Profile lookup failed');
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) throw new RuntimeException('Profile lookup failed');
    $result = $stmt->get_result();
    if (!$result) throw new RuntimeException('Profile lookup failed');
    $profile = $result->fetch_assoc() ?: [];
    if ($result->fetch_assoc()) throw new RuntimeException('Ambiguous profile');
    $stmt->close();
    return $profile;
}
function vayuma_profile_value($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
