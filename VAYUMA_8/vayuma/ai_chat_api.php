```php
<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(int $status, array $data): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['logged_in'] ?? false) !== true
) {
    respond(401, [
        'success' => false,
        'error' => 'Please log in to use VAYUMA AI.'
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');

    respond(405, [
        'success' => false,
        'error' => 'Only POST requests are allowed.'
    ]);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    respond(400, [
        'success' => false,
        'error' => 'Invalid request format.'
    ]);
}

$question = trim((string)($data['question'] ?? ''));
$language = (string)($data['language'] ?? 'en');

if (!in_array($language, ['en', 'sw'], true)) {
    $language = 'en';
}

if ($question === '' || mb_strlen($question) > 2000) {
    respond(400, [
        'success' => false,
        'error' => 'Enter a question of up to 2000 characters.'
    ]);
}

$config = require __DIR__ . '/config/ai.php';

$apiKey = trim((string)($config['api_key'] ?? ''));

if ($apiKey === '') {
    respond(503, [
        'success' => false,
        'error' => 'The AI service is not configured on the server yet.'
    ]);
}

$languageInstruction = $language === 'sw'
    ? 'Respond in clear, natural Kiswahili.'
    : 'Respond in clear English.';

$role = strtolower(trim((string)($_SESSION['role'] ?? 'user')));

$instructions = "
You are VAYUMA AI, an agricultural information assistant for users in Kenya.

$languageInstruction

Help with crop production, soil health, weather interpretation, crop pests
and diseases, livestock care, agricultural planning, and navigating the
VAYUMA platform.

Give practical, understandable guidance. Ask a brief follow-up question
when important information is missing. Do not invent live weather readings,
market prices, marketplace listings, or scientific facts. Explain when
information is uncertain or needs local verification.

For potentially serious animal illness, dangerous pesticide use, or severe
crop disease, recommend an appropriate qualified agricultural or veterinary
professional. Do not claim to diagnose with certainty.

The current user's VAYUMA role is: $role.

Do not claim to have accessed private VAYUMA records or live data unless
that information is explicitly provided in the conversation.
";

$payload = [
    'model' => $config['model'],
    'instructions' => $instructions,
    'input' => $question,
    'max_output_tokens' => 700,
];

$ch = curl_init($config['api_url']);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS => json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE
    ),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => (int)$config['timeout'],
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($response === false) {
    error_log('VAYUMA AI connection error: ' . $curlError);

    respond(502, [
        'success' => false,
        'error' => 'VAYUMA AI could not connect to the AI service. Please try again later.'
    ]);
}

$result = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    // Log only the HTTP status, never the API key or full request headers.
    error_log('VAYUMA AI API returned HTTP ' . $httpCode);

    if ($httpCode === 401) {
        respond(502, [
            'success' => false,
            'error' => 'The AI service could not authenticate. Check the server configuration.'
        ]);
    }

    if ($httpCode === 429) {
        respond(503, [
            'success' => false,
            'error' => 'The AI service is temporarily unavailable or its usage allowance has been reached.'
        ]);
    }

    respond(502, [
        'success' => false,
        'error' => 'The AI service returned an error. Please try again later.'
    ]);
}

$answer = '';

foreach (($result['output'] ?? []) as $item) {
    if (($item['type'] ?? '') !== 'message') {
        continue;
    }

    foreach (($item['content'] ?? []) as $content) {
        if (
            ($content['type'] ?? '') === 'output_text' &&
            isset($content['text'])
        ) {
            $answer .= $content['text'];
        }
    }
}

$answer = trim($answer);

if ($answer === '') {
    respond(502, [
        'success' => false,
        'error' => 'The AI service returned no text. Please try again.'
    ]);
}

respond(200, [
    'success' => true,
    'answer' => $answer,
    'language' => $language
]);

