-- DO NOT RUN ON TiDB: TiDB cannot add STORED generated columns with ALTER
-- TABLE and does not support NULLIF in generated columns. This is a MariaDB
-- reference only; a TiDB-specific migration must be designed and tested.
-- MANUAL ONLY. Backup and run 04_identity_audit_READ_ONLY.sql first.
-- Resolve all duplicate identity groups with an approved human-reviewed plan.
-- Treat NULL and empty/whitespace-only email or phone as absent; they are not indexed.
-- Generated keys enforce the same phone formatting and Kenyan prefix equivalence
-- reported by the audit. Original login values are NOT rewritten.
-- Apply only if identity columns/indexes do not already exist; test exact TiDB
-- version/collation and DDL privileges first. This does not enforce phone presence.
ALTER TABLE users
  ADD COLUMN phone_identity VARCHAR(30) GENERATED ALWAYS AS (CASE WHEN phone IS NULL OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), '') = '' THEN NULL WHEN LEFT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), ''), 4) = '+254' AND CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), '')) = 13 THEN CONCAT('0', SUBSTRING(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), ''), 5)) WHEN LEFT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), ''), 3) = '254' AND CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), '')) = 12 THEN CONCAT('0', SUBSTRING(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), ''), 4)) ELSE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), '') END) STORED,
  ADD COLUMN email_identity VARCHAR(190) GENERATED ALWAYS AS (LOWER(NULLIF(TRIM(email), ''))) STORED,
  ADD UNIQUE KEY uq_users_phone_identity (phone_identity),
  ADD UNIQUE KEY uq_users_email_identity (email_identity);
