<?php
require_once __DIR__ . '/role_routes.php';
[$profileBackUrl, $profileBackLabel] = vayuma_workspace_return_navigation($_GET['return_to'] ?? ($_GET['from'] ?? null));
?>
<nav class="va-profile-navigation" aria-label="Profile setup navigation">
  <a href="<?= htmlspecialchars($profileBackUrl, ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&larr;</span> <?= htmlspecialchars($profileBackLabel, ENT_QUOTES, 'UTF-8') ?></a>
  <span>Stakeholder profile setup</span>
  <?php if (function_exists('vayuma_language_selector')) vayuma_language_selector(); ?>
  <?php require __DIR__ . '/header_user_menu.php'; ?>
</nav>
