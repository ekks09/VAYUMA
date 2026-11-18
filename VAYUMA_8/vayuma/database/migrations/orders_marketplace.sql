-- Run ONCE in phpMyAdmin on vayuma_db
ALTER TABLE orders
    ADD COLUMN produce_listing_id INT NULL AFTER product_id,
    ADD COLUMN seller_id INT NULL AFTER buyer_id,
    ADD COLUMN quantity DECIMAL(10,2) NULL AFTER seller_id,
    ADD COLUMN unit VARCHAR(30) NULL AFTER quantity,
    ADD COLUMN price_per_unit DECIMAL(10,2) NULL AFTER unit,
    ADD COLUMN total_amount DECIMAL(12,2) NULL AFTER price_per_unit,
    ADD COLUMN status ENUM('pending','accepted','rejected','cancelled','completed') NOT NULL DEFAULT 'pending' AFTER total_amount;
CREATE INDEX idx_orders_produce_listing ON orders (produce_listing_id);
CREATE INDEX idx_orders_seller ON orders (seller_id);
CREATE INDEX idx_orders_buyer ON orders (buyer_id);
