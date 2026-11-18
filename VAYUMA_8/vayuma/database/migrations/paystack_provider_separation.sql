-- VAYUMA Paystack + M-Pesa provider separation migration.
-- Adds payment_provider to user_subscriptions so each provider owns its own
-- subscription state and cannot silently overwrite the other.
--
-- Precedence rule (documented):
--   A provider's webhook may update user_subscriptions only when:
--     payment_provider IS NULL          (first-ever activation)
--   OR payment_provider = 'this provider'  (same provider renewing)
--   OR status = 'expired' / expires_at <= NOW()
--                                      (expired subscriptions can switch providers)
--   When activating, the provider sets payment_provider = 'itself'.

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions' AND COLUMN_NAME='payment_provider')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN payment_provider ENUM(\'mpesa\',\'paystack\') NULL AFTER plan_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions' AND INDEX_NAME='idx_payment_provider')=0,
  'CREATE INDEX idx_payment_provider ON user_subscriptions (payment_provider)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Prevent duplicate pending Paystack payments for the same subscription
-- Uses a generated column (MySQL doesn't support partial indexes)
UPDATE subscription_payments older
JOIN subscription_payments newer
  ON newer.subscription_id=older.subscription_id
 AND newer.payment_method='paystack'
 AND newer.status='pending'
 AND (newer.created_at>older.created_at
      OR (newer.created_at=older.created_at AND newer.id>older.id))
SET older.status='failed',
    older.result_description=COALESCE(older.result_description, 'Superseded by a newer pending Paystack checkout.')
WHERE older.payment_method='paystack'
  AND older.status='pending';

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments' AND COLUMN_NAME='pending_paystack_flag')=0,
  'ALTER TABLE subscription_payments ADD COLUMN pending_paystack_flag TINYINT(1) AS (CASE WHEN payment_method=''paystack'' AND status=''pending'' THEN 1 ELSE NULL END) VIRTUAL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments' AND INDEX_NAME='uq_pending_paystack')=0,
  'ALTER TABLE subscription_payments ADD UNIQUE KEY uq_pending_paystack (subscription_id, pending_paystack_flag)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
