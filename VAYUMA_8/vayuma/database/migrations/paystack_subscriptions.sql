-- VAYUMA Paystack subscription migration. Safe to run on existing vayuma_db.
-- Extends subscription_plans, user_subscriptions, subscription_payments for Paystack
-- card-based recurring billing, plus webhook audit and status history tables.

-- subscription_plans: add paystack plan code + billing interval + currency
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_plans' AND COLUMN_NAME='paystack_plan_code')=0,
  'ALTER TABLE subscription_plans ADD COLUMN paystack_plan_code VARCHAR(100) NULL AFTER monthly_price',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_plans' AND COLUMN_NAME='billing_interval')=0,
  'ALTER TABLE subscription_plans ADD COLUMN billing_interval VARCHAR(20) NOT NULL DEFAULT \'monthly\' AFTER paystack_plan_code',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_plans' AND COLUMN_NAME='currency')=0,
  'ALTER TABLE subscription_plans ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT \'KES\' AFTER billing_interval',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- user_subscriptions: add paystack customer/subscription codes, next billing, grace, auto-renew
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions' AND COLUMN_NAME='paystack_customer_code')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN paystack_customer_code VARCHAR(100) NULL AFTER expires_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions' AND COLUMN_NAME='paystack_subscription_code')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN paystack_subscription_code VARCHAR(100) NULL AFTER paystack_customer_code',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions' AND COLUMN_NAME='next_billing_date')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN next_billing_date DATE NULL AFTER paystack_subscription_code',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions' AND COLUMN_NAME='grace_period_ends_at')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN grace_period_ends_at TIMESTAMP NULL AFTER next_billing_date',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions' AND COLUMN_NAME='auto_renew')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN auto_renew TINYINT(1) NOT NULL DEFAULT 1 AFTER grace_period_ends_at',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_subscriptions' AND COLUMN_NAME='billing_interval')=0,
  'ALTER TABLE user_subscriptions ADD COLUMN billing_interval VARCHAR(20) NOT NULL DEFAULT \'monthly\' AFTER auto_renew',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- subscription_payments: allow paystack method + paystack fields
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments' AND COLUMN_NAME='payment_method')>0,
  'ALTER TABLE subscription_payments MODIFY COLUMN payment_method ENUM(\'mpesa\',\'paystack\') NOT NULL DEFAULT \'mpesa\'',
  'ALTER TABLE subscription_payments ADD COLUMN payment_method ENUM(\'mpesa\',\'paystack\') NOT NULL DEFAULT \'mpesa\''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments' AND COLUMN_NAME='paystack_transaction_id')=0,
  'ALTER TABLE subscription_payments ADD COLUMN paystack_transaction_id BIGINT NULL AFTER checkout_request_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments' AND COLUMN_NAME='paystack_customer_code')=0,
  'ALTER TABLE subscription_payments ADD COLUMN paystack_customer_code VARCHAR(100) NULL AFTER paystack_transaction_id',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments' AND COLUMN_NAME='paystack_subscription_code')=0,
  'ALTER TABLE subscription_payments ADD COLUMN paystack_subscription_code VARCHAR(100) NULL AFTER paystack_customer_code',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments' AND COLUMN_NAME='channel')=0,
  'ALTER TABLE subscription_payments ADD COLUMN channel VARCHAR(30) NULL AFTER paystack_subscription_code',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='subscription_payments' AND COLUMN_NAME='raw_payload')=0,
  'ALTER TABLE subscription_payments ADD COLUMN raw_payload LONGTEXT NULL AFTER channel',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- payment_events: webhook audit trail (idempotency)
CREATE TABLE IF NOT EXISTS payment_events (
  id INT NOT NULL AUTO_INCREMENT,
  event_id VARCHAR(100) UNIQUE NULL,
  event_type VARCHAR(100) NOT NULL,
  reference VARCHAR(100) NULL,
  paystack_subscription_code VARCHAR(100) NULL,
  paystack_transaction_id BIGINT NULL,
  payload LONGTEXT NOT NULL,
  processed TINYINT(1) NOT NULL DEFAULT 0,
  processed_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_payment_events_event_id (event_id),
  KEY idx_payment_events_processed (processed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- subscription_status_history: audit/debugging
CREATE TABLE IF NOT EXISTS subscription_status_history (
  id INT NOT NULL AUTO_INCREMENT,
  user_membership_id INT NOT NULL,
  old_status VARCHAR(30) NULL,
  new_status VARCHAR(30) NOT NULL,
  reason VARCHAR(255) NULL,
  source VARCHAR(30) NOT NULL DEFAULT 'webhook',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_subscription_status_history_membership (user_membership_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
