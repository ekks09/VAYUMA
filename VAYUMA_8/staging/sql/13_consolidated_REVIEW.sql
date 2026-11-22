-- VAYUMA consolidated TiDB schema review and repeatable additions.
-- MANUAL ONLY: select the intended database and back it up first. Never run blindly.
-- Review the first result sets before continuing. DDL commits independently; stop on error.
-- IF NOT EXISTS avoids repeat creation, but does not repair or validate existing schemas.
-- Existing incompatible tables, columns, indexes or foreign keys require individual review.
-- users.id must be INT; identity generated columns and unique indexes from 08 are prerequisites.
-- Password recovery uses the separate review-only schema 14_password_recovery_REVIEW.sql; it is not created by this script.
USE `vayuma_db`;
SELECT DATABASE() AS selected_database, VERSION() AS tidb_version;
SELECT table_name, table_type FROM information_schema.tables
 WHERE table_schema=DATABASE() AND table_name IN ('users','ai_daily_usage','user_avatars','soil_test_records','onboarding_records','onboarding_phone_locks','onboarding_unlock_limits') ORDER BY table_name;
SELECT table_name, column_name, column_type, is_nullable, column_default, extra, generation_expression
 FROM information_schema.columns WHERE table_schema=DATABASE()
 AND table_name IN ('users','ai_daily_usage','user_avatars','soil_test_records','onboarding_records','onboarding_phone_locks','onboarding_unlock_limits') ORDER BY table_name, ordinal_position;
SELECT table_name, index_name, non_unique, seq_in_index, column_name
 FROM information_schema.statistics WHERE table_schema=DATABASE()
 AND table_name IN ('users','ai_daily_usage','user_avatars','soil_test_records','onboarding_records','onboarding_phone_locks','onboarding_unlock_limits') ORDER BY table_name,index_name,seq_in_index;
SELECT table_name, constraint_name, constraint_type FROM information_schema.table_constraints
 WHERE table_schema=DATABASE() AND table_name IN ('user_avatars','soil_test_records','ai_daily_usage','onboarding_records','onboarding_unlock_limits') ORDER BY table_name,constraint_name;
-- Legacy phone identities containing dots need review: the application accepts them,
-- but the existing generated unique index does not normalize dots. No identities are printed.
SELECT COUNT(*) AS legacy_phone_identities_with_dots FROM users WHERE phone_identity LIKE '%.%';
-- Confirm selected_database and compatibility above BEFORE continuing.
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255) NULL;
CREATE TABLE IF NOT EXISTS user_avatars (
 user_id INT NOT NULL PRIMARY KEY, jpeg_bytes MEDIUMBLOB NOT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_user_avatars_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS soil_test_records (
 id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL,
 sample_date DATE NOT NULL, location VARCHAR(160) NOT NULL, depth_cm DECIMAL(6,2) NOT NULL,
 laboratory VARCHAR(160) NOT NULL, method VARCHAR(255) NOT NULL, results_json TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_soil_records_user_date (user_id,sample_date),
 CONSTRAINT fk_soil_record_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS ai_daily_usage (
 user_id INT NOT NULL, usage_date DATE NOT NULL, request_count INT NOT NULL DEFAULT 0,
 PRIMARY KEY (user_id,usage_date),
 CONSTRAINT fk_ai_usage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- MANUAL REVIEW: back up vayuma_db, confirm tables below are absent and users.id is INT.
-- Do not enable VAYUMA_ONBOARDING_ENABLED until the migration AND code are deployed.
-- DDL may commit independently. Stop on error and inspect before retrying.
CREATE TABLE IF NOT EXISTS onboarding_phone_locks (
    phone_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY
);
CREATE TABLE IF NOT EXISTS onboarding_records (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone_identity VARCHAR(30) NOT NULL,
    county VARCHAR(100) NOT NULL,
    stakeholder_role VARCHAR(60) NOT NULL,
    activity VARCHAR(255) NOT NULL,
    farm_size DECIMAL(9,2) NULL,
    farm_size_unit VARCHAR(12) NULL,
    preferred_language VARCHAR(5) NOT NULL,
    onboarded_by_user_id INT NOT NULL,
    claim_code_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
    claim_expires_at DATETIME NULL,
    claim_attempts INT NOT NULL DEFAULT 0,
    claimed_by_user_id INT NULL,
    claimed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_onboarding_phone (phone_identity),
    KEY idx_onboarding_created (created_at, id),
    KEY idx_onboarding_staff (onboarded_by_user_id, created_at),
    UNIQUE KEY uq_onboarding_claimed_user (claimed_by_user_id),
    CONSTRAINT fk_onboarding_claimed_user FOREIGN KEY (claimed_by_user_id) REFERENCES users(id),
    CONSTRAINT fk_onboarding_staff FOREIGN KEY (onboarded_by_user_id) REFERENCES users(id)
);
-- Shared, account-scoped passphrase attempt limit; required before enabling onboarding.
CREATE TABLE IF NOT EXISTS onboarding_unlock_limits (
    user_id INT NOT NULL PRIMARY KEY,
    failures INT NOT NULL DEFAULT 0,
    window_started_at DATETIME NOT NULL,
    CONSTRAINT fk_onboarding_unlock_user FOREIGN KEY (user_id) REFERENCES users(id)
);

-- If an earlier onboarding_records table exists, add missing claim fields.
-- Inspect existing types and uniqueness first. Stop if these definitions differ.
ALTER TABLE onboarding_records ADD COLUMN IF NOT EXISTS claim_code_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL;
ALTER TABLE onboarding_records ADD COLUMN IF NOT EXISTS claim_expires_at DATETIME NULL;
ALTER TABLE onboarding_records ADD COLUMN IF NOT EXISTS claim_attempts INT NOT NULL DEFAULT 0;
ALTER TABLE onboarding_records ADD COLUMN IF NOT EXISTS claimed_by_user_id INT NULL;
ALTER TABLE onboarding_records ADD COLUMN IF NOT EXISTS claimed_at DATETIME NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_onboarding_claimed_user ON onboarding_records (claimed_by_user_id);
-- An existing table is not automatically given missing foreign keys or other indexes.
-- Check the actual resulting shapes; IF NOT EXISTS never verifies an existing definition.
SELECT table_name, column_name, column_type, is_nullable, extra
 FROM information_schema.columns WHERE table_schema=DATABASE()
 AND table_name IN ('users','ai_daily_usage','user_avatars','soil_test_records','onboarding_records','onboarding_phone_locks','onboarding_unlock_limits') ORDER BY table_name,ordinal_position;
SELECT table_name,index_name,non_unique,seq_in_index,column_name FROM information_schema.statistics
 WHERE table_schema=DATABASE() AND table_name IN ('users','ai_daily_usage','user_avatars','soil_test_records','onboarding_records','onboarding_phone_locks','onboarding_unlock_limits') ORDER BY table_name,index_name,seq_in_index;
