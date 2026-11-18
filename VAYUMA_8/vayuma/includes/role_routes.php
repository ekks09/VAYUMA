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
