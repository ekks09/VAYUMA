<?php
require_once __DIR__.'/config/db.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);exit('Method not allowed');}
$token=trim($_POST['token']??''); $name=trim($_POST['full_name']??''); $phone=trim($_POST['phone']??''); $email=trim($_POST['email']??''); $county=trim($_POST['county']??''); $notes=trim($_POST['notes']??'');
if($token===''||$name===''||$phone===''||strlen($name)>150||strlen($phone)>30||strlen($email)>150||strlen($notes)>500){http_response_code(400);exit('Please provide valid required details.');}
$stmt=$conn->prepare("SELECT id FROM government_programmes WHERE public_token=? AND status='published' LIMIT 1");$stmt->bind_param('s',$token);$stmt->execute();$p=$stmt->get_result()->fetch_assoc();$stmt->close();
if(!$p){http_response_code(404);exit('Programme is not available for registration.');}
if(session_status()===PHP_SESSION_NONE)session_start();
$userId=isset($_SESSION['user_id'])?(int)$_SESSION['user_id']:null;
$stmt=$conn->prepare("INSERT INTO government_programme_registrations(programme_id,user_id,full_name,phone,email,county,notes) VALUES(?,?,?,?,?,?,?)");
$stmt->bind_param('iisssss',$p['id'],$userId,$name,$phone,$email,$county,$notes);$stmt->execute();$stmt->close();
header('Location: government_programmes.php?token='.urlencode($token).'&registered=1');exit;
