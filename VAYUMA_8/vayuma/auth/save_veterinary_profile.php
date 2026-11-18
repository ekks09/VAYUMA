<?php
session_start();require_once __DIR__.'/../config/db.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: ../login.php');exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'||strtolower(trim($_SESSION['role']??''))!=='veterinary_officer'){header('Location: ../role.php');exit;}
$v=static fn($k)=>trim((string)($_POST[$k]??''));
$org=$v('organization_name');$designation=$v('organization_type');$county=$v('county');$location=$v('location');$license=$v('license_number');$registration=$v('registration_number');$animals=$v('animals_handled');$focus=$v('service_focus');$area=$v('service_area');$description=$v('description');
$allowed=['Veterinary Surgeon','Veterinary Practitioner','Animal Health Assistant','Animal Health Technician','Para-veterinary Professional','Veterinary Clinic / Hospital','Animal Welfare Organisation'];
if($org===''||$county===''||!in_array($designation,$allowed,true)){$_SESSION['profile_error']='Please provide the practice name, county and a valid professional designation.';header('Location: ../veterinary_officer_profile.php');exit;}
$uid=(int)$_SESSION['user_id'];$role='veterinary_officer';
$sql="INSERT INTO stakeholder_profiles (user_id,role,organization_name,organization_type,county,location,contact_phone,service_focus,description,license_number,registration_number,capacity,service_area,profile_completed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE role=VALUES(role),organization_name=VALUES(organization_name),organization_type=VALUES(organization_type),county=VALUES(county),location=VALUES(location),contact_phone=VALUES(contact_phone),service_focus=VALUES(service_focus),description=VALUES(description),license_number=VALUES(license_number),registration_number=VALUES(registration_number),capacity=VALUES(capacity),service_area=VALUES(service_area),profile_completed=1";
$stmt=$conn->prepare($sql);if(!$stmt){$_SESSION['profile_error']='The stakeholder_profiles table is not ready. Please check the Phase 1 / stakeholder profile migrations.';header('Location: ../veterinary_officer_profile.php');exit;}
$phone=(string)($_SESSION['phone']??'');$stmt->bind_param('issssssssssss',$uid,$role,$org,$designation,$county,$location,$phone,$focus,$description,$license,$registration,$animals,$area);
if(!$stmt->execute()){$stmt->close();$_SESSION['profile_error']='Could not save veterinary profile. Please try again.';header('Location: ../veterinary_officer_profile.php');exit;}$stmt->close();$_SESSION['profile_completed']=true;header('Location: ../veterinary_officer_dashboard.php');exit;
