<?php
// Check actual inline HTML heads, not PHP strings or API endpoints.
foreach (glob(__DIR__ . '/../vayuma/*.php') as $path) {
    $source = file_get_contents($path); $hasHead = false;
    foreach (token_get_all($source) as $token) {
        if (is_array($token) && $token[0] === T_INLINE_HTML && preg_match('/<head\s*>/i', $token[1])) $hasHead = true;
    }
    if ($hasHead && strpos($source, 'includes/pwa.php') === false && strpos($source, 'includes/ui_simple.php') === false) {
        fwrite(STDERR, 'Missing shared head: ' . basename($path) . "\n"); exit(1);
    }
}
echo "PASS shared PWA head coverage for root HTML templates\n";
