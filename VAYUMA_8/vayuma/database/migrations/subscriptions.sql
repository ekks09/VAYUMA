-- VAYUMA subscriptions. Run once in phpMyAdmin against vayuma_db.
CREATE TABLE IF NOT EXISTS subscription_plans (
  id INT NOT NULL AUTO_INCREMENT,
  role_key VARCHAR(60) NOT NULL,
  stakeholder_name VARCHAR(120) NOT NULL,
  monthly_price DECIMAL(12,2) NULL,
  plan_type ENUM('paid','free','negotiated') NOT NULL DEFAULT 'paid',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_subscription_plan_role (role_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS user_subscriptions (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  plan_id INT NOT NULL,
  status ENUM('active','payment_due','expired','free_account','negotiated_plan') NOT NULL DEFAULT 'payment_due',
  starts_at DATETIME NULL,
  expires_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_user_subscription (user_id),
  KEY idx_subscription_status_expiry (status, expires_at),
  CONSTRAINT fk_user_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS subscription_payments (
  id INT NOT NULL AUTO_INCREMENT,
  subscription_id INT NOT NULL,
  user_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method ENUM('mpesa') NOT NULL DEFAULT 'mpesa',
  phone_number VARCHAR(30) NULL,
  merchant_request_id VARCHAR(100) NULL,
  checkout_request_id VARCHAR(100) NULL,
  transaction_reference VARCHAR(100) NULL,
  mpesa_receipt_number VARCHAR(100) NULL,
  status ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  result_code INT NULL,
  result_description VARCHAR(255) NULL,
  callback_received_at DATETIME NULL,
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_subscription_checkout (checkout_request_id),
  KEY idx_subscription_payment_subscription (subscription_id),
  CONSTRAINT fk_subscription_payments_subscription FOREIGN KEY (subscription_id) REFERENCES user_subscriptions(id) ON DELETE CASCADE,
  CONSTRAINT fk_subscription_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO subscription_plans (role_key, stakeholder_name, monthly_price, plan_type) VALUES
('farmer','Farmer',299,'paid'),('consumer','Consumer',199,'paid'),('buyer','Buyer',499,'paid'),('transport_provider','Transport Provider',399,'paid'),('input_supplier','Input Supplier',999,'paid'),('manufacturer','Manufacturer',999,'paid'),('warehouse_provider','Warehouse Provider',799,'paid'),('retailer','Retailer',499,'paid'),('agribusiness','Agribusiness / Trader',699,'paid'),('cooperative','Cooperative',999,'paid'),('researcher','Researcher / Research Institution',999,'paid'),('extension_officer','Agricultural Extension Officer',NULL,'free'),('veterinary_officer','Veterinary Officer',499,'paid'),('financial_institution','Financial Institution',2999,'paid'),('insurance_provider','Insurance Provider',1999,'paid'),('government_officer','Government Officer',NULL,'free'),('ngo_partner','NGO / Development Partner',1499,'paid'),('agricultural_service_provider','Agricultural Service Provider',799,'paid'),('strategic_partner','Strategic Partner',NULL,'negotiated')
ON DUPLICATE KEY UPDATE stakeholder_name=VALUES(stakeholder_name);
