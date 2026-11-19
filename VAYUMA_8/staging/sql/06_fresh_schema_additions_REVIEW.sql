-- REVIEW ONLY. Do not run against TiDB or production until validated on an
-- isolated TiDB cluster, with a backup and a verified schema audit.
-- For the supplied fresh schema only. Select the intended database manually.
-- Confirm users.avatar_url and produce_listings.category_id are absent, and
-- produce_categories, saved_items, subscription_status_history do not exist.
-- DDL may commit independently; if any statement fails, STOP and inspect each
-- change before resuming. This file is not an automatic or rerunnable migration.
-- This is only a partial reconciliation, NOT a production-ready schema.
-- It does not seed plans, grant access, enable payments, or configure storage.

ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) NULL;

CREATE TABLE produce_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  description TEXT NULL,
  color_hex VARCHAR(7) NOT NULL DEFAULT '#087d42',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_produce_categories_slug (slug),
  KEY idx_produce_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE produce_listings ADD COLUMN category_id INT UNSIGNED NULL AFTER category;
CREATE INDEX idx_produce_listings_category_id ON produce_listings (category_id);
ALTER TABLE produce_listings ADD CONSTRAINT fk_produce_listings_category
  FOREIGN KEY (category_id) REFERENCES produce_categories(id)
  ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE saved_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT NOT NULL,
  produce_listing_id INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_saved_item_user_listing (user_id, produce_listing_id),
  KEY idx_saved_items_user (user_id),
  KEY idx_saved_items_listing (produce_listing_id),
  CONSTRAINT fk_saved_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_saved_items_listing FOREIGN KEY (produce_listing_id) REFERENCES produce_listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscription_status_history (
  id INT NOT NULL AUTO_INCREMENT,
  user_membership_id INT NOT NULL,
  old_status VARCHAR(30) NULL,
  new_status VARCHAR(30) NOT NULL,
  reason VARCHAR(255) NULL,
  source VARCHAR(30) NOT NULL DEFAULT 'webhook',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_subscription_status_history_membership (user_membership_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
