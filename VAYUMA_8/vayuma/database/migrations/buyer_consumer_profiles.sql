-- Buyer and consumer onboarding tables for an existing VAYUMA account database.
CREATE TABLE IF NOT EXISTS buyer_profiles (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  business_name VARCHAR(150) DEFAULT NULL,
  business_type VARCHAR(100) DEFAULT NULL,
  county VARCHAR(120) NOT NULL,
  location VARCHAR(255) NOT NULL,
  products_interested TEXT DEFAULT NULL,
  expected_volume VARCHAR(100) DEFAULT NULL,
  delivery_preference VARCHAR(100) DEFAULT NULL,
  full_name VARCHAR(150) NOT NULL,
  buyer_type VARCHAR(60) NOT NULL,
  preferred_product VARCHAR(120) NOT NULL,
  payment_method VARCHAR(60) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_buyer_profiles_user (user_id),
  CONSTRAINT fk_buyer_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS consumer_profiles (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  county VARCHAR(120) NOT NULL,
  location VARCHAR(255) NOT NULL,
  favorite_products TEXT DEFAULT NULL,
  shopping_frequency VARCHAR(60) DEFAULT NULL,
  preferred_delivery VARCHAR(100) DEFAULT NULL,
  full_name VARCHAR(150) NOT NULL,
  preferred_product VARCHAR(120) NOT NULL,
  shopping_preference VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_consumer_profiles_user (user_id),
  CONSTRAINT fk_consumer_profiles_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
