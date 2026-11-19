<?php
/** Payment processing is locked until the reconciler is implemented and audited.
 * Do not change this by setting an environment variable: the legacy callbacks
 * and retry paths are not safe to activate. History/receipts remain accessible.
 */
function vayuma_payment_processing_enabled(): bool
{
    return false;
}
function vayuma_require_payment_processing(): void
{
    if (!vayuma_payment_processing_enabled()) {
        http_response_code(503);
        header('Cache-Control: no-store');
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Payment processing is temporarily unavailable.';
        exit;
    }
}
