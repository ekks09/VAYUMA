-- =====================================================================
-- Vayuma schema, cleaned for TiDB (MySQL-compatible)
-- Paste into the TiDB Cloud SQL Editor and run.
-- Tables are ordered so every parent table is created before the
-- tables that reference it (no FK errors, no need to disable checks).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `vayuma_db`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vayuma_db`;

-- =====================================================================
-- 1. CORE / REFERENCE TABLES
-- =====================================================================

CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100),
  `phone` VARCHAR(20),
  `email` VARCHAR(100),
  `password` VARCHAR(255),
  `role` VARCHAR(100),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `administrative_units` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED,
  `unit_type` ENUM('county','sub_county','ward') NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `normalized_name` VARCHAR(120) NOT NULL,
  `source_name` VARCHAR(120),
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_parent_type_name` (`parent_id`,`unit_type`,`normalized_name`),
  KEY `idx_admin_parent_type` (`parent_id`,`unit_type`),
  CONSTRAINT `fk_admin_parent` FOREIGN KEY (`parent_id`) REFERENCES `administrative_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. SUBSCRIPTIONS
-- =====================================================================

CREATE TABLE `subscription_plans` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `role_key` VARCHAR(60) NOT NULL,
  `stakeholder_name` VARCHAR(120) NOT NULL,
  `monthly_price` DECIMAL(12,2),
  `plan_type` ENUM('paid','free','negotiated') NOT NULL DEFAULT 'paid',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscription_plan_role` (`role_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_subscriptions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `plan_id` INT NOT NULL,
  `status` ENUM('active','payment_due','expired','free_account','negotiated_plan') NOT NULL DEFAULT 'payment_due',
  `starts_at` DATETIME,
  `expires_at` DATETIME,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_subscription` (`user_id`),
  KEY `idx_subscription_status_expiry` (`status`,`expires_at`),
  KEY `fk_user_subscriptions_plan` (`plan_id`),
  CONSTRAINT `fk_user_subscriptions_plan` FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`),
  CONSTRAINT `fk_user_subscriptions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscription_payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `subscription_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('mpesa') NOT NULL DEFAULT 'mpesa',
  `phone_number` VARCHAR(30),
  `merchant_request_id` VARCHAR(100),
  `checkout_request_id` VARCHAR(100),
  `transaction_reference` VARCHAR(100),
  `mpesa_receipt_number` VARCHAR(100),
  `status` ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `result_code` INT,
  `result_description` VARCHAR(255),
  `callback_received_at` DATETIME,
  `paid_at` DATETIME,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_subscription_checkout` (`checkout_request_id`),
  KEY `idx_subscription_payment_subscription` (`subscription_id`),
  KEY `fk_subscription_payments_user` (`user_id`),
  CONSTRAINT `fk_subscription_payments_subscription` FOREIGN KEY (`subscription_id`) REFERENCES `user_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subscription_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. ROLE PROFILES
-- =====================================================================

CREATE TABLE `buyer_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `business_name` VARCHAR(150),
  `business_type` VARCHAR(100),
  `county` VARCHAR(100),
  `location` VARCHAR(255),
  `products_interested` TEXT,
  `expected_volume` VARCHAR(100),
  `delivery_preference` VARCHAR(100),
  `verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `full_name` VARCHAR(150),
  `buyer_type` VARCHAR(100),
  `preferred_product` VARCHAR(100),
  `payment_method` VARCHAR(100),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_buyer_user` (`user_id`),
  CONSTRAINT `fk_buyer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `consumer_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `county` VARCHAR(100),
  `location` VARCHAR(255),
  `favorite_products` TEXT,
  `shopping_frequency` VARCHAR(100),
  `preferred_delivery` VARCHAR(100),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `full_name` VARCHAR(150),
  `preferred_product` VARCHAR(100),
  `shopping_preference` VARCHAR(100),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_consumer_user` (`user_id`),
  CONSTRAINT `fk_consumer_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cooperative_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `cooperative_name` VARCHAR(200) NOT NULL,
  `registration_number` VARCHAR(100),
  `county` VARCHAR(100),
  `location` VARCHAR(255),
  `member_count` INT,
  `agricultural_focus` VARCHAR(200),
  `main_products` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cooperative_type` VARCHAR(100),
  `contact_person` VARCHAR(150),
  `description` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cooperative_user` (`user_id`),
  CONSTRAINT `fk_cooperative_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `extension_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `organization` VARCHAR(200) NOT NULL,
  `county` VARCHAR(100),
  `sub_county` VARCHAR(100),
  `specialization` VARCHAR(200),
  `years_experience` INT,
  `coverage_area` VARCHAR(255),
  `qualification` VARCHAR(200),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `location` VARCHAR(255),
  `bio` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_extension_user` (`user_id`),
  CONSTRAINT `fk_extension_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `farmer_profile` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT,
  `farm_size` VARCHAR(50),
  `crop_type` VARCHAR(100),
  `location` VARCHAR(100),
  `soil_type` VARCHAR(50),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `farmer_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT,
  `farm_name` VARCHAR(100),
  `county` VARCHAR(100),
  `sub_county` VARCHAR(100),
  `farm_location` VARCHAR(255),
  `farm_size` VARCHAR(50),
  `farm_size_unit` VARCHAR(50),
  `farming_experience` VARCHAR(100),
  `main_crops` VARCHAR(255),
  `soil_type` VARCHAR(100),
  `irrigation` VARCHAR(100),
  `secondary_crop` VARCHAR(50),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `financial_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `institution_name` VARCHAR(200) NOT NULL,
  `institution_type` VARCHAR(100),
  `county` VARCHAR(100),
  `branch_location` VARCHAR(255),
  `agricultural_services` TEXT,
  `financing_types` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registration_number` VARCHAR(100),
  `location` VARCHAR(255),
  `services` TEXT,
  `contact_person` VARCHAR(150),
  `description` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_financial_user` (`user_id`),
  CONSTRAINT `fk_financial_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `researcher_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `institution` VARCHAR(200) NOT NULL,
  `specialization` VARCHAR(200),
  `county` VARCHAR(100),
  `research_area` TEXT,
  `qualification` VARCHAR(200),
  `bio` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `location` VARCHAR(255),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_researcher_user` (`user_id`),
  CONSTRAINT `fk_researcher_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `transport_profiles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `business_name` VARCHAR(150),
  `county` VARCHAR(100),
  `vehicle_type` VARCHAR(100) NOT NULL,
  `vehicle_capacity` VARCHAR(100),
  `registration_number` VARCHAR(100),
  `operating_area` VARCHAR(255),
  `service_type` VARCHAR(100),
  `verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `phone` VARCHAR(30),
  `vehicle_registration` VARCHAR(50),
  `load_capacity` VARCHAR(50),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_transport_user` (`user_id`),
  CONSTRAINT `fk_transport_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. STAKEHOLDERS, OFFERS & VERIFICATION
-- =====================================================================

CREATE TABLE `stakeholder_profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `role` VARCHAR(100) NOT NULL,
  `organization_name` VARCHAR(150) NOT NULL,
  `organization_type` VARCHAR(120),
  `county` VARCHAR(100) NOT NULL,
  `location` VARCHAR(150),
  `contact_phone` VARCHAR(20),
  `service_focus` VARCHAR(255),
  `description` TEXT,
  `profile_completed` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `license_number` VARCHAR(100),
  `registration_number` VARCHAR(100),
  `capacity` VARCHAR(180),
  `service_area` VARCHAR(180),
  `verification_status` ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stakeholder_profile_user` (`user_id`),
  CONSTRAINT `fk_stakeholder_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stakeholder_verification_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stakeholder_profile_id` INT UNSIGNED NOT NULL,
  `target_user_id` INT NOT NULL,
  `admin_user_id` INT NOT NULL,
  `old_status` VARCHAR(30) NOT NULL,
  `new_status` ENUM('pending','verified','rejected') NOT NULL,
  `decision_note` VARCHAR(500),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_verification_log_profile` (`stakeholder_profile_id`),
  KEY `idx_verification_log_admin` (`admin_user_id`),
  KEY `idx_verification_log_date` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stakeholder_offers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT NOT NULL,
  `provider_role` VARCHAR(100) NOT NULL,
  `title` VARCHAR(160) NOT NULL,
  `offer_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `county` VARCHAR(100) NOT NULL,
  `price_note` VARCHAR(100),
  `availability` VARCHAR(100),
  `status` ENUM('active','paused','closed') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `offers_provider_index` (`provider_id`),
  KEY `offers_status_county_index` (`status`,`county`),
  CONSTRAINT `fk_stakeholder_offers_provider` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stakeholder_offer_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `offer_id` INT UNSIGNED NOT NULL,
  `requester_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `contact_phone` VARCHAR(20),
  `status` ENUM('pending','accepted','declined','completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_offer_requester` (`offer_id`,`requester_id`),
  KEY `requests_requester_index` (`requester_id`),
  CONSTRAINT `fk_stakeholder_requests_offer` FOREIGN KEY (`offer_id`) REFERENCES `stakeholder_offers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stakeholder_requests_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. USER LOCATION, PREFERENCES & SERVICE AREAS
-- =====================================================================

CREATE TABLE `user_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `county_id` INT UNSIGNED NOT NULL,
  `sub_county_id` INT UNSIGNED NOT NULL,
  `ward_id` INT UNSIGNED NOT NULL,
  `village_estate` VARCHAR(150),
  `address_note` VARCHAR(255),
  `latitude` DECIMAL(10,7),
  `longitude` DECIMAL(10,7),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_location_user` (`user_id`),
  KEY `idx_user_location_county` (`county_id`),
  KEY `idx_user_location_subcounty` (`sub_county_id`),
  KEY `idx_user_location_ward` (`ward_id`),
  CONSTRAINT `fk_user_locations_county` FOREIGN KEY (`county_id`) REFERENCES `administrative_units` (`id`),
  CONSTRAINT `fk_user_locations_subcounty` FOREIGN KEY (`sub_county_id`) REFERENCES `administrative_units` (`id`),
  CONSTRAINT `fk_user_locations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_locations_ward` FOREIGN KEY (`ward_id`) REFERENCES `administrative_units` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `language` ENUM('en','sw') NOT NULL DEFAULT 'en',
  `voice_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `weather_alerts` TINYINT(1) NOT NULL DEFAULT 1,
  `market_alerts` TINYINT(1) NOT NULL DEFAULT 1,
  `crop_alerts` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_preferences_user` (`user_id`),
  CONSTRAINT `fk_user_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_service_areas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `administrative_unit_id` INT UNSIGNED NOT NULL,
  `coverage_note` VARCHAR(255),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_service_area` (`user_id`,`administrative_unit_id`),
  KEY `idx_service_area_unit` (`administrative_unit_id`),
  CONSTRAINT `fk_service_area_unit` FOREIGN KEY (`administrative_unit_id`) REFERENCES `administrative_units` (`id`),
  CONSTRAINT `fk_service_area_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `official_jurisdictions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `administrative_unit_id` INT UNSIGNED NOT NULL,
  `assignment_title` VARCHAR(150),
  `institution` VARCHAR(180),
  `verification_status` ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` INT,
  `verified_at` DATETIME,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_official_jurisdiction` (`user_id`,`administrative_unit_id`),
  KEY `idx_jurisdiction_unit` (`administrative_unit_id`),
  KEY `idx_jurisdiction_status` (`verification_status`),
  KEY `fk_jurisdiction_verifier` (`verified_by`),
  CONSTRAINT `fk_jurisdiction_unit` FOREIGN KEY (`administrative_unit_id`) REFERENCES `administrative_units` (`id`),
  CONSTRAINT `fk_jurisdiction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jurisdiction_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. MARKETPLACE: LISTINGS, ORDERS, PAYMENTS, DELIVERY
-- =====================================================================

CREATE TABLE `produce_listings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `farmer_id` INT NOT NULL,
  `produce_name` VARCHAR(100) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'kg',
  `price_per_unit` DECIMAL(10,2) NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `harvest_date` DATE,
  `description` TEXT,
  `image` VARCHAR(255),
  `status` ENUM('available','sold','removed') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT,
  `product_name` VARCHAR(100),
  `price` DECIMAL(10,2),
  `quantity` VARCHAR(50),
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `image` VARCHAR(255),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `produce_listing_id` INT NOT NULL,
  `buyer_id` INT NOT NULL,
  `seller_id` INT,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit` VARCHAR(30),
  `price_per_unit` DECIMAL(10,2),
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending','accepted','rejected','cancelled','paid','completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_orders_produce_listing` (`produce_listing_id`),
  KEY `idx_orders_seller` (`seller_id`),
  KEY `idx_orders_buyer` (`buyer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `payer_id` INT NOT NULL,
  `seller_id` INT,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_method` ENUM('demo','mpesa','bank') NOT NULL DEFAULT 'demo',
  `transaction_reference` VARCHAR(100),
  `merchant_request_id` VARCHAR(100),
  `checkout_request_id` VARCHAR(100),
  `mpesa_receipt_number` VARCHAR(100),
  `result_code` INT,
  `result_description` VARCHAR(255),
  `callback_received_at` TIMESTAMP NULL DEFAULT NULL,
  `phone_number` VARCHAR(30),
  `status` ENUM('pending','paid','failed','cancelled') NOT NULL DEFAULT 'pending',
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bank_transaction_reference` VARCHAR(150),
  `bank_proof_file` VARCHAR(255),
  `bank_verified_by` INT,
  `bank_verified_at` DATETIME,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_payment` (`order_id`),
  KEY `idx_payment_payer` (`payer_id`),
  KEY `idx_payment_seller` (`seller_id`),
  KEY `idx_payment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `delivery_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `requester_id` INT NOT NULL,
  `transport_provider_id` INT,
  `pickup_location` VARCHAR(255) NOT NULL,
  `delivery_location` VARCHAR(255) NOT NULL,
  `requested_date` DATE,
  `notes` TEXT,
  `status` ENUM('requested','accepted','in_transit','delivered','cancelled') NOT NULL DEFAULT 'requested',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_delivery_order` (`order_id`),
  KEY `idx_delivery_requester` (`requester_id`),
  KEY `idx_delivery_provider` (`transport_provider_id`),
  KEY `idx_delivery_status` (`status`),
  CONSTRAINT `fk_delivery_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_delivery_provider` FOREIGN KEY (`transport_provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_delivery_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. EXTENSION & VETERINARY SERVICES
-- =====================================================================

CREATE TABLE `extension_field_visits` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `extension_officer_id` INT NOT NULL,
  `farmer_id` INT,
  `farmer_name_snapshot` VARCHAR(150) NOT NULL,
  `county` VARCHAR(100),
  `visit_location` VARCHAR(255),
  `visit_date` DATETIME NOT NULL,
  `purpose` VARCHAR(200) NOT NULL,
  `notes` TEXT,
  `status` ENUM('planned','completed','cancelled') NOT NULL DEFAULT 'planned',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_extension_visit_owner` (`extension_officer_id`),
  KEY `idx_extension_visit_farmer` (`farmer_id`),
  KEY `idx_extension_visit_date` (`visit_date`),
  KEY `idx_extension_visit_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `extension_field_reports` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `extension_officer_id` INT NOT NULL,
  `farmer_id` INT,
  `visit_id` INT UNSIGNED,
  `farmer_name_snapshot` VARCHAR(150),
  `county` VARCHAR(100),
  `report_location` VARCHAR(255),
  `report_date` DATE NOT NULL,
  `report_type` VARCHAR(100) NOT NULL,
  `observations` TEXT NOT NULL,
  `recommendations` TEXT,
  `follow_up_date` DATE,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_extension_report_owner` (`extension_officer_id`),
  KEY `idx_extension_report_farmer` (`farmer_id`),
  KEY `idx_extension_report_visit` (`visit_id`),
  KEY `idx_extension_report_date` (`report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `extension_support_requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `requester_id` INT NOT NULL,
  `provider_id` INT NOT NULL,
  `topic` VARCHAR(150) NOT NULL,
  `support_mode` ENUM('remote','farm_visit','office_visit','group_training') NOT NULL DEFAULT 'remote',
  `farm_activity` VARCHAR(180),
  `details` TEXT NOT NULL,
  `county` VARCHAR(100) NOT NULL,
  `locality` VARCHAR(180),
  `preferred_date` DATE,
  `contact_phone` VARCHAR(30),
  `status` ENUM('pending','accepted','declined','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ext_requester` (`requester_id`),
  KEY `idx_ext_provider_status` (`provider_id`,`status`),
  CONSTRAINT `fk_ext_req_provider` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ext_req_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `veterinary_service_requests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `requester_id` INT NOT NULL,
  `provider_id` INT NOT NULL,
  `animal_species` VARCHAR(120) NOT NULL,
  `urgency` ENUM('routine','soon','urgent','emergency') NOT NULL DEFAULT 'routine',
  `details` TEXT NOT NULL,
  `county` VARCHAR(100) NOT NULL,
  `locality` VARCHAR(180),
  `preferred_date` DATE,
  `contact_phone` VARCHAR(30),
  `status` ENUM('pending','accepted','declined','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vet_requester` (`requester_id`),
  KEY `idx_vet_provider_status` (`provider_id`,`status`),
  CONSTRAINT `fk_vet_req_provider` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vet_req_requester` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. GOVERNMENT PROGRAMMES
-- =====================================================================

CREATE TABLE `government_programmes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `created_by` INT NOT NULL,
  `institution_name` VARCHAR(180) NOT NULL,
  `title` VARCHAR(180) NOT NULL,
  `programme_type` VARCHAR(80) NOT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `description` TEXT NOT NULL,
  `eligibility` TEXT,
  `county` VARCHAR(100),
  `sub_county` VARCHAR(120),
  `ward` VARCHAR(120),
  `venue` VARCHAR(220),
  `start_at` DATETIME,
  `end_at` DATETIME,
  `registration_deadline` DATETIME,
  `visibility` ENUM('public','location_targeted','restricted') NOT NULL DEFAULT 'public',
  `status` ENUM('draft','published','closed','cancelled') NOT NULL DEFAULT 'draft',
  `public_token` CHAR(48) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gov_program_token` (`public_token`),
  KEY `idx_gov_program_status_date` (`status`,`start_at`),
  KEY `idx_gov_program_location` (`county`,`sub_county`,`ward`),
  KEY `idx_gov_program_creator` (`created_by`),
  CONSTRAINT `fk_gov_program_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `government_programme_registrations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `programme_id` BIGINT UNSIGNED NOT NULL,
  `user_id` INT,
  `full_name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(150),
  `county` VARCHAR(100),
  `notes` VARCHAR(500),
  `attendance_status` ENUM('registered','attended','not_attended','cancelled') NOT NULL DEFAULT 'registered',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gov_reg_programme` (`programme_id`),
  KEY `idx_gov_reg_phone` (`phone`),
  KEY `fk_gov_reg_user` (`user_id`),
  CONSTRAINT `fk_gov_reg_programme` FOREIGN KEY (`programme_id`) REFERENCES `government_programmes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gov_reg_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 9. CLIMATE, SOIL & MESSAGING (SMS / USSD)
-- =====================================================================

CREATE TABLE `climate_data` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `location` VARCHAR(255) NOT NULL,
  `latitude` DECIMAL(10,7),
  `longitude` DECIMAL(10,7),
  `temperature` DECIMAL(5,2),
  `humidity` DECIMAL(5,2),
  `rainfall` DECIMAL(8,2),
  `wind_speed` DECIMAL(8,2),
  `weather_condition` VARCHAR(100),
  `forecast` TEXT,
  `farming_advice` TEXT,
  `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `soil_analysis` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `soil_type` VARCHAR(100),
  `ph_level` DECIMAL(4,2),
  `nitrogen` DECIMAL(8,2),
  `phosphorus` DECIMAL(8,2),
  `potassium` DECIMAL(8,2),
  `organic_matter` DECIMAL(8,2),
  `moisture` DECIMAL(8,2),
  `analysis_result` TEXT,
  `recommendations` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sms_messages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone_number` VARCHAR(20) NOT NULL,
  `message_text` TEXT NOT NULL,
  `message_type` VARCHAR(50) NOT NULL DEFAULT 'general',
  `status` VARCHAR(30) NOT NULL DEFAULT 'queued',
  `provider_message_id` VARCHAR(120),
  `provider_response` MEDIUMTEXT,
  `error_message` TEXT,
  `sent_at` DATETIME,
  `delivered_at` DATETIME,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sms_phone_created` (`phone_number`,`created_at`),
  KEY `idx_sms_provider_message` (`provider_message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sms_subscriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone_number` VARCHAR(20) NOT NULL,
  `topic` VARCHAR(50) NOT NULL,
  `category` VARCHAR(100),
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sms_subscription` (`phone_number`,`topic`,`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ussd_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(100) NOT NULL,
  `phone_number` VARCHAR(20) NOT NULL,
  `service_code` VARCHAR(50),
  `request_text` VARCHAR(255) NOT NULL,
  `response_text` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ussd_session` (`session_id`),
  KEY `idx_ussd_phone_created` (`phone_number`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;