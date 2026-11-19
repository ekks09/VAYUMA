-- MANUAL, NOT AUTOMATIC: first inspect the target database with the schema audit.
-- Run only if users.avatar_url is absent and after backing up the target schema.
-- Existing avatar_url columns must be checked for type and semantics separately.
ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) NULL;
