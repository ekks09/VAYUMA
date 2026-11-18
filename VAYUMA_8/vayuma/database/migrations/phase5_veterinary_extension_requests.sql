-- VAYUMA Phase 5: separate veterinary and agricultural-extension request workflows.
-- Additive only. Does not alter or delete existing stakeholder requests.
CREATE TABLE IF NOT EXISTS veterinary_service_requests (
 id INT NOT NULL AUTO_INCREMENT,
 requester_id INT NOT NULL,
 provider_id INT NOT NULL,
 animal_species VARCHAR(120) NOT NULL,
 urgency ENUM('routine','soon','urgent','emergency') NOT NULL DEFAULT 'routine',
 details TEXT NOT NULL,
 county VARCHAR(100) NOT NULL,
 locality VARCHAR(180) DEFAULT NULL,
 preferred_date DATE DEFAULT NULL,
 contact_phone VARCHAR(30) DEFAULT NULL,
 status ENUM('pending','accepted','declined','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id), KEY idx_vet_requester (requester_id), KEY idx_vet_provider_status (provider_id,status),
 CONSTRAINT fk_vet_req_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_vet_req_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS extension_support_requests (
 id INT NOT NULL AUTO_INCREMENT,
 requester_id INT NOT NULL,
 provider_id INT NOT NULL,
 topic VARCHAR(150) NOT NULL,
 support_mode ENUM('remote','farm_visit','office_visit','group_training') NOT NULL DEFAULT 'remote',
 farm_activity VARCHAR(180) DEFAULT NULL,
 details TEXT NOT NULL,
 county VARCHAR(100) NOT NULL,
 locality VARCHAR(180) DEFAULT NULL,
 preferred_date DATE DEFAULT NULL,
 contact_phone VARCHAR(30) DEFAULT NULL,
 status ENUM('pending','accepted','declined','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY (id), KEY idx_ext_requester (requester_id), KEY idx_ext_provider_status (provider_id,status),
 CONSTRAINT fk_ext_req_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_ext_req_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
