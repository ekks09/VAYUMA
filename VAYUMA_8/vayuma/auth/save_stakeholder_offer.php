<?php
session_start();
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../includes/csrf.php";

if (!isset($_SESSION["user_id"]) || ($_SESSION["logged_in"] ?? false) !== true) { header("Location: ../login.php"); exit; }
if ($_SERVER["REQUEST_METHOD"] !== "POST") { header("Location: ../stakeholder_offers.php?view=manage"); exit; }
if (!vayuma_csrf_valid()) { $_SESSION["offer_error"] = "Your session token expired. Please refresh and try again."; header("Location: ../stakeholder_offers.php?view=manage"); exit; }

function offer_input(string $key): string { return trim($_POST[$key] ?? ""); }
$title = offer_input("title"); $offer_type = offer_input("offer_type"); $description = offer_input("description");
$county = offer_input("county"); $price_note = offer_input("price_note"); $availability = offer_input("availability");
if ($title === "" || $offer_type === "" || $description === "" || $county === "") {
    $_SESSION["offer_error"] = "Title, offer type, county and description are required.";
    header("Location: ../stakeholder_offers.php?view=manage"); exit;
}
$user_id = (int) $_SESSION["user_id"]; $role = strtolower(trim($_SESSION["role"] ?? ""));
$stmt = $conn->prepare("INSERT INTO stakeholder_offers (provider_id, provider_role, title, offer_type, description, county, price_note, availability) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) { die("Unable to prepare the offer. Please try again."); }
$stmt->bind_param("isssssss", $user_id, $role, $title, $offer_type, $description, $county, $price_note, $availability);
if (!$stmt->execute()) { $stmt->close(); die("Unable to publish your offer. Please try again."); }
$stmt->close(); $_SESSION["offer_success"] = "Your offer is now visible across VAYUMA.";
header("Location: ../stakeholder_offers.php?view=manage"); exit;
