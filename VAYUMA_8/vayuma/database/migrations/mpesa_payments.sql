-- VAYUMA M-Pesa / Daraja payment migration.
-- ACID-compliant payments table: every payment attempt is recorded in its own row,
-- so a buyer can retry after a cancelled/failed M-Pesa prompt.
-- Foreign keys enforce that a payment always belongs to a real order and buyer.
-- Indexes support fast callback lookups by checkout_request_id and status.

CREATE TABLE IF NOT EXISTS payments (
  id INT NOT NULL AUTO_INCREMENT,
  order_id INT NOT NULL,
  payer_id INT NOT NULL,
  seller_id INT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method ENUM('demo','mpesa','bank','paystack') NOT NULL DEFAULT 'mpesa',
  transaction_reference VARCHAR(100) NULL,
  phone_number VARCHAR(30) NULL,
  status ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  paid_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- M-Pesa Daraja fields
  merchant_request_id VARCHAR(100) NULL,
  checkout_request_id VARCHAR(100) NULL,
  mpesa_receipt_number VARCHAR(100) NULL,
  result_code INT NULL,
  result_description VARCHAR(255) NULL,
  callback_received_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_payments_order (order_id),
  KEY idx_payments_payer (payer_id),
  KEY idx_payments_checkout (checkout_request_id),
  KEY idx_payments_status (status),
  KEY idx_payments_created (created_at),
  CONSTRAINT fk_payments_order
    FOREIGN KEY (order_id) REFERENCES orders(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_payments_payer
    FOREIGN KEY (payer_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add M-Pesa columns to an existing payments table if they are missing.
DROP PROCEDURE IF EXISTS vayuma_mpesa_columns;
DELIMITER $$
CREATE PROCEDURE vayuma_mpesa_columns()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='merchant_request_id') THEN
    ALTER TABLE payments ADD COLUMN merchant_request_id VARCHAR(100) NULL AFTER transaction_reference;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='checkout_request_id') THEN
    ALTER TABLE payments ADD COLUMN checkout_request_id VARCHAR(100) NULL AFTER merchant_request_id;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='mpesa_receipt_number') THEN
    ALTER TABLE payments ADD COLUMN mpesa_receipt_number VARCHAR(100) NULL AFTER checkout_request_id;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='result_code') THEN
    ALTER TABLE payments ADD COLUMN result_code INT NULL AFTER mpesa_receipt_number;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='result_description') THEN
    ALTER TABLE payments ADD COLUMN result_description VARCHAR(255) NULL AFTER result_code;
  END IF;
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='callback_received_at') THEN
    ALTER TABLE payments ADD COLUMN callback_received_at TIMESTAMP NULL AFTER result_description;
  END IF;
END$$
DELIMITER ;
CALL vayuma_mpesa_columns();
DROP PROCEDURE vayuma_mpesa_columns;

-- Ensure indexes exist on existing tables (safe to re-run).
DROP PROCEDURE IF EXISTS vayuma_mpesa_indexes;
DELIMITER $$
CREATE PROCEDURE vayuma_mpesa_indexes()
BEGIN
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_checkout') THEN
    CREATE INDEX idx_payments_checkout ON payments(checkout_request_id);
  END IF;
  IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_status') THEN
    CREATE INDEX idx_payments_status ON payments(status);
  END IF;
END$$
DELIMITER ;
CALL vayuma_mpesa_indexes();
DROP PROCEDURE vayuma_mpesa_indexes;
