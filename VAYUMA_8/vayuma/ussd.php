<?php
/**
 * VAYUMA USSD service
 *
 * Replacement for ussd.php.  It uses only the confirmed columns in users,
 * produce_listings and orders.  Optional SMS/USSD logging tables are never
 * required for a menu response.
 */

require_once __DIR__ . '/config/db.php';

$crops = require __DIR__ . '/config/crop_advice.php';
$counties = require __DIR__ . '/config/kenya_counties.php';

header('Content-Type: text/plain; charset=UTF-8');

$sessionId = trim($_POST['sessionId'] ?? '');
$serviceCode = trim($_POST['serviceCode'] ?? '');
$phoneNumber = trim($_POST['phoneNumber'] ?? '');
$text = trim($_POST['text'] ?? '');
$parts = $text === '' ? [] : explode('*', $text);
$level = count($parts);

/** Send a response and, when the optional table exists, record the request. */
function ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $request, $message)
{
    $stmt = $conn->prepare('INSERT INTO ussd_requests (session_id, phone_number, service_code, request_text, response_text) VALUES (?, ?, ?, ?, ?)');
    if ($stmt) {
        $stmt->bind_param('sssss', $sessionId, $phoneNumber, $serviceCode, $request, $message);
        $stmt->execute();
        $stmt->close();
    }
    echo $message;
    exit;
}

function normalisePhone($phone)
{
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (substr($digits, 0, 3) === '254' && strlen($digits) >= 12) {
        return '254' . substr($digits, 3);
    }
    if (substr($digits, 0, 1) === '0' && strlen($digits) === 10) {
        return '254' . substr($digits, 1);
    }
    if (strlen($digits) === 9 && substr($digits, 0, 1) === '7') {
        return '254' . $digits;
    }
    return '';
}

/** Return the VAYUMA user ID linked to the current USSD phone number. */
function phoneUserId($conn, $phone)
{
    $international = normalisePhone($phone);
    if ($international === '') {
        return 0;
    }
    $local = '0' . substr($international, 3);
    $stmt = $conn->prepare("SELECT id FROM users WHERE REPLACE(REPLACE(REPLACE(phone, ' ', ''), '+', ''), '-', '') IN (?, ?) LIMIT 1");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('ss', $international, $local);
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return (int) ($row['id'] ?? 0);
}

function cropMenu($crop)
{
    return "CON " . strtoupper($crop['name']) . "\n1. Farming Advice\n2. Weather Suitability\n3. Crop Information";
}

function cropResult($crop, $option)
{
    if ($option === 1) {
        return 'END ' . $crop['name'] . "\n" . $crop['advice'];
    }
    if ($option === 2) {
        return 'END ' . $crop['name'] . "\n" . $crop['weather'];
    }
    if ($option === 3) {
        return 'END ' . $crop['name'] . "\nWater: " . $crop['water'] . "\nClimate: " . $crop['climate'] . "\nMarket: " . $crop['market'];
    }
    return 'END Invalid crop-advice option.';
}

if ($text === '') {
    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text,
        "CON Welcome to VAYUMA\n1. Weather\n2. Crop Advice\n3. Market\n4. Disease Help\n5. Crop Calendar\n6. My Produce\n7. My Orders\n8. Help");
}

$mainOption = $parts[0];

/* Weather: retained from the working service. */
if ($mainOption === '1') {
    $regions = [
        '1' => 'Central', '2' => 'Coast', '3' => 'Eastern', '4' => 'Nairobi',
        '5' => 'North Eastern', '6' => 'Nyanza', '7' => 'Rift Valley', '8' => 'Western'
    ];
    if ($level === 1) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text,
            "CON VAYUMA Weather\nSelect region:\n1. Central\n2. Coast\n3. Eastern\n4. Nairobi\n5. North Eastern\n6. Nyanza\n7. Rift Valley\n8. Western");
    }
    $region = $regions[$parts[1] ?? ''] ?? null;
    if (!$region || !isset($counties[$region])) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid region. Please dial again.');
    }
    if ($level === 2) {
        $menu = "CON " . $region . "\nSelect county:\n";
        foreach ($counties[$region] as $number => $county) {
            $menu .= $number . '. ' . $county['name'] . "\n";
        }
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, rtrim($menu));
    }
    $county = $counties[$region][$parts[2] ?? ''] ?? null;
    if (!$county) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid county. Please dial again.');
    }
    $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . urlencode($county['latitude']) . '&longitude=' . urlencode($county['longitude']) . '&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Africa%2FNairobi';
    $context = stream_context_create(['http' => ['timeout' => 8]]);
    $raw = @file_get_contents($url, false, $context);
    $weather = $raw ? json_decode($raw, true) : null;
    $current = $weather['current'] ?? null;
    if (!$current || !isset($current['temperature_2m'])) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Weather service is temporarily unavailable. Please try again later.');
    }
    $code = (int) ($current['weather_code'] ?? -1);
    $condition = $code === 0 ? 'Clear sky' : ($code >= 1 && $code <= 3 ? 'Partly cloudy' : ($code >= 45 && $code <= 48 ? 'Foggy' : ($code >= 51 && $code <= 57 ? 'Drizzle' : ($code >= 61 && $code <= 82 ? 'Rain' : ($code >= 95 ? 'Thunderstorm' : 'Current conditions')))));
    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text,
        'END VAYUMA Weather' . "\n" . $county['name'] . "\n" . $condition . "\nTemp: " . round($current['temperature_2m']) . " C\nHumidity: " . round($current['relative_humidity_2m'] ?? 0) . "%\nWind: " . round($current['wind_speed_10m'] ?? 0) . ' km/h');
}

/* Location-aware crop advice: region -> county -> category -> crop -> guidance. */
if ($mainOption === '2') {
    $regions = [
        '1' => 'Central', '2' => 'Coast', '3' => 'Eastern', '4' => 'Nairobi',
        '5' => 'North Eastern', '6' => 'Nyanza', '7' => 'Rift Valley', '8' => 'Western'
    ];
    $categories = [1 => 'cereals', 2 => 'legumes', 3 => 'roots_tubers', 4 => 'vegetables', 5 => 'fruits', 6 => 'cash_industrial'];

    if ($level === 1) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text,
            "CON VAYUMA Crop Advice\nSelect your region:\n1. Central\n2. Coast\n3. Eastern\n4. Nairobi\n5. North Eastern\n6. Nyanza\n7. Rift Valley\n8. Western");
    }

    $region = $regions[$parts[1] ?? ''] ?? null;
    if (!$region || !isset($counties[$region])) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid region. Please dial again.');
    }

    if ($level === 2) {
        $out = 'CON Select county - ' . $region . ":\n";
        foreach ($counties[$region] as $number => $countyItem) {
            $out .= $number . '. ' . $countyItem['name'] . "\n";
        }
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, rtrim($out));
    }

    $county = $counties[$region][$parts[2] ?? ''] ?? null;
    if (!$county) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid county. Please dial again.');
    }

    if ($level === 3) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text,
            "CON Select crop category:\n1. Cereals\n2. Legumes\n3. Roots & Tubers\n4. Vegetables\n5. Fruits\n6. Cash/Industrial");
    }

    $category = $categories[(int) ($parts[3] ?? 0)] ?? null;
    if (!$category || !isset($crops[$category])) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid crop category.');
    }

    $items = array_values($crops[$category]);
    if ($level === 4) {
        $out = 'CON ' . $county['name'] . ' - ' . strtoupper(str_replace('_', ' ', $category)) . "\n";
        foreach (array_slice($items, 0, 6) as $i => $cropItem) {
            $out .= ($i + 1) . '. ' . $cropItem['name'] . "\n";
        }
        if (count($items) > 6) $out .= "7. More crops";
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, rtrim($out));
    }

    $cropChoice = (int) ($parts[4] ?? 0);
    $morePage = ($cropChoice === 7);
    if ($level === 5 && $morePage) {
        $out = 'CON More crops - ' . $county['name'] . "\n";
        foreach (array_slice($items, 6, 7) as $i => $cropItem) {
            $out .= ($i + 1) . '. ' . $cropItem['name'] . "\n";
        }
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, rtrim($out));
    }

    if ($morePage) {
        $cropIndex = 6 + (int) ($parts[5] ?? 0) - 1;
        $menuLevel = 6;
        $resultLevel = 7;
    } else {
        $cropIndex = $cropChoice - 1;
        $menuLevel = 5;
        $resultLevel = 6;
    }

    $crop = ($cropIndex >= 0) ? ($items[$cropIndex] ?? null) : null;
    if (!$crop) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid crop selection.');
    }

    if ($level === $menuLevel) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text,
            'CON ' . $crop['name'] . " - " . $county['name'] . "\n1. Farming Advice\n2. Weather Suitability\n3. Crop Information");
    }

    if ($level === $resultLevel) {
        $option = (int) end($parts);
        $location = $county['name'];
        if ($option === 1) {
            $message = 'END ' . $crop['name'] . ' in ' . $location . "\n" . $crop['advice'] . "\nUse advice from your local extension officer for variety and planting dates.";
        } elseif ($option === 2) {
            // Retrieve current conditions for the selected county; do not claim this is a seasonal forecast.
            $url = 'https://api.open-meteo.com/v1/forecast?latitude=' . urlencode($county['latitude']) . '&longitude=' . urlencode($county['longitude']) . '&current=temperature_2m,precipitation,relative_humidity_2m,weather_code&timezone=Africa%2FNairobi';
            $context = stream_context_create(['http' => ['timeout' => 6]]);
            $raw = @file_get_contents($url, false, $context);
            $weatherData = $raw ? json_decode($raw, true) : null;
            $current = $weatherData['current'] ?? null;
            if (!$current) {
                $message = 'END Current weather is unavailable. ' . $crop['weather'];
            } else {
                $message = 'END ' . $crop['name'] . ' - ' . $location . "\nTemp: " . round((float)$current['temperature_2m']) . ' C' . "\nRain now: " . round((float)($current['precipitation'] ?? 0), 1) . ' mm' . "\nHumidity: " . round((float)($current['relative_humidity_2m'] ?? 0)) . '%' . "\n" . $crop['weather'] . "\nCurrent weather is not a seasonal forecast.";
            }
        } elseif ($option === 3) {
            $message = 'END ' . $crop['name'] . "\nLocation: " . $location . "\nWater: " . $crop['water'] . "\nClimate: " . $crop['climate'] . "\nMarket: " . $crop['market'];
        } else {
            $message = 'END Invalid crop-advice option.';
        }
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, $message);
    }

    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid crop advice request. Please dial again.');
}

/* Market listings use only confirmed produce_listings columns. */
if ($mainOption === '3') {
    if ($level === 1) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, "CON VAYUMA Market\n1. Available Produce\n2. My Market Alerts\n3. Subscribe to Price Alerts");
    }
    if (($parts[1] ?? '') === '1' && $level === 2) {
        $rows = $conn->query("SELECT produce_name, quantity, unit, price_per_unit, location FROM produce_listings WHERE status='available' ORDER BY created_at DESC LIMIT 5");
        if ($rows === false) {
            ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Market listings are temporarily unavailable. Please try again later.');
        }
        $out = 'END Available Produce';
        while ($row = $rows->fetch_assoc()) {
            $out .= "\n" . $row['produce_name'] . ': ' . $row['quantity'] . ' ' . $row['unit'] . ' @ KES ' . number_format((float) $row['price_per_unit'], 2) . ' (' . $row['location'] . ')';
        }
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, $out === 'END Available Produce' ? $out . "\nNo produce is listed yet." : $out);
    }
    $alertCategories = [1 => 'Cereals', 2 => 'Vegetables', 3 => 'Fruits', 4 => 'Legumes', 5 => 'Roots & Tubers'];
    if (($parts[1] ?? '') === '2' && $level === 2) {
        $phone = normalisePhone($phoneNumber);
        $stmt = $conn->prepare("SELECT category FROM sms_subscriptions WHERE phone_number=? AND topic='market_price' AND is_active=1");
        if (!$phone || !$stmt) {
            ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Market alerts are not available yet.');
        }
        $stmt->bind_param('s', $phone);
        if (!$stmt->execute()) {
            $stmt->close();
            ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Market alerts are not available yet.');
        }
        $result = $stmt->get_result();
        $out = 'END Active market alerts:';
        while ($result && ($row = $result->fetch_assoc())) {
            $out .= "\n" . ($row['category'] ?: 'All produce');
        }
        $stmt->close();
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, $out === 'END Active market alerts:' ? $out . "\nNone" : $out);
    }
    if (($parts[1] ?? '') === '3' && $level === 2) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, "CON Price alerts for:\n1. Cereals\n2. Vegetables\n3. Fruits\n4. Legumes\n5. Roots & Tubers");
    }
    if (($parts[1] ?? '') === '3' && $level === 3 && isset($alertCategories[(int) ($parts[2] ?? 0)])) {
        $phone = normalisePhone($phoneNumber);
        $category = $alertCategories[(int) $parts[2]];
        $stmt = $phone ? $conn->prepare("INSERT INTO sms_subscriptions (phone_number, topic, category, is_active) VALUES (?, 'market_price', ?, 1) ON DUPLICATE KEY UPDATE is_active=1") : false;
        if (!$stmt) {
            ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Price alerts are not available yet.');
        }
        $stmt->bind_param('ss', $phone, $category);
        $ok = $stmt->execute();
        $stmt->close();
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, $ok ? 'END You are subscribed to ' . $category . ' price alerts.' : 'END Price alerts are not available yet.');
    }
    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid market option.');
}

/* General safety guidance only: it is not a diagnosis or prescription. */
if ($mainOption === '4') {
    if ($level === 1) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, "CON Disease Help\n1. Crop health\n2. Animal health\n3. Urgent warning signs");
    }
    if (($parts[1] ?? '') === '1' && $level === 2) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, "CON Crop symptom\n1. Yellowing leaves\n2. Leaf spots\n3. Wilting plants\n4. Pests on leaves");
    }
    $cropTips = [
        1 => 'Check soil moisture and nutrients. Inspect for pests before treating.',
        2 => 'Remove badly affected leaves, avoid wet foliage, and consult an extension officer before using a registered product.',
        3 => 'Check moisture and roots. Isolate affected plants and seek extension help quickly.',
        4 => 'Inspect under leaves. Use only approved pest control, exactly as labelled.'
    ];
    if (($parts[1] ?? '') === '1' && $level === 3) {
        $tip = $cropTips[(int) ($parts[2] ?? 0)] ?? null;
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, $tip ? 'END Crop health: ' . $tip : 'END Invalid crop-health option.');
    }
    if (($parts[1] ?? '') === '2' && $level === 2) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, "CON Animal symptom\n1. Coughing/breathing\n2. Diarrhea\n3. Lameness/swelling\n4. Sudden illness/death");
    }
    $animalTips = [
        1 => 'Separate the animal, provide clean water and contact a veterinary officer promptly. Difficulty breathing is urgent.',
        2 => 'Separate the animal, keep clean water available and contact a veterinary officer, especially for young or weak animals.',
        3 => 'Limit movement and check for injury. Contact a veterinary officer before giving medicine.',
        4 => 'URGENT: isolate affected animals, do not move or sell them, and contact a veterinary officer immediately. Do not open a carcass.'
    ];
    if (($parts[1] ?? '') === '2' && $level === 3) {
        $tip = $animalTips[(int) ($parts[2] ?? 0)] ?? null;
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, $tip ? 'END Animal health: ' . $tip : 'END Invalid animal-health option.');
    }
    if (($parts[1] ?? '') === '3' && $level === 2) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Urgent: sudden death, breathing trouble, inability to stand, or many animals ill needs a veterinary officer immediately. Isolate animals; do not sell, slaughter or self-medicate.');
    }
    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid disease-help option.');
}

/* Crop calendar: crop-specific stage guidance, with no invented planting dates. */
if ($mainOption === '5') {
    if ($level === 1) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, "CON Crop Calendar\n1. Maize\n2. Beans\n3. Tomatoes\n4. Potatoes\n5. General seasonal plan");
    }
    $calendars = [
        1 => 'Maize: Before rains prepare well-drained land and seed. Plant with reliable rain/moisture. Weed early, monitor fall armyworm, then harvest at maturity and dry grain safely.',
        2 => 'Beans: Prepare land and use clean seed. Plant with reliable rain/moisture. Weed early, avoid waterlogging, monitor pests/disease, and harvest dry pods promptly.',
        3 => 'Tomatoes: Raise healthy seedlings and prepare drainage. Transplant with adequate moisture. Support plants, water consistently and monitor blight/pests. Harvest mature fruit regularly.',
        4 => 'Potatoes: Use certified seed in well-drained soil. Plant at suitable moisture, earth up as plants grow, monitor late blight, and harvest only after skin has set.',
        5 => 'Plan before reliable rains: prepare soil, drainage and inputs. Plant using suitable local advice. Weed early, inspect weekly for pests/disease and harvest, sort and store safely.'
    ];
    $entry = $calendars[(int) ($parts[1] ?? 0)] ?? null;
    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, $entry ? 'END ' . $entry : 'END Invalid calendar option.');
}

if ($mainOption === '6') {
    $userId = phoneUserId($conn, $phoneNumber);
    if (!$userId) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Your phone is not linked to a VAYUMA account. Register on the VAYUMA website first.');
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(status='available'), 0) AS available FROM produce_listings WHERE farmer_id=?");
    if (!$stmt) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Your produce summary is temporarily unavailable.');
    }
    $stmt->bind_param('i', $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Your produce summary is temporarily unavailable.');
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END My Produce' . "\nTotal listings: " . (int) $row['total'] . "\nAvailable: " . (int) $row['available'] . "\nManage listings on the VAYUMA website.");
}

/* The status values counted here are the confirmed order statuses only. */
if ($mainOption === '7') {
    $userId = phoneUserId($conn, $phoneNumber);
    if (!$userId) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Your phone is not linked to a VAYUMA account. Register on the VAYUMA website first.');
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(status='pending'),0) AS pending, COALESCE(SUM(status='accepted'),0) AS accepted, COALESCE(SUM(status='rejected'),0) AS rejected, COALESCE(SUM(status='cancelled'),0) AS cancelled FROM orders WHERE buyer_id=? OR seller_id=?");
    if (!$stmt) {
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Your order summary is temporarily unavailable.');
    }
    $stmt->bind_param('ii', $userId, $userId);
    if (!$stmt->execute()) {
        $stmt->close();
        ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Your order summary is temporarily unavailable.');
    }
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text,
        'END My Orders' . "\nTotal: " . (int) $row['total'] . "\nPending: " . (int) $row['pending'] . "\nAccepted: " . (int) $row['accepted'] . "\nRejected: " . (int) $row['rejected'] . "\nCancelled: " . (int) $row['cancelled'] . "\nManage orders on the VAYUMA website.");
}

if ($mainOption === '8') {
    ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text,
        'END VAYUMA Help: 1 Weather, 2 Crop Advice, 3 Market, 4 Health Help, 5 Calendar, 6 My Produce, 7 My Orders. My Produce and Orders need the same phone number as your VAYUMA account.');
}

ussdReply($conn, $sessionId, $phoneNumber, $serviceCode, $text, 'END Invalid option. Please dial again.');
