-- Unified coin system upgrade script
-- Safe to run after the original money.sql has already been imported.

SET NAMES utf8mb4;

-- 1. User wallet
CREATE TABLE IF NOT EXISTS coin_wallets (
    user_id INT NOT NULL,
    balance INT NOT NULL DEFAULT 0,
    total_earned INT NOT NULL DEFAULT 0,
    total_spent INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    CONSTRAINT fk_coin_wallets_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Ledger for every coin change
CREATE TABLE IF NOT EXISTS coin_ledger (
    ledger_id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    source_game VARCHAR(32) NOT NULL,
    source_type VARCHAR(32) NOT NULL,
    source_ref_id BIGINT NULL,
    delta_amount INT NOT NULL,
    balance_after INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (ledger_id),
    KEY idx_coin_ledger_user_time (user_id, created_at),
    KEY idx_coin_ledger_source (source_game, source_type, source_ref_id),
    CONSTRAINT fk_coin_ledger_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE,
    CONSTRAINT uk_coin_ledger_idempotent
        UNIQUE (user_id, source_game, source_type, source_ref_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Reward rules
CREATE TABLE IF NOT EXISTS coin_reward_rules (
    rule_id INT NOT NULL AUTO_INCREMENT,
    game_code VARCHAR(32) NOT NULL,
    mode_code VARCHAR(32) NOT NULL,
    pair_count INT NULL,
    difficulty_key VARCHAR(32) NULL,
    outcome_code VARCHAR(32) NOT NULL,
    reward_amount INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (rule_id),
    KEY idx_coin_reward_lookup (game_code, mode_code, pair_count, difficulty_key, outcome_code, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Generic unlock records
CREATE TABLE IF NOT EXISTS user_game_unlocks (
    unlock_id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_code VARCHAR(32) NOT NULL,
    unlock_type VARCHAR(32) NOT NULL,
    unlock_key VARCHAR(64) NOT NULL,
    cost_coins INT NOT NULL DEFAULT 0,
    unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (unlock_id),
    UNIQUE KEY uk_user_unlock (user_id, game_code, unlock_type, unlock_key),
    CONSTRAINT fk_user_unlocks_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. First-reward-per-day records for games like canteen / stage
CREATE TABLE IF NOT EXISTS coin_daily_game_rewards (
    daily_reward_id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_code VARCHAR(32) NOT NULL,
    reward_key VARCHAR(64) NOT NULL DEFAULT 'default',
    reward_date DATE NOT NULL,
    reward_amount INT NOT NULL DEFAULT 0,
    metadata_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (daily_reward_id),
    UNIQUE KEY uk_coin_daily_reward (user_id, game_code, reward_key, reward_date),
    KEY idx_coin_daily_reward_lookup (user_id, game_code, reward_date),
    CONSTRAINT fk_coin_daily_rewards_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Mining map progression and clear state
CREATE TABLE IF NOT EXISTS mining_map_progress (
    progress_id BIGINT NOT NULL AUTO_INCREMENT,
    user_id INT NOT NULL,
    map_key VARCHAR(32) NOT NULL,
    is_unlocked TINYINT(1) NOT NULL DEFAULT 0,
    unlocked_at DATETIME NULL,
    is_cleared TINYINT(1) NOT NULL DEFAULT 0,
    cleared_at DATETIME NULL,
    last_played_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (progress_id),
    UNIQUE KEY uk_mining_map_progress (user_id, map_key),
    KEY idx_mining_map_progress_user (user_id),
    CONSTRAINT fk_mining_map_progress_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed reward rules that are needed by the current games.
INSERT INTO coin_reward_rules (game_code, mode_code, pair_count, difficulty_key, outcome_code, reward_amount, is_active)
SELECT 'mining', 'pvp', NULL, NULL, 'win', 20, 1
WHERE NOT EXISTS (
    SELECT 1 FROM coin_reward_rules
    WHERE game_code = 'mining'
      AND mode_code = 'pvp'
      AND pair_count IS NULL
      AND difficulty_key IS NULL
      AND outcome_code = 'win'
);

INSERT INTO coin_reward_rules (game_code, mode_code, pair_count, difficulty_key, outcome_code, reward_amount, is_active)
SELECT 'canteen', 'daily', NULL, NULL, 'complete', 20, 1
WHERE NOT EXISTS (
    SELECT 1 FROM coin_reward_rules
    WHERE game_code = 'canteen'
      AND mode_code = 'daily'
      AND pair_count IS NULL
      AND difficulty_key IS NULL
      AND outcome_code = 'complete'
);

-- Make sure every existing memory player profile has a wallet row.
INSERT IGNORE INTO coin_wallets (user_id, balance, total_earned, total_spent)
SELECT user_id, coins, GREATEST(coins, 0), 0
FROM memory_player_profiles;

-- Ensure every user gets the starter mining map unlocked.
INSERT IGNORE INTO mining_map_progress (user_id, map_key, is_unlocked, unlocked_at, is_cleared)
SELECT user_id, 'map1', 1, NOW(), 0
FROM users;
