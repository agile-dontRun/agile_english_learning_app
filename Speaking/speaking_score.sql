-- =========================
-- 17. User Speaking Practice Records Table
-- Records conversations with AI Emma and scoring results
-- =========================
CREATE TABLE user_speaking_attempts (
    attempt_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    session_id VARCHAR(50) NOT NULL, -- Corresponds to uuid in code
    user_text TEXT,                  -- User ASR recognition result
    ai_response TEXT,               -- AI Emma's response content
    
    -- Scoring Details (Reference Volcano Engine Evaluation Metrics)
    overall_score DECIMAL(5,2) DEFAULT 0.00,    -- Total score
    pronunciation_score DECIMAL(5,2) DEFAULT 0.00, -- Pronunciation score
    fluency_score DECIMAL(5,2) DEFAULT 0.00,     -- Fluency score
    integrity_score DECIMAL(5,2) DEFAULT 0.00,   -- Completeness score
    
    evaluation_json JSON,            -- Stores word-level detailed feedback (color-coded in red/green)
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_speaking_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_speaking_user ON user_speaking_attempts(user_id);