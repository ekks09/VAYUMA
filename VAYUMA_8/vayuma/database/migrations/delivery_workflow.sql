-- VAYUMA delivery workflow foundation.
CREATE TABLE IF NOT EXISTS delivery_requests (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT,
 order_id INT NOT NULL,
 requester_id INT NOT NULL,
 transport_provider_id INT NULL,
 pickup_location VARCHAR(255) NOT NULL,
 delivery_location VARCHAR(255) NOT NULL,
 requested_date DATE DEFAULT NULL,
 notes TEXT DEFAULT NULL,
 status ENUM('requested','accepted','in_transit','delivered','cancelled') NOT NULL DEFAULT 'requested',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id),
 UNIQUE KEY uq_delivery_order (order_id),
 KEY idx_delivery_requester (requester_id),
 KEY idx_delivery_provider (transport_provider_id),
 KEY idx_delivery_status (status),
 CONSTRAINT fk_delivery_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
 CONSTRAINT fk_delivery_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_delivery_provider FOREIGN KEY (transport_provider_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
