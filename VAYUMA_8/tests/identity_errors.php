<?php
require __DIR__ . '/../vayuma/includes/identity_errors.php';
if (!vayuma_is_duplicate_key(new mysqli_sql_exception('duplicate', 1062))) exit(1);
if (vayuma_is_duplicate_key(new mysqli_sql_exception('foreign key', 1452))) exit(2);
if (vayuma_is_duplicate_key(new RuntimeException('generic', 1062))) exit(3);
echo "PASS MySQL duplicate-key classification (not generic integrity errors)\n";
