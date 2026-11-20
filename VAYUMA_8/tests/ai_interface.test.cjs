const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '../vayuma');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
test('AI interface retains explicit opt-in, context and safe conversation reset', () => {
  const page = read('ai_advisor.php');
  for (const id of ['externalConsent', 'question', 'chatForm', 'clearBtn', 'welcomeTitle', 'welcomeText']) assert.match(page, new RegExp(`id="${id}"`));
  assert.match(page, /frontend\/css\/ai-assistant\.css/);
  assert.match(page, /chatController\.abort\(\)/);
  assert.match(page, /request !== chatRequest/);
  assert.match(page, /role="log"/);
});
test('AI shortcut is signed-in allowlisted and enters through the feature gate', () => {
  const pwa = read('includes/pwa.php');
  assert.match(pwa, /\$_SESSION\['logged_in'\]/);
  assert.match(pwa, /in_array\(basename\(\$_SERVER\['SCRIPT_NAME'\]/);
  const allowlist = pwa.match(/\$aiShortcutPages = \[(.*?)\];/s)[1];
  assert.doesNotMatch(allowlist, /admin_dashboard|ai_advisor|login\.php/);
  assert.match(read('frontend/js/ai-shortcut.js'), /feature_access\.php\?feature=ai/);
  assert.match(read('frontend/css/ai-shortcut.css'), /va-install-notice/);
});
test('NVIDIA gets curated guidance, not live account access', () => {
  const guide = read('includes/ai_platform_guide.php');
  assert.match(guide, /role-specific dashboard/);
  assert.match(guide, /cannot see current listings/);
  assert.match(guide, /Payment processing is currently unavailable/);
  assert.match(read('includes/nvidia_advisor.php'), /vayuma_ai_guide\(\$question, \$language, true\)/);
});
