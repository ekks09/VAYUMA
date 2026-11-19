<?php
require_once __DIR__ . '/role_routes.php';
[$profileBackUrl, $profileBackLabel] = vayuma_profile_return_navigation($_GET['from'] ?? null);
?>
<nav class="va-profile-navigation" aria-label="Profile setup navigation">
  <a href="<?= htmlspecialchars($profileBackUrl, ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&larr;</span> <?= htmlspecialchars($profileBackLabel, ENT_QUOTES, 'UTF-8') ?></a>
  <span>Stakeholder profile setup</span>
</nav>
