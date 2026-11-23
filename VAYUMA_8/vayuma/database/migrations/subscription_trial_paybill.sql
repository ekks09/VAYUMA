-- TiDB: apply after the existing subscription and billing migrations. Back up
-- and inspect the schema and status values first. DDL commits independently.
-- IF NOT EXISTS does not check or repair a conflicting existing definition.
-- Payment acceptance remains disabled.
ALTER TABLE user_subscriptions MODIFY COLUMN status ENUM('active','trialing','payment_due','expired','free_account','negotiated_plan') NOT NULL DEFAULT 'payment_due';
ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS has_used_trial TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS trial_started_at DATETIME NULL;
ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS trial_ends_at DATETIME NULL;
ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS mpesa_paybill_reference VARCHAR(100) NULL;
-- TiDB cannot always add an index on a new column in the same ALTER statement.
CREATE UNIQUE INDEX IF NOT EXISTS uq_subscription_paybill_reference ON user_subscriptions (mpesa_paybill_reference);

CREATE TABLE IF NOT EXISTS subscription_paybill_payments (
  id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  subscription_id INT NOT NULL,
  user_id INT NOT NULL,
  receipt VARCHAR(100) NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  phone VARCHAR(30) NULL,
  account_reference VARCHAR(100) NOT NULL,
  status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_paybill_receipt (receipt),
  KEY idx_paybill_payment_user (user_id),
  CONSTRAINT fk_paybill_subscription FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id),
  CONSTRAINT fk_paybill_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
