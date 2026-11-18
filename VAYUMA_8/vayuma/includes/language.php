<?php
/**
 * VAYUMA bilingual language service.
 * Add future Kenyan languages by extending the supported list and dictionaries.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$supported_languages = ['en', 'sw'];
if (!isset($_SESSION['vayuma_language']) || !in_array($_SESSION['vayuma_language'], $supported_languages, true)) {
    $_SESSION['vayuma_language'] = 'en';
}

// Change language without discarding other query-string parameters.
if (isset($_GET['lang'])) {
    $requested = strtolower(trim((string)$_GET['lang']));
    if (in_array($requested, $supported_languages, true)) {
        $_SESSION['vayuma_language'] = $requested;
    }
    $params = $_GET;
    unset($params['lang']);
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $query = http_build_query($params);
    header('Location: ' . $path . ($query !== '' ? '?' . $query : ''));
    exit;
}

$current_language = $_SESSION['vayuma_language'];

$translations = [
 'en' => [
  'language'=>'Language', 'english'=>'English', 'swahili'=>'Kiswahili',
  'welcome'=>'Welcome', 'welcome_back'=>'Welcome Back', 'dashboard'=>'Dashboard',
  'home'=>'Home', 'profile'=>'Profile', 'settings'=>'Settings', 'logout'=>'Logout',
  'login'=>'Login', 'register'=>'Register', 'create_account'=>'Create Account',
  'save'=>'Save', 'cancel'=>'Cancel', 'submit'=>'Submit', 'search'=>'Search',
  'marketplace'=>'Marketplace', 'orders'=>'Orders', 'payments'=>'Payments',
  'notifications'=>'Notifications', 'help'=>'Help', 'contact'=>'Contact Us',
  'crop_advice'=>'Crop Advice', 'soil_analysis'=>'Soil Analysis',
  'weather'=>'Weather Guidance', 'disease_helper'=>'Disease Helper',
  'livestock'=>'Livestock', 'farm_planner'=>'Farm Planner', 'market_prices'=>'Market Prices',
  'ask_ai'=>'Ask VAYUMA AI', 'send'=>'Send', 'clear_chat'=>'Clear Conversation',
  'produce'=>'Produce', 'add_produce'=>'Add Produce', 'buy'=>'Buy', 'sell'=>'Sell',
  'view_produce'=>'View Produce', 'success'=>'Operation completed successfully.',
  'error'=>'Something went wrong. Please try again.', 'no_results'=>'No results found.',
  'login_intro'=>'Sign in to continue your agricultural journey.',
  'email_phone'=>'Email or Phone Number', 'enter_email_phone'=>'Enter email or phone number',
  'password'=>'Password', 'enter_password'=>'Enter your password',
  'forgot_password'=>'Forgot password?', 'sign_in'=>'Sign In',
  'no_account'=>"Don't have a VAYUMA account?", 'secure_account'=>'Your account is securely protected',
  'invalid_login'=>'Invalid email/phone or password.', 'fill_required'=>'Please fill in all required fields.',
  'complete_profile'=>'Please complete your profile first.', 'login_failed'=>'Unable to login. Please try again.',
  'tagline'=>'Connecting Agriculture. Empowering Every Harvest.',
  'future_agriculture'=>'The Future of Smart Agriculture Begins Here.',
  'climate_guidance'=>'Climate Guidance', 'climate_desc'=>'Make smarter weather-based decisions.',
  'soil_intelligence'=>'Soil Intelligence', 'soil_desc'=>'Understand your soil and crop needs.',
  'ai_guidance'=>'Get intelligent agricultural guidance.',
  'market_connect'=>'Market Connect', 'market_desc'=>'Connect produce with verified buyers.',
  'welcome_intro'=>'Connecting Agriculture. Empowering Every Harvest. Bringing people and opportunity into one smart ecosystem.',
  'welcome_title'=>'The Future of Smart Agriculture Begins Here.',
  'ecosystem_intro'=>'VAYUMA brings farmers, buyers, consumers, transport providers, researchers, extension officers, cooperatives and financial partners together in one integrated agricultural ecosystem.'
 ],
 'sw' => [
  'language'=>'Lugha', 'english'=>'Kiingereza', 'swahili'=>'Kiswahili',
  'welcome'=>'Karibu', 'welcome_back'=>'Karibu Tena', 'dashboard'=>'Dashibodi',
  'home'=>'Nyumbani', 'profile'=>'Wasifu', 'settings'=>'Mipangilio', 'logout'=>'Ondoka',
  'login'=>'Ingia', 'register'=>'Jisajili', 'create_account'=>'Fungua Akaunti',
  'save'=>'Hifadhi', 'cancel'=>'Ghairi', 'submit'=>'Wasilisha', 'search'=>'Tafuta',
  'marketplace'=>'Soko', 'orders'=>'Maagizo', 'payments'=>'Malipo',
  'notifications'=>'Arifa', 'help'=>'Msaada', 'contact'=>'Wasiliana Nasi',
  'crop_advice'=>'Ushauri wa Mazao', 'soil_analysis'=>'Uchambuzi wa Udongo',
  'weather'=>'Mwongozo wa Hali ya Hewa', 'disease_helper'=>'Msaada wa Magonjwa',
  'livestock'=>'Mifugo', 'farm_planner'=>'Mpangaji wa Kilimo', 'market_prices'=>'Bei za Soko',
  'ask_ai'=>'Uliza VAYUMA AI', 'send'=>'Tuma', 'clear_chat'=>'Futa Mazungumzo',
  'produce'=>'Mazao', 'add_produce'=>'Ongeza Mazao', 'buy'=>'Nunua', 'sell'=>'Uza',
  'view_produce'=>'Tazama Mazao', 'success'=>'Operesheni imekamilika kikamilifu.',
  'error'=>'Hitilafu imetokea. Tafadhali jaribu tena.', 'no_results'=>'Hakuna matokeo yaliyopatikana.',
  'login_intro'=>'Ingia ili kuendelea na safari yako ya kilimo.',
  'email_phone'=>'Barua pepe au Nambari ya Simu', 'enter_email_phone'=>'Weka barua pepe au nambari ya simu',
  'password'=>'Nenosiri', 'enter_password'=>'Weka nenosiri lako',
  'forgot_password'=>'Umesahau nenosiri?', 'sign_in'=>'Ingia',
  'no_account'=>'Huna akaunti ya VAYUMA?', 'secure_account'=>'Akaunti yako imelindwa kwa usalama',
  'invalid_login'=>'Barua pepe/simu au nenosiri si sahihi.', 'fill_required'=>'Tafadhali jaza sehemu zote zinazohitajika.',
  'complete_profile'=>'Tafadhali kamilisha wasifu wako kwanza.', 'login_failed'=>'Imeshindikana kuingia. Tafadhali jaribu tena.',
  'tagline'=>'Kuunganisha Kilimo. Kuwezesha Kila Mavuno.',
  'future_agriculture'=>'Mustakabali wa Kilimo Bora Una Anza Hapa.',
  'climate_guidance'=>'Mwongozo wa Hali ya Hewa', 'climate_desc'=>'Fanya maamuzi bora kwa kutumia taarifa za hali ya hewa.',
  'soil_intelligence'=>'Taarifa za Udongo', 'soil_desc'=>'Elewa mahitaji ya udongo na mazao yako.',
  'ai_guidance'=>'Pata mwongozo mahiri wa kilimo.',
  'market_connect'=>'Unganisha na Soko', 'market_desc'=>'Unganisha mazao na wanunuzi waliothibitishwa.',
  'welcome_intro'=>'Kuunganisha Kilimo. Kuwezesha Kila Mavuno. Kuleta watu na fursa katika mfumo mmoja mahiri wa kilimo.',
  'welcome_title'=>'Mustakabali wa Kilimo Bora Una Anza Hapa.',
  'ecosystem_intro'=>'VAYUMA inawaleta wakulima, wanunuzi, watumiaji, wasafirishaji, watafiti, maafisa ugani, vyama vya ushirika na washirika wa kifedha pamoja katika mfumo mmoja wa kilimo.'
 ]
];

function t(string $key): string {
    global $translations, $current_language;
    return $translations[$current_language][$key] ?? $translations['en'][$key] ?? $key;
}

function vayuma_language_url(string $language): string {
    $params = $_GET;
    unset($params['lang']);
    $params['lang'] = $language;
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    return $path . '?' . http_build_query($params);
}

function vayuma_language_selector(): void {
    global $current_language;
    ?>
    
<style>
.va-language-selector {
  display: flex; align-items: center; justify-content: flex-start; gap: var(--va-space-2);
  flex-wrap: nowrap; padding: var(--va-space-2) var(--va-space-3);
  font-size: var(--va-font-size-sm); position: relative; z-index: 1000;
}
.va-language-selector span { font-weight: var(--va-font-weight-semibold); }
.va-language-selector a {
  display: inline-block; padding: var(--va-space-2) var(--va-space-3);
  border: 1px solid var(--va-border); border-radius: var(--va-radius-full);
  text-decoration: none; color: var(--va-text-secondary); font-weight: var(--va-font-weight-semibold);
  background: var(--va-surface);
}
.va-language-selector a.active {
  background: var(--va-primary); color: #fff; border-color: var(--va-primary);
}
.va-language-selector a:focus-visible { outline: 3px solid var(--va-primary); outline-offset: 2px; }
@media (max-width: 600px) { .va-language-selector { justify-content: flex-start; gap: var(--va-space-1); } }

.va-location-fields {
  border: 1px solid var(--va-border); border-radius: var(--va-radius-md);
  padding: var(--va-space-4); margin: var(--va-space-5) 0;
}
.va-location-fields legend {
  font-weight: var(--va-font-weight-bold); padding: 0 var(--va-space-2);
  color: var(--va-primary);
}
.va-location-grid {
  display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--va-space-4);
}
.va-location-grid label {
  display: flex; flex-direction: column; gap: var(--va-space-1);
  font-weight: var(--va-font-weight-semibold);
}
.va-location-grid input, .va-location-grid select {
  width: 100%; padding: var(--va-space-3); border: 1px solid var(--va-border);
  border-radius: var(--va-radius); font: inherit; background: #fff;
}
.va-location-note {
  font-size: var(--va-font-size-xs); color: var(--va-text-muted);
  margin: var(--va-space-3) 0 0;
}
@media (max-width: 650px) { .va-location-grid { grid-template-columns: 1fr; } }
</style>
    <nav class="va-language-selector" aria-label="Language selection">
        <span><?= htmlspecialchars(t('language'), ENT_QUOTES, 'UTF-8') ?>:</span>
        <a href="<?= htmlspecialchars(vayuma_language_url('en'), ENT_QUOTES, 'UTF-8') ?>" lang="en" hreflang="en" class="<?= $current_language === 'en' ? 'active' : '' ?>"<?= $current_language === 'en' ? ' aria-current="true"' : '' ?>>English</a>
        <a href="<?= htmlspecialchars(vayuma_language_url('sw'), ENT_QUOTES, 'UTF-8') ?>" lang="sw" hreflang="sw" class="<?= $current_language === 'sw' ? 'active' : '' ?>"<?= $current_language === 'sw' ? ' aria-current="true"' : '' ?>>Kiswahili</a>
    </nav>
    <script src="assets/js/vayuma-language.js?v=language-2" defer></script>
    <?php
}
