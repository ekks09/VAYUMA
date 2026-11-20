-- MANUAL REVIEW ONLY. Back up and check users.id type and table absence before applying.
-- A request counter only: no questions, answers, API keys or farm data are stored.
CREATE TABLE ai_daily_usage (
    user_id INT NOT NULL,
    usage_date DATE NOT NULL,
    request_count INT NOT NULL DEFAULT 0,
    PRIMARY KEY (user_id, usage_date),
    CONSTRAINT fk_ai_usage_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
