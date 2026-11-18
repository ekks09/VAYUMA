<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if(!isset($_SESSION['user_id'])){header('Location: ../login.php');exit;}
if(($_SESSION['role']??'')!=='buyer'){header('Location: ../role.php');exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: ../buyer_profile.php');exit;}
$userId=(int)$_SESSION['user_id'];
$fullName=trim($_POST['full_name']??'');$buyerType=trim($_POST['buyer_type']??'');
$businessName=trim($_POST['business_name']??'');$businessType=trim($_POST['business_type']??'');
$location=trim($_POST['location']??'');$countyId=(int)($_POST['county_id']??0);
$preferred=trim($_POST['preferred_product']??'');$products=trim($_POST['products_interested']??'');
$volume=trim($_POST['expected_volume']??'');$delivery=trim($_POST['delivery_preference']??'');
$payment=trim($_POST['payment_method']??'');
if($fullName===''||$buyerType===''||$location===''||$countyId<1||$preferred===''||$payment===''){
 $missing=[];
 if($fullName==='')$missing[]="contact person's full name";
 if($buyerType==='')$missing[]='buyer type';
 if($location==='')$missing[]='town / area / estate';
 if($countyId<1)$missing[]='a county selected from the suggestions';
 if($preferred==='')$missing[]='main preferred product';
 if($payment==='')$missing[]='preferred payment method';
 $_SESSION['buyer_profile_error']='Please complete these required fields: '.implode(', ',$missing).'.';
 $_SESSION['buyer_profile_old']=[
  'full_name'=>$fullName,'buyer_type'=>$buyerType,'business_name'=>$businessName,
  'business_type'=>$businessType,'county_id'=>$countyId,
  'county_search'=>trim($_POST['county_search']??''),'location'=>$location,
  'preferred_product'=>$preferred,'products_interested'=>$products,
  'expected_volume'=>$volume,'delivery_preference'=>$delivery,
  'payment_method'=>$payment
 ];
 header('Location: ../buyer_profile.php');exit;
}
$c=$conn->prepare("SELECT name FROM administrative_units WHERE id=? AND unit_type='county' AND is_active=1 LIMIT 1");
if(!$c){http_response_code(500);exit('Could not validate county: '.$conn->error);}
$c->bind_param('i',$countyId);$c->execute();$countyRow=$c->get_result()->fetch_assoc();$c->close();
if(!$countyRow){
 $_SESSION['buyer_profile_error']='Please select a valid county from the suggestions.';
 $_SESSION['buyer_profile_old']=[
  'full_name'=>$fullName,'buyer_type'=>$buyerType,'business_name'=>$businessName,
  'business_type'=>$businessType,'county_id'=>0,
  'county_search'=>trim($_POST['county_search']??''),'location'=>$location,
  'preferred_product'=>$preferred,'products_interested'=>$products,
  'expected_volume'=>$volume,'delivery_preference'=>$delivery,
  'payment_method'=>$payment
 ];
 header('Location: ../buyer_profile.php');exit;
}
$county=$countyRow['name'];
try{
 $check=$conn->prepare("SELECT id FROM buyer_profiles WHERE user_id=? LIMIT 1");
 if(!$check)throw new RuntimeException($conn->error);
 $check->bind_param('i',$userId);$check->execute();$existing=$check->get_result()->fetch_assoc();$check->close();
 if($existing){
  $stmt=$conn->prepare("UPDATE buyer_profiles SET business_name=?,business_type=?,county=?,location=?,products_interested=?,expected_volume=?,delivery_preference=?,full_name=?,buyer_type=?,preferred_product=?,payment_method=? WHERE user_id=?");
  if(!$stmt)throw new RuntimeException($conn->error);
  $stmt->bind_param('sssssssssssi',$businessName,$businessType,$county,$location,$products,$volume,$delivery,$fullName,$buyerType,$preferred,$payment,$userId);
 }else{
  $stmt=$conn->prepare("INSERT INTO buyer_profiles (user_id,business_name,business_type,county,location,products_interested,expected_volume,delivery_preference,full_name,buyer_type,preferred_product,payment_method) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
  if(!$stmt)throw new RuntimeException($conn->error);
  $stmt->bind_param('isssssssssss',$userId,$businessName,$businessType,$county,$location,$products,$volume,$delivery,$fullName,$buyerType,$preferred,$payment);
 }
 if(!$stmt->execute())throw new RuntimeException($stmt->error);
 $stmt->close();
 $_SESSION['full_name']=$fullName;
 header('Location: ../buyer_dashboard.php?profile=saved');exit;
}catch(Throwable $e){http_response_code(500);error_log('VAYUMA buyer profile save: '.$e->getMessage());exit('Unable to save buyer profile. Please check the server error log or contact support.');}
