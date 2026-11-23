-- TiDB: apply after subscription_trial_paybill.sql and the existing onboarding
-- and farmer profile migrations. Back up and inspect the target schema first.
-- DDL commits separately. IF NOT EXISTS does not repair conflicting columns.
ALTER TABLE onboarding_records ADD COLUMN IF NOT EXISTS farmer_id_ciphertext VARCHAR(255) NULL;
ALTER TABLE onboarding_records ADD COLUMN IF NOT EXISTS farmer_location VARCHAR(255) NULL;
ALTER TABLE farmer_profiles ADD COLUMN IF NOT EXISTS farmer_id_ciphertext VARCHAR(255) NULL;
ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS dashboard_free_month CHAR(7) NULL;
ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS dashboard_free_until_utc DATETIME NULL;
