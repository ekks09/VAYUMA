-- VAYUMA Phase 7: Administrator verification dashboard audit trail
-- Additive migration. Does not change existing profile, payment or programme records.
CREATE TABLE IF NOT EXISTS stakeholder_verification_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  stakeholder_profile_id INT(10) UNSIGNED NOT NULL,
  target_user_id INT(11) NOT NULL,
  admin_user_id INT(11) NOT NULL,
  old_status VARCHAR(30) NOT NULL,
  new_status ENUM('pending','verified','rejected') NOT NULL,
  decision_note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_verification_log_profile (stakeholder_profile_id),
  KEY idx_verification_log_admin (admin_user_id),
  KEY idx_verification_log_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
