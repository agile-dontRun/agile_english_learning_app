CREATE DATABASE IF NOT EXISTS english_learning_app
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE english_learning_app;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================
-- 按依赖顺序先删表
-- =========================
DROP TABLE IF EXISTS user_ielts_attempts;
DROP TABLE IF EXISTS user_ted_attempts;

DROP TABLE IF EXISTS ielts_answers;
DROP TABLE IF EXISTS ielts_part_images;
DROP TABLE IF EXISTS ielts_listening_parts;

DROP TABLE IF EXISTS notebook_word_records;
DROP TABLE IF EXISTS vocabulary_notebooks;
DROP TABLE IF EXISTS word_search_records;
DROP TABLE IF EXISTS words;

DROP TABLE IF EXISTS ted_blank_answers;
DROP TABLE IF EXISTS ted_gapfill_texts;
DROP TABLE IF EXISTS daily_talks;
DROP TABLE IF EXISTS ted_transcripts;
DROP TABLE IF EXISTS ted_watch_history;
DROP TABLE IF EXISTS ted_talks;

DROP TABLE IF EXISTS daily_checkin_records;
DROP TABLE IF EXISTS checkin_calendars;
DROP TABLE IF EXISTS users;


CREATE TABLE daily_talks (
    daily_talk_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    speaker VARCHAR(100),
    description TEXT,
    subtitle_mode ENUM('with_subtitle', 'without_subtitle') NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    cover_url VARCHAR(500),
    duration_seconds INT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_daily_talk_title_subtitle_mode UNIQUE (title, subtitle_mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 1. 用户表
-- =========================
CREATE TABLE users (
    user_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nickname VARCHAR(50),
    avatar_url VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 2. 打卡日历本表
-- 每个用户一个日历本：users 1 : 1 checkin_calendars
-- =========================
CREATE TABLE checkin_calendars (
    calendar_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL UNIQUE,
    calendar_name VARCHAR(100) DEFAULT 'My Check-in Calendar',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calendar_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 3. 每日打卡记录表
-- 一个日历本可以有很多天的打卡记录：checkin_calendars 1 : N daily_checkin_records
-- =========================
CREATE TABLE daily_checkin_records (
    record_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    calendar_id BIGINT NOT NULL,
    checkin_date DATE NOT NULL,
    login_time DATETIME,
    logout_time DATETIME,
    study_minutes INT NOT NULL DEFAULT 0,
    note VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_checkin_calendar
        FOREIGN KEY (calendar_id) REFERENCES checkin_calendars(calendar_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT uk_calendar_date UNIQUE (calendar_id, checkin_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 4. TED Talk 视频表
-- 只存链接和元数据，不直接存视频文件
-- =========================
CREATE TABLE ted_talks (
    ted_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    speaker VARCHAR(100),
    topic VARCHAR(100),
    description TEXT,
    duration_seconds INT,
    subtitle_mode ENUM('with_subtitle', 'without_subtitle') NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    subtitle_en_url VARCHAR(500),
    subtitle_cn_url VARCHAR(500),
    cover_url VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_ted_title_subtitle_mode UNIQUE (title, subtitle_mode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 5. TED 观看历史表
-- users 和 ted_talks 的多对多连接表
-- =========================
CREATE TABLE ted_watch_history (
    watch_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    ted_id BIGINT NOT NULL,
    watched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    watched_seconds INT NOT NULL DEFAULT 0,
    last_position_seconds INT NOT NULL DEFAULT 0,
    subtitle_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    completed BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_watch_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_watch_ted
        FOREIGN KEY (ted_id) REFERENCES ted_talks(ted_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 6. TED 文本表
-- 每个 TED Talk 对应一个完整文本：ted_talks 1 : 1 ted_transcripts
-- =========================
CREATE TABLE ted_transcripts (
    transcript_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ted_id BIGINT NOT NULL UNIQUE,
    full_text_en LONGTEXT NOT NULL,
    full_text_cn LONGTEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_transcript_ted
        FOREIGN KEY (ted_id) REFERENCES ted_talks(ted_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ted_gapfill_texts (
    gapfill_text_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    transcript_id BIGINT NOT NULL UNIQUE,
    blanked_text_en LONGTEXT NOT NULL,
    instructions TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_gapfill_transcript
        FOREIGN KEY (transcript_id) REFERENCES ted_transcripts(transcript_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 7. TED 填空答案表
-- 一个文本可以对应多个填空答案：ted_transcripts 1 : N ted_blank_answers
-- =========================
CREATE TABLE ted_blank_answers (
    answer_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    gapfill_text_id BIGINT NOT NULL,
    question_no INT NOT NULL,
    blank_sentence TEXT NOT NULL,
    correct_answer VARCHAR(255) NOT NULL,
    answer_position VARCHAR(100),
    hint VARCHAR(255),
    explanation TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_answer_gapfill_text
        FOREIGN KEY (gapfill_text_id) REFERENCES ted_gapfill_texts(gapfill_text_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT uk_gapfill_question UNIQUE (gapfill_text_id, question_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 8. 单词表
-- 存储系统中的所有单词 / 短语
-- example_translation 当前可临时用于存英文解释
-- =========================
CREATE TABLE words (
    word_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    english_word VARCHAR(100) NOT NULL UNIQUE,
    chinese_meaning TEXT,
    phonetic VARCHAR(100),
    part_of_speech VARCHAR(50),
    example_sentence TEXT,
    example_translation TEXT,
    audio_url VARCHAR(500),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 9. 单词查询记录表
-- users 和 words 的多对多连接表
-- =========================
CREATE TABLE word_search_records (
    search_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    word_id BIGINT NOT NULL,
    search_keyword VARCHAR(100) NOT NULL,
    searched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_search_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_search_word
        FOREIGN KEY (word_id) REFERENCES words(word_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 10. 生词本表
-- 每个用户一个生词本：users 1 : 1 vocabulary_notebooks
-- =========================
CREATE TABLE vocabulary_notebooks (
    notebook_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL UNIQUE,
    notebook_name VARCHAR(100) NOT NULL DEFAULT 'My Vocabulary Notebook',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notebook_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 11. 生词记录表
-- vocabulary_notebooks 和 words 的多对多连接表
-- =========================
CREATE TABLE notebook_word_records (
    notebook_word_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    notebook_id BIGINT NOT NULL,
    word_id BIGINT NOT NULL,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    familiarity_level ENUM('new', 'learning', 'mastered') NOT NULL DEFAULT 'new',
    note VARCHAR(255),
    CONSTRAINT fk_notebook_word_notebook
        FOREIGN KEY (notebook_id) REFERENCES vocabulary_notebooks(notebook_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_notebook_word_word
        FOREIGN KEY (word_id) REFERENCES words(word_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT uk_notebook_word UNIQUE (notebook_id, word_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 12. IELTS 听力 Part 主表
-- 一条记录唯一表示一个具体的 Cambridge-Test-Part
-- 例如：Cambridge 12 / Test 3 / Part 2
-- =========================
CREATE TABLE ielts_listening_parts (
    part_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cambridge_no INT NOT NULL,
    test_no INT NOT NULL,
    part_no INT NOT NULL,
    title VARCHAR(255),
    audio_url VARCHAR(500) NOT NULL,
    transcript_text LONGTEXT,
    answer_text LONGTEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uk_cambridge_test_part UNIQUE (cambridge_no, test_no, part_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 13. IELTS 题目图片表
-- 一个 part 可以有多张图片
-- =========================
CREATE TABLE ielts_part_images (
    image_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    part_id BIGINT NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    image_order INT NOT NULL DEFAULT 1,
    image_type VARCHAR(50),
    description VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ielts_image_part
        FOREIGN KEY (part_id) REFERENCES ielts_listening_parts(part_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 14. IELTS 答案表
-- 一个 part 对应多道题的答案
-- =========================
CREATE TABLE ielts_answers (
    answer_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    part_id BIGINT NOT NULL,
    question_no INT NOT NULL,
    answer_type ENUM('choice', 'blank_fill') NOT NULL DEFAULT 'blank_fill',
    correct_answer VARCHAR(255) NOT NULL,
    explanation TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_ielts_answer_part
        FOREIGN KEY (part_id) REFERENCES ielts_listening_parts(part_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT uk_ielts_part_question UNIQUE (part_id, question_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 15. 用户 IELTS 作答记录表
-- users 和 ielts_listening_parts 的多对多连接表
-- 记录用户做某个 IELTS part 的成绩
-- =========================
CREATE TABLE user_ielts_attempts (
    attempt_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    part_id BIGINT NOT NULL,
    total_questions INT NOT NULL DEFAULT 0,
    correct_count INT NOT NULL DEFAULT 0,
    wrong_count INT NOT NULL DEFAULT 0,
    score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_ielts_attempt_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_user_ielts_attempt_part
        FOREIGN KEY (part_id) REFERENCES ielts_listening_parts(part_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 16. 用户 TED 填空作答记录表
-- users 和 ted_transcripts 的多对多连接表
-- 记录用户做某个 TED 文本填空的成绩
-- =========================
CREATE TABLE user_ted_attempts (
    attempt_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    transcript_id BIGINT NOT NULL,
    total_questions INT NOT NULL DEFAULT 0,
    correct_count INT NOT NULL DEFAULT 0,
    wrong_count INT NOT NULL DEFAULT 0,
    score DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_ted_attempt_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_user_ted_attempt_transcript
        FOREIGN KEY (transcript_id) REFERENCES ted_transcripts(transcript_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================
-- 索引
-- =========================
CREATE INDEX idx_daily_checkin_date ON daily_checkin_records(checkin_date);

CREATE INDEX idx_ted_title ON ted_talks(title);
CREATE INDEX idx_ted_speaker ON ted_talks(speaker);
CREATE INDEX idx_watch_user ON ted_watch_history(user_id);
CREATE INDEX idx_watch_ted ON ted_watch_history(ted_id);
CREATE INDEX idx_ted_attempt_user ON user_ted_attempts(user_id);
CREATE INDEX idx_ted_attempt_transcript ON user_ted_attempts(transcript_id);

CREATE INDEX idx_word_english ON words(english_word);
CREATE INDEX idx_search_user ON word_search_records(user_id);
CREATE INDEX idx_search_word ON word_search_records(word_id);
CREATE INDEX idx_notebook_id ON notebook_word_records(notebook_id);
CREATE INDEX idx_word_id ON notebook_word_records(word_id);

CREATE INDEX idx_ielts_cambridge ON ielts_listening_parts(cambridge_no);
CREATE INDEX idx_ielts_test ON ielts_listening_parts(test_no);
CREATE INDEX idx_ielts_part ON ielts_listening_parts(part_no);
CREATE INDEX idx_ielts_image_part_id ON ielts_part_images(part_id);
CREATE INDEX idx_ielts_answer_part_id ON ielts_answers(part_id);
CREATE INDEX idx_ielts_attempt_user ON user_ielts_attempts(user_id);
CREATE INDEX idx_ielts_attempt_part ON user_ielts_attempts(part_id);

SET FOREIGN_KEY_CHECKS = 1;