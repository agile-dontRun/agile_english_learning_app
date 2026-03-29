-- 删除旧数据库（如果存在）
DROP DATABASE IF EXISTS wardrobe_game;

-- 创建新数据库
CREATE DATABASE wardrobe_game 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE wardrobe_game;

-- ============================================
-- 1. 图层表
-- ============================================
CREATE TABLE IF NOT EXISTS layers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL COMMENT '图层代码',
    name VARCHAR(50) NOT NULL COMMENT '显示名称',
    sort_order INT DEFAULT 0 COMMENT '渲染顺序',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- 2. 图片表
-- ============================================
CREATE TABLE IF NOT EXISTS images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    layer_code VARCHAR(50) NOT NULL COMMENT '所属图层',
    name VARCHAR(100) NOT NULL COMMENT '图片名称',
    file_path VARCHAR(500) NOT NULL COMMENT '图片路径',
    thumbnail_path VARCHAR(500) COMMENT '缩略图路径',
    sort_order INT DEFAULT 0 COMMENT '排序',
    is_default BOOLEAN DEFAULT FALSE COMMENT '是否默认款式',
    is_enabled BOOLEAN DEFAULT TRUE COMMENT '是否启用',
    use_count INT DEFAULT 0 COMMENT '使用次数',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_layer (layer_code),
    FOREIGN KEY (layer_code) REFERENCES layers(code) ON DELETE CASCADE
);

-- ============================================
-- 3. 穿搭表
-- ============================================
CREATE TABLE IF NOT EXISTS outfits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL COMMENT '穿搭名称',
    description TEXT COMMENT '描述',
    cover_image VARCHAR(500) COMMENT '封面图',
    is_favorite BOOLEAN DEFAULT FALSE COMMENT '是否收藏',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_favorite (is_favorite)
);

-- ============================================
-- 4. 穿搭详情表
-- ============================================
CREATE TABLE IF NOT EXISTS outfit_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    outfit_id INT NOT NULL,
    layer_code VARCHAR(50) NOT NULL,
    image_id INT NOT NULL,
    FOREIGN KEY (outfit_id) REFERENCES outfits(id) ON DELETE CASCADE,
    FOREIGN KEY (image_id) REFERENCES images(id),
    UNIQUE KEY unique_outfit_layer (outfit_id, layer_code)
);

-- ============================================
-- 5. 冲突规则表
-- ============================================
CREATE TABLE IF NOT EXISTS conflict_rules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    layer_code VARCHAR(50) NOT NULL,
    conflict_with VARCHAR(50) NOT NULL,
    INDEX idx_layer (layer_code)
);

-- ============================================
-- 6. 插入图层数据（鞋子在 body 之后）
-- ============================================
INSERT INTO layers (code, name, sort_order) VALUES
('background', '🎨 背景', 0),
('body', '💃 人体', 1),
('shoes', '👠 鞋子', 2),
('top', '👕 上衣', 3),
('pants', '👖 裤子', 4),
('dress', '👗 裙子', 5),
('suit', '✨ 套装', 6),
('eye', '👀 眼睛', 7),
('eyebrows', '✏️ 眉毛', 8),
('nose', '👃 鼻子', 9),
('mouse', '👄 嘴巴', 10),
('hair', '💇 头发', 11),
('earings', '💎 耳环', 12),
('glass', '🕶️ 墨镜', 13),
('head', '👑 头饰', 14),
('character', '🧚 角色整体', 15);

-- ============================================
-- 7. 插入冲突规则
-- ============================================
INSERT INTO conflict_rules (layer_code, conflict_with) VALUES
-- 裙子冲突
('dress', 'top'),
('dress', 'pants'),
('dress', 'suit'),

-- 套装冲突
('suit', 'top'),
('suit', 'pants'),
('suit', 'dress'),

-- 上衣冲突
('top', 'dress'),
('top', 'suit'),

-- 裤子冲突
('pants', 'dress'),
('pants', 'suit'),

-- 角色整体冲突（清除五官）
('character', 'eye'),
('character', 'eyebrows'),
('character', 'nose'),
('character', 'mouse'),
('character', 'hair'),

-- 五官冲突（清除角色整体）
('eye', 'character'),
('eyebrows', 'character'),
('nose', 'character'),
('mouse', 'character'),
('hair', 'character');

-- ============================================
-- 8. 查看结果
-- ============================================
SELECT '✅ 数据库创建成功！' AS status;
SELECT * FROM layers ORDER BY sort_order;