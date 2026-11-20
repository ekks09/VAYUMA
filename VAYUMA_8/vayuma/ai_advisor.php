<?php
session_start();
require_once __DIR__ . '/includes/language.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/role_routes.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['logged_in'] ?? false) !== true
) {
    header('Location: login.php');
    exit;
}

$role = strtolower(trim($_SESSION['role'] ?? ''));

$dashboards = [
    'farmer' => 'farmer_dashboard.php',
    'buyer' => 'buyer_dashboard.php',
    'extension_officer' => 'extension_dashboard.php',
    'veterinary_officer' => 'veterinary_officer_dashboard.php',
];

$dashboard = vayuma_dashboard_return_route();

$userName = trim($_SESSION['name'] ?? 'VAYUMA User');
$userName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

$roleLabel = ucwords(str_replace('_', ' ', $role ?: 'user'));
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_language, ENT_QUOTES, 'UTF-8') ?>">
<head><?php require __DIR__ . '/includes/pwa.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>VAYUMA AI Assistant</title>

<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-profile-page { max-width: 950px; margin: 0 auto; padding: var(--va-space-6); }
.va-profile-brand { text-align: center; margin-bottom: var(--va-space-6); }
.va-profile-brand h1 { color: var(--va-primary); font-size: var(--va-font-size-3xl); letter-spacing: 0.05em; margin: 0; }
.va-profile-brand p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-profile-card { background: var(--va-surface); border-radius: var(--va-radius-lg); padding: var(--va-space-6); box-shadow: var(--va-shadow-sm); border: 1px solid var(--va-border); }
.va-profile-heading { margin-bottom: var(--va-space-5); }
.va-profile-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: var(--va-space-2); }
.va-profile-heading h2 { font-size: var(--va-font-size-2xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-profile-heading p { color: var(--va-text-secondary); line-height: var(--va-line-height-relaxed); }
.va-profile-welcome { background: var(--va-primary-light); border-radius: var(--va-radius-md); padding: var(--va-space-4); margin: var(--va-space-6) 0; color: #17663a; line-height: var(--va-line-height-relaxed); }
.va-profile-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--va-space-4); }
.va-profile-form-group { display: flex; flex-direction: column; }
.va-profile-full { grid-column: 1 / -1; }
.va-profile-label-field { font-size: var(--va-font-size-sm); font-weight: var(--va-font-weight-bold); margin-bottom: var(--va-space-2); color: var(--va-text-secondary); }
.va-profile-input { width: 100%; padding: 0.75rem 1rem; font-size: var(--va-font-size-base); font-family: inherit; border: 1px solid var(--va-border); border-radius: var(--va-radius); background: var(--va-surface); color: var(--va-text-primary); }
.va-profile-input:focus { outline: none; border-color: var(--va-border-focus); box-shadow: 0 0 0 3px rgba(10, 138, 73, 0.1); }
.va-profile-hint { font-size: var(--va-font-size-xs); color: var(--va-text-muted); margin-top: var(--va-space-1); }
.va-profile-button-area { margin-top: var(--va-space-6); }
.va-profile-btn { width: 100%; border: none; padding: var(--va-space-4); background: var(--va-primary); color: #fff; border-radius: var(--va-radius); font-size: var(--va-font-size-base); font-weight: var(--va-font-weight-bold); cursor: pointer; }
.va-profile-btn:hover { background: var(--va-primary-hover); }
.va-profile-back { display: inline-block; margin-top: var(--va-space-4); color: var(--va-primary); text-decoration: none; font-weight: var(--va-font-weight-bold); }
.va-profile-error { background: #fde7e7; color: #a32121; padding: var(--va-space-3); border-radius: var(--va-radius-md); margin-bottom: var(--va-space-4); }
@media (max-width: 700px) {
  .va-profile-page { padding: var(--va-space-4); }
  .va-profile-card { padding: var(--va-space-4); }
  .va-profile-form-grid { grid-template-columns: 1fr; }
  .va-profile-full { grid-column: auto; }
}
</style>
<link rel="stylesheet" href="frontend/css/agriculture-tools.css?v=1">
<link rel="stylesheet" href="frontend/css/ai-assistant.css?v=1">
</head>

<body class="va-ai-tool">
<header class="top">
    <div class="va-profile-brand">
        VAYUMA
        <small>SMART AGRICULTURAL ECOSYSTEM</small>
    </div>

    <a class="va-profile-back" href="<?= htmlspecialchars($dashboard, ENT_QUOTES, 'UTF-8') ?>">
        ← <span data-en="Dashboard" data-sw="Dashibodi">Dashboard</span>
    </a>
</header>

<main class="container" id="main-content">

    <section class="intro">
        <div class="intro-icon" aria-hidden="true"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></div>

        <div class="intro-copy"><span class="intro-eyebrow" data-en="YOUR AGRICULTURAL COMPANION" data-sw="MSAIDIZI WAKO WA KILIMO">YOUR AGRICULTURAL COMPANION</span><h1>VAYUMA AI</h1>

        <p
            data-en="Your agricultural companion for smarter farming decisions."
            data-sw="Mshirika wako wa kilimo kwa maamuzi bora ya kilimo."
        >
            Your agricultural companion for smarter farming decisions.
        </p></div>
    </section>

    <div class="language-row">
        <label for="language" data-en="Language:" data-sw="Lugha:">
            Language:
        </label>

        <select class="va-profile-input" id="language" aria-label="Choose language">
            <option value="en">🇬🇧 English</option>
            <option value="sw" <?= $current_language === 'sw' ? 'selected' : '' ?>>🇰🇪 Kiswahili</option>
        </select>
    </div>

    <div class="assistant-layout">
    <section class="chat-card" aria-label="Conversation">

        <div class="chat-header">
            <div class="chat-mark" aria-hidden="true"><svg class="va-icon" width="24" height="24"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></div>

            <div>
                <strong>VAYUMA AI</strong>
                <small data-en="Agricultural assistance" data-sw="Msaada wa kilimo">
                    Agricultural assistance
                </small>
            </div>

            <span class="status-label"
                  data-en="Free helper by default"
                  data-sw="Msaidizi wa bure kwa chaguo-msingi">
                Free helper by default
            </span>
        </div>

        <p class="chat-context" data-en="Ask a question below. The free helper is rule-based; optional NVIDIA AI requires your permission." data-sw="Uliza swali hapa chini. Msaidizi wa bure hutumia kanuni; NVIDIA AI ya hiari inahitaji ruhusa yako.">Ask a question below. The free helper is rule-based; optional NVIDIA AI requires your permission.</p>
        <div
            class="messages"
            id="messages"
            role="log"
            aria-live="polite"
            aria-relevant="additions"
        >
            <div class="message assistant">
                <div class="avatar"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></div>

                <div class="bubble">
                    <div class="welcome-title" id="welcomeTitle">
                        Karibu to VAYUMA AI!
                    </div>

                    <div id="welcomeText">
                        I am being prepared to help you with crop production,
                        soil health, weather, pests, livestock and agricultural
                        market questions. Choose a suggested question below
                        or type your own.
                    </div>
                </div>
            </div>
        </div>

        <section class="suggestions">
            <h3 data-en="Try asking about" data-sw="Unaweza kuuliza kuhusu">
                Try asking about
            </h3>

            <div class="suggestion-list" id="suggestions">
                <button class="suggestion" data-question="crops">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Crop advice
                </button>

                <button class="suggestion" data-question="soil">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-soil"/></svg> Soil health
                </button>

                <button class="suggestion" data-question="weather">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg> Weather
                </button>

                <button class="suggestion" data-question="disease">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-diseases"/></svg> Crop diseases
                </button>

                <button class="suggestion" data-question="livestock">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg> Livestock
                </button>

                <button class="suggestion" data-question="market">
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Markets
                </button>
            </div>
        </section>

        <form class="composer" id="chatForm">

            <label class="composer-label" for="question" data-en="Your question" data-sw="Swali lako">Your question</label>
            <div class="input-row">
                <textarea
                    id="question"
                    maxlength="2000"
                    rows="1"
                    placeholder="Type your agricultural question..."
                    aria-label="Your question"
                    required
                ></textarea>

                <button type="submit" id="sendBtn">
                    Send ➤
                </button>
            </div>

            <details class="privacy-details"><summary><span data-en="Privacy &amp; farm context" data-sw="Faragha na taarifa za shamba">Privacy &amp; farm context</span><small data-en="Optional" data-sw="Hiari">Optional</small></summary>
            <fieldset><legend data-en="Privacy and optional context" data-sw="Faragha na taarifa za hiari">Privacy and optional context</legend>
                <p data-en="The free helper explains selected inputs using rules, not AI. If configured by the administrator, NVIDIA AI is optional, may make mistakes, and is subject to a daily limit." data-sw="Msaidizi wa bure hueleza taarifa ulizochagua kwa kutumia kanuni, si AI. Msimamizi akiisanidi, NVIDIA AI ni ya hiari, inaweza kukosea na ina kikomo cha kila siku.">The free helper explains selected inputs using rules, not AI. If configured by the administrator, NVIDIA AI is optional, may make mistakes, and is subject to a daily limit.</p>
                <label><input type="checkbox" id="externalConsent"> <span data-en="Send my question and selected context to NVIDIA for AI processing" data-sw="Tuma swali na taarifa nilizochagua kwa NVIDIA ili AI izichakate">Send my question and selected context to NVIDIA for AI processing</span></label><br>
                <label><input type="checkbox" id="shareWeather"> <span data-en="Include the forecast I chose to keep in this session" data-sw="Jumuisha utabiri niliouchagua kuhifadhi katika kipindi hiki">Include the forecast I chose to keep in this session</span></label><br>
                <label><input type="checkbox" id="shareSoil"> <span data-en="Include my latest saved soil report" data-sw="Jumuisha ripoti yangu ya hivi karibuni ya udongo">Include my latest saved soil report</span></label><br>
                <label for="cropContext" data-en="Crop (optional, your entry)" data-sw="Zao (hiari, umeingiza mwenyewe)">Crop (optional, your entry)</label><input id="cropContext" maxlength="80">
                <label for="stageContext" data-en="Growth stage (optional, your entry)" data-sw="Hatua ya ukuaji (hiari, umeingiza mwenyewe)">Growth stage (optional, your entry)</label><input id="stageContext" maxlength="80">
                <label for="locationContext" data-en="County (optional, your entry; exact county name for reviewed guidance)" data-sw="Kaunti (hiari; ingiza jina sahihi la kaunti kwa mwongozo uliopitiwa)">County (optional, your entry; exact county name for reviewed guidance)</label><input id="locationContext" maxlength="100">
                <p data-en="If you choose external AI, your question and a minimized summary of selected weather and soil results are sent to NVIDIA. Do not enter names, contact details, precise locations or other personal or confidential information in your question. NVIDIA trial inputs and outputs may be logged. Reviewed crop guidance is shown only when available; no rules are currently approved." data-sw="Ukichagua AI ya nje, swali lako na muhtasari uliopunguzwa wa hali ya hewa na vipimo vya udongo utatumwa kwa NVIDIA. Usiandike majina, mawasiliano, eneo sahihi au taarifa binafsi au za siri kwenye swali. NVIDIA inaweza kuhifadhi ingizo na majibu ya majaribio. Hakuna kanuni za mazao zilizoidhinishwa kwa sasa.">If you choose external AI, your question and a minimized summary of selected weather and soil results are sent to NVIDIA. Do not enter names, contact details, precise locations or other personal or confidential information in your question. NVIDIA trial inputs and outputs may be logged. Reviewed crop guidance is shown only when available; no rules are currently approved.</p>
            </fieldset></details>
            <p class="composer-note"
                data-en="Ask VAYUMA AI about crops, soil, livestock, diseases, or farming."
                data-sw="Uliza VAYUMA AI kuhusu mazao, udongo, mifugo, magonjwa au kilimo.">
                Ask VAYUMA AI about crops, soil, livestock, diseases, or farming.
            </p>

            <div class="actions">
                <button type="button" class="clear-btn" id="clearBtn"
                        data-en="Clear conversation"
                        data-sw="Futa mazungumzo">
                    Clear conversation
                </button>

                <a class="full-btn"
                   href="<?= htmlspecialchars($dashboard, ENT_QUOTES, 'UTF-8') ?>"
                   data-en="Return to dashboard →"
                   data-sw="Rudi kwenye dashibodi →">
                    Return to dashboard →
                </a>
            </div>

        </form>
    </section>
    <aside class="assistant-aside" aria-label="Helpful resources">
        <section class="resource-card">
            <span class="resource-kicker" data-en="YOUR TOOLKIT" data-sw="ZANA ZAKO">YOUR TOOLKIT</span>
            <h2 data-en="More ways to get help" data-sw="Njia zaidi za kupata msaada">More ways to get help</h2>
            <p data-en="Bring the right information to your next decision." data-sw="Pata taarifa sahihi kwa uamuzi wako unaofuata.">Bring the right information to your next decision.</p>
            <a class="resource-link" href="climate.php"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-weather"/></svg><span data-en="Climate guidance" data-sw="Mwongozo wa hali ya hewa">Climate guidance</span><span aria-hidden="true">→</span></a>
            <a class="resource-link" href="soil_records.php"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-soil"/></svg><span data-en="Soil test history" data-sw="Historia ya vipimo vya udongo">Soil test history</span><span aria-hidden="true">→</span></a>
            <a class="resource-link" href="stakeholder_directory.php?role=extension_officer"><svg class="va-icon" width="20" height="20" aria-hidden="true"><use href="frontend/images/icons.svg#va-icon-crops"/></svg><span data-en="Find an extension officer" data-sw="Tafuta afisa ugani">Find an extension officer</span><span aria-hidden="true">→</span></a>
        </section>
        <section class="trust-card">
            <h2 data-en="Guidance you can check" data-sw="Mwongozo unaoweza kuthibitisha">Guidance you can check</h2>
            <p data-en="Forecasts are estimates. Soil reports are user-entered. Ask a qualified professional before costly or risky decisions." data-sw="Utabiri ni makadirio. Ripoti za udongo zimeingizwa na mtumiaji. Uliza mtaalamu kabla ya maamuzi yenye gharama au hatari.">Forecasts are estimates. Soil reports are user-entered. Ask a qualified professional before costly or risky decisions.</p>
            <div class="source-links"><a href="https://open-meteo.com/" rel="noopener noreferrer">Open-Meteo</a><a href="https://www.fao.org/4/i0131e/i0131e.pdf" rel="noopener noreferrer">FAO soil guidance</a></div>
        </section>
    </aside>
    </div>

    <footer class="footer">
        VAYUMA — Smart Agricultural Ecosystem<br>
        <span data-en="Agricultural guidance should be verified when decisions involve significant costs or risks."
              data-sw="Thibitisha ushauri wa kilimo kabla ya kufanya maamuzi yenye gharama au hatari kubwa.">
            Agricultural guidance should be verified when decisions involve
            significant costs or risks.
        </span>
    </footer>

</main>

<script>
const languageSelect = document.getElementById('language');
const messages = document.getElementById('messages');
const form = document.getElementById('chatForm');
const questionInput = document.getElementById('question');
const sendBtn = document.getElementById('sendBtn');
let chatRequest = 0;
let chatController = null;
const suggestionButtons = [...document.querySelectorAll('.suggestion')];
function setChatBusy(busy) { sendBtn.disabled = busy; questionInput.disabled = busy; suggestionButtons.forEach(button => button.disabled = busy); }

const translations = {
    en: {
        welcomeTitle: 'Welcome to VAYUMA AI!',
        welcomeText: 'Ask about VAYUMA tools, your soil report or local forecast. Choose a topic or type a question. For risky decisions, speak to a qualified adviser.',
        placeholder: 'Type your agricultural question...',
        send: 'Send ➤',
        suggestions: [
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Crop advice',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-soil"/></svg> Soil health',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg> Weather',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-diseases"/></svg> Crop diseases',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg> Livestock',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Markets'
        ],
        questions: {
            crops: 'What crops are suitable for my area?',
            soil: 'How can I improve my soil fertility?',
            weather: 'How should weather conditions affect my farming plans?',
            disease: 'How can I identify and manage crop diseases?',
            livestock: 'What should I do if my animal appears sick?',
            market: 'How can I find produce on the VAYUMA marketplace?'
        },
        unavailable: 'The live AI engine has not been connected yet. Your question has been added to this chat interface, but it has not been sent to an AI model. Once the API is connected, VAYUMA AI will be able to generate a response.',
        userRole: 'Your account role is',
        cleared: 'The conversation has been cleared.',
        error: 'Please enter a question first.'
    },
    sw: {
        welcomeTitle: 'Karibu kwenye VAYUMA AI!',
        welcomeText: 'Uliza kuhusu huduma za VAYUMA, ripoti ya udongo au utabiri wa eneo lako. Chagua mada au andika swali. Kwa maamuzi yenye hatari, wasiliana na mtaalamu.',
        placeholder: 'Andika swali lako la kilimo...',
        send: 'Tuma ➤',
        suggestions: [
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Ushauri wa mazao',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-soil"/></svg> Afya ya udongo',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg> Hali ya hewa',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-diseases"/></svg> Magonjwa ya mazao',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-cattle"/></svg> Mifugo',
            '<svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-marketplace"/></svg> Masoko'
        ],
        questions: {
            crops: 'Ni mazao gani yanafaa katika eneo langu?',
            soil: 'Ninawezaje kuboresha rutuba ya udongo wangu?',
            weather: 'Ninawezaje kuzingatia hali ya hewa katika mipango yangu ya kilimo?',
            disease: 'Ninawezaje kutambua na kudhibiti magonjwa ya mazao?',
            livestock: 'Nifanye nini mnyama wangu anapoonekana kuwa mgonjwa?',
            market: 'Ninawezaje kupata mazao katika soko la VAYUMA?'
        },
        unavailable: 'Huduma ya AI ya moja kwa moja bado haijaunganishwa. Swali lako limeongezwa kwenye kiolesura hiki, lakini halijatumwa kwa mfumo wa AI. API itakapounganishwa, VAYUMA AI itaweza kutoa majibu.',
        userRole: 'Aina ya akaunti yako ni',
        cleared: 'Mazungumzo yamefutwa.',
        error: 'Tafadhali andika swali kwanza.'
    }
};

function currentLanguage() {
    return languageSelect.value;
}

function applyLanguage() {
    const lang = currentLanguage();
    const t = translations[lang];

    document.documentElement.lang = lang === 'sw' ? 'sw' : 'en';

    document.querySelectorAll('[data-en][data-sw]').forEach(element => {
        element.textContent = element.dataset[lang];
    });

    const welcomeTitle = document.getElementById('welcomeTitle');
    const welcomeText = document.getElementById('welcomeText');
    if (welcomeTitle) welcomeTitle.textContent = t.welcomeTitle;
    if (welcomeText) welcomeText.textContent = t.welcomeText;
    questionInput.placeholder = t.placeholder;
    sendBtn.textContent = t.send;

    document.querySelectorAll('.suggestion').forEach(button => {
        const key = button.dataset.question;
        const label = t.suggestions[
            ['crops', 'soil', 'weather', 'disease', 'livestock', 'market'].indexOf(key)
        ].replace(/^<svg\b[^>]*>.*?<\/svg>\s*/, '');
        const icon = button.querySelector('svg');
        button.replaceChildren();
        if (icon) button.append(icon, document.createTextNode(' '));
        button.append(document.createTextNode(label));
    });
}

function addMessage(text, type) {
    const row = document.createElement('div');
    row.className = 'message ' + type;

    if (type === 'assistant') {
        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        icon.setAttribute('class', 'va-icon'); icon.setAttribute('width', '20'); icon.setAttribute('height', '20'); icon.setAttribute('aria-hidden', 'true');
        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use'); use.setAttribute('href', 'frontend/images/icons.svg#va-icon-ai'); icon.appendChild(use); avatar.appendChild(icon);
        row.appendChild(avatar);
    }

    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.textContent = text;

    row.appendChild(bubble);
    messages.appendChild(row);
    messages.scrollTop = messages.scrollHeight;
}

async function submitQuestion(text) {
    const question = (text || '').trim();

    if (!question) {
        questionInput.focus();
        return;
    }

    if (chatController) return;
    const request = ++chatRequest;
    chatController = new AbortController();
    addMessage(question, 'user');
    questionInput.value = '';

    const lang = currentLanguage();

    setChatBusy(true);

    const loadingMessage = lang === 'sw'
        ? 'Tunaandaa jibu...'
        : 'Preparing a response...' ;

    addMessage(loadingMessage, 'assistant');

    const loadingBubble = messages.lastElementChild.querySelector('.bubble');

    try {
        const response = await fetch('ai_chat_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': <?= json_encode($_SESSION['vayuma_csrf_token'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>
            },
            credentials: 'same-origin',
            signal: chatController.signal,
            body: JSON.stringify({
                question: question,
                language: lang, external_consent: document.getElementById('externalConsent').checked, share_weather: document.getElementById('shareWeather').checked, share_soil: document.getElementById('shareSoil').checked, crop: document.getElementById('cropContext').value, stage: document.getElementById('stageContext').value, location: document.getElementById('locationContext').value
            })
        });

        const data = await response.json();
        if (request !== chatRequest) return;

        if (!response.ok || !data.success) {
            loadingBubble.textContent = data.error ||
                (lang === 'sw'
                    ? 'Huduma ya AI haipatikani kwa sasa.'
                    : 'The AI service is currently unavailable.');
        } else {
            loadingBubble.textContent = data.answer;
        }
    } catch (error) {
        if (request !== chatRequest || error.name === 'AbortError') return;
        loadingBubble.textContent = lang === 'sw'
            ? 'Imeshindikana kuwasiliana na huduma ya AI. Tafadhali jaribu tena.'
            : 'Could not contact the AI service. Please try again.';
    } finally {
        if (request === chatRequest) {
            chatController = null;
            setChatBusy(false);
            messages.scrollTop = messages.scrollHeight;
        }
    }
}


form.addEventListener('submit', function(event) {
    event.preventDefault();

    const question = questionInput.value.trim();

    if (!question) {
        questionInput.focus();
        return;
    }

    submitQuestion(question);
});

document.querySelectorAll('.suggestion').forEach(button => {
    button.addEventListener('click', function() {
        const key = button.dataset.question;
        submitQuestion(translations[currentLanguage()].questions[key]);
    });
});

languageSelect.addEventListener('change', applyLanguage);

document.getElementById('clearBtn').addEventListener('click', function() {
    ++chatRequest;
    if (chatController) chatController.abort();
    chatController = null;
    setChatBusy(false);
    messages.replaceChildren();

    const row = document.createElement('div');
    row.className = 'message assistant';

    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    icon.setAttribute('class', 'va-icon'); icon.setAttribute('width', '20'); icon.setAttribute('height', '20'); icon.setAttribute('aria-hidden', 'true');
    const use = document.createElementNS('http://www.w3.org/2000/svg', 'use'); use.setAttribute('href', 'frontend/images/icons.svg#va-icon-ai'); icon.appendChild(use); avatar.appendChild(icon);

    const bubble = document.createElement('div');
    bubble.className = 'bubble';

    const title = document.createElement('div');
    title.className = 'welcome-title';
    title.id = 'welcomeTitle';
    title.textContent = translations[currentLanguage()].welcomeTitle;

    const text = document.createElement('div');
    text.id = 'welcomeText';
    text.textContent = translations[currentLanguage()].welcomeText;

    bubble.appendChild(title);
    bubble.appendChild(text);
    row.appendChild(avatar);
    row.appendChild(bubble);
    messages.appendChild(row);
});

applyLanguage();
</script>

<script src="assets/js/vayuma-language.js" defer></script>
</body>
</html>
