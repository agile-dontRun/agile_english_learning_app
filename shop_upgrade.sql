SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS dress_up_shop_items (
    image_id INT NOT NULL,
    price_coins INT NOT NULL DEFAULT 0,
    is_free TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (image_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dress_up_user_unlocks (
    unlock_id BIGINT NOT NULL AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    image_id INT NOT NULL,
    unlock_source VARCHAR(32) NOT NULL DEFAULT 'purchase',
    unlocked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (unlock_id),
    UNIQUE KEY uk_dress_up_unlock (user_id, image_id),
    KEY idx_dress_up_unlock_user (user_id),
    KEY idx_dress_up_unlock_image (image_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO dress_up_shop_items (image_id, price_coins, is_free)
SELECT priced.image_id, priced.price_coins,
       CASE WHEN priced.price_coins = 0 THEN 1 ELSE 0 END AS is_free
FROM (
    SELECT
        i.id AS image_id,
        CASE
            WHEN i.layer_code = 'body' THEN 0
            WHEN i.layer_code IN ('eye', 'eyebrows', 'nose', 'mouse')
                 AND CAST(SUBSTRING_INDEX(i.name, '_', -1) AS UNSIGNED) = 1 THEN 0
            WHEN i.layer_code IN ('eye', 'eyebrows', 'nose', 'mouse') THEN 100
            WHEN i.layer_code = 'hair'
                 AND CAST(SUBSTRING_INDEX(i.name, '_', -1) AS UNSIGNED) IN (1, 9) THEN 0
            WHEN i.layer_code = 'hair' THEN 200
            WHEN i.layer_code = 'top'
                 AND CAST(SUBSTRING_INDEX(i.name, '_', -1) AS UNSIGNED) = 3 THEN 0
            WHEN i.layer_code = 'top' THEN 100
            WHEN i.layer_code = 'pants'
                 AND CAST(SUBSTRING_INDEX(i.name, '_', -1) AS UNSIGNED) = 5 THEN 0
            WHEN i.layer_code = 'pants' THEN 100
            WHEN i.layer_code IN ('dress', 'suit') THEN 1000
            WHEN i.layer_code = 'shoes'
                 AND CAST(SUBSTRING_INDEX(i.name, '_', -1) AS UNSIGNED) = 1 THEN 0
            WHEN i.layer_code = 'shoes' THEN 888
            WHEN i.layer_code = 'glass'
                 AND CAST(SUBSTRING_INDEX(i.name, '_', -1) AS UNSIGNED) IN (1, 2) THEN 0
            WHEN i.layer_code = 'glass' THEN 100
            WHEN i.layer_code = 'head'
                 AND CAST(SUBSTRING_INDEX(i.name, '_', -1) AS UNSIGNED) = 11 THEN 0
            WHEN i.layer_code = 'head' THEN 900
            WHEN i.layer_code = 'character' THEN 999
            WHEN i.layer_code = 'background' THEN 2000
            WHEN i.layer_code = 'earings'
                 AND CAST(SUBSTRING_INDEX(i.name, '_', -1) AS UNSIGNED) = 1 THEN 0
            WHEN i.layer_code = 'earings' THEN 100
            ELSE 0
        END AS price_coins
    FROM images i
    WHERE i.is_enabled = 1
) AS priced
ON DUPLICATE KEY UPDATE
    price_coins = VALUES(price_coins),
    is_free = VALUES(is_free),
    updated_at = CURRENT_TIMESTAMP;

INSERT IGNORE INTO dress_up_user_unlocks (user_id, image_id, unlock_source)
SELECT u.user_id, si.image_id, 'free'
FROM users u
INNER JOIN dress_up_shop_items si ON si.price_coins = 0;

INSERT IGNORE INTO dress_up_user_unlocks (user_id, image_id, unlock_source)
SELECT DISTINCT o.user_id, oi.image_id, 'legacy'
FROM outfits o
INNER JOIN outfit_items oi ON oi.outfit_id = o.id;
