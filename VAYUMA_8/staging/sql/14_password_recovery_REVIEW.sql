-- MANUAL REVIEW ONLY. Back up vayuma_db before applying.
-- Confirm users.id is INT, email_identity exists and is uniquely indexed.
-- Run only after reviewing all existing tables, indexes and definitions below.
-- IF NOT EXISTS does not repair an incompatible table. No records are deleted.
USE vayuma_db;

SELECT table_name, column_name, column_type, is_nullable, extra
FROM information_schema.columns
WHERE table_schema=DATABASE() AND table_name IN ('users','password_reset_tokens','password_reset_limits')
ORDER BY table_name, ordinal_position;
SELECT table_name,index_name,non_unique,seq_in_index,column_name
FROM information_schema.statistics
WHERE table_schema=DATABASE() AND table_name IN ('users','password_reset_tokens','password_reset_limits')
ORDER BY table_name,index_name,seq_in_index;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
 user_id INT NOT NULL PRIMARY KEY,
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 email_identity VARCHAR(100) NOT NULL,
 created_at DATETIME NOT NULL,
 expires_at DATETIME NOT NULL,
 UNIQUE KEY uq_reset_token_hash (token_hash),
 KEY idx_reset_expires (expires_at),
 CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS password_reset_limits (
 scope VARCHAR(12) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 subject_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 window_started_at DATETIME NOT NULL,
 attempts INT NOT NULL DEFAULT 0,
 PRIMARY KEY (scope, subject_hash),
 KEY idx_reset_limit_window (window_started_at)
);
-- Inspect the resulting definitions and foreign keys before enabling recovery.
SHOW CREATE TABLE password_reset_tokens;
SHOW CREATE TABLE password_reset_limits;
SELECT table_name,index_name,non_unique,seq_in_index,column_name
FROM information_schema.statistics
WHERE table_schema=DATABASE() AND table_name IN ('users','password_reset_tokens','password_reset_limits')
ORDER BY table_name,index_name,seq_in_index;
