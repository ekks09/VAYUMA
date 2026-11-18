<?php

require_once __DIR__ . '/config/db.php';

$crops = require __DIR__ . '/config/crop_advice.php';

header('Content-Type: text/plain; charset=UTF-8');

/*
|--------------------------------------------------------------------------
| VAYUMA USSD SERVICE
|--------------------------------------------------------------------------
*/

$sessionId   = $_POST['sessionId'] ?? '';
$serviceCode = $_POST['serviceCode'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$text        = trim($_POST['text'] ?? '');

/*
|--------------------------------------------------------------------------
| Load Kenya county data
|--------------------------------------------------------------------------
*/

$counties = require __DIR__ . '/config/kenya_counties.php';

/*
|--------------------------------------------------------------------------
| MAIN MENU
|--------------------------------------------------------------------------
*/

if ($text === '') {

    echo "CON Welcome to VAYUMA\n";
    echo "1. Weather\n";
    echo "2. Crop Advice\n";
    echo "3. Market\n";
    echo "4. Disease Help\n";
    echo "5. Crop Calendar\n";
    echo "6. My Produce\n";
    echo "7. My Orders\n";
    echo "8. Help";

    exit;
}

/*
|--------------------------------------------------------------------------
| Split USSD input
|--------------------------------------------------------------------------
|
| Example:
|
| 1       = Weather
| 1*2     = Weather > Coast
| 1*2*3   = Weather > Coast > Kwale
|
*/

$parts = explode('*', $text);

$level = count($parts);

$mainOption = $parts[0];

/*
|--------------------------------------------------------------------------
| WEATHER
|--------------------------------------------------------------------------
*/

if ($mainOption === '1') {

    /*
    |--------------------------------------------------------------------------
    | WEATHER - SELECT REGION
    |--------------------------------------------------------------------------
    */

    if (count($parts) === 1) {

        echo "CON VAYUMA Weather\n";
        echo "Select region:\n";
        echo "1. Central\n";
        echo "2. Coast\n";
        echo "3. Eastern\n";
        echo "4. Nairobi\n";
        echo "5. North Eastern\n";
        echo "6. Nyanza\n";
        echo "7. Rift Valley\n";
        echo "8. Western";

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Convert numeric region to region name
    |--------------------------------------------------------------------------
    */

    $regionMap = [

        '1' => 'Central',
        '2' => 'Coast',
        '3' => 'Eastern',
        '4' => 'Nairobi',
        '5' => 'North Eastern',
        '6' => 'Nyanza',
        '7' => 'Rift Valley',
        '8' => 'Western'

    ];

    $regionNumber = $parts[1];

    if (!isset($regionMap[$regionNumber])) {

        echo "END Invalid region.\n";
        echo "Please dial *384*10930# again.";

        exit;
    }

    $region = $regionMap[$regionNumber];

    /*
    |--------------------------------------------------------------------------
    | WEATHER - SELECT COUNTY
    |--------------------------------------------------------------------------
    */

    if (count($parts) === 2) {

        echo "CON " . $region . "\n";
        echo "Select county:\n";

        foreach ($counties[$region] as $number => $county) {

            echo $number . ". " . $county['name'] . "\n";
        }

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | COUNTY SELECTED
    |--------------------------------------------------------------------------
    */

    $countyNumber = $parts[2];

    if (!isset($counties[$region][$countyNumber])) {

        echo "END Invalid county.\n";
        echo "Please dial *384*10930# again.";

        exit;
    }

    $county = $counties[$region][$countyNumber];

    $countyName = $county['name'];
    $latitude   = $county['latitude'];
    $longitude  = $county['longitude'];

    /*
    |--------------------------------------------------------------------------
    | REQUEST LIVE WEATHER FROM OPEN-METEO
    |--------------------------------------------------------------------------
    */

    $url =
        "https://api.open-meteo.com/v1/forecast" .
        "?latitude=" . urlencode($latitude) .
        "&longitude=" . urlencode($longitude) .
        "&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m" .
        "&timezone=Africa%2FNairobi";

    $context = stream_context_create([
        'http' => [
            'timeout' => 8
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    /*
    |--------------------------------------------------------------------------
    | WEATHER SERVICE ERROR
    |--------------------------------------------------------------------------
    */

    if ($response === false) {

        echo "END Weather service temporarily unavailable.\n";
        echo "Please try again later.";

        exit;
    }

    $weather = json_decode($response, true);

    /*
    |--------------------------------------------------------------------------
    | VALIDATE WEATHER RESPONSE
    |--------------------------------------------------------------------------
    */

    if (
        !isset($weather['current']) ||
        !isset($weather['current']['temperature_2m'])
    ) {

        echo "END Unable to retrieve weather.\n";
        echo "Please try again later.";

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | WEATHER DATA
    |--------------------------------------------------------------------------
    */

    $current = $weather['current'];

    $temperature = $current['temperature_2m'] ?? null;
    $humidity    = $current['relative_humidity_2m'] ?? null;
    $wind        = $current['wind_speed_10m'] ?? null;
    $code        = $current['weather_code'] ?? null;

    /*
    |--------------------------------------------------------------------------
    | WEATHER DESCRIPTION
    |--------------------------------------------------------------------------
    */

    $description = "Current conditions";

    if ($code === 0) {

        $description = "Clear sky";

    } elseif ($code >= 1 && $code <= 3) {

        $description = "Partly cloudy";

    } elseif ($code >= 45 && $code <= 48) {

        $description = "Foggy";

    } elseif ($code >= 51 && $code <= 57) {

        $description = "Drizzle";

    } elseif ($code >= 61 && $code <= 67) {

        $description = "Rain";

    } elseif ($code >= 71 && $code <= 77) {

        $description = "Snow";

    } elseif ($code >= 80 && $code <= 82) {

        $description = "Rain showers";

    } elseif ($code >= 95) {

        $description = "Thunderstorm";
    }

    /*
    |--------------------------------------------------------------------------
    | RETURN WEATHER TO USER
    |--------------------------------------------------------------------------
    */

    echo "END VAYUMA Weather\n";
    echo $countyName . "\n";
    echo $description . "\n";

    if ($temperature !== null) {

        echo "Temp: " . round($temperature) . " C\n";
    }

    if ($humidity !== null) {

        echo "Humidity: " . round($humidity) . "%\n";
    }

    if ($wind !== null) {

        echo "Wind: " . round($wind) . " km/h";
    }

    exit;
}

/*
|--------------------------------------------------------------------------
| OTHER VAYUMA SERVICES
|--------------------------------------------------------------------------
*/

switch ($mainOption) {

    case '2':

    /*
     * CROP ADVICE
     *
     * USSD path:
     * 2                    = Crop Advice
     * 2*1                  = Cereals
     * 2*1*1                = Maize
     * 2*1*1*1              = Farming advice
     * 2*1*1*2              = Weather suitability
     */

    // Crop categories
    $categories = [
        1 => 'cereals',
        2 => 'legumes',
        3 => 'roots_tubers',
        4 => 'vegetables',
        5 => 'fruits',
        6 => 'cash_industrial'
    ];

    // ---------------------------------------------------------
    // CROP ADVICE MAIN MENU
    // ---------------------------------------------------------
    if ($level === 1) {

        echo "CON CROP ADVICE\n";
        echo "1. Cereals\n";
        echo "2. Legumes\n";
        echo "3. Roots & Tubers\n";
        echo "4. Vegetables\n";
        echo "5. Fruits\n";
        echo "6. Cash/Industrial";

        exit;
    }


    // ---------------------------------------------------------
// CATEGORY SELECTED
// ---------------------------------------------------------
if ($level === 2) {

    $categoryNumber = (int)$parts[1];

    if (!isset($categories[$categoryNumber])) {
        echo "END Invalid crop category.";
        exit;
    }

    $category = $categories[$categoryNumber];

    if (!isset($crops[$category])) {
        echo "END Crop category unavailable.";
        exit;
    }

    $cropList = $crops[$category];

    $perPage = 6;

    /*
     * Convert the crop array to a simple numbered list.
     */
    $cropItems = array_values($cropList);

    echo "CON " . strtoupper(str_replace('_', ' ', $category)) . "\n";

    // First six crops
    for ($i = 0; $i < min($perPage, count($cropItems)); $i++) {

        $number = $i + 1;

        echo $number . ". " . $cropItems[$i]['name'] . "\n";
    }

    // More option
    if (count($cropItems) > $perPage) {
        echo "7. More";
    }

    exit;
}

// ---------------------------------------------------------
// MORE CROPS
// ---------------------------------------------------------
if ($level === 3 && isset($parts[2]) && $parts[2] == '7') {

    $categoryNumber = (int)$parts[1];

    if (!isset($categories[$categoryNumber])) {
        echo "END Invalid crop category.";
        exit;
    }

    $category = $categories[$categoryNumber];

    if (!isset($crops[$category])) {
        echo "END Crop category unavailable.";
        exit;
    }

    $cropItems = array_values($crops[$category]);

    $perPage = 6;

    echo "CON MORE " . strtoupper(str_replace('_', ' ', $category)) . "\n";

    $total = count($cropItems);

    for ($i = $perPage; $i < $total; $i++) {

        $number = $i - $perPage + 1;

        echo $number . ". " . $cropItems[$i]['name'] . "\n";
    }

    exit;
}


    /// ---------------------------------------------------------
// CROP SELECTED
// ---------------------------------------------------------
if ($level === 3) {

    $categoryNumber = (int)$parts[1];
    $cropNumber = (int)$parts[2];

    if (!isset($categories[$categoryNumber])) {
        echo "END Invalid crop category.";
        exit;
    }

    $category = $categories[$categoryNumber];

    if (!isset($crops[$category])) {
        echo "END Crop category unavailable.";
        exit;
    }

    $cropItems = array_values($crops[$category]);

    /*
     * Normal first-page selection
     */
    if ($cropNumber >= 1 && $cropNumber <= 6) {

        if (!isset($cropItems[$cropNumber - 1])) {
            echo "END Invalid crop selection.";
            exit;
        }

        $crop = $cropItems[$cropNumber - 1];

        echo "CON " . strtoupper($crop['name']) . "\n";
        echo "1. Farming Advice\n";
        echo "2. Weather Suitability\n";
        echo "3. Crop Information";

        exit;
    }

    /*
     * More page
     */
    if ($cropNumber == 7) {

        echo "CON MORE CROPS\n";

        $perPage = 6;

        for ($i = $perPage; $i < count($cropItems); $i++) {

            $number = $i - $perPage + 1;

            echo $number . ". " . $cropItems[$i]['name'] . "\n";
        }

        exit;
    }

    echo "END Invalid crop selection.";
    exit;
}


    // ---------------------------------------------------------
    // ADVICE / WEATHER / INFORMATION
    // ---------------------------------------------------------
    if ($level === 4) {

        $categoryNumber = (int)$parts[1];
        $cropNumber = (int)$parts[2];
        $option = (int)$parts[3];

        if (
            !isset($categories[$categoryNumber]) ||
            !isset($crops[$categories[$categoryNumber]][$cropNumber])
        ) {
            echo "END Invalid crop selection.";
            break;
        }

        $category = $categories[$categoryNumber];
        $crop = $crops[$category][$cropNumber];

        // ---------------------------------------------
        // Farming advice
        // ---------------------------------------------
        if ($option === 1) {

            echo "END " . $crop['name'] . "\n\n";
            echo $crop['advice'];
        }

        // ---------------------------------------------
        // Weather suitability
        // ---------------------------------------------
        elseif ($option === 2) {

            echo "END " . $crop['name'] . "\n\n";
            echo $crop['weather'];
        }

        // ---------------------------------------------
        // Crop information
        // ---------------------------------------------
        elseif ($option === 3) {

            echo "END " . $crop['name'] . "\n";
            echo "Water: " . $crop['water'] . "\n";
            echo "Climate: " . $crop['climate'] . "\n";
            echo "Market: " . $crop['market'];
        }

        else {

            echo "END Invalid option.";
        }

        break;
    }


    echo "END Invalid crop advice request.";
    break;

    case '3':

        echo "END VAYUMA Market\n";
        echo "Market service is being connected.";

        break;


    case '4':

        echo "END VAYUMA Disease Help\n";
        echo "Disease Helper is being connected.";

        break;


    case '5':

        echo "END VAYUMA Crop Calendar\n";
        echo "Crop Calendar is being connected.";

        break;


    case '6':

        echo "END VAYUMA My Produce\n";
        echo "Your produce service is being connected.";

        break;


    case '7':

        echo "END VAYUMA My Orders\n";
        echo "Your orders service is being connected.";

        break;


    case '8':

        echo "END VAYUMA Help\n";
        echo "For assistance, contact VAYUMA support.";

        break;


    default:

        echo "END Invalid option.\n";
        echo "Please dial *384*10930# again.";

        break;
}

?>