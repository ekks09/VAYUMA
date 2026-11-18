-- Run once in the vayuma_db database.
CREATE TABLE IF NOT EXISTS stakeholder_offers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    provider_id INT(11) NOT NULL,
    provider_role VARCHAR(100) NOT NULL,
    title VARCHAR(160) NOT NULL,
    offer_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    county VARCHAR(100) NOT NULL,
    price_note VARCHAR(100) DEFAULT NULL,
    availability VARCHAR(100) DEFAULT NULL,
    status ENUM('active','paused','closed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY offers_provider_index (provider_id),
    KEY offers_status_county_index (status, county),
    CONSTRAINT fk_stakeholder_offers_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS stakeholder_offer_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    offer_id INT UNSIGNED NOT NULL,
    requester_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    contact_phone VARCHAR(20) DEFAULT NULL,
    status ENUM('pending','accepted','declined','completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_offer_requester (offer_id, requester_id),
    KEY requests_requester_index (requester_id),
    CONSTRAINT fk_stakeholder_requests_offer FOREIGN KEY (offer_id) REFERENCES stakeholder_offers(id) ON DELETE CASCADE,
    CONSTRAINT fk_stakeholder_requests_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
