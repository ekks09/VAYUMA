<?php
session_start();
require_once __DIR__.'/config/db.php';
if(!isset($_SESSION['user_id']) || ($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
if(strtolower(trim($_SESSION['role']??''))!=='manufacturer'){header('Location: role.php');exit;}
$expected_role='manufacturer';
$profile=[];
$stmt=$conn->prepare("SELECT * FROM stakeholder_profiles WHERE user_id=? LIMIT 1");
if($stmt){ $uid=(int)$_SESSION['user_id']; $stmt->bind_param('i',$uid); $stmt->execute(); $profile=$stmt->get_result()->fetch_assoc()?:[]; $stmt->close(); }
require __DIR__.'/stakeholder_profile_form.php';
?>