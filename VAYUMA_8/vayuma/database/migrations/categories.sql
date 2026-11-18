-- VAYUMA Phase 2: Produce Category System
-- Creates produce_categories table and adds category_id FK to produce_listings.
-- Safe additive migration: preserves existing produce listings and free-text category data.
-- Run once in phpMyAdmin or via PHP against vayuma_db.

CREATE TABLE IF NOT EXISTS produce_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL,
  color_hex VARCHAR(7) NOT NULL DEFAULT '#087d42',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_produce_categories_slug (slug),
  KEY idx_produce_categories_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default agricultural categories
INSERT INTO produce_categories (name, slug, description, color_hex, sort_order) VALUES
  ('Cereals & Grains', 'cereals-grains', 'Maize, wheat, rice, sorghum, millet and other cereal crops.', '#247a3b', 1),
  ('Vegetables', 'vegetables', 'Leafy greens, tomatoes, onions, cabbage, kale and other vegetables.', '#0a8746', 2),
  ('Fruits', 'fruits', 'Mangoes, oranges, bananas, avocados, pineapples and other fruits.', '#0b9950', 3),
  ('Livestock', 'livestock', 'Cattle, goats, sheep, poultry and other livestock.', '#1b5e20', 4),
  ('Roots & Tubers', 'roots-tubers', 'Potatoes, sweet potatoes, cassava, yams and other root crops.', '#318642', 5),
  ('Legumes', 'legumes', 'Beans, peas, lentils, chickpeas and other legumes.', '#4a9e5c', 6),
  ('Oil Crops', 'oil-crops', 'Sunflower, simsim, groundnuts and other oil crops.', '#6b8e54', 7),
  ('Spices & Herbs', 'spices-herbs', 'Herbs, spices and aromatic crops.', '#5d8a48', 8),
  ('Other', 'other', 'Other agricultural produce not listed above.', '#8a8a8a', 99)
ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), color_hex=VALUES(color_hex), sort_order=VALUES(sort_order);

-- Add category_id FK to produce_listings (safe additive change)
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='produce_listings' AND COLUMN_NAME='category_id')=0,
  'ALTER TABLE produce_listings ADD COLUMN category_id INT UNSIGNED NULL AFTER category',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='produce_listings' AND INDEX_NAME='idx_produce_listings_category_id')=0,
  'CREATE INDEX idx_produce_listings_category_id ON produce_listings (category_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='produce_listings' AND CONSTRAINT_NAME='fk_produce_listings_category')=0,
  'ALTER TABLE produce_listings ADD CONSTRAINT fk_produce_listings_category FOREIGN KEY (category_id) REFERENCES produce_categories(id) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
