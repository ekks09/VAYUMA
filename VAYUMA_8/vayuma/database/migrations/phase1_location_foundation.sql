-- VAYUMA Phase 1: common stakeholder location foundation
-- Compatible with MariaDB 10.4 / MySQL 8. Take a DB backup before running.
CREATE TABLE IF NOT EXISTS administrative_units (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id INT UNSIGNED DEFAULT NULL,
  unit_type ENUM('county','sub_county','ward') NOT NULL,
  name VARCHAR(120) NOT NULL,
  normalized_name VARCHAR(120) NOT NULL,
  source_name VARCHAR(120) DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_admin_parent_type_name (parent_id, unit_type, normalized_name),
  KEY idx_admin_parent_type (parent_id, unit_type),
  CONSTRAINT fk_admin_parent FOREIGN KEY (parent_id) REFERENCES administrative_units(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_locations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  county_id INT UNSIGNED NOT NULL,
  sub_county_id INT UNSIGNED NOT NULL,
  ward_id INT UNSIGNED NOT NULL,
  village_estate VARCHAR(150) DEFAULT NULL,
  address_note VARCHAR(255) DEFAULT NULL,
  latitude DECIMAL(10,7) DEFAULT NULL,
  longitude DECIMAL(10,7) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_location_user (user_id),
  KEY idx_user_location_county (county_id),
  KEY idx_user_location_subcounty (sub_county_id),
  KEY idx_user_location_ward (ward_id),
  CONSTRAINT fk_user_locations_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_locations_county FOREIGN KEY (county_id) REFERENCES administrative_units(id),
  CONSTRAINT fk_user_locations_subcounty FOREIGN KEY (sub_county_id) REFERENCES administrative_units(id),
  CONSTRAINT fk_user_locations_ward FOREIGN KEY (ward_id) REFERENCES administrative_units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_service_areas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  administrative_unit_id INT UNSIGNED NOT NULL,
  coverage_note VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_user_service_area (user_id, administrative_unit_id),
  KEY idx_service_area_unit (administrative_unit_id),
  CONSTRAINT fk_service_area_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_service_area_unit FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS official_jurisdictions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  administrative_unit_id INT UNSIGNED NOT NULL,
  assignment_title VARCHAR(150) DEFAULT NULL,
  institution VARCHAR(180) DEFAULT NULL,
  verification_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  verified_by INT(11) DEFAULT NULL,
  verified_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_official_jurisdiction (user_id, administrative_unit_id),
  KEY idx_jurisdiction_unit (administrative_unit_id),
  KEY idx_jurisdiction_status (verification_status),
  CONSTRAINT fk_jurisdiction_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_jurisdiction_unit FOREIGN KEY (administrative_unit_id) REFERENCES administrative_units(id),
  CONSTRAINT fk_jurisdiction_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed county units from VAYUMA's existing config/kenya_counties.php.
-- Review the county seed list and load verified sub-county/ward data before enforcing location completion.
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Baringo','baringo','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='baringo');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Bomet','bomet','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='bomet');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Bungoma','bungoma','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='bungoma');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Busia','busia','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='busia');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Elgeyo-Marakwet','elgeyo-marakwet','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='elgeyo-marakwet');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Embu','embu','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='embu');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Garissa','garissa','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='garissa');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Homa Bay','homa bay','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='homa bay');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Isiolo','isiolo','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='isiolo');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kajiado','kajiado','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kajiado');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kakamega','kakamega','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kakamega');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kericho','kericho','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kericho');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kiambu','kiambu','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kiambu');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kilifi','kilifi','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kilifi');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kirinyaga','kirinyaga','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kirinyaga');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kisii','kisii','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kisii');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kisumu','kisumu','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kisumu');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kitui','kitui','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kitui');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Kwale','kwale','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='kwale');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Laikipia','laikipia','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='laikipia');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Lamu','lamu','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='lamu');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Machakos','machakos','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='machakos');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Makueni','makueni','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='makueni');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Mandera','mandera','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='mandera');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Meru','meru','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='meru');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Migori','migori','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='migori');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Mombasa','mombasa','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='mombasa');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Muranga','muranga','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='muranga');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Nairobi','nairobi','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='nairobi');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Nakuru','nakuru','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='nakuru');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Nandi','nandi','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='nandi');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Narok','narok','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='narok');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Nyamira','nyamira','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='nyamira');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Nyandarua','nyandarua','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='nyandarua');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Nyeri','nyeri','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='nyeri');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Samburu','samburu','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='samburu');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Siaya','siaya','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='siaya');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Taita-Taveta','taita-taveta','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='taita-taveta');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Tana River','tana river','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='tana river');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Tharaka-Nithi','tharaka-nithi','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='tharaka-nithi');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Trans Nzoia','trans nzoia','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='trans nzoia');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Turkana','turkana','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='turkana');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Uasin Gishu','uasin gishu','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='uasin gishu');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Vihiga','vihiga','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='vihiga');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Wajir','wajir','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='wajir');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','West Pokot','west pokot','VAYUMA county config' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='west pokot');
INSERT INTO administrative_units (parent_id,unit_type,name,normalized_name,source_name) SELECT NULL,'county','Marsabit','marsabit','Kenya 47-county completion' WHERE NOT EXISTS (SELECT 1 FROM administrative_units WHERE parent_id IS NULL AND unit_type='county' AND normalized_name='marsabit');
