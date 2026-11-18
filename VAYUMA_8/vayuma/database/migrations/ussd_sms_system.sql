-- VAYUMA USSD and SMS support tables. Run once in phpMyAdmin against vayuma_db.

CREATE TABLE IF NOT EXISTS sms_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    message_text TEXT NOT NULL,
    message_type VARCHAR(50) NOT NULL DEFAULT 'general',
    status VARCHAR(30) NOT NULL DEFAULT 'queued',
    provider_message_id VARCHAR(120) NULL,
    provider_response MEDIUMTEXT NULL,
    error_message TEXT NULL,
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_phone_created (phone_number, created_at),
    INDEX idx_sms_provider_message (provider_message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sms_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    topic VARCHAR(50) NOT NULL,
    category VARCHAR(100) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_sms_subscription (phone_number, topic, category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ussd_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    service_code VARCHAR(50) NULL,
    request_text VARCHAR(255) NOT NULL,
    response_text TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ussd_session (session_id),
    INDEX idx_ussd_phone_created (phone_number, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
