<?php
/** Called inside a transaction after the plan row has been locked.
 * Free: all members become free; paid: non-paid-plan members become due;
 * negotiated: approval must be granted again. Never silently revoke an active
 * paid period for a negotiated plan; admin must wait until it has expired.
 * Provider references and immutable payment records are deliberately retained.
 */
function vayuma_reconcile_plan_memberships($conn, int $planId, string $oldType, string $newType, string $interval, int $actorId = 0): void
{
    if ($oldType === $newType) return;
    $read = $conn->prepare('SELECT id, status FROM user_subscriptions WHERE plan_id=? ORDER BY id FOR UPDATE');
    $read->bind_param('i', $planId);
    if (!$read->execute()) throw new RuntimeException('Membership lookup failed');
    $result = $read->get_result();
    $members = [];
    while ($member = $result->fetch_assoc()) $members[] = $member;
    $read->close();
    if ($oldType === 'paid' && $newType === 'negotiated') {
        foreach ($members as $member) {
            if ($member['status'] === 'active') throw new DomainException('A paid membership is active. Wait until active paid periods end before changing to a negotiated plan.');
        }
    }
    $newStatus = $newType === 'free' ? 'free_account' : ($newType === 'paid' ? 'payment_due' : 'negotiated_plan');
    $update = $conn->prepare('UPDATE user_subscriptions SET status=?, starts_at=NULL, expires_at=NULL, next_billing_date=NULL, grace_period_ends_at=NULL, negotiated_approved=0, negotiated_approved_by=NULL, negotiated_approved_at=NULL, billing_interval=?, updated_at=NOW() WHERE id=? AND plan_id=?');
    $history = $conn->prepare("INSERT INTO subscription_status_history (user_membership_id, old_status, new_status, reason, source) VALUES (?, ?, ?, ?, 'admin')");
    foreach ($members as $member) {
        $id = (int)$member['id']; $oldStatus = (string)$member['status'];
        $update->bind_param('ssii', $newStatus, $interval, $id, $planId);
        if (!$update->execute()) throw new RuntimeException('Membership transition failed');
        $reason = 'Plan type changed from ' . $oldType . ' to ' . $newType . ' by administrator' . ($actorId > 0 ? ' #' . $actorId : '') . '.';
        $history->bind_param('isss', $id, $oldStatus, $newStatus, $reason);
        if (!$history->execute()) throw new RuntimeException('Membership transition history failed');
    }
    $update->close(); $history->close();
}
