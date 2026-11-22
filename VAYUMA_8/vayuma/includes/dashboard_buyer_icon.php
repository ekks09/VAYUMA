<?php
$icons = ['Dashboard'=>'dashboard', 'Marketplace'=>'marketplace', 'My Orders'=>'orders', 'Deliveries'=>'transport', 'Agricultural Products'=>'crops', 'Recommended'=>'crops', 'VAYUMA TRIX AI'=>'ai', 'My Profile'=>'profile', 'Profile & Settings'=>'profile'];
$icon = $icons[$label] ?? 'checkmark';
?><span class="va-nav-icon"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"/></svg></span>
