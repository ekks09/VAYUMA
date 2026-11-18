<?php

session_start();
require_once __DIR__ . '/includes/language.php';

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true
) {
    header("Location: login.php");
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'Farmer';

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Climate Guidance | VAYUMA</title>


<link rel="stylesheet" href="frontend/css/tokens.css">
<link rel="stylesheet" href="frontend/css/components.css">
<link rel="stylesheet" href="frontend/css/components_addition.css">
<link rel="stylesheet" href="frontend/css/layout.css">
<link rel="stylesheet" href="frontend/css/pages.css">
<style>
.va-dash-sidebar { width: 260px; background: var(--va-surface); border-right: 1px solid var(--va-border); position: fixed; left: 0; top: 0; bottom: 0; padding: var(--va-space-5); overflow-y: auto; z-index: 100; }
.va-dash-sidebar--logo { padding: 0 var(--va-space-4) var(--va-space-6); }
.va-dash-sidebar--logo h1 { color: var(--va-primary); font-size: var(--va-font-size-xl); font-weight: var(--va-font-weight-bold); letter-spacing: 0.05em; margin: 0; }
.va-dash-sidebar--logo p { color: var(--va-text-muted); font-size: var(--va-font-size-xs); letter-spacing: 0.05em; margin-top: var(--va-space-1); }
.va-dash-sidebar--section { font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); text-transform: uppercase; letter-spacing: 0.1em; color: var(--va-text-muted); margin: var(--va-space-6) var(--va-space-4) var(--va-space-2); }
.va-dash-sidebar--nav-item { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3) var(--va-space-4); margin: var(--va-space-1) 0; border-radius: var(--va-radius-md); text-decoration: none; color: var(--va-text-secondary); font-size: var(--va-font-size-sm); transition: all var(--va-transition); }
.va-dash-sidebar--nav-item:hover, .va-dash-sidebar--nav-item--active { background: var(--va-primary-light); color: var(--va-primary); }
.va-dash-sidebar--nav-item--active { font-weight: var(--va-font-weight-semibold); }
.va-dash-sidebar--icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: var(--va-font-size-sm); flex-shrink: 0; }
.va-dash-main { margin-left: 260px; padding: var(--va-space-6); }
.va-dash-topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--va-space-6); }
.va-dash-search { width: 340px; background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-md); padding: var(--va-space-3) var(--va-space-4); color: var(--va-text-muted); font-size: var(--va-font-size-sm); }
.va-dash-profile { display: flex; align-items: center; gap: var(--va-space-3); }
.va-dash-profile-info { text-align: right; }
.va-dash-profile-info strong { display: block; font-size: var(--va-font-size-sm); color: var(--va-text-primary); }
.va-dash-profile-info small { color: var(--va-text-muted); font-size: var(--va-font-size-xs); }
.va-dash-avatar { width: 44px; height: 44px; border-radius: 50%; background: var(--va-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: var(--va-font-weight-bold); font-size: var(--va-font-size-sm); }
.va-dash-role-label { color: var(--va-primary); font-size: var(--va-font-size-xs); font-weight: var(--va-font-weight-bold); letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: var(--va-space-2); }
.va-dash-page-title { font-size: var(--va-font-size-3xl); margin-bottom: var(--va-space-2); color: var(--va-text-primary); }
.va-dash-subtitle { color: var(--va-text-secondary); font-size: var(--va-font-size-base); line-height: var(--va-line-height-relaxed); margin-bottom: var(--va-space-6); }
.va-dash-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--va-space-4); margin-bottom: var(--va-space-6); }
.va-dash-stat { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); padding: var(--va-space-5); }
.va-dash-stat-icon { font-size: var(--va-font-size-xl); margin-bottom: var(--va-space-3); }
.va-dash-stat h3 { color: var(--va-text-muted); font-size: var(--va-font-size-xs); margin-bottom: var(--va-space-2); font-weight: var(--va-font-weight-semibold); text-transform: uppercase; letter-spacing: 0.05em; }
.va-dash-number { color: var(--va-primary); font-size: var(--va-font-size-2xl); font-weight: var(--va-font-weight-bold); }
.va-dash-description { color: var(--va-text-muted); font-size: var(--va-font-size-xs); margin-top: var(--va-space-1); }
.va-dash-content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: var(--va-space-6); }
.va-dash-panel { background: var(--va-surface); border: 1px solid var(--va-border); border-radius: var(--va-radius-lg); padding: var(--va-space-5); }
.va-dash-panel h2 { font-size: var(--va-font-size-lg); margin-bottom: var(--va-space-4); color: var(--va-text-primary); }
.va-dash-feature { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3) 0; border-bottom: 1px solid var(--va-border-light); }
.va-dash-feature:last-child { border-bottom: none; }
.va-dash-feature-icon { width: 40px; height: 40px; border-radius: var(--va-radius-md); background: var(--va-primary-light); color: var(--va-primary); display: flex; align-items: center; justify-content: center; font-size: var(--va-font-size-sm); }
.va-dash-feature strong { display: block; margin-bottom: var(--va-space-1); color: var(--va-text-primary); font-size: var(--va-font-size-sm); }
.va-dash-feature span { color: var(--va-text-secondary); font-size: var(--va-font-size-xs); line-height: var(--va-line-height-relaxed); }
.va-dash-info { margin-top: var(--va-space-5); padding: var(--va-space-4); background: var(--va-primary-light); border-radius: var(--va-radius-md); }
.va-dash-info h3 { color: var(--va-primary); margin-bottom: var(--va-space-2); font-size: var(--va-font-size-sm); }
.va-dash-info p { color: var(--va-text-secondary); font-size: var(--va-font-size-xs); line-height: var(--va-line-height-relaxed); }
.va-dash-action { display: flex; align-items: center; gap: var(--va-space-3); padding: var(--va-space-3); margin-bottom: var(--va-space-2); border: 1px solid var(--va-border); border-radius: var(--va-radius-md); text-decoration: none; color: var(--va-text-secondary); font-size: var(--va-font-size-sm); transition: all var(--va-transition); }
.va-dash-action:hover { background: var(--va-primary-light); color: var(--va-primary); border-color: var(--va-primary); }
@media (max-width: 800px) { .va-dash-sidebar { width: 220px; } .va-dash-main { margin-left: 220px; } }
@media (max-width: 650px) { .va-dash-sidebar { position: fixed; left: -260px; transition: left 0.3s; box-shadow: var(--va-shadow-lg); z-index: 200; } .va-dash-sidebar--open { left: 0; } .va-dash-main { margin-left: 0; width: 100%; padding: var(--va-space-4); } .va-dash-stats { grid-template-columns: 1fr; } }
</style>

</head>


<body>
<?php vayuma_language_selector(); ?>


<div class="container">


    <!-- HEADER -->

    <div class="header">

        <div class="va-dash-sidebar--logo">
            VAYUMA
        </div>

        <a
            href="farmer_dashboard.php"
            class="back"
        >
            ← Farmer Dashboard
        </a>

    </div>


    <!-- HERO -->

    <div class="hero">

        <small>
            VAYUMA CLIMATE GUIDANCE
        </small>

        <h1>
            Climate intelligence for your farm
        </h1>

        <p>
            Get real-time weather information and
            farming recommendations based on your
            location.
        </p>


        <form
            id="climateForm"
            class="location-form"
        >

            <input
                type="text"
                id="location"
                placeholder="Enter your location e.g. Nakuru"
                required
            >

            <button type="submit">
                Check Climate
            </button>

        </form>

    </div>


    <!-- STATUS MESSAGE -->

    <div
        id="status"
        class="status"
    >
        Loading climate information...
    </div>


    <!-- WEATHER -->

    <div class="weather-grid">


        <!-- TEMPERATURE -->

        <div class="card">

            <div class="current-weather">

                <div
                    class="weather-icon"
                    id="weatherIcon"
                >
                    <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg>️
                </div>

                <div>

                    <div
                        class="temperature"
                        id="temperature"
                    >
                        --
                    </div>

                    <div
                        class="muted"
                        id="condition"
                    >
                        Enter your location
                    </div>

                </div>

            </div>

        </div>


        <!-- HUMIDITY -->

        <div class="card">

            <h3>
                💧 Humidity
            </h3>

            <div
                class="value"
                id="humidity"
            >
                --
            </div>

            <div class="muted">
                Relative humidity
            </div>

        </div>


        <!-- RAINFALL -->

        <div class="card">

            <h3>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-weather"/></svg>️ Rainfall
            </h3>

            <div
                class="value"
                id="rainfall"
            >
                --
            </div>

            <div class="muted">
                Current precipitation
            </div>

        </div>


        <!-- WIND -->

        <div class="card">

            <h3>
                💨 Wind
            </h3>

            <div
                class="value"
                id="wind"
            >
                --
            </div>

            <div class="muted">
                Wind speed
            </div>

        </div>


    </div>


    <!-- LOWER CONTENT -->

    <div class="lower-grid">


        <!-- FARMING ADVICE -->

        <div class="advice">

            <h2>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-crops"/></svg> Farming Recommendation
            </h2>

            <p id="farmingAdvice">

                Enter your location above to receive
                climate-based farming guidance.

            </p>

        </div>


        <!-- FORECAST -->

        <div class="forecast">

            <h2>
                <svg class="va-icon" width="20" height="20"><use href="frontend/images/icons.svg#va-icon-checkmark"/></svg> 5-Day Forecast
            </h2>


            <div
                id="forecast"
            >

                <div class="forecast-day">

                    <span>
                        Today
                    </span>

                    <span>
                        --
                    </span>

                </div>


                <div class="forecast-day">

                    <span>
                        Tomorrow
                    </span>

                    <span>
                        --
                    </span>

                </div>


                <div class="forecast-day">

                    <span>
                        Day 3
                    </span>

                    <span>
                        --
                    </span>

                </div>


                <div class="forecast-day">

                    <span>
                        Day 4
                    </span>

                    <span>
                        --
                    </span>

                </div>


                <div class="forecast-day">

                    <span>
                        Day 5
                    </span>

                    <span>
                        --
                    </span>

                </div>

            </div>

        </div>


    </div>


</div>


<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script>


/*
|--------------------------------------------------------------------------
| GET ELEMENTS
|--------------------------------------------------------------------------
*/

const form =
    document.getElementById("climateForm");

const locationInput =
    document.getElementById("location");

const status =
    document.getElementById("status");


/*
|--------------------------------------------------------------------------
| WHEN USER CLICKS CHECK CLIMATE
|--------------------------------------------------------------------------
*/

form.addEventListener(
    "submit",
    function(event) {

        event.preventDefault();

        const location =
            locationInput.value.trim();


        if (location === "") {

            alert(
                "Please enter your location."
            );

            return;

        }


        getClimate(location);

    }
);


/*
|--------------------------------------------------------------------------
| GET CLIMATE DATA
|--------------------------------------------------------------------------
*/

async function getClimate(location) {


    status.style.display = "block";

    status.textContent =
        "Loading climate information for "
        + location
        + "...";


    try {


        /*
        ------------------------------------------------------
        SEND LOCATION TO get_climate.php
        ------------------------------------------------------
        */

        const response =
            await fetch(
                "get_climate.php?location="
                + encodeURIComponent(location)
            );


        const data =
            await response.json();


        /*
        ------------------------------------------------------
        CHECK FOR ERROR
        ------------------------------------------------------
        */

        if (!data.success) {

            status.textContent =
                data.message;

            return;

        }


        /*
        ------------------------------------------------------
        TEMPERATURE
        ------------------------------------------------------
        */

        document.getElementById(
            "temperature"
        ).textContent =
            Math.round(
                data.current.temperature
            )
            + "°C";


        /*
        ------------------------------------------------------
        WEATHER CONDITION
        ------------------------------------------------------
        */

        document.getElementById(
            "condition"
        ).textContent =
            data.current.condition;


        /*
        ------------------------------------------------------
        HUMIDITY
        ------------------------------------------------------
        */

        document.getElementById(
            "humidity"
        ).textContent =
            data.current.humidity
            + "%";


        /*
        ------------------------------------------------------
        RAINFALL
        ------------------------------------------------------
        */

        document.getElementById(
            "rainfall"
        ).textContent =
            data.current.rainfall
            + " mm";


        /*
        ------------------------------------------------------
        WIND
        ------------------------------------------------------
        */

        document.getElementById(
            "wind"
        ).textContent =
            data.current.wind
            + " km/h";


        /*
        ------------------------------------------------------
        FARMING ADVICE
        ------------------------------------------------------
        */

        document.getElementById(
            "farmingAdvice"
        ).textContent =
            data.farming_advice;


        /*
        ------------------------------------------------------
        STATUS
        ------------------------------------------------------
        */

        status.textContent =
            "Climate information loaded for "
            + data.location.name
            + ", "
            + data.location.country;


        /*
        ------------------------------------------------------
        FORECAST
        ------------------------------------------------------
        */

        const forecastContainer =
            document.getElementById(
                "forecast"
            );


        forecastContainer.innerHTML = "";


        data.forecast.forEach(
            function(day) {


                const date =
                    new Date(day.date);


                const dayName =
                    date.toLocaleDateString(
                        undefined,
                        {
                            weekday: "long"
                        }
                    );


                const row =
                    document.createElement(
                        "div"
                    );


                row.className =
                    "forecast-day";


                row.innerHTML =

                    "<span>"
                    + dayName
                    + "</span>"

                    +

                    "<span>"
                    + Math.round(day.max)
                    + "°C / "
                    + Math.round(day.min)
                    + "°C"
                    + "</span>";


                forecastContainer.appendChild(
                    row
                );

            }
        );


    }


    catch (error) {


        console.error(error);


        status.style.display = "block";


        status.textContent =
            "Unable to load climate information. "
            + "Make sure XAMPP is running and "
            + "your internet connection is working.";


    }

}

</script>


<script src="assets/js/vayuma-language.js" defer></script>
</body>

</html>