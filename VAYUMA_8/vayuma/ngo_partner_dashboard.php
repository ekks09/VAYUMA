<?php
session_start(); require_once __DIR__.'/config/db.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: login.php');exit;}
if(strtolower(trim($_SESSION['role']??''))!=='ngo_partner'){header('Location: dashboard.php');exit;}
$expected_role='ngo_partner'; require __DIR__.'/stakeholder_dashboard_core.php';
?>