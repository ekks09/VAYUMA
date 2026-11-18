-- VAYUMA Phase 4: stakeholder request withdrawal support
-- Safe additive change: preserves existing request rows and existing statuses.
ALTER TABLE stakeholder_offer_requests
  MODIFY COLUMN status ENUM('pending','accepted','declined','completed','cancelled')
  NOT NULL DEFAULT 'pending';
