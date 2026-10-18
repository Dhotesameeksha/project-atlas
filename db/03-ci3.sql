USE atlas;
CREATE TABLE IF NOT EXISTS chat_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(64) NOT NULL,
  client_id VARCHAR(10) NOT NULL,
  question TEXT NOT NULL,
  answer MEDIUMTEXT NOT NULL,
  confidence DECIMAL(4,2) NULL,
  escalated TINYINT(1) NOT NULL DEFAULT 0,
  route VARCHAR(20) NULL,
  payload MEDIUMTEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_session (session_id)
);
CREATE TABLE IF NOT EXISTS chat_feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  message_id INT NOT NULL,
  rating TINYINT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_message (message_id),
  FOREIGN KEY (message_id) REFERENCES chat_messages(id)
);
