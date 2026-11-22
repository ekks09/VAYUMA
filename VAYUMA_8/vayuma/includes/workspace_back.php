<?php
// Never use an arbitrary referrer or unvalidated URL as a return destination.
if (isset($_SESSION['user_id']) && ($_SESSION['logged_in'] ?? false) === true) {
    require_once __DIR__ . '/role_routes.php';
    [$workspaceBack, $workspaceBackLabel] = vayuma_workspace_return_navigation($_GET['return_to'] ?? null);
    ?>
    <div class="vy-workspace-navigation">
      <nav class="vy-workspace-back" aria-label="Workspace navigation"><a href="<?= htmlspecialchars($workspaceBack, ENT_QUOTES, 'UTF-8') ?>"><span aria-hidden="true">&larr;</span> <?= htmlspecialchars($workspaceBackLabel, ENT_QUOTES, 'UTF-8') ?></a></nav>
      <?php if (!empty($workspaceBackLanguage) && function_exists('vayuma_language_selector')) vayuma_language_selector(); ?>
      <?php if (($workspaceBackMenu ?? true) === true) require __DIR__ . '/header_user_menu.php'; ?>
    </div>
    <?php
}
