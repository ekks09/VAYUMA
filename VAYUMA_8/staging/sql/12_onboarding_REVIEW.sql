-- MANUAL REVIEW: back up vayuma_db, confirm tables below are absent and users.id is INT.
-- Do not enable VAYUMA_ONBOARDING_ENABLED until the migration AND code are deployed.
-- DDL may commit independently. Stop on error and inspect before retrying.
CREATE TABLE onboarding_phone_locks (
    phone_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY
);
CREATE TABLE onboarding_records (
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
CREATE TABLE onboarding_unlock_limits (
    user_id INT NOT NULL PRIMARY KEY,
    failures INT NOT NULL DEFAULT 0,
    window_started_at DATETIME NOT NULL,
    CONSTRAINT fk_onboarding_unlock_user FOREIGN KEY (user_id) REFERENCES users(id)
);
