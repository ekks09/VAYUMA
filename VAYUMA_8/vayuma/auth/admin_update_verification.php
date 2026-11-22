<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/admin.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['logged_in'] ?? false) !== true || !is_vayuma_admin((int)$_SESSION['user_id'])) {
    http_response_code(403); exit('Forbidden: administrator access required.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !vayuma_csrf_valid()) {
    http_response_code(400); exit('Invalid or expired form security token. Go back, refresh the page and try again.');
}
$profileId=(int)($_POST['profile_id']??0);
$newStatus=(string)($_POST['status']??'');
$note=trim((string)($_POST['decision_note']??''));
if($profileId<1 || !in_array($newStatus,['pending','verified','rejected'],true) || strlen($note)>500){http_response_code(400);exit('Invalid verification request.');}
$adminId=(int)$_SESSION['user_id'];
$conn->begin_transaction();
try {
    $stmt=$conn->prepare('SELECT id,user_id,verification_status FROM stakeholder_profiles WHERE id=? LIMIT 1 FOR UPDATE');
    $stmt->bind_param('i',$profileId);$stmt->execute();$profile=$stmt->get_result()->fetch_assoc();$stmt->close();
    if(!$profile) throw new RuntimeException('Stakeholder profile not found.');
    $oldStatus=(string)$profile['verification_status'];
    if($oldStatus!==$newStatus){
        $up=$conn->prepare('UPDATE stakeholder_profiles SET verification_status=? WHERE id=?');
        $up->bind_param('si',$newStatus,$profileId);$up->execute();$up->close();
        $log=$conn->prepare('INSERT INTO stakeholder_verification_logs (stakeholder_profile_id,target_user_id,admin_user_id,old_status,new_status,decision_note) VALUES (?,?,?,?,?,?)');
        $targetUser=(int)$profile['user_id'];$log->bind_param('iiisss',$profileId,$targetUser,$adminId,$oldStatus,$newStatus,$note);$log->execute();$log->close();
    }
    $conn->commit();
} catch(Throwable $e){$conn->rollback();http_response_code(500);error_log('VAYUMA verification decision save unavailable.');exit('The verification decision could not be saved. Please return to the verification centre and try again.');}
$role=$_POST['return_role']??'';
header('Location: ../admin_verification.php?role='.urlencode($role).'&status=all&updated=1');exit;
