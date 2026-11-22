<?php
/** Server-side recovery settings. Reset links never use the request Host header. */
function vayuma_recovery_config(): ?array {
    if (getenv('VAYUMA_PASSWORD_RESET_ENABLED') !== '1' || !defined('VAYUMA_SESSION_GUARD_ACTIVE')) return null;
    $key = trim((string)getenv('BREVO_API_KEY'));
    $sender = trim((string)(getenv('BREVO_SENDER_EMAIL') ?: getenv('BREVO_FROM_EMAIL')));
    $senderName = trim((string)(getenv('BREVO_SENDER_NAME') ?: 'VAYUMA'));
    $templateId = trim((string)getenv('BREVO_RESET_TEMPLATE_ID'));
    $url = rtrim(trim((string)(getenv('VAYUMA_PUBLIC_URL') ?: getenv('RENDER_EXTERNAL_URL'))), '/');
    $parsed = parse_url($url);
    if (strlen($key) < 10 || preg_match('/[\r\n]/', $key) || ($sender !== '' && (!filter_var($sender, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $sender))) || strlen($senderName) > 100 || preg_match('/[\r\n]/', $senderName) || !preg_match('/^[1-9][0-9]{0,8}$/D', $templateId)) return null;
    if (!is_array($parsed) || !filter_var($url, FILTER_VALIDATE_URL) || strtolower($parsed['scheme'] ?? '') !== 'https' || empty($parsed['host']) || isset($parsed['user']) || isset($parsed['pass']) || isset($parsed['query']) || isset($parsed['fragment']) || (isset($parsed['port']) && $parsed['port'] !== 443) || !empty($parsed['path'])) return null;
    return ['key'=>$key, 'sender'=>$sender, 'sender_name'=>$senderName, 'template_id'=>(int)$templateId, 'url'=>$url];
}

/** Shared atomic, database-backed limits; keys contain no raw identifiers. */
function vayuma_recovery_limit($conn, string $scope, string $value, string $key, int $maximum): bool {
    $digest = hash_hmac('sha256', $scope . ':' . $value, $key);
    $stmt = $conn->prepare("INSERT INTO password_reset_limits (scope,subject_hash,window_started_at,attempts) VALUES (?,?,UTC_TIMESTAMP(),1) ON DUPLICATE KEY UPDATE attempts=IF(window_started_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR),1,attempts+1), window_started_at=IF(window_started_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 HOUR),UTC_TIMESTAMP(),window_started_at)");
    if (!$stmt) throw new RuntimeException('Limit unavailable');
    $stmt->bind_param('ss',$scope,$digest); if (!$stmt->execute()) throw new RuntimeException('Limit unavailable'); $stmt->close();
    $stmt=$conn->prepare('SELECT attempts FROM password_reset_limits WHERE scope=? AND subject_hash=? LIMIT 1');
    if (!$stmt) throw new RuntimeException('Limit unavailable');
    $stmt->bind_param('ss',$scope,$digest); if (!$stmt->execute()) throw new RuntimeException('Limit unavailable');
    $result=$stmt->get_result(); if (!$result) throw new RuntimeException('Limit unavailable');
    $row=$result->fetch_assoc(); $stmt->close();
    if (!is_array($row) || !isset($row['attempts'])) throw new RuntimeException('Limit unavailable');
    return (int)$row['attempts'] <= $maximum;
}

/** Brevo is accessed over HTTPS; API key and provider responses stay server-side. */
function vayuma_recovery_send(array $config, string $email, string $token): bool {
    // The fragment avoids sending the token in HTTP requests or access logs.
    // Disable Brevo click tracking; link rewriting can still expose the token.
    $link=$config['url'].'/reset_password.php#token='.$token;
    $body=['to'=>[['email'=>$email]], 'templateId'=>$config['template_id'], 'params'=>['reset_url'=>$link]];
    return vayuma_recovery_brevo_send($config, $body);
}

function vayuma_recovery_notice(array $config, string $email): bool {
    if ($config['sender'] === '') return false;
    return vayuma_recovery_brevo_send($config, [
        'sender'=>['name'=>$config['sender_name'],'email'=>$config['sender']], 'to'=>[['email'=>$email]],
        'subject'=>'Your VAYUMA password was changed',
        'textContent'=>'Your VAYUMA password has been changed. If this was not you, contact VAYUMA support immediately.',
        'htmlContent'=>'<p>Your VAYUMA password has been changed.</p><p>If this was not you, contact VAYUMA support immediately.</p>'
    ]);
}

function vayuma_recovery_brevo_send(array $config, array $body): bool {
    $ch=curl_init('https://api.brevo.com/v3/smtp/email');
    if (!$ch) return false;
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
        CURLOPT_HTTPHEADER=>['accept: application/json','content-type: application/json','api-key: '.$config['key']],
        CURLOPT_POSTFIELDS=>json_encode($body,JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>3]);
    $response=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
    return is_string($response) && $status>=200 && $status<300;
}
