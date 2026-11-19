-- TiDB-oriented candidate for the supplied fresh schema. MANUAL ONLY.
-- Test first on an isolated TiDB instance with backups; no real-user data.
-- Requires zero duplicate normalized phone/email values and absent columns/indexes.
-- DDL commits separately. Stop on failure; inspect each step before retrying.
-- Preserves original identity values; NULL/blank values are not indexed.
-- TiDB compatibility and integration have not yet been verified by execution.
ALTER TABLE users
  ADD COLUMN phone_identity VARCHAR(30) GENERATED ALWAYS AS (CASE WHEN phone IS NULL OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), '') = '' THEN NULL WHEN LEFT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), ''), 4) = '+254' AND CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), '')) = 13 THEN CONCAT('0', SUBSTRING(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), ''), 5)) WHEN LEFT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), ''), 3) = '254' AND CHAR_LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), '')) = 12 THEN CONCAT('0', SUBSTRING(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), ''), 4)) ELSE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(phone), ' ', ''), '-', ''), '(', ''), ')', ''), CHAR(9), '') END) VIRTUAL;

ALTER TABLE users
  ADD COLUMN email_identity VARCHAR(190) GENERATED ALWAYS AS (CASE WHEN email IS NULL OR TRIM(email) = '' THEN NULL ELSE LOWER(TRIM(email)) END) VIRTUAL;

CREATE UNIQUE INDEX uq_users_phone_identity ON users (phone_identity);
CREATE UNIQUE INDEX uq_users_email_identity ON users (email_identity);
