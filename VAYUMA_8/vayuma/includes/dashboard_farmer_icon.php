<?php
// Navigation icons map to existing VAYUMA symbols; all destinations remain unchanged.
$farmerIcons = [
    'Dashboard'=>'dashboard', 'Climate Guidance'=>'weather', 'Soil Analysis'=>'soil',
    'Farming Score'=>'target', 'Crop Opportunities'=>'crops', 'Crop Calendar'=>'education',
    'Disease Helper'=>'diseases', 'Budget Planner'=>'money', 'Market Connect'=>'marketplace',
    'Add Produce'=>'crops', 'My Produce'=>'crops', 'My Orders'=>'orders',
    'Produce Requests'=>'orders', 'VAYUMA TRIX AI'=>'ai', 'Profile & Settings'=>'profile',
];
$farmerIcon = $farmerIcons[$label] ?? 'checkmark';
?><span class="va-nav-icon"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-<?= htmlspecialchars($farmerIcon, ENT_QUOTES, 'UTF-8') ?>"/></svg></span>
