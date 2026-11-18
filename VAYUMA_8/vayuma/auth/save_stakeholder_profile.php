<?php
session_start(); require_once __DIR__.'/../config/db.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: ../login.php');exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: ../role.php');exit;}
$role=strtolower(trim($_SESSION['role']??''));
$allowed=array_keys(require __DIR__.'/../stakeholder_roles_array.php');
if(!in_array($role,$allowed,true)){header('Location: ../role.php');exit;}
$v=function($k){return trim($_POST[$k]??'');};
$organization_name=$v('organization_name'); $organization_type=$v('organization_type'); $county=$v('county'); $location=$v('location'); $contact_phone=$v('contact_phone'); $service_focus=$v('service_focus'); $description=$v('description'); $license_number=$v('license_number'); $registration_number=$v('registration_number'); $capacity=$v('capacity'); $service_area=$v('service_area');
if($organization_name===''||$county===''){$_SESSION['profile_error']='Business / organisation name and county are required.'; header('Location: ../'.($role==='veterinary_officer'?'veterinary_officer':$role).'_profile.php');exit;}
$uid=(int)$_SESSION['user_id'];
$sql="INSERT INTO stakeholder_profiles (user_id,role,organization_name,organization_type,county,location,contact_phone,service_focus,description,license_number,registration_number,capacity,service_area,profile_completed) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE role=VALUES(role),organization_name=VALUES(organization_name),organization_type=VALUES(organization_type),county=VALUES(county),location=VALUES(location),contact_phone=VALUES(contact_phone),service_focus=VALUES(service_focus),description=VALUES(description),license_number=VALUES(license_number),registration_number=VALUES(registration_number),capacity=VALUES(capacity),service_area=VALUES(service_area),profile_completed=1";
$stmt=$conn->prepare($sql); if(!$stmt){die('Profile storage is not ready. Run database/migrations/stakeholder_profiles.sql first.');}
$stmt->bind_param('issssssssssss',$uid,$role,$organization_name,$organization_type,$county,$location,$contact_phone,$service_focus,$description,$license_number,$registration_number,$capacity,$service_area);
if(!$stmt->execute()){$_SESSION['profile_error']='Unable to save your profile. Please check the database migration and try again.'; $stmt->close(); header('Location: ../'.($role==='veterinary_officer'?'veterinary_officer':$role).'_profile.php');exit;}
$stmt->close(); $_SESSION['profile_completed']=true;
$dashboard = $role === 'strategic_partner' ? 'stakeholder_dashboard.php' : $role . '_dashboard.php';
header('Location: ../' . $dashboard); exit;
