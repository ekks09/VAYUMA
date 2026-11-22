<?php

function vayuma_role_route(string $role, string $destination): ?string
{
    $routes = [
        'farmer' => ['dashboard' => 'farmer_dashboard.php', 'profile' => 'profile_setup.php'],
        'buyer' => ['dashboard' => 'buyer_dashboard.php', 'profile' => 'buyer_profile.php'],
        'consumer' => ['dashboard' => 'consumer_dashboard.php', 'profile' => 'consumer_profile.php'],
        'transport_provider' => ['dashboard' => 'transport_dashboard.php', 'profile' => 'transport_profile.php'],
        'researcher' => ['dashboard' => 'researcher_dashboard.php', 'profile' => 'researcher_profile.php'],
        'extension_officer' => ['dashboard' => 'extension_dashboard.php', 'profile' => 'extension_profile.php'],
        'cooperative' => ['dashboard' => 'cooperative_dashboard.php', 'profile' => 'cooperative_profile.php'],
        'financial_institution' => ['dashboard' => 'finance_dashboard.php', 'profile' => 'finance_profile.php'],
        'input_supplier' => ['dashboard' => 'input_supplier_dashboard.php', 'profile' => 'input_supplier_profile.php'],
        'manufacturer' => ['dashboard' => 'manufacturer_dashboard.php', 'profile' => 'manufacturer_profile.php'],
        'warehouse_provider' => ['dashboard' => 'warehouse_provider_dashboard.php', 'profile' => 'warehouse_provider_profile.php'],
        'retailer' => ['dashboard' => 'retailer_dashboard.php', 'profile' => 'retailer_profile.php'],
        'agribusiness' => ['dashboard' => 'agribusiness_dashboard.php', 'profile' => 'agribusiness_profile.php'],
        'veterinary_officer' => ['dashboard' => 'veterinary_officer_dashboard.php', 'profile' => 'veterinary_officer_profile.php'],
        'insurance_provider' => ['dashboard' => 'insurance_provider_dashboard.php', 'profile' => 'insurance_provider_profile.php'],
        'government_officer' => ['dashboard' => 'government_officer_dashboard.php', 'profile' => 'government_officer_profile.php'],
        'ngo_partner' => ['dashboard' => 'ngo_partner_dashboard.php', 'profile' => 'ngo_partner_profile.php'],
        'agricultural_service_provider' => [
            'dashboard' => 'agricultural_service_provider_dashboard.php',
            'profile' => 'agricultural_service_provider_profile.php',
        ],
        'strategic_partner' => ['dashboard' => 'stakeholder_dashboard.php', 'profile' => 'stakeholder_profile.php'],
    ];

    $role = strtolower(trim($role));
    return $routes[$role][$destination] ?? null;
}

function vayuma_platform_home_route(): string
{
    if (isset($_SESSION['user_id']) && ($_SESSION['logged_in'] ?? false) === true) {
        return vayuma_role_route((string)($_SESSION['role'] ?? ''), 'dashboard') ?? 'dashboard.php';
    }

    return 'welcome.php';
}

/** Safe destination for links back from shared pages. */
function vayuma_dashboard_return_route(): string
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
        return 'login.php';
    }
    return vayuma_role_route((string)($_SESSION['role'] ?? ''), 'dashboard') ?? 'role.php';
}

/** Only the current role's dashboard or subscription can be a profile return target. */
function vayuma_profile_return_navigation($from): array
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) {
        return ['login.php', 'Back to login'];
    }
    $dashboard = vayuma_role_route((string)($_SESSION['role'] ?? ''), 'dashboard');
    if ($from === 'subscription.php') return ['subscription.php', 'Back to subscription'];
    if ($dashboard !== null) return [$dashboard, 'Back to dashboard'];
    return ['role.php', 'Back to category selection'];
}

/** Profile hub accepts only subscription or this user's own dashboard. */
function vayuma_profile_hub_return_route($candidate): string
{
    $dashboard = vayuma_dashboard_return_route();
    if ($dashboard === 'login.php') return $dashboard;
    if ($candidate === 'subscription.php') return 'subscription.php';
    return $dashboard;
}

/** Resolve only explicitly allowed local pages; all pages retain their own access checks. */
function vayuma_workspace_return_navigation($candidate): array
{
    $dashboard = vayuma_dashboard_return_route();
    if ($dashboard === 'login.php') return [$dashboard, 'Back to login'];
    $allowed = [
        'subscription.php' => 'Back to subscription',
        'profile.php' => 'Back to profile & settings',
        'market_connect.php' => 'Back to marketplace',
        'my_orders.php' => 'Back to my orders',
        'stakeholder_offers.php' => 'Back to exchange',
        'stakeholder_requests.php' => 'Back to requests',
        'stakeholder_directory.php' => 'Back to directory',
        'government_programmes.php' => 'Back to programmes',
        'transport_deliveries.php' => 'Back to deliveries',
    ];
    if (is_string($candidate) && isset($allowed[$candidate])) return [$candidate, $allowed[$candidate]];
    return [$dashboard, $dashboard === 'role.php' ? 'Back to account category' : 'Back to dashboard'];
}
