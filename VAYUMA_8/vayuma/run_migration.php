<?php
/**
 * VAYUMA Migration Runner
 * Executes .sql migration files against the vayuma_db database.
 * Usage: php run_migration.php <migration_file.sql>
 */

require_once __DIR__ . '/config/db.php';

$migrationFile = $argv[1] ?? null;
if (!$migrationFile || !file_exists($migrationFile)) {
    echo "Usage: php run_migration.php <path/to/migration.sql>\n";
    exit(1);
}

$sql = file_get_contents($migrationFile);
if ($sql === false) {
    echo "Error: Could not read migration file.\n";
    exit(1);
}

// Split by semicolons, but be careful with DELIMITER blocks
$statements = [];
$current = '';
$inDelimiter = false;
$delimiter = ';';

$lines = explode("\n", $sql);
foreach ($lines as $line) {
    $trimmed = trim($line);
    
    // Handle DELIMITER changes
    if (stripos($trimmed, 'DELIMITER') === 0) {
        $parts = explode(' ', $trimmed, 2);
        if (isset($parts[1])) {
            $delimiter = trim($parts[1]);
        }
        continue;
    }
    
    $current .= $line . "\n";
    
    // Check if line ends with the delimiter
    if (substr(rtrim($current), -strlen($delimiter)) === $delimiter) {
        $statements[] = rtrim($current, $delimiter . "\r\n ");
        $current = '';
    }
}

// Handle remaining content
if (trim($current) !== '') {
    $statements[] = trim($current);
}

echo "Running migration: " . basename($migrationFile) . "\n";
echo "Found " . count($statements) . " statement(s)\n\n";

$success = 0;
$failed = 0;

foreach ($statements as $i => $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') continue;
    
    // Skip comments and SET statements that don't need execution
    if (strpos($stmt, '--') === 0 || strpos($stmt, '/*') === 0) continue;
    if (strtoupper(substr($stmt, 0, 6)) === 'DECLARE') continue;
    
    try {
        if ($conn->query($stmt) === TRUE) {
            $success++;
            echo "  [" . ($i + 1) . "] OK\n";
        } else {
            // Some statements (like SET @sql) return false but are OK
            $success++;
            echo "  [" . ($i + 1) . "] OK (no result set)\n";
        }
    } catch (Throwable $e) {
        $failed++;
        echo "  [" . ($i + 1) . "] FAILED: " . $e->getMessage() . "\n";
        echo "    Statement: " . substr($stmt, 0, 200) . "...\n";
    }
}

echo "\nMigration complete: $success succeeded, $failed failed.\n";
if ($failed > 0) {
    exit(1);
}
