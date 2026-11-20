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
test('AI is the default with explicit third-party consent and honest mode labels', () => {
  const page=read('ai_advisor.php');
  assert.match(page, /class="ai-provider-choice"[\s\S]*?id="externalConsent"/);
  assert.doesNotMatch(page.match(/<input[^>]*id="externalConsent"[^>]*>/)[0], /\bchecked\b/);
  assert.ok(page.indexOf('id="externalConsent"') < page.indexOf('class="privacy-details"'));
  assert.match(page, /externalConsent\.checked/); assert.match(page, /data\.mode === 'ai_support'/);
  assert.match(page, /third-party AI service/); assert.match(page, /may log inputs and outputs/);
  assert.match(page, /<option value="ai"[\s\S]*?<option value="rules"/);
  assert.match(page, /useAi && !externalConsent.checked/); assert.match(page, /useAi && externalConsent.checked/);
  assert.doesNotMatch(page, /NVIDIA/);
});
test('farmer dashboard uses working destinations and does not present voice or SMS as available', () => {
  const dashboard=read('farmer_dashboard.php');
  assert.match(dashboard, /'VAYUMA AI' => 'feature_access\.php\?feature=ai'/);
  assert.match(dashboard, /'Profile & Settings' => 'profile\.php\?return_to=farmer_dashboard\.php'/);
  assert.match(dashboard, /Voice support and SMS \/ USSD are not available yet/);
  assert.match(dashboard, /farmer-quick-links/);
  for (const file of ['climate.php','soil_analysis.php','market_connect.php','profile.php']) assert.ok(fs.existsSync(path.join(root,file)), file);
  assert.match(read('includes/dashboard_farmer_icon.php'), /'Climate Guidance'=>'weather'/);
  assert.match(read('includes/dashboard_farmer_icon.php'), /'VAYUMA AI'=>'ai'/);
});
