-- VAYUMA Phase 5 extension officer workspace
-- Additive migration: creates field visit and field report records.
CREATE TABLE IF NOT EXISTS extension_field_visits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  extension_officer_id INT NOT NULL,
  farmer_id INT NULL,
  farmer_name_snapshot VARCHAR(150) NOT NULL,
  county VARCHAR(100) DEFAULT NULL,
  visit_location VARCHAR(255) DEFAULT NULL,
  visit_date DATETIME NOT NULL,
  purpose VARCHAR(200) NOT NULL,
  notes TEXT DEFAULT NULL,
  status ENUM('planned','completed','cancelled') NOT NULL DEFAULT 'planned',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_extension_visit_owner (extension_officer_id),
  KEY idx_extension_visit_farmer (farmer_id),
  KEY idx_extension_visit_date (visit_date),
  KEY idx_extension_visit_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS extension_field_reports (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  extension_officer_id INT NOT NULL,
  farmer_id INT NULL,
  visit_id INT UNSIGNED NULL,
  farmer_name_snapshot VARCHAR(150) DEFAULT NULL,
  county VARCHAR(100) DEFAULT NULL,
  report_location VARCHAR(255) DEFAULT NULL,
  report_date DATE NOT NULL,
  report_type VARCHAR(100) NOT NULL,
  observations TEXT NOT NULL,
  recommendations TEXT DEFAULT NULL,
  follow_up_date DATE DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_extension_report_owner (extension_officer_id),
  KEY idx_extension_report_farmer (farmer_id),
  KEY idx_extension_report_visit (visit_id),
  KEY idx_extension_report_date (report_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
