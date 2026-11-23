<?php
function vayuma_payment_feedback(string $title, string $message, int $statusCode = 400, string $backUrl = 'my_orders.php'): void
{
    http_response_code($statusCode);
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeBackUrl = htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8');
    $language = htmlspecialchars((string)($GLOBALS['current_language'] ?? 'en'), ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html lang="' . $language . '"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, interactive-widget=resizes-content"><title>' . $safeTitle . ' | VAYUMA TRIX</title>';
    echo '<link rel="stylesheet" href="frontend/css/tokens.css"><link rel="stylesheet" href="frontend/css/components.css"><link rel="stylesheet" href="frontend/css/layout.css"><link rel="stylesheet" href="assets/css/vayuma-ui.css?v=2"><link rel="stylesheet" href="frontend/css/user-menu.css?v=3">';
    echo '<style>.va-feedback{max-width:640px;margin:3rem auto;padding:0 1rem}.va-feedback__card{padding:2rem;background:var(--va-surface);border:1px solid var(--va-border);border-radius:var(--va-radius-lg);box-shadow:var(--va-shadow-sm)}.va-feedback__card h1{margin-bottom:1rem;color:var(--va-text-primary)}.va-feedback__card p{margin-bottom:1.5rem;color:var(--va-text-secondary)}</style></head><body>';
    echo '<main class="va-feedback" id="va-main-content">';
    $conn = $GLOBALS['conn'] ?? null;
    require __DIR__ . '/workspace_back.php';
    echo '<section class="va-feedback__card" role="alert"><h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p><a class="va-btn va-btn--primary" href="' . $safeBackUrl . '">Return to orders</a></section></main></body></html>';
    exit;
}
