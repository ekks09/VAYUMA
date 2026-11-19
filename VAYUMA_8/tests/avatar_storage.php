<?php
require __DIR__ . '/../vayuma/includes/avatar_storage.php';
function check($v, $message) { if (!$v) { fwrite(STDERR,"FAIL: $message\n"); exit(1); } }
putenv('VAYUMA_AVATAR_STORAGE_PERSISTENT=0'); putenv('VAYUMA_STORAGE_ROOT=' . sys_get_temp_dir());
check(vayuma_avatar_directory() === null, 'storage disabled by default');
putenv('VAYUMA_AVATAR_STORAGE_PERSISTENT=1'); putenv('VAYUMA_STORAGE_ROOT=' . realpath(__DIR__ . '/../vayuma'));
check(vayuma_avatar_directory() === null, 'web root rejected');
putenv('VAYUMA_STORAGE_ROOT=relative/path'); check(vayuma_avatar_directory() === null, 'relative path rejected');
try { vayuma_avatar_compress(['error'=>UPLOAD_ERR_INI_SIZE]); check(false, 'upload error'); } catch (VayumaProfileError $e) { }
echo "PASS storage opt-in, path checks and upload error handling\n";
