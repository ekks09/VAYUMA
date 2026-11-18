<?php
session_start();
require_once __DIR__ . '/../config/db.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true) { header('Location: ../login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../extension_profile.php'); exit; }
if (strtolower(trim($_SESSION['role'] ?? '')) !== 'extension_officer') { header('Location: ../role.php'); exit; }
$v=static fn($k)=>trim((string)($_POST[$k]??''));
$organization=$v('organization');$county=$v('county');$subCounty=$v('sub_county');$specialization=$v('specialization');$qualification=$v('qualification');$location=$v('location');$coverage=$v('coverage_area');$bio=$v('bio');
$years=filter_var($_POST['experience_years']??0,FILTER_VALIDATE_INT); if($years===false||$years<0||$years>60)$years=0;
if($organization===''||$county===''||$specialization===''||$qualification===''){$_SESSION['profile_error']='Please complete organisation, county, specialization and qualification.';header('Location: ../extension_profile.php');exit;}
$userId=(int)$_SESSION['user_id'];
$sql="INSERT INTO extension_profiles (user_id,organization,county,sub_county,specialization,years_experience,coverage_area,qualification,location,bio) VALUES (?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE organization=VALUES(organization),county=VALUES(county),sub_county=VALUES(sub_county),specialization=VALUES(specialization),years_experience=VALUES(years_experience),coverage_area=VALUES(coverage_area),qualification=VALUES(qualification),location=VALUES(location),bio=VALUES(bio)";
$stmt=$conn->prepare($sql);if(!$stmt){$_SESSION['profile_error']='Could not prepare profile save. Please confirm the existing extension_profiles table matches the project database.';header('Location: ../extension_profile.php');exit;}
$stmt->bind_param('issssissss',$userId,$organization,$county,$subCounty,$specialization,$years,$coverage,$qualification,$location,$bio);
if(!$stmt->execute()){$stmt->close();$_SESSION['profile_error']='Extension profile could not be saved. Please try again or share the exact database error.';header('Location: ../extension_profile.php');exit;}$stmt->close();
// Also publish the officer in the common directory, while keeping verification pending unless an administrator has verified the profile.
$orgType='Agricultural extension officer';$focus=$specialization;$phone=(string)($_SESSION['phone']??'');$license='';$registration='';$capacity='';
$dir=$conn->prepare("INSERT INTO stakeholder_profiles (user_id,role,organization_name,organization_type,county,location,contact_phone,service_focus,description,license_number,registration_number,capacity,service_area,profile_completed) VALUES (?,'extension_officer',?,?,?,?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE role=VALUES(role),organization_name=VALUES(organization_name),organization_type=VALUES(organization_type),county=VALUES(county),location=VALUES(location),contact_phone=VALUES(contact_phone),service_focus=VALUES(service_focus),description=VALUES(description),service_area=VALUES(service_area),profile_completed=1");
if($dir){$dir->bind_param('isssssssssss',$userId,$organization,$orgType,$county,$location,$phone,$focus,$bio,$license,$registration,$capacity,$coverage);$dir->execute();$dir->close();}
$_SESSION['profile_completed']=true;header('Location: ../extension_dashboard.php');exit;
