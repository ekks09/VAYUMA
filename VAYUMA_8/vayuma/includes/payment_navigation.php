<?php
require_once __DIR__ . '/role_routes.php';

function vayuma_payment_navigation(string $backHref, string $backLabel): void
{
    $homeUrl = htmlspecialchars(vayuma_platform_home_route(), ENT_QUOTES, 'UTF-8');
    $safeBackHref = htmlspecialchars($backHref, ENT_QUOTES, 'UTF-8');
    $safeBackLabel = htmlspecialchars($backLabel, ENT_QUOTES, 'UTF-8');

    echo '<nav class="va-payment-navigation" aria-label="Payment navigation">';
    echo '<a class="va-payment-navigation__brand" href="' . $homeUrl . '" aria-label="VAYUMA home">';
    echo '<img src="frontend/images/vayuma-logo.jpg" alt="">';
    echo '<span>VAYUMA</span>';
    echo '</a>';
    echo '<a class="va-payment-navigation__back" href="' . $safeBackHref . '">';
    echo '<span aria-hidden="true">←</span> ' . $safeBackLabel;
    echo '</a>';
    echo '</nav>';
}
