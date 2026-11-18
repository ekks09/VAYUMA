-- Apply after subscriptions.sql, paystack_subscriptions.sql, and
-- paystack_provider_separation.sql.
-- Adds a durable Paystack reference and an explicit manual approval state for
-- negotiated memberships without changing existing M-Pesa payment records.

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments'
     AND COLUMN_NAME='paystack_reference')=0,
  'ALTER TABLE subscription_payments ADD COLUMN paystack_reference VARCHAR(100) NULL AFTER transaction_reference',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE subscription_payments
SET status='failed',
    result_description=COALESCE(result_description, 'Legacy Paystack checkout superseded during subscription migration.')
WHERE payment_method='paystack'
  AND status='pending'
  AND paystack_reference IS NULL;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments'
     AND COLUMN_NAME='billing_interval')=0,
  'ALTER TABLE subscription_payments ADD COLUMN billing_interval VARCHAR(20) NOT NULL DEFAULT \'monthly\' AFTER payment_method',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments'
     AND COLUMN_NAME='paystack_authorization_url')=0,
  'ALTER TABLE subscription_payments ADD COLUMN paystack_authorization_url VARCHAR(500) NULL AFTER paystack_reference',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments'
     AND COLUMN_NAME='subscription_expires_at')=0,
  'ALTER TABLE subscription_payments ADD COLUMN subscription_expires_at DATETIME NULL AFTER paid_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments'
     AND INDEX_NAME='uq_subscription_paystack_reference')=0,
  'ALTER TABLE subscription_payments ADD UNIQUE KEY uq_subscription_paystack_reference (paystack_reference)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions'
     AND COLUMN_NAME='negotiated_approved')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN negotiated_approved TINYINT(1) NOT NULL DEFAULT 0 AFTER auto_renew',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions'
     AND COLUMN_NAME='negotiated_approved_by')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN negotiated_approved_by INT NULL AFTER negotiated_approved',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions'
     AND COLUMN_NAME='negotiated_approved_at')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN negotiated_approved_at DATETIME NULL AFTER negotiated_approved_by',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
