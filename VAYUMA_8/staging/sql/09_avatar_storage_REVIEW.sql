-- MANUAL, REVIEW FIRST: compressed profile photos only; do not rerun blindly.
-- Confirm this table does not already exist and back up the target database.
-- The application stores only JPEG thumbnails of at most 81920 bytes.
CREATE TABLE user_avatars (
    user_id INT NOT NULL PRIMARY KEY,
    jpeg_bytes MEDIUMBLOB NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_avatars_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- After review and application, configure VAYUMA_AVATAR_STORAGE=tidb.
-- No production database is modified by this file until an operator runs it.
