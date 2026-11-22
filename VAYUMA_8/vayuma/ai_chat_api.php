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

$token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if ($token === '' || empty($_SESSION['vayuma_csrf_token']) || !hash_equals($_SESSION['vayuma_csrf_token'], $token)) respond(403, ['success'=>false,'error'=>'Your session expired. Refresh and try again.']);
require_once __DIR__ . '/includes/feature_access_policy.php';
$vayumaDbReadinessProbe = true;
require_once __DIR__ . '/config/db.php';
try {
    if (!isset($conn) || !$conn) throw new RuntimeException('Database unavailable');
    $decision = vayuma_feature_access_decision($conn, $_SESSION);
    if ($decision !== 'allow') respond(403, ['success'=>false,'error'=>'Complete your profile and subscription before using VAYUMA AI.']);
} catch (Throwable $ignored) { respond(503, ['success'=>false,'error'=>'Unable to check account setup. Please try again later.']); }
$now = time();
$_SESSION['ai_requests'] = array_values(array_filter($_SESSION['ai_requests'] ?? [], static fn($t) => is_int($t) && $t > $now - 3600));
if (count($_SESSION['ai_requests']) >= 20) respond(429, ['success'=>false,'error'=>'Session limit reached. Please try again later.']);
$_SESSION['ai_requests'][] = $now;
$raw = file_get_contents('php://input');
if (strlen($raw) > 40000) respond(413, ['success'=>false,'error'=>'Request is too large.']);
$data = json_decode($raw, true);

if (!is_array($data)) {
    respond(400, [
        'success' => false,
        'error' => 'Invalid request format.'
    ]);
}
// Older clients may still explicitly decline external processing. Never send
// their message to a provider; the new disclosed chat omits this legacy field.
if (array_key_exists('external_consent', $data) && $data['external_consent'] !== true) {
    respond(400, ['success'=>false,'error'=>'Your message was not sent. Refresh to use the current AI chat.']);
}

if (!is_string($data['question'] ?? null) || !is_string($data['language'] ?? 'en')) respond(400, ['success'=>false,'error'=>'Invalid request format.']);
$question = trim($data['question']);
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

// Only a short, client-held conversation is forwarded; no system/tool roles.
$history = $data['history'] ?? [];
if (!is_array($history) || !array_is_list($history) || count($history) > 6) respond(400, ['success'=>false,'error'=>'Conversation is too long. Clear chat and try again.']);
foreach ($history as $turn) {
    if (!is_array($turn) || !in_array($turn['role'] ?? null, ['user','assistant'], true) || !is_string($turn['content'] ?? null) || trim($turn['content']) === '' || mb_strlen($turn['content']) > 1000) {
        respond(400, ['success'=>false,'error'=>'Invalid conversation. Clear chat and try again.']);
    }
}

$context = []; $contextUsed = [];
foreach (['crop'=>'Crop','stage'=>'Growth stage','location'=>'Location'] as $key=>$label) {
    if (!is_string($data[$key] ?? '')) respond(400,['success'=>false,'error'=>'Invalid context format.']);
    $value = trim($data[$key] ?? '');
    if (mb_strlen($value)>100) respond(400,['success'=>false,'error'=>'Context fields must be at most 100 characters.']);
    if ($value !== '') { $context[$label] = $value; $contextUsed[] = $label . ': ' . $value . ' (user supplied)'; }
}
if (($data['share_weather'] ?? false) === true) {
    $weather=$_SESSION['climate_ai_context'] ?? null;
    if (is_array($weather) && ($weather['saved_at'] ?? 0) >= time()-43200 && is_array($weather['data'] ?? null)) {
        $context['Weather forecast'] = $weather['data'];
        $contextUsed[] = 'Open-Meteo model forecast (estimate, not field measurement): ' . json_encode($weather['data'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    } else $contextUsed[] = 'No recent saved weather forecast available';
}
if (($data['share_soil'] ?? false) === true) {
    try {
        $vayumaDbReadinessProbe = true;
        require_once __DIR__ . '/config/db.php';
        if (!isset($conn) || !$conn) throw new RuntimeException();
        $id=(int)$_SESSION['user_id']; $stmt=$conn->prepare('SELECT sample_date,location,depth_cm,laboratory,method,results_json FROM soil_test_records WHERE user_id=? ORDER BY sample_date DESC,id DESC LIMIT 1');
        $stmt->bind_param('i',$id); $stmt->execute(); $soil=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($soil) { $soil['results']=json_decode($soil['results_json'],true); unset($soil['results_json']); $context['User-entered laboratory report (not independently verified)']=$soil; $contextUsed[]='User-entered laboratory report (not independently verified): '.json_encode($soil, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); }
        else $contextUsed[]='No saved soil report available';
    } catch (Throwable $ignored) { $contextUsed[]='Soil history unavailable'; }
}
$config = require __DIR__ . '/config/ai.php';
$apiKey = trim((string)($config['api_key'] ?? ''));
$model = trim((string)($config['model'] ?? ''));
if (getenv('VAYUMA_AI_EXTERNAL_ENABLED') !== '1' || $apiKey === '' || $model === '') respond(503,['success'=>false,'error'=>'VAYUMA AI is not available right now. Please try again later.']);
if (!preg_match('/^[a-zA-Z0-9_.\/-]{1,150}$/D', $model) || preg_match('/[\r\n]/', $apiKey)) respond(503,['success'=>false,'error'=>'VAYUMA AI is not available right now. Please try again later.']);
require_once __DIR__.'/includes/nvidia_advisor.php';
try { $vayumaDbReadinessProbe = true; require_once __DIR__.'/config/db.php'; if (!isset($conn) || !$conn) throw new RuntimeException(); } catch (Throwable $ignored) { respond(503,['success'=>false,'error'=>'VAYUMA AI is temporarily unavailable. Please try again later.']); }
if (!vayuma_reserve_ai_request($conn, (int)$_SESSION['user_id'])) respond(503,['success'=>false,'error'=>'AI daily limit reached or usage control is unavailable. Please try again later.']);
// Close the session before network I/O; all context has already been selected and copied.
session_write_close();
$providerEvidence = vayuma_nvidia_minimized_evidence($context, $language);
$payload = vayuma_nvidia_payload($model, $question, $language, $providerEvidence, $history);
$ch=curl_init('https://integrate.api.nvidia.com/v1/chat/completions');
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$apiKey],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>(int)$config['timeout']]);
$response=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
$result = is_string($response) && strlen($response) <= 200000 ? json_decode($response,true) : null;
$answer = ($status>=200 && $status<300) ? vayuma_nvidia_answer($result) : null;
if ($answer === null) {
    error_log('VAYUMA NVIDIA AI unavailable or incomplete. HTTP status '.$status);
    respond(503,['success'=>false,'error'=>'VAYUMA AI is temporarily unavailable. Please try again later.']);
}
respond(200,['success'=>true,'answer'=>$answer,'language'=>$language,'mode'=>'ai_support','context_used'=>$contextUsed]);
