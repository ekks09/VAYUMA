-- VAYUMA Paystack payment migration. Safe to run on an existing payments table.
-- Adds 'paystack' to the payment_method ENUM and Paystack-specific columns.

ALTER TABLE payments
MODIFY COLUMN payment_method ENUM('demo','mpesa','bank','paystack') NOT NULL DEFAULT 'demo';

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='paystack_reference')=0,
  'ALTER TABLE payments ADD COLUMN paystack_reference VARCHAR(100) NULL AFTER transaction_reference',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='paystack_channel')=0,
  'ALTER TABLE payments ADD COLUMN paystack_channel VARCHAR(30) NULL AFTER paystack_reference',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='paystack_access_code')=0,
  'ALTER TABLE payments ADD COLUMN paystack_access_code VARCHAR(100) NULL AFTER paystack_channel',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='paystack_authorization_url')=0,
  'ALTER TABLE payments ADD COLUMN paystack_authorization_url TEXT NULL AFTER paystack_access_code',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='paystack_fees')=0,
  'ALTER TABLE payments ADD COLUMN paystack_fees DECIMAL(12,2) NULL AFTER paystack_authorization_url',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
