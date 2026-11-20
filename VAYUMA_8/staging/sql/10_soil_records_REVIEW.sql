-- MANUAL REVIEW ONLY: verify absence and back up the target database first.
-- User-entered lab reports are not independently verified by VAYUMA.
CREATE TABLE soil_test_records (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sample_date DATE NOT NULL,
    location VARCHAR(160) NOT NULL,
    depth_cm DECIMAL(6,2) NOT NULL,
    laboratory VARCHAR(160) NOT NULL,
    method VARCHAR(255) NOT NULL,
    results_json TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_soil_records_user_date (user_id, sample_date),
    CONSTRAINT fk_soil_record_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
