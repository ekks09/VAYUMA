<?php

session_start();
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !vayuma_csrf_valid()) {
    http_response_code(403);
    exit('Your session expired. Refresh the profile form and try again.');
}
require_once __DIR__ . '/../config/db.php';


/*
|--------------------------------------------------------------------------
| Check login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"]) || ($_SESSION['logged_in'] ?? false) !== true) {

    header("Location: ../register.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Only POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../profile_setup.php");
    exit;

}


$user_id = $_SESSION["user_id"];

$role = $_POST["role"] ?? "";


/*
|--------------------------------------------------------------------------
| Allowed roles
|--------------------------------------------------------------------------
*/

$allowed_roles = [
    "farmer",
    "buyer",
    "consumer",
    "transport",
    "researcher",
    "extension_officer",
    "cooperative",
    "financial_institution"
];


if (!is_string($role) || !in_array($role, $allowed_roles, true)) {
    http_response_code(400); exit('Invalid VAYUMA role.');
}
$sessionRole = (string)($_SESSION['role'] ?? '');
if (($role === 'transport' ? 'transport_provider' : $role) !== $sessionRole) {
    http_response_code(403); exit('Access denied.');
}
// The legacy farmer insert cannot update existing profiles and can create duplicates.
if ($role === 'farmer') {
    http_response_code(410); exit('Please use the current farmer profile form.');
}


/*
|--------------------------------------------------------------------------
| Helper function
|--------------------------------------------------------------------------
*/

function post_value($name)
{
    return trim($_POST[$name] ?? "");
}


/*
|--------------------------------------------------------------------------
| FARMER
|--------------------------------------------------------------------------
*/

if ($role === "farmer") {

    $farm_name = post_value("farm_name");
    $county = post_value("county");
    $sub_county = post_value("sub_county");
    $farm_size = post_value("farm_size");
    $farm_size_unit = post_value("farm_size_unit");
    $farming_experience = post_value("farming_experience");
    $main_crops = post_value("main_crops");
    $soil_type = post_value("soil_type");
    $irrigation = post_value("irrigation");
    $farm_location = post_value("farm_location");


    if ($farm_name === "" || $county === "") {

        die("Please provide the farm name and county.");

    }


    $stmt = $conn->prepare(
        "INSERT INTO farmer_profiles
        (
            user_id,
            farm_name,
            county,
            sub_county,
            farm_size,
            farm_size_unit,
            farming_experience,
            main_crops,
            soil_type,
            irrigation,
            farm_location
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "isssdssssss",
        $user_id,
        $farm_name,
        $county,
        $sub_county,
        $farm_size,
        $farm_size_unit,
        $farming_experience,
        $main_crops,
        $soil_type,
        $irrigation,
        $farm_location
    );


    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| BUYER
|--------------------------------------------------------------------------
*/

elseif ($role === "buyer") {

    $business_name = post_value("business_name");
    $business_type = post_value("business_type");
    $county = post_value("county");
    $location = post_value("location");
    $products_interested = post_value("products_interested");
    $expected_volume = post_value("expected_volume");
    $delivery_preference = post_value("delivery_preference");


    if ($business_name === "" || $county === "") {

        die("Please provide the business name and county.");

    }


    $stmt = $conn->prepare(
        "INSERT INTO buyer_profiles
        (
            user_id,
            business_name,
            business_type,
            county,
            location,
            products_interested,
            expected_volume,
            delivery_preference
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "isssssss",
        $user_id,
        $business_name,
        $business_type,
        $county,
        $location,
        $products_interested,
        $expected_volume,
        $delivery_preference
    );


    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| CONSUMER
|--------------------------------------------------------------------------
*/

elseif ($role === "consumer") {

    $county = post_value("county");
    $location = post_value("location");
    $favorite_products = post_value("favorite_products");
    $shopping_frequency = post_value("shopping_frequency");
    $preferred_delivery = post_value("preferred_delivery");


    if ($county === "") {

        die("Please provide your county.");

    }


    $stmt = $conn->prepare(
        "INSERT INTO consumer_profiles
        (
            user_id,
            county,
            location,
            favorite_products,
            shopping_frequency,
            preferred_delivery
        )
        VALUES (?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "isssss",
        $user_id,
        $county,
        $location,
        $favorite_products,
        $shopping_frequency,
        $preferred_delivery
    );


    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| TRANSPORT
|--------------------------------------------------------------------------
*/

elseif ($role === "transport") {

    $business_name = post_value("business_name");
    $county = post_value("county");
    $vehicle_type = post_value("vehicle_type");
    $vehicle_capacity = post_value("vehicle_capacity");
    $registration_number = post_value("registration_number");
    $operating_area = post_value("operating_area");
    $service_type = post_value("service_type");


    if ($county === "" || $vehicle_type === "") {

        die("Please provide county and vehicle type.");

    }


    $stmt = $conn->prepare(
        "INSERT INTO transport_profiles
        (
            user_id,
            business_name,
            county,
            vehicle_type,
            vehicle_capacity,
            registration_number,
            operating_area,
            service_type
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "isssssss",
        $user_id,
        $business_name,
        $county,
        $vehicle_type,
        $vehicle_capacity,
        $registration_number,
        $operating_area,
        $service_type
    );


    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| RESEARCHER
|--------------------------------------------------------------------------
*/

elseif ($role === "researcher") {

    $institution = post_value("institution");
    $specialization = post_value("specialization");
    $county = post_value("county");
    $research_area = post_value("research_area");
    $qualification = post_value("qualification");
    $bio = post_value("bio");


    if ($institution === "") {

        die("Please provide your institution.");

    }


    $stmt = $conn->prepare(
        "INSERT INTO researcher_profiles
        (
            user_id,
            institution,
            specialization,
            county,
            research_area,
            qualification,
            bio
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "issssss",
        $user_id,
        $institution,
        $specialization,
        $county,
        $research_area,
        $qualification,
        $bio
    );


    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| EXTENSION OFFICER
|--------------------------------------------------------------------------
*/

elseif ($role === "extension_officer") {

    $organization = post_value("organization");
    $county = post_value("county");
    $sub_county = post_value("sub_county");
    $specialization = post_value("specialization");
    $years_experience = post_value("years_experience");
    $coverage_area = post_value("coverage_area");
    $qualification = post_value("qualification");


    if ($organization === "" || $county === "") {

        die("Please provide your organization and county.");

    }


    $stmt = $conn->prepare(
        "INSERT INTO extension_profiles
        (
            user_id,
            organization,
            county,
            sub_county,
            specialization,
            years_experience,
            coverage_area,
            qualification
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "issssiss",
        $user_id,
        $organization,
        $county,
        $sub_county,
        $specialization,
        $years_experience,
        $coverage_area,
        $qualification
    );


    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| COOPERATIVE
|--------------------------------------------------------------------------
*/

elseif ($role === "cooperative") {

    $cooperative_name = post_value("cooperative_name");
    $registration_number = post_value("registration_number");
    $county = post_value("county");
    $location = post_value("location");
    $member_count = post_value("member_count");
    $agricultural_focus = post_value("agricultural_focus");
    $main_products = post_value("main_products");


    if ($cooperative_name === "" || $county === "") {

        die("Please provide cooperative name and county.");

    }


    $stmt = $conn->prepare(
        "INSERT INTO cooperative_profiles
        (
            user_id,
            cooperative_name,
            registration_number,
            county,
            location,
            member_count,
            agricultural_focus,
            main_products
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "isssisss",
        $user_id,
        $cooperative_name,
        $registration_number,
        $county,
        $location,
        $member_count,
        $agricultural_focus,
        $main_products
    );


    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| FINANCIAL INSTITUTION
|--------------------------------------------------------------------------
*/

elseif ($role === "financial_institution") {

    $institution_name = post_value("institution_name");
    $institution_type = post_value("institution_type");
    $county = post_value("county");
    $branch_location = post_value("branch_location");
    $agricultural_services = post_value("agricultural_services");
    $financing_types = post_value("financing_types");


    if ($institution_name === "") {

        die("Please provide the institution name.");

    }


    $stmt = $conn->prepare(
        "INSERT INTO financial_profiles
        (
            user_id,
            institution_name,
            institution_type,
            county,
            branch_location,
            agricultural_services,
            financing_types
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );


    $stmt->bind_param(
        "issssss",
        $user_id,
        $institution_name,
        $institution_type,
        $county,
        $branch_location,
        $agricultural_services,
        $financing_types
    );


    $stmt->execute();

    $stmt->close();

}


/*
|--------------------------------------------------------------------------
| Profile completed
|--------------------------------------------------------------------------
*/

$_SESSION["profile_completed"] = true;


/*
|--------------------------------------------------------------------------
| Next step
|--------------------------------------------------------------------------
*/

header("Location: ../complete.php");

exit;

?>