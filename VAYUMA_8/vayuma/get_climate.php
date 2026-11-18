<?php

header('Content-Type: application/json');

$location = trim($_GET['location'] ?? '');

if ($location === '') {
    echo json_encode([
        "success" => false,
        "message" => "Please provide a location."
    ]);
    exit;
}


/*
|--------------------------------------------------------------------------
| STEP 1: FIND LOCATION
|--------------------------------------------------------------------------
*/

$geocode_url =
    "https://geocoding-api.open-meteo.com/v1/search"
    . "?name=" . urlencode($location)
    . "&count=1"
    . "&language=en"
    . "&format=json";


$geocode_response = @file_get_contents($geocode_url);

if ($geocode_response === false) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to connect to the climate service."
    ]);

    exit;
}


$geocode_data = json_decode(
    $geocode_response,
    true
);


if (
    !isset($geocode_data['results']) ||
    count($geocode_data['results']) === 0
) {

    echo json_encode([
        "success" => false,
        "message" => "Location not found."
    ]);

    exit;
}


$place = $geocode_data['results'][0];

$latitude = $place['latitude'];
$longitude = $place['longitude'];

$city = $place['name'];

$country = $place['country'] ?? '';


/*
|--------------------------------------------------------------------------
| STEP 2: GET WEATHER
|--------------------------------------------------------------------------
*/

$weather_url =
    "https://api.open-meteo.com/v1/forecast"
    . "?latitude=" . urlencode($latitude)
    . "&longitude=" . urlencode($longitude)
    . "&current=temperature_2m,relative_humidity_2m,"
    . "precipitation,wind_speed_10m,weather_code"
    . "&daily=temperature_2m_max,temperature_2m_min,"
    . "precipitation_sum,weather_code"
    . "&timezone=auto"
    . "&forecast_days=5";


$weather_response =
    @file_get_contents($weather_url);


if ($weather_response === false) {

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve weather information."
    ]);

    exit;
}


$weather = json_decode(
    $weather_response,
    true
);


if (!isset($weather['current'])) {

    echo json_encode([
        "success" => false,
        "message" => "Weather data unavailable."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| WEATHER INFORMATION
|--------------------------------------------------------------------------
*/

$current = $weather['current'];

$temperature =
    $current['temperature_2m'] ?? null;

$humidity =
    $current['relative_humidity_2m'] ?? null;

$rainfall =
    $current['precipitation'] ?? null;

$wind =
    $current['wind_speed_10m'] ?? null;

$weather_code =
    $current['weather_code'] ?? null;


/*
|--------------------------------------------------------------------------
| WEATHER DESCRIPTION
|--------------------------------------------------------------------------
*/

function weatherDescription($code)
{

    $codes = [

        0 => "Clear sky",

        1 => "Mainly clear",
        2 => "Partly cloudy",
        3 => "Overcast",

        45 => "Fog",
        48 => "Depositing rime fog",

        51 => "Light drizzle",
        53 => "Moderate drizzle",
        55 => "Dense drizzle",

        61 => "Slight rain",
        63 => "Moderate rain",
        65 => "Heavy rain",

        71 => "Slight snow",
        73 => "Moderate snow",
        75 => "Heavy snow",

        80 => "Slight rain showers",
        81 => "Moderate rain showers",
        82 => "Violent rain showers",

        95 => "Thunderstorm",
        96 => "Thunderstorm with hail",
        99 => "Thunderstorm with heavy hail"

    ];

    return $codes[$code] ?? "Unknown conditions";
}


$condition =
    weatherDescription($weather_code);


/*
|--------------------------------------------------------------------------
| FARMING ADVICE
|--------------------------------------------------------------------------
*/

if ($rainfall >= 10) {

    $farming_advice =
        "Significant rainfall is occurring or expected. "
        . "Avoid unnecessary irrigation and monitor "
        . "your farm for waterlogging.";

}
elseif ($rainfall > 0) {

    $farming_advice =
        "Some rainfall is present. Consider soil moisture "
        . "conditions before irrigating.";

}
elseif ($temperature >= 32) {

    $farming_advice =
        "High temperatures are present. Monitor crops "
        . "for heat stress and maintain adequate soil moisture.";

}
elseif ($temperature <= 15) {

    $farming_advice =
        "Cool conditions are present. Monitor crops that "
        . "are sensitive to low temperatures.";

}
else {

    $farming_advice =
        "Current conditions appear generally suitable "
        . "for normal farm activities. Continue monitoring "
        . "weather changes.";

}


/*
|--------------------------------------------------------------------------
| FORECAST
|--------------------------------------------------------------------------
*/

$forecast = [];

if (isset($weather['daily'])) {

    $daily = $weather['daily'];

    for (
        $i = 0;
        $i < count($daily['time']);
        $i++
    ) {

        $forecast[] = [

            "date" =>
                $daily['time'][$i],

            "max" =>
                $daily['temperature_2m_max'][$i],

            "min" =>
                $daily['temperature_2m_min'][$i],

            "rainfall" =>
                $daily['precipitation_sum'][$i],

            "condition" =>
                weatherDescription(
                    $daily['weather_code'][$i]
                )

        ];

    }

}


/*
|--------------------------------------------------------------------------
| RETURN DATA
|--------------------------------------------------------------------------
*/

echo json_encode([

    "success" => true,

    "location" => [

        "name" => $city,

        "country" => $country,

        "latitude" => $latitude,

        "longitude" => $longitude

    ],

    "current" => [

        "temperature" => $temperature,

        "humidity" => $humidity,

        "rainfall" => $rainfall,

        "wind" => $wind,

        "condition" => $condition,

        "weather_code" => $weather_code

    ],

    "farming_advice" =>
        $farming_advice,

    "forecast" =>
        $forecast

]);

?>