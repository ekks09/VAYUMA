<?php
// Weather is an estimate, never an observation from the farmer's field.
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
function climate_reply(int $status, array $data): void { http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); exit; }
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) climate_reply(401, ['success'=>false,'message'=>'Please sign in to use climate guidance.']);
if ($_SERVER['REQUEST_METHOD'] !== 'GET') climate_reply(405, ['success'=>false,'message'=>'Only GET requests are supported.']);
$now=time();
$_SESSION['climate_requests'] = array_values(array_filter($_SESSION['climate_requests'] ?? [], static fn($t) => is_int($t) && $t > $now-60));
if (count($_SESSION['climate_requests']) >= 30) climate_reply(429, ['success'=>false,'message'=>'Too many weather requests. Please wait a minute.']);
$_SESSION['climate_requests'][]=$now;
session_write_close();
function climate_fetch(string $url): ?array {
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS]);
    $body=curl_exec($ch); $status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if (!is_string($body) || $status!==200 || strlen($body)>1000000) return null;
    $data=json_decode($body,true); return is_array($data)?$data:null;
}
function kenya_coordinates($lat,$lon): bool { return is_numeric($lat) && is_numeric($lon) && (float)$lat >= -5 && (float)$lat <= 5.6 && (float)$lon >= 33.5 && (float)$lon <= 42; }
$mode=(string)($_GET['mode'] ?? 'search');
if ($mode==='search') {
    $location=trim((string)($_GET['location'] ?? '')); $county=trim((string)($_GET['county'] ?? ''));
    if (mb_strlen($location)<2 || mb_strlen($location)>80 || mb_strlen($county)>80) climate_reply(400,['success'=>false,'message'=>'Enter a location of 2–80 characters.']);
    $url='https://geocoding-api.open-meteo.com/v1/search?'.http_build_query(['name'=>$location,'count'=>20,'language'=>'en','format'=>'json','countryCode'=>'KE']);
    $data=climate_fetch($url); if ($data===null) climate_reply(503,['success'=>false,'message'=>'Location search is unavailable. Please try again later.']);
    $matches=[];
    foreach (($data['results'] ?? []) as $place) {
        if (($place['country_code'] ?? '') !== 'KE' || !kenya_coordinates($place['latitude'] ?? null,$place['longitude'] ?? null)) continue;
        $region=(string)($place['admin1'] ?? '');
        if ($county!=='' && ($region==='' || (stripos($region,$county)===false && stripos($county,$region)===false))) continue;
        $matches[]=['name'=>(string)($place['name'] ?? ''),'county'=>$region,'country'=>'Kenya','latitude'=>(float)$place['latitude'],'longitude'=>(float)$place['longitude']];
    }
    climate_reply(200,['success'=>true,'matches'=>$matches]);
}
if ($mode!=='forecast' || !kenya_coordinates($_GET['lat'] ?? null,$_GET['lon'] ?? null)) climate_reply(400,['success'=>false,'message'=>'Choose a valid location in Kenya.']);
$lat=(float)$_GET['lat']; $lon=(float)$_GET['lon'];
$url='https://api.open-meteo.com/v1/forecast?'.http_build_query(['latitude'=>$lat,'longitude'=>$lon,'current'=>'temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m,weather_code','daily'=>'temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,weather_code','timezone'=>'auto','forecast_days'=>5]);
$weather=climate_fetch($url);
if (!$weather || !isset($weather['current']['time']) || !isset($weather['daily']['time']) || !is_array($weather['daily']['time'])) climate_reply(503,['success'=>false,'message'=>'Weather information is unavailable. Please try again later.']);
function weather_description($code): string { $codes=[0=>'Clear',1=>'Mainly clear',2=>'Partly cloudy',3=>'Overcast',45=>'Fog',48=>'Fog',51=>'Light drizzle',53=>'Drizzle',55=>'Heavy drizzle',61=>'Light rain',63=>'Rain',65=>'Heavy rain',71=>'Snow',73=>'Snow',75=>'Snow',80=>'Light showers',81=>'Showers',82=>'Heavy showers',95=>'Thunderstorm',96=>'Thunderstorm with hail',99=>'Thunderstorm with hail']; return $codes[$code] ?? 'Conditions unavailable'; }
$c=$weather['current']; $d=$weather['daily']; $forecast=[];
foreach (array_slice($d['time'],0,5,true) as $i=>$date) {
    $forecast[]=['date'=>$date,'max'=>$d['temperature_2m_max'][$i] ?? null,'min'=>$d['temperature_2m_min'][$i] ?? null,'rainfall'=>$d['precipitation_sum'][$i] ?? null,'rain_probability'=>$d['precipitation_probability_max'][$i] ?? null,'max_wind'=>$d['wind_speed_10m_max'][$i] ?? null,'condition'=>weather_description($d['weather_code'][$i] ?? null)];
}
$output=['success'=>true,'source'=>'Open-Meteo forecast (model estimate)','retrieved_at'=>gmdate('c'),'forecast_time'=>$c['time'],'timezone'=>$weather['timezone'] ?? 'Africa/Nairobi','location'=>['latitude'=>$lat,'longitude'=>$lon],'current'=>['temperature'=>$c['temperature_2m'] ?? null,'humidity'=>$c['relative_humidity_2m'] ?? null,'rainfall'=>$c['precipitation'] ?? null,'wind'=>$c['wind_speed_10m'] ?? null,'condition'=>weather_description($c['weather_code'] ?? null)],'forecast'=>$forecast];
if (($_GET['remember'] ?? '')==='1') { session_start(); $_SESSION['climate_ai_context']=['saved_at'=>time(),'data'=>$output]; session_write_close(); }
climate_reply(200,$output);
