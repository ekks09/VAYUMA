<?php
/** Render an access denial without changing the caller's authorization checks. */
function vayuma_workspace_denied(string $message): void
{
    http_response_code(403);
    ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Access unavailable | VAYUMA</title><link rel="stylesheet" href="assets/css/vayuma-ui.css?v=1"></head><body><main class="wrap vy-denied-page"><?php require __DIR__ . '/workspace_back.php'; ?><section class="card"><h1>Access unavailable</h1><p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p></section></main></body></html><?php
    exit;
}
