-- VAYUMA Phase 6: Government agricultural programmes and public events
-- Additive migration. Does not modify existing marketplace/payment/profile tables.
CREATE TABLE IF NOT EXISTS government_programmes (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  created_by INT NOT NULL,
  institution_name VARCHAR(180) NOT NULL,
  title VARCHAR(180) NOT NULL,
  programme_type VARCHAR(80) NOT NULL,
  summary VARCHAR(500) NOT NULL,
  description TEXT NOT NULL,
  eligibility TEXT NULL,
  county VARCHAR(100) NULL,
  sub_county VARCHAR(120) NULL,
  ward VARCHAR(120) NULL,
  venue VARCHAR(220) NULL,
  start_at DATETIME NULL,
  end_at DATETIME NULL,
  registration_deadline DATETIME NULL,
  visibility ENUM('public','location_targeted','restricted') NOT NULL DEFAULT 'public',
  status ENUM('draft','published','closed','cancelled') NOT NULL DEFAULT 'draft',
  public_token CHAR(48) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gov_program_token (public_token),
  KEY idx_gov_program_status_date (status,start_at),
  KEY idx_gov_program_location (county,sub_county,ward),
  KEY idx_gov_program_creator (created_by),
  CONSTRAINT fk_gov_program_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS government_programme_registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  programme_id BIGINT UNSIGNED NOT NULL,
  user_id INT NULL,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  email VARCHAR(150) NULL,
  county VARCHAR(100) NULL,
  notes VARCHAR(500) NULL,
  attendance_status ENUM('registered','attended','not_attended','cancelled') NOT NULL DEFAULT 'registered',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_gov_reg_programme (programme_id),
  KEY idx_gov_reg_phone (phone),
  CONSTRAINT fk_gov_reg_programme FOREIGN KEY (programme_id) REFERENCES government_programmes(id) ON DELETE CASCADE,
  CONSTRAINT fk_gov_reg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
