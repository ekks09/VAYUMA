<?php
// Shared, server-rendered dashboard entry; the destination enforces its own
// session and subscription rules. Keep the link usable without JavaScript.
$categoryLinkClass = ($categoryLinkStyle ?? '') === 'simple' ? 'va-nav-item' : 'va-dash-sidebar--nav-item';
require_once __DIR__ . '/role_routes.php';
$categoryLinkDashboard = vayuma_role_route((string)($_SESSION['role'] ?? ''), 'dashboard');
$categoryProfileUrl = 'profile.php' . ($categoryLinkDashboard !== null ? '?return_to=' . rawurlencode($categoryLinkDashboard) : '');
$personalLinkLabel = ($current_language ?? 'en') === 'sw' ? 'Badili taarifa binafsi' : 'Edit personal details';
$categoryLinkLabel = ($current_language ?? 'en') === 'sw' ? 'Chagua aina ya akaunti' : 'Select account category';
?>
<a class="<?= $categoryLinkClass ?> va-category-link" href="role.php"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-profile"/></svg><span><?= htmlspecialchars($categoryLinkLabel, ENT_QUOTES, 'UTF-8') ?></span></a>
<a class="<?= $categoryLinkClass ?> va-personal-details-link" href="<?= htmlspecialchars($categoryProfileUrl, ENT_QUOTES, 'UTF-8') ?>"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-profile"/></svg><span><?= htmlspecialchars($personalLinkLabel, ENT_QUOTES, 'UTF-8') ?></span></a>
<?php unset($categoryLinkClass, $categoryLinkLabel, $categoryLinkStyle, $categoryLinkDashboard, $categoryProfileUrl, $personalLinkLabel); ?>
