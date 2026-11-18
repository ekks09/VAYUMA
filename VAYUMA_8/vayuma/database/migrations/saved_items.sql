-- VAYUMA Phase 3: Saved Items / Wishlist
-- Allows buyers and other stakeholders to save produce listings for later.
-- Safe additive migration: creates new table, preserves existing data.

CREATE TABLE IF NOT EXISTS saved_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NOT NULL,
  produce_listing_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_saved_item_user_listing (user_id, produce_listing_id),
  KEY idx_saved_items_user (user_id),
  KEY idx_saved_items_listing (produce_listing_id),
  CONSTRAINT fk_saved_items_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_saved_items_listing FOREIGN KEY (produce_listing_id) REFERENCES produce_listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
