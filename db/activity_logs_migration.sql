-- Activity Logs Table
-- This table tracks all important actions taken in the system

CREATE TABLE IF NOT EXISTS activity_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NULL,
  action_type VARCHAR(100) NOT NULL,
  action_description TEXT NOT NULL,
  entity_type VARCHAR(50) NULL,
  entity_id BIGINT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  metadata JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_user_id (user_id),
  INDEX idx_action_type (action_type),
  INDEX idx_entity (entity_type, entity_id),
  INDEX idx_created_at (created_at)
);
