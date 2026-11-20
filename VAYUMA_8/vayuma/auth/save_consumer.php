<?php
session_start();
require_once __DIR__ . '/../includes/csrf.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !vayuma_csrf_valid()) {
    http_response_code(403);
    exit('Your session expired. Refresh the profile form and try again.');
}
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: ../login.php'); exit; }
if (($_SESSION['role'] ?? '') !== 'consumer') { header('Location: ../role.php'); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Location: ../consumer_profile.php'); exit; }
require_once __DIR__ . '/../config/db.php';
$userId=(int)$_SESSION['user_id'];
$fullName=trim($_POST['full_name']??'');$location=trim($_POST['location']??'');
$countyId=(int)($_POST['county_id']??0);$preferred=trim($_POST['preferred_product']??'');
$shopping=trim($_POST['shopping_preference']??'');$frequency=trim($_POST['shopping_frequency']??'');
$delivery=trim($_POST['preferred_delivery']??'');$favorites=trim($_POST['favorite_products']??'');
if($fullName===''||$location===''||$countyId<1||$preferred===''||$shopping===''){http_response_code(422);exit('Please complete all required consumer profile fields.');}
$c=$conn->prepare("SELECT name FROM administrative_units WHERE id=? AND unit_type='county' AND is_active=1 LIMIT 1");
if(!$c){http_response_code(500);exit('Could not validate county: '.$conn->error);}
$c->bind_param('i',$countyId);$c->execute();$countyRow=$c->get_result()->fetch_assoc();$c->close();
if(!$countyRow){http_response_code(422);exit('Please select a valid county.');}
$county=$countyRow['name'];
try{
 $check=$conn->prepare("SELECT id FROM consumer_profiles WHERE user_id=? LIMIT 1");
 if(!$check)throw new RuntimeException($conn->error);
 $check->bind_param('i',$userId);$check->execute();$existing=$check->get_result()->fetch_assoc();$check->close();
 if($existing){
  $stmt=$conn->prepare("UPDATE consumer_profiles SET county=?,location=?,favorite_products=?,shopping_frequency=?,preferred_delivery=?,full_name=?,preferred_product=?,shopping_preference=? WHERE user_id=?");
  if(!$stmt)throw new RuntimeException($conn->error);
  $stmt->bind_param('ssssssssi',$county,$location,$favorites,$frequency,$delivery,$fullName,$preferred,$shopping,$userId);
 }else{
  $stmt=$conn->prepare("INSERT INTO consumer_profiles (user_id,county,location,favorite_products,shopping_frequency,preferred_delivery,full_name,preferred_product,shopping_preference) VALUES (?,?,?,?,?,?,?,?,?)");
  if(!$stmt)throw new RuntimeException($conn->error);
  $stmt->bind_param('issssssss',$userId,$county,$location,$favorites,$frequency,$delivery,$fullName,$preferred,$shopping);
 }
 if(!$stmt->execute())throw new RuntimeException($stmt->error);
 $stmt->close();
 $_SESSION['full_name']=$fullName;
 header('Location: ../consumer_dashboard.php?profile=saved');exit;
}catch(Throwable $e){http_response_code(500);error_log('VAYUMA consumer profile save: '.$e->getMessage());exit('Unable to save consumer profile. Please check the server error log or contact support.');}
