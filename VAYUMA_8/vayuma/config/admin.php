<?php
// VAYUMA administrator allow-list.
// Preferred local XAMPP setup: create config/admin.local.php (not publicly distributed)
// returning: <?php return [1, 7];  // replace with trusted administrator user IDs.
$ADMIN_USER_IDS = [];
$localAdminFile = __DIR__ . '/admin.local.php';
if (is_file($localAdminFile)) {
    $localIds = require $localAdminFile;
    if (is_array($localIds)) {
        $ADMIN_USER_IDS = array_merge($ADMIN_USER_IDS, $localIds);
    }
}
$rawAdminIds = getenv('VAYUMA_ADMIN_USER_IDS') ?: '';
if ($rawAdminIds !== '') {
    $ADMIN_USER_IDS = array_merge($ADMIN_USER_IDS, preg_split('/\s*,\s*/', $rawAdminIds));
}
$ADMIN_USER_IDS = array_values(array_unique(array_filter(array_map('intval', $ADMIN_USER_IDS), static fn($id) => $id > 0)));
function is_vayuma_admin(int $userId): bool {
    global $ADMIN_USER_IDS;
    return in_array($userId, $ADMIN_USER_IDS, true);
}
