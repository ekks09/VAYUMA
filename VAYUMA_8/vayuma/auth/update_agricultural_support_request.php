<?php
session_start();require_once __DIR__.'/../config/db.php';require_once __DIR__.'/../includes/csrf.php';
if(!isset($_SESSION['user_id'])||($_SESSION['logged_in']??false)!==true){header('Location: ../login.php');exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: ../agricultural_support_requests.php');exit;}
// This endpoint also accepts form submissions from the authenticated session; use same-origin POST and ownership checks.
if(!vayuma_csrf_valid()){http_response_code(403);exit('Invalid request token.');}
$type=($_POST['type']??'')==='veterinary'?'veterinary':(($_POST['type']??'')==='extension'?'extension':'');$id=(int)($_POST['id']??0);$action=$_POST['action']??'';$uid=(int)$_SESSION['user_id'];if(!$type||$id<1){header('Location: ../agricultural_support_requests.php');exit;}
$table=$type==='veterinary'?'veterinary_service_requests':'extension_support_requests';$s=$conn->prepare("SELECT requester_id,provider_id,status FROM {$table} WHERE id=? LIMIT 1");$s->bind_param('i',$id);$s->execute();$r=$s->get_result()->fetch_assoc();$s->close();if(!$r){http_response_code(404);exit('Request not found.');}
$new='';if((int)$r['requester_id']===$uid&&$r['status']==='pending'&&$action==='cancel')$new='cancelled';elseif((int)$r['provider_id']===$uid&&$r['status']==='pending'&&in_array($action,['accept','decline'],true))$new=$action==='accept'?'accepted':'declined';elseif((int)$r['provider_id']===$uid&&$r['status']==='accepted'&&$action==='in_progress')$new='in_progress';elseif((int)$r['provider_id']===$uid&&in_array($r['status'],['accepted','in_progress'],true)&&$action==='completed')$new='completed';else{http_response_code(403);exit('You are not allowed to make this status change.');}
$u=$conn->prepare("UPDATE {$table} SET status=? WHERE id=? AND status=?");$u->bind_param('sis',$new,$id,$r['status']);$u->execute();$u->close();header('Location: ../agricultural_support_requests.php?view='.( (int)$r['provider_id']===$uid?'received':'sent' ).'&type='.$type);exit;
