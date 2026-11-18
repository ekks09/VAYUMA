<?php
/**
 * VAYUMA Shared Assets Include
 * Outputs the design system CSS and JS for any page.
 *
 * Usage:
 *   require_once __DIR__ . '/assets.php';
 *   // Outputs <link> and <script> tags in <head>
 */
$vaDesignTokens = $vaDesignTokens ?? 'frontend/css/tokens.css';
$vaComponents = $vaComponents ?? 'frontend/css/components.css';
$vaComponentsAddition = $vaComponentsAddition ?? 'frontend/css/components_addition.css';
$vaLayout = $vaLayout ?? 'frontend/css/layout.css';
$vaPages = $vaPages ?? 'frontend/css/pages.css';
$vaUiJs = $vaUiJs ?? 'frontend/js/vayuma-ui.js';
$vaLanguageJs = $vaLanguageJs ?? 'assets/js/vayuma-language.js';

?>
<link rel="stylesheet" href="<?= htmlspecialchars($vaDesignTokens) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($vaComponents) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($vaComponentsAddition) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($vaLayout) ?>">
<link rel="stylesheet" href="<?= htmlspecialchars($vaPages) ?>">
<script src="<?= htmlspecialchars($vaUiJs) ?>" defer></script>
<script src="<?= htmlspecialchars($vaLanguageJs) ?>" defer></script>
