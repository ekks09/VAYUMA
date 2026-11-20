<?php
require_once __DIR__ . '/user_menu_data.php';
if (isset($_SESSION['user_id']) && ($_SESSION['logged_in'] ?? false) === true):
    [$vyUser, $vyPlan] = vayuma_menu_data($conn ?? null);
    [$vyStatus, $vyStatusClass, $vyDate] = vayuma_menu_plan($vyPlan);
    $vyName = trim((string)($vyUser['full_name'] ?? '')) ?: 'Member';
    $vyRole = ucwords(str_replace('_', ' ', (string)($_SESSION['role'] ?? 'Member')));
    $vyContact = trim((string)($vyUser['email'] ?? '')) ?: trim((string)($vyUser['phone'] ?? ''));
    $vyDashboard = vayuma_dashboard_return_route();
    $vyOrigin = basename((string)($_SERVER['SCRIPT_NAME'] ?? '')) === 'subscription.php' ? 'subscription.php' : $vyDashboard;
    $vyProfile = 'profile.php?return_to=' . rawurlencode($vyOrigin);
    $vyAvatarKey = 'avatar_' . (int)$_SESSION['user_id'] . '.jpg';
    require_once __DIR__ . '/avatar_storage.php';
    $vyAvatarDir = vayuma_avatar_uses_tidb() ? null : vayuma_avatar_directory();
    $vyHasAvatar = ($vyUser['avatar_url'] ?? '') === $vyAvatarKey && (vayuma_avatar_uses_tidb()
        ? vayuma_avatar_tidb_exists($conn ?? null, (int)$_SESSION['user_id'])
        : $vyAvatarDir !== null && is_file($vyAvatarDir . '/' . $vyAvatarKey) && !is_link($vyAvatarDir . '/' . $vyAvatarKey));
    require_once __DIR__ . '/onboarding_policy.php';
    $vyCanOnboard = getenv('VAYUMA_ONBOARDING_ENABLED') === '1' && vayuma_onboarding_authorized((int)$_SESSION['user_id'], (string)(getenv('VAYUMA_ONBOARDING_USER_IDS') ?: ''));
    $vyH = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<details class="vy-user-menu">
  <summary class="vy-user-menu__trigger" aria-label="Open account menu" aria-expanded="false">
    <?php if ($vyHasAvatar): ?><img class="vy-user-menu__avatar" src="avatar.php" alt=""><?php else: ?><span class="vy-user-menu__avatar vy-user-menu__initials"><?= $vyH(vayuma_user_initials($vyName)) ?></span><?php endif; ?>
    <span class="vy-user-menu__dot vy-user-menu__dot--<?= $vyH($vyStatusClass) ?>" aria-hidden="true"></span>
  </summary>
  <div class="vy-user-menu__popover">
    <div class="vy-user-menu__summary"><strong><?= $vyH($vyName) ?></strong><span class="vy-user-menu__role"><?= $vyH($vyRole) ?></span><?php if ($vyContact !== ''): ?><span class="vy-user-menu__contact"><?= $vyH($vyContact) ?></span><?php endif; ?></div>
    <div class="vy-user-menu__plan"><strong><?= $vyH($vyPlan['stakeholder_name'] ?? 'Subscription') ?></strong><span class="vy-user-menu__status vy-user-menu__status--<?= $vyH($vyStatusClass) ?>"><?= $vyH($vyStatus) ?></span><?php if ($vyDate): ?><small><?= $vyStatusClass === 'grace' ? 'Grace ends' : 'Expiry' ?>: <?= $vyH($vyDate) ?></small><?php endif; ?><a href="subscription.php">Manage subscription</a></div>
    <a class="vy-user-menu__link" href="<?= $vyH($vyDashboard) ?>">Dashboard</a>
    <a class="vy-user-menu__link" href="<?= $vyH($vyProfile) ?>">Profile &amp; Settings</a>
    <a class="vy-user-menu__link" href="feature_access.php?feature=ai">VAYUMA AI</a>
    <a class="vy-user-menu__link" href="role.php">Change account category</a>
    <?php if ($vyCanOnboard): ?><a class="vy-user-menu__link" href="onboarding.php">Staff Access</a><?php endif; ?>
    <a class="vy-user-menu__link" href="auth/logout.php">Sign out</a>
  </div>
</details>
<?php endif; ?>
