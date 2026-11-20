<?php
require __DIR__ . '/../vayuma/includes/avatar_storage.php';
function check_avatar($ok, $message) { if (!$ok) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
class AvatarResult {
    public function free() {}
    public function fetch_row() { return [1]; }
    public function fetch_assoc() { return ['jpeg_bytes' => 'thumbnail']; }
}
class AvatarStatement {
    public function bind_param($types, &...$vars) { return true; }
    public function execute() { return true; }
    public function get_result() { return new AvatarResult(); }
    public function close() {}
}
class AvatarDb {
    public function query($sql) { return new AvatarResult(); }
    public function prepare($sql) { return new AvatarStatement(); }
}
$db = new AvatarDb();
putenv('VAYUMA_AVATAR_STORAGE=');
check_avatar(!vayuma_avatar_tidb_ready($db), 'disabled by default');
check_avatar(vayuma_avatar_tidb_get($db, 1) === null, 'read disabled');
putenv('VAYUMA_AVATAR_STORAGE=tidb');
check_avatar(vayuma_avatar_tidb_ready($db), 'table availability');
check_avatar(vayuma_avatar_tidb_exists($db, 1), 'avatar exists');
check_avatar(vayuma_avatar_tidb_get($db, 1) === 'thumbnail', 'avatar read');
vayuma_avatar_tidb_put($db, 1, str_repeat('x', 81920));
try { vayuma_avatar_tidb_put($db, 1, str_repeat('x', 81921)); check_avatar(false, 'reject oversized'); } catch (VayumaProfileError $e) {}
try { vayuma_avatar_tidb_put($db, 0, 'x'); check_avatar(false, 'reject invalid id'); } catch (VayumaProfileError $e) {}
echo "PASS TiDB avatar storage gating and size guards (mock database)\n";
