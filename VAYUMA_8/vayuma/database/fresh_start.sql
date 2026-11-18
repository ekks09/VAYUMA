-- Fresh local VAYUMA account database for XAMPP.
-- This creates the database and the account table needed for registration,
-- stakeholder selection, and login. It does not contain old accounts or
-- replace the application's complete production database.

CREATE DATABASE IF NOT EXISTS vayuma_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE vayuma_db;

CREATE TABLE IF NOT EXISTS users (
  id INT NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(190) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(60) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_phone (phone),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS farmer_profiles (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  farm_name VARCHAR(150) NOT NULL,
  county VARCHAR(120) NOT NULL,
  sub_county VARCHAR(120) NOT NULL,
  farm_location VARCHAR(180) NOT NULL,
  farm_size VARCHAR(40) NOT NULL,
  farm_size_unit VARCHAR(40) NOT NULL,
  farming_experience VARCHAR(60) NOT NULL,
  main_crops VARCHAR(255) NOT NULL,
  soil_type VARCHAR(100) NOT NULL,
  irrigation VARCHAR(100) NOT NULL,
  secondary_crop VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_farmer_profiles_user (user_id),
  CONSTRAINT fk_farmer_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transport_profiles (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  business_name VARCHAR(150) NOT NULL,
  county VARCHAR(120) NOT NULL,
  vehicle_type VARCHAR(100) NOT NULL,
  vehicle_capacity VARCHAR(100) NOT NULL,
  registration_number VARCHAR(100) DEFAULT NULL,
  operating_area VARCHAR(255) NOT NULL,
  service_type VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  vehicle_registration VARCHAR(100) DEFAULT NULL,
  load_capacity VARCHAR(100) DEFAULT NULL,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_transport_profiles_user (user_id),
  KEY idx_transport_profiles_county (county),
  CONSTRAINT fk_transport_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS researcher_profiles (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  institution VARCHAR(180) NOT NULL,
  research_area VARCHAR(180) NOT NULL,
  qualification VARCHAR(180) NOT NULL,
  location VARCHAR(180) NOT NULL,
  bio TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_researcher_profiles_user (user_id),
  CONSTRAINT fk_researcher_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS extension_profiles (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  organization VARCHAR(180) NOT NULL,
  county VARCHAR(120) NOT NULL,
  sub_county VARCHAR(120) DEFAULT NULL,
  specialization VARCHAR(180) NOT NULL,
  years_experience INT NOT NULL DEFAULT 0,
  coverage_area VARCHAR(180) DEFAULT NULL,
  qualification VARCHAR(180) NOT NULL,
  location VARCHAR(180) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_extension_profiles_user (user_id),
  CONSTRAINT fk_extension_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cooperative_profiles (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  cooperative_name VARCHAR(180) NOT NULL,
  registration_number VARCHAR(100) DEFAULT NULL,
  cooperative_type VARCHAR(120) NOT NULL,
  location VARCHAR(180) NOT NULL,
  member_count INT NOT NULL DEFAULT 0,
  contact_person VARCHAR(150) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cooperative_profiles_user (user_id),
  CONSTRAINT fk_cooperative_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_profiles (
  id INT NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  institution_name VARCHAR(180) NOT NULL,
  institution_type VARCHAR(120) NOT NULL,
  registration_number VARCHAR(100) DEFAULT NULL,
  location VARCHAR(180) NOT NULL,
  services TEXT DEFAULT NULL,
  contact_person VARCHAR(150) NOT NULL,
  description TEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_financial_profiles_user (user_id),
  CONSTRAINT fk_financial_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stakeholder_profiles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  role VARCHAR(100) NOT NULL,
  organization_name VARCHAR(180) NOT NULL,
  organization_type VARCHAR(120) DEFAULT NULL,
  county VARCHAR(100) NOT NULL,
  location VARCHAR(180) DEFAULT NULL,
  contact_phone VARCHAR(30) DEFAULT NULL,
  service_focus VARCHAR(180) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  license_number VARCHAR(100) DEFAULT NULL,
  registration_number VARCHAR(100) DEFAULT NULL,
  capacity VARCHAR(180) DEFAULT NULL,
  service_area VARCHAR(180) DEFAULT NULL,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  profile_completed TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_stakeholder_user (user_id),
  KEY idx_stakeholder_role (role),
  KEY idx_stakeholder_county (county),
  CONSTRAINT fk_stakeholder_profile_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
