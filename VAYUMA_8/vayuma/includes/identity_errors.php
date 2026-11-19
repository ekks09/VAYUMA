<?php
/** Only the MySQL duplicate-key code means duplicate; SQLSTATE 23000 also covers foreign keys. */
function vayuma_is_duplicate_key(Throwable $error): bool {
    return $error instanceof mysqli_sql_exception && (int)$error->getCode() === 1062;
}
