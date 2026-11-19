<?php
session_start();
require_once __DIR__ . '/includes/language.php';

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

$dashboard = $dashboards[$role] ?? 'dashboard.php';

$userName = trim($_SESSION['name'] ?? 'VAYUMA User');
$userName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');

$roleLabel = ucwords(str_replace('_', ' ', $role ?: 'user'));
?>
<!DOCTYPE html>
<html lang="en">
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
</head>

<body>
<?php vayuma_language_selector(); ?>

<header class="top">
    <div class="va-profile-brand">
        VAYUMA
        <small>SMART AGRICULTURAL ECOSYSTEM</small>
    </div>

    <a class="va-profile-back" href="<?= htmlspecialchars($dashboard, ENT_QUOTES, 'UTF-8') ?>">
        ← <span data-en="Dashboard" data-sw="Dashibodi">Dashboard</span>
    </a>
</header>

<main class="container">

    <section class="intro">
        <div class="ai-icon"><svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-ai"/></svg></div>

        <h1>VAYUMA AI Assistant</h1>

        <p
            data-en="Your agricultural companion for smarter farming decisions."
            data-sw="Mshirika wako wa kilimo kwa maamuzi bora ya kilimo."
        >
            Your agricultural companion for smarter farming decisions.
        </p>
    </section>

    <div class="language-row">
        <label for="language" data-en="Language:" data-sw="Lugha:">
            Language:
        </label>

        <select class="va-profile-input" id="language" aria-label="Choose language">
            <option value="en">🇬🇧 English</option>
            <option value="sw">🇰🇪 Kiswahili</option>
        </select>
    </div>

    <section class="chat-card">

        <div class="chat-header">
            <div class="status-dot"></div>

            <div>
                <strong>VAYUMA AI</strong>
                <small data-en="Agricultural assistance" data-sw="Msaada wa kilimo">
                    Agricultural assistance
                </small>
            </div>

            <span class="status-label"
                  data-en="Setup stage"
                  data-sw="Hatua ya maandalizi">
                Setup stage
            </span>
        </div>

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

const translations = {
    en: {
        welcomeTitle: 'Welcome to VAYUMA AI!',
        welcomeText: 'I am being prepared to help you with crop production, soil health, weather, pests, livestock and agricultural market questions. Choose a suggested question below or type your own.',
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
        welcomeText: 'Ninaandaliwa kukusaidia kuhusu uzalishaji wa mazao, afya ya udongo, hali ya hewa, wadudu, mifugo na masoko ya kilimo. Chagua swali hapa chini au andika swali lako.',
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

    document.getElementById('welcomeTitle').textContent = t.welcomeTitle;
    document.getElementById('welcomeText').textContent = t.welcomeText;
    questionInput.placeholder = t.placeholder;
    sendBtn.textContent = t.send;

    document.querySelectorAll('.suggestion').forEach(button => {
        const key = button.dataset.question;
        const emoji = button.textContent.split(' ')[0];
        button.textContent = emoji + ' ' + t.suggestions[
            ['crops', 'soil', 'weather', 'disease', 'livestock', 'market'].indexOf(key)
        ].replace(/^[^ ]+ /, '');
    });
}

function addMessage(text, type) {
    const row = document.createElement('div');
    row.className = 'message ' + type;

    if (type === 'assistant') {
        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        avatar.textContent = '🌱';
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

    addMessage(question, 'user');
    questionInput.value = '';

    const lang = currentLanguage();

    sendBtn.disabled = true;
    questionInput.disabled = true;

    const loadingMessage = lang === 'sw'
        ? 'VAYUMA AI inafikiria...'
        : 'VAYUMA AI is thinking...';

    addMessage(loadingMessage, 'assistant');

    const loadingBubble = messages.lastElementChild.querySelector('.bubble');

    try {
        const response = await fetch('ai_chat_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                question: question,
                language: lang
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            loadingBubble.textContent = data.error ||
                (lang === 'sw'
                    ? 'Huduma ya AI haipatikani kwa sasa.'
                    : 'The AI service is currently unavailable.');
        } else {
            loadingBubble.textContent = data.answer;
        }
    } catch (error) {
        loadingBubble.textContent = lang === 'sw'
            ? 'Imeshindikana kuwasiliana na huduma ya AI. Tafadhali jaribu tena.'
            : 'Could not contact the AI service. Please try again.';
    } finally {
        sendBtn.disabled = false;
        questionInput.disabled = false;
        questionInput.focus();
        messages.scrollTop = messages.scrollHeight;
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
    messages.innerHTML = '';

    const row = document.createElement('div');
    row.className = 'message assistant';

    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    avatar.textContent = '🌱';

    const bubble = document.createElement('div');
    bubble.className = 'bubble';

    const title = document.createElement('div');
    title.className = 'welcome-title';
    title.textContent = translations[currentLanguage()].welcomeTitle;

    const text = document.createElement('div');
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
