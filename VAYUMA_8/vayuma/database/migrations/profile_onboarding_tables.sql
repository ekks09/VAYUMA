-- Profile tables used by the active stakeholder onboarding forms.
-- Safe to apply to an existing VAYUMA account database.

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
