-- REVIEW ONLY: not validated on TiDB. Do not execute on production.
-- Requires the supplied fresh reference schema and 06 applied on isolated test DB.
-- Check every column and index is absent, and confirm backups and target first.
-- DDL may commit statement by statement: stop on error and inspect before retry.
-- This reconciles existing app subscription fields only; it does NOT enable or
-- implement payments, and is NOT proof of production readiness. No data seeds.

ALTER TABLE subscription_plans
  ADD COLUMN paystack_plan_code VARCHAR(100) NULL,
  ADD COLUMN billing_interval VARCHAR(20) NOT NULL DEFAULT 'monthly',
  ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'KES';

ALTER TABLE user_subscriptions
  ADD COLUMN payment_provider ENUM('mpesa','paystack') NULL,
  ADD COLUMN paystack_customer_code VARCHAR(100) NULL,
  ADD COLUMN paystack_subscription_code VARCHAR(100) NULL,
  ADD COLUMN next_billing_date DATE NULL,
  ADD COLUMN grace_period_ends_at TIMESTAMP NULL,
  ADD COLUMN auto_renew TINYINT(1) NOT NULL DEFAULT 1,
  ADD COLUMN billing_interval VARCHAR(20) NOT NULL DEFAULT 'monthly',
  ADD COLUMN negotiated_approved TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN negotiated_approved_by INT NULL,
  ADD COLUMN negotiated_approved_at DATETIME NULL;
CREATE INDEX idx_payment_provider ON user_subscriptions (payment_provider);

ALTER TABLE subscription_payments
  MODIFY COLUMN payment_method ENUM('mpesa','paystack') NOT NULL DEFAULT 'mpesa',
  ADD COLUMN paystack_transaction_id BIGINT NULL,
  ADD COLUMN paystack_customer_code VARCHAR(100) NULL,
  ADD COLUMN paystack_subscription_code VARCHAR(100) NULL,
  ADD COLUMN channel VARCHAR(30) NULL,
  ADD COLUMN raw_payload LONGTEXT NULL,
  ADD COLUMN paystack_reference VARCHAR(100) NULL,
  ADD COLUMN billing_interval VARCHAR(20) NOT NULL DEFAULT 'monthly',
  ADD COLUMN paystack_authorization_url VARCHAR(500) NULL,
  ADD COLUMN subscription_expires_at DATETIME NULL;
CREATE UNIQUE INDEX uq_subscription_paystack_reference ON subscription_payments (paystack_reference);

CREATE TABLE payment_events (
  id INT NOT NULL AUTO_INCREMENT,
  event_id VARCHAR(100) NULL,
  event_type VARCHAR(100) NOT NULL,
  reference VARCHAR(100) NULL,
  paystack_subscription_code VARCHAR(100) NULL,
  paystack_transaction_id BIGINT NULL,
  payload LONGTEXT NOT NULL,
  processed TINYINT(1) NOT NULL DEFAULT 0,
  processed_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_payment_events_event_id (event_id),
  KEY idx_payment_events_processed (processed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Legacy provider-specific pending-payment uniqueness is intentionally not
-- migrated here. Payment flows remain hard locked pending separate redesign.
