-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2026-04-02 22:30:24
-- 服务器版本： 5.7.40-log
-- PHP 版本： 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `english_learning_app`
--
CREATE DATABASE IF NOT EXISTS `english_learning_app` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `english_learning_app`;

-- --------------------------------------------------------

--
-- 表的结构 `articles`
--

CREATE TABLE `articles` (
  `article_id` bigint(20) NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Anonymous',
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `difficulty` enum('beginner','intermediate','advanced') COLLATE utf8mb4_unicode_ci DEFAULT 'intermediate',
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `word_count` int(11) DEFAULT '0',
  `read_time` int(11) DEFAULT '0',
  `view_count` int(11) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `checkin_calendars`
--

CREATE TABLE `checkin_calendars` (
  `calendar_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `calendar_name` varchar(100) DEFAULT 'My Check-in Calendar',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `coin_ledger`
--

CREATE TABLE `coin_ledger` (
  `ledger_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `source_game` varchar(32) NOT NULL,
  `source_type` varchar(32) NOT NULL,
  `source_ref_id` bigint(20) DEFAULT NULL,
  `delta_amount` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `metadata_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `coin_reward_rules`
--

CREATE TABLE `coin_reward_rules` (
  `rule_id` int(11) NOT NULL,
  `game_code` varchar(32) NOT NULL,
  `mode_code` varchar(32) NOT NULL,
  `pair_count` int(11) DEFAULT NULL,
  `difficulty_key` varchar(32) DEFAULT NULL,
  `outcome_code` varchar(32) NOT NULL,
  `reward_amount` int(11) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `coin_wallets`
--

CREATE TABLE `coin_wallets` (
  `user_id` bigint(20) NOT NULL,
  `balance` int(11) NOT NULL DEFAULT '0',
  `total_earned` int(11) NOT NULL DEFAULT '0',
  `total_spent` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `conflict_rules`
--

CREATE TABLE `conflict_rules` (
  `id` int(11) NOT NULL,
  `layer_code` varchar(50) NOT NULL,
  `conflict_with` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `daily_checkin_records`
--

CREATE TABLE `daily_checkin_records` (
  `record_id` bigint(20) NOT NULL,
  `calendar_id` bigint(20) NOT NULL,
  `checkin_date` date NOT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `study_minutes` int(11) NOT NULL DEFAULT '0',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `daily_talks`
--

CREATE TABLE `daily_talks` (
  `daily_talk_id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `speaker` varchar(100) DEFAULT NULL,
  `accents` varchar(100) DEFAULT 'General American',
  `description` text,
  `subtitle_mode` enum('with_subtitle','without_subtitle') NOT NULL,
  `video_url` varchar(500) NOT NULL,
  `cover_url` varchar(500) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `forum_comments`
--

CREATE TABLE `forum_comments` (
  `comment_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `parent_comment_id` bigint(20) DEFAULT NULL,
  `reply_to_user_id` bigint(20) DEFAULT NULL,
  `content` text NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `is_seen_by_post_author` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `forum_comment_media`
--

CREATE TABLE `forum_comment_media` (
  `media_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('image','video','audio') NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `forum_posts`
--

CREATE TABLE `forum_posts` (
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` longtext NOT NULL,
  `visibility` enum('public','followers_only','friends_only','private') NOT NULL DEFAULT 'public',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `forum_post_media`
--

CREATE TABLE `forum_post_media` (
  `media_id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('image','video','audio') NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ielts_answers`
--

CREATE TABLE `ielts_answers` (
  `answer_id` bigint(20) NOT NULL,
  `part_id` bigint(20) NOT NULL,
  `question_no` int(11) NOT NULL,
  `answer_type` enum('choice','blank_fill') NOT NULL DEFAULT 'blank_fill',
  `correct_answer` varchar(255) NOT NULL,
  `explanation` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ielts_listening_parts`
--

CREATE TABLE `ielts_listening_parts` (
  `part_id` bigint(20) NOT NULL,
  `cambridge_no` int(11) NOT NULL,
  `test_no` int(11) NOT NULL,
  `part_no` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `audio_url` varchar(500) NOT NULL,
  `transcript_text` longtext,
  `answer_text` longtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ielts_part_images`
--

CREATE TABLE `ielts_part_images` (
  `image_id` bigint(20) NOT NULL,
  `part_id` bigint(20) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `image_order` int(11) NOT NULL DEFAULT '1',
  `image_type` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `images`
--

CREATE TABLE `images` (
  `id` int(11) NOT NULL,
  `layer_code` varchar(50) NOT NULL COMMENT '所属图层',
  `name` varchar(100) NOT NULL COMMENT '图片名称',
  `file_path` varchar(500) NOT NULL COMMENT '图片路径',
  `thumbnail_path` varchar(500) DEFAULT NULL COMMENT '缩略图路径',
  `sort_order` int(11) DEFAULT '0' COMMENT '排序',
  `is_default` tinyint(1) DEFAULT '0' COMMENT '是否默认款式',
  `is_enabled` tinyint(1) DEFAULT '1' COMMENT '是否启用',
  `use_count` int(11) DEFAULT '0' COMMENT '使用次数',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `layers`
--

CREATE TABLE `layers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL COMMENT '图层代码',
  `name` varchar(50) NOT NULL COMMENT '显示名称',
  `sort_order` int(11) DEFAULT '0' COMMENT '渲染顺序',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `memory_flip_logs`
--

CREATE TABLE `memory_flip_logs` (
  `flip_log_id` bigint(20) NOT NULL,
  `turn_id` bigint(20) NOT NULL,
  `match_player_id` bigint(20) NOT NULL,
  `card_id` bigint(20) NOT NULL,
  `flip_order` tinyint(4) NOT NULL,
  `flipped_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `memory_game_modes`
--

CREATE TABLE `memory_game_modes` (
  `mode_id` bigint(20) NOT NULL,
  `mode_name` varchar(100) NOT NULL,
  `pair_count` int(11) NOT NULL,
  `time_limit_seconds` int(11) NOT NULL DEFAULT '90',
  `win_rule` enum('first_finish','highest_score_on_timeout') NOT NULL DEFAULT 'highest_score_on_timeout',
  `difficulty_level` enum('high_school','cet4','cet6','ielts_toefl','gre') NOT NULL,
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `memory_matches`
--

CREATE TABLE `memory_matches` (
  `match_id` bigint(20) NOT NULL,
  `mode_id` bigint(20) NOT NULL,
  `status` enum('waiting','in_progress','finished','cancelled') NOT NULL DEFAULT 'waiting',
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `winner_user_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `memory_match_players`
--

CREATE TABLE `memory_match_players` (
  `match_player_id` bigint(20) NOT NULL,
  `match_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `player_slot` tinyint(4) NOT NULL,
  `score` int(11) NOT NULL DEFAULT '0',
  `matched_pairs_count` int(11) NOT NULL DEFAULT '0',
  `flip_count` int(11) NOT NULL DEFAULT '0',
  `finished_all` tinyint(1) NOT NULL DEFAULT '0',
  `finished_at` datetime DEFAULT NULL,
  `is_winner` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `memory_match_word_pairs`
--

CREATE TABLE `memory_match_word_pairs` (
  `pair_id` bigint(20) NOT NULL,
  `match_id` bigint(20) NOT NULL,
  `pair_no` int(11) NOT NULL,
  `word_id` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `memory_player_cards`
--

CREATE TABLE `memory_player_cards` (
  `card_id` bigint(20) NOT NULL,
  `match_player_id` bigint(20) NOT NULL,
  `pair_id` bigint(20) NOT NULL,
  `card_type` enum('word','audio') NOT NULL,
  `position_no` int(11) NOT NULL,
  `is_face_up` tinyint(1) NOT NULL DEFAULT '0',
  `is_matched` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `memory_player_profiles`
--

CREATE TABLE `memory_player_profiles` (
  `profile_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `coins` bigint(20) NOT NULL DEFAULT '0',
  `total_matches` int(11) NOT NULL DEFAULT '0',
  `total_wins` int(11) NOT NULL DEFAULT '0',
  `best_time_seconds` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `memory_player_turns`
--

CREATE TABLE `memory_player_turns` (
  `turn_id` bigint(20) NOT NULL,
  `match_player_id` bigint(20) NOT NULL,
  `turn_no` int(11) NOT NULL,
  `first_card_id` bigint(20) DEFAULT NULL,
  `second_card_id` bigint(20) DEFAULT NULL,
  `is_match` tinyint(1) NOT NULL DEFAULT '0',
  `score_gained` int(11) NOT NULL DEFAULT '0',
  `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_answer_logs`
--

CREATE TABLE `miner_answer_logs` (
  `answer_log_id` bigint(20) NOT NULL,
  `match_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `match_ore_id` bigint(20) NOT NULL,
  `sequence_no` int(11) NOT NULL,
  `word_id` bigint(20) NOT NULL,
  `selected_option` varchar(255) DEFAULT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `progress_after` int(11) NOT NULL,
  `answered_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_collection_entries`
--

CREATE TABLE `miner_collection_entries` (
  `collection_id` bigint(20) NOT NULL,
  `ore_type_id` bigint(20) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text,
  `rarity` enum('common','rare','epic','legendary','hidden') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_garden_plants`
--

CREATE TABLE `miner_garden_plants` (
  `plant_id` bigint(20) NOT NULL,
  `plant_name` varchar(100) NOT NULL,
  `seed_price` int(11) NOT NULL DEFAULT '0',
  `grow_time_minutes` int(11) NOT NULL DEFAULT '0',
  `description` text,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_item_types`
--

CREATE TABLE `miner_item_types` (
  `item_type_id` bigint(20) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `item_category` enum('consumable','equipment','cosmetic') NOT NULL,
  `description` text,
  `item_price` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_maps`
--

CREATE TABLE `miner_maps` (
  `map_id` bigint(20) NOT NULL,
  `map_name` varchar(100) NOT NULL,
  `theme` varchar(100) DEFAULT NULL,
  `difficulty_level` enum('high_school','cet4','cet6','ielts_toefl','gre') NOT NULL,
  `description` text,
  `rare_ore_rate_multiplier` decimal(5,2) NOT NULL DEFAULT '1.00',
  `max_reward_multiplier` decimal(5,2) NOT NULL DEFAULT '1.00',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_map_ore_rules`
--

CREATE TABLE `miner_map_ore_rules` (
  `rule_id` bigint(20) NOT NULL,
  `map_id` bigint(20) NOT NULL,
  `ore_type_id` bigint(20) NOT NULL,
  `spawn_weight` int(11) NOT NULL DEFAULT '100',
  `min_count` int(11) NOT NULL DEFAULT '0',
  `max_count` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_matches`
--

CREATE TABLE `miner_matches` (
  `match_id` bigint(20) NOT NULL,
  `map_id` bigint(20) NOT NULL,
  `match_mode` enum('pvp_local','pvp_online') NOT NULL DEFAULT 'pvp_online',
  `random_seed` bigint(20) NOT NULL,
  `status` enum('waiting','in_progress','finished','cancelled') NOT NULL DEFAULT 'waiting',
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_match_ores`
--

CREATE TABLE `miner_match_ores` (
  `match_ore_id` bigint(20) NOT NULL,
  `match_id` bigint(20) NOT NULL,
  `ore_type_id` bigint(20) NOT NULL,
  `position_x` decimal(8,2) NOT NULL,
  `position_y` decimal(8,2) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `total_required_answers` int(11) NOT NULL,
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_match_ore_words`
--

CREATE TABLE `miner_match_ore_words` (
  `ore_word_id` bigint(20) NOT NULL,
  `match_ore_id` bigint(20) NOT NULL,
  `sequence_no` int(11) NOT NULL,
  `word_id` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_match_players`
--

CREATE TABLE `miner_match_players` (
  `match_player_id` bigint(20) NOT NULL,
  `match_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `player_slot` tinyint(4) NOT NULL,
  `final_score` int(11) NOT NULL DEFAULT '0',
  `final_coins_earned` int(11) NOT NULL DEFAULT '0',
  `detector_used` int(11) NOT NULL DEFAULT '0',
  `drill_equipped` tinyint(1) NOT NULL DEFAULT '0',
  `is_winner` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_ore_types`
--

CREATE TABLE `miner_ore_types` (
  `ore_type_id` bigint(20) NOT NULL,
  `ore_name` varchar(100) NOT NULL,
  `rarity` enum('common','rare','epic','legendary','hidden') NOT NULL,
  `base_value` int(11) NOT NULL DEFAULT '0',
  `required_correct_answers` int(11) NOT NULL,
  `spawn_weight` int(11) NOT NULL DEFAULT '100',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `requires_drill` tinyint(1) NOT NULL DEFAULT '0',
  `image_url` varchar(255) DEFAULT NULL,
  `description` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_player_collections`
--

CREATE TABLE `miner_player_collections` (
  `player_collection_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `collection_id` bigint(20) NOT NULL,
  `unlocked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_player_garden_plants`
--

CREATE TABLE `miner_player_garden_plants` (
  `player_plant_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `plant_id` bigint(20) NOT NULL,
  `growth_stage` enum('seed','sprout','growing','mature') NOT NULL DEFAULT 'seed',
  `progress_points` int(11) NOT NULL DEFAULT '0',
  `planted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_player_items`
--

CREATE TABLE `miner_player_items` (
  `player_item_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `item_type_id` bigint(20) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_player_ore_attempts`
--

CREATE TABLE `miner_player_ore_attempts` (
  `attempt_id` bigint(20) NOT NULL,
  `match_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `match_ore_id` bigint(20) NOT NULL,
  `current_progress` int(11) NOT NULL DEFAULT '0',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_player_profiles`
--

CREATE TABLE `miner_player_profiles` (
  `profile_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `coins` bigint(20) NOT NULL DEFAULT '0',
  `total_matches` int(11) NOT NULL DEFAULT '0',
  `total_wins` int(11) NOT NULL DEFAULT '0',
  `total_ores_mined` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_player_upgrades`
--

CREATE TABLE `miner_player_upgrades` (
  `player_upgrade_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `upgrade_type_id` bigint(20) NOT NULL,
  `current_level` int(11) NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_upgrade_level_costs`
--

CREATE TABLE `miner_upgrade_level_costs` (
  `cost_id` bigint(20) NOT NULL,
  `upgrade_type_id` bigint(20) NOT NULL,
  `level_no` int(11) NOT NULL,
  `upgrade_cost` int(11) NOT NULL,
  `effect_value` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `miner_upgrade_types`
--

CREATE TABLE `miner_upgrade_types` (
  `upgrade_type_id` bigint(20) NOT NULL,
  `upgrade_code` varchar(50) NOT NULL,
  `upgrade_name` varchar(100) NOT NULL,
  `description` text,
  `max_level` int(11) NOT NULL DEFAULT '10',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `notebook_word_records`
--

CREATE TABLE `notebook_word_records` (
  `notebook_word_id` bigint(20) NOT NULL,
  `notebook_id` bigint(20) NOT NULL,
  `word_id` bigint(20) NOT NULL,
  `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `familiarity_level` enum('new','learning','mastered') NOT NULL DEFAULT 'new',
  `note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `outfits`
--

CREATE TABLE `outfits` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL COMMENT '所属用户 ID',
  `name` varchar(100) NOT NULL COMMENT '穿搭名称',
  `description` text COMMENT '描述',
  `cover_image` varchar(500) DEFAULT NULL COMMENT '封面图',
  `is_favorite` tinyint(1) DEFAULT '0' COMMENT '是否收藏',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `outfit_items`
--

CREATE TABLE `outfit_items` (
  `id` int(11) NOT NULL,
  `outfit_id` int(11) NOT NULL,
  `layer_code` varchar(50) NOT NULL,
  `image_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `private_messages`
--

CREATE TABLE `private_messages` (
  `message_id` bigint(20) NOT NULL,
  `sender_id` bigint(20) NOT NULL,
  `receiver_id` bigint(20) NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `is_deleted_by_sender` tinyint(1) NOT NULL DEFAULT '0',
  `is_deleted_by_receiver` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `private_message_media`
--

CREATE TABLE `private_message_media` (
  `media_id` bigint(20) NOT NULL,
  `message_id` bigint(20) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` enum('image','video','audio') NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ted_blank_answers`
--

CREATE TABLE `ted_blank_answers` (
  `answer_id` bigint(20) NOT NULL,
  `gapfill_text_id` bigint(20) NOT NULL,
  `question_no` int(11) NOT NULL,
  `blank_sentence` text NOT NULL,
  `correct_answer` varchar(255) NOT NULL,
  `answer_position` varchar(100) DEFAULT NULL,
  `hint` varchar(255) DEFAULT NULL,
  `explanation` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ted_gapfill_texts`
--

CREATE TABLE `ted_gapfill_texts` (
  `gapfill_text_id` bigint(20) NOT NULL,
  `transcript_id` bigint(20) NOT NULL,
  `blanked_text_en` longtext NOT NULL,
  `instructions` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ted_talks`
--

CREATE TABLE `ted_talks` (
  `ted_id` bigint(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `speaker` varchar(100) DEFAULT NULL,
  `accents` varchar(100) DEFAULT 'General American',
  `topic` varchar(100) DEFAULT NULL,
  `accent` varchar(100) DEFAULT 'General American',
  `description` text,
  `duration_seconds` int(11) DEFAULT NULL,
  `subtitle_mode` enum('with_subtitle','without_subtitle') NOT NULL,
  `video_url` varchar(500) NOT NULL,
  `subtitle_en_url` varchar(500) DEFAULT NULL,
  `subtitle_cn_url` varchar(500) DEFAULT NULL,
  `cover_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ted_transcripts`
--

CREATE TABLE `ted_transcripts` (
  `transcript_id` bigint(20) NOT NULL,
  `ted_id` bigint(20) NOT NULL,
  `full_text_en` longtext NOT NULL,
  `full_text_cn` longtext,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `ted_watch_history`
--

CREATE TABLE `ted_watch_history` (
  `watch_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `ted_id` bigint(20) NOT NULL,
  `watched_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `watched_seconds` int(11) NOT NULL DEFAULT '0',
  `last_position_seconds` int(11) NOT NULL DEFAULT '0',
  `subtitle_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `completed` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nickname` varchar(50) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `student_level` enum('freshman','sophomore','junior','senior','graduate','other') DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `user_annotations`
--

CREATE TABLE `user_annotations` (
  `annotation_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `article_id` bigint(20) NOT NULL,
  `selected_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `position_start` int(11) DEFAULT NULL,
  `position_end` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `start_offset` int(11) DEFAULT NULL COMMENT '选中文字在全文中的起始位置',
  `end_offset` int(11) DEFAULT NULL COMMENT '选中文字在全文中的结束位置'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `user_favorites`
--

CREATE TABLE `user_favorites` (
  `favorite_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `article_id` bigint(20) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `user_follows`
--

CREATE TABLE `user_follows` (
  `follow_id` bigint(20) NOT NULL,
  `follower_id` bigint(20) NOT NULL,
  `followed_id` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `user_friendships`
--

CREATE TABLE `user_friendships` (
  `friendship_id` bigint(20) NOT NULL,
  `user_id_1` bigint(20) NOT NULL,
  `user_id_2` bigint(20) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `user_game_unlocks`
--

CREATE TABLE `user_game_unlocks` (
  `unlock_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `game_code` varchar(32) NOT NULL,
  `unlock_type` varchar(32) NOT NULL,
  `unlock_key` varchar(64) NOT NULL,
  `cost_coins` int(11) NOT NULL DEFAULT '0',
  `unlocked_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `user_ielts_attempts`
--

CREATE TABLE `user_ielts_attempts` (
  `attempt_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `part_id` bigint(20) NOT NULL,
  `total_questions` int(11) NOT NULL DEFAULT '0',
  `correct_count` int(11) NOT NULL DEFAULT '0',
  `wrong_count` int(11) NOT NULL DEFAULT '0',
  `score` decimal(6,2) NOT NULL DEFAULT '0.00',
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `user_reading_progress`
--

CREATE TABLE `user_reading_progress` (
  `progress_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `article_id` bigint(20) NOT NULL,
  `last_position` int(11) DEFAULT '0' COMMENT '阅读位置（字符数）',
  `last_read_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `user_speaking_attempts`
--

CREATE TABLE `user_speaking_attempts` (
  `attempt_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `session_id` varchar(50) NOT NULL,
  `user_text` text,
  `ai_response` text,
  `overall_score` decimal(5,2) DEFAULT '0.00',
  `pronunciation_score` decimal(5,2) DEFAULT '0.00',
  `fluency_score` decimal(5,2) DEFAULT '0.00',
  `integrity_score` decimal(5,2) DEFAULT '0.00',
  `evaluation_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `user_ted_attempts`
--

CREATE TABLE `user_ted_attempts` (
  `attempt_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `transcript_id` bigint(20) NOT NULL,
  `total_questions` int(11) NOT NULL DEFAULT '0',
  `correct_count` int(11) NOT NULL DEFAULT '0',
  `wrong_count` int(11) NOT NULL DEFAULT '0',
  `score` decimal(6,2) NOT NULL DEFAULT '0.00',
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `vocabulary_notebooks`
--

CREATE TABLE `vocabulary_notebooks` (
  `notebook_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `notebook_name` varchar(100) NOT NULL DEFAULT 'My Vocabulary Notebook',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `words`
--

CREATE TABLE `words` (
  `word_id` bigint(20) NOT NULL,
  `english_word` varchar(100) NOT NULL,
  `chinese_meaning` text,
  `phonetic` varchar(100) DEFAULT NULL,
  `part_of_speech` varchar(50) DEFAULT NULL,
  `example_sentence` text,
  `example_translation` text,
  `audio_url` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `difficulty_level` enum('high_school','cet4','cet6','ielts_toefl','gre') DEFAULT NULL,
  `exam_tag` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 表的结构 `word_search_records`
--

CREATE TABLE `word_search_records` (
  `search_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `word_id` bigint(20) NOT NULL,
  `search_keyword` varchar(100) NOT NULL,
  `searched_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`article_id`),
  ADD KEY `idx_articles_category` (`category`),
  ADD KEY `idx_articles_difficulty` (`difficulty`);

--
-- 表的索引 `checkin_calendars`
--
ALTER TABLE `checkin_calendars`
  ADD PRIMARY KEY (`calendar_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- 表的索引 `coin_ledger`
--
ALTER TABLE `coin_ledger`
  ADD PRIMARY KEY (`ledger_id`),
  ADD UNIQUE KEY `uk_coin_ledger_idempotent` (`user_id`,`source_game`,`source_type`,`source_ref_id`),
  ADD KEY `idx_coin_ledger_user_time` (`user_id`,`created_at`),
  ADD KEY `idx_coin_ledger_source` (`source_game`,`source_type`,`source_ref_id`);

--
-- 表的索引 `coin_reward_rules`
--
ALTER TABLE `coin_reward_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD KEY `idx_coin_reward_lookup` (`game_code`,`mode_code`,`pair_count`,`difficulty_key`,`outcome_code`,`is_active`);

--
-- 表的索引 `coin_wallets`
--
ALTER TABLE `coin_wallets`
  ADD PRIMARY KEY (`user_id`);

--
-- 表的索引 `conflict_rules`
--
ALTER TABLE `conflict_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_layer` (`layer_code`);

--
-- 表的索引 `daily_checkin_records`
--
ALTER TABLE `daily_checkin_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `uk_calendar_date` (`calendar_id`,`checkin_date`),
  ADD KEY `idx_daily_checkin_date` (`checkin_date`);

--
-- 表的索引 `daily_talks`
--
ALTER TABLE `daily_talks`
  ADD PRIMARY KEY (`daily_talk_id`),
  ADD UNIQUE KEY `uk_daily_talk_title_subtitle_mode` (`title`,`subtitle_mode`);

--
-- 表的索引 `forum_comments`
--
ALTER TABLE `forum_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `idx_forum_comments_post` (`post_id`),
  ADD KEY `idx_forum_comments_user` (`user_id`),
  ADD KEY `idx_forum_comments_parent` (`parent_comment_id`),
  ADD KEY `idx_forum_comments_reply_to_user` (`reply_to_user_id`),
  ADD KEY `idx_forum_comments_created_at` (`created_at`);

--
-- 表的索引 `forum_comment_media`
--
ALTER TABLE `forum_comment_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `fk_forum_comment_media_comment` (`comment_id`);

--
-- 表的索引 `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `idx_forum_posts_user` (`user_id`),
  ADD KEY `idx_forum_posts_visibility` (`visibility`),
  ADD KEY `idx_forum_posts_created_at` (`created_at`);

--
-- 表的索引 `forum_post_media`
--
ALTER TABLE `forum_post_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `fk_forum_post_media_post` (`post_id`);

--
-- 表的索引 `ielts_answers`
--
ALTER TABLE `ielts_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD UNIQUE KEY `uk_ielts_part_question` (`part_id`,`question_no`),
  ADD KEY `idx_ielts_answer_part_id` (`part_id`);

--
-- 表的索引 `ielts_listening_parts`
--
ALTER TABLE `ielts_listening_parts`
  ADD PRIMARY KEY (`part_id`),
  ADD UNIQUE KEY `uk_cambridge_test_part` (`cambridge_no`,`test_no`,`part_no`),
  ADD KEY `idx_ielts_cambridge` (`cambridge_no`),
  ADD KEY `idx_ielts_test` (`test_no`),
  ADD KEY `idx_ielts_part` (`part_no`);

--
-- 表的索引 `ielts_part_images`
--
ALTER TABLE `ielts_part_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_ielts_image_part_id` (`part_id`);

--
-- 表的索引 `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_layer` (`layer_code`);

--
-- 表的索引 `layers`
--
ALTER TABLE `layers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- 表的索引 `memory_flip_logs`
--
ALTER TABLE `memory_flip_logs`
  ADD PRIMARY KEY (`flip_log_id`),
  ADD KEY `fk_memory_flip_match_player` (`match_player_id`),
  ADD KEY `idx_memory_flip_logs_turn` (`turn_id`),
  ADD KEY `idx_memory_flip_logs_card` (`card_id`);

--
-- 表的索引 `memory_game_modes`
--
ALTER TABLE `memory_game_modes`
  ADD PRIMARY KEY (`mode_id`),
  ADD UNIQUE KEY `mode_name` (`mode_name`),
  ADD KEY `idx_memory_modes_difficulty` (`difficulty_level`);

--
-- 表的索引 `memory_matches`
--
ALTER TABLE `memory_matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `fk_memory_matches_winner` (`winner_user_id`),
  ADD KEY `idx_memory_matches_status` (`status`),
  ADD KEY `idx_memory_matches_mode` (`mode_id`);

--
-- 表的索引 `memory_match_players`
--
ALTER TABLE `memory_match_players`
  ADD PRIMARY KEY (`match_player_id`),
  ADD UNIQUE KEY `uk_memory_match_player` (`match_id`,`user_id`),
  ADD UNIQUE KEY `uk_memory_match_slot` (`match_id`,`player_slot`),
  ADD KEY `idx_memory_match_players_match` (`match_id`),
  ADD KEY `idx_memory_match_players_user` (`user_id`);

--
-- 表的索引 `memory_match_word_pairs`
--
ALTER TABLE `memory_match_word_pairs`
  ADD PRIMARY KEY (`pair_id`),
  ADD UNIQUE KEY `uk_memory_match_pair` (`match_id`,`pair_no`),
  ADD UNIQUE KEY `uk_memory_match_word` (`match_id`,`word_id`),
  ADD KEY `idx_memory_pairs_match` (`match_id`),
  ADD KEY `idx_memory_pairs_word` (`word_id`);

--
-- 表的索引 `memory_player_cards`
--
ALTER TABLE `memory_player_cards`
  ADD PRIMARY KEY (`card_id`),
  ADD UNIQUE KEY `uk_memory_player_position` (`match_player_id`,`position_no`),
  ADD KEY `idx_memory_cards_match_player` (`match_player_id`),
  ADD KEY `idx_memory_cards_pair` (`pair_id`),
  ADD KEY `idx_memory_cards_faceup` (`is_face_up`),
  ADD KEY `idx_memory_cards_matched` (`is_matched`);

--
-- 表的索引 `memory_player_profiles`
--
ALTER TABLE `memory_player_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_memory_profiles_user` (`user_id`);

--
-- 表的索引 `memory_player_turns`
--
ALTER TABLE `memory_player_turns`
  ADD PRIMARY KEY (`turn_id`),
  ADD UNIQUE KEY `uk_memory_player_turn` (`match_player_id`,`turn_no`),
  ADD KEY `fk_memory_turns_first_card` (`first_card_id`),
  ADD KEY `fk_memory_turns_second_card` (`second_card_id`),
  ADD KEY `idx_memory_turns_match_player` (`match_player_id`),
  ADD KEY `idx_memory_turns_completed` (`completed_at`);

--
-- 表的索引 `miner_answer_logs`
--
ALTER TABLE `miner_answer_logs`
  ADD PRIMARY KEY (`answer_log_id`),
  ADD KEY `fk_answer_logs_user` (`user_id`),
  ADD KEY `idx_answer_logs_match_user` (`match_id`,`user_id`),
  ADD KEY `idx_answer_logs_match_ore` (`match_ore_id`),
  ADD KEY `idx_answer_logs_word` (`word_id`);

--
-- 表的索引 `miner_collection_entries`
--
ALTER TABLE `miner_collection_entries`
  ADD PRIMARY KEY (`collection_id`),
  ADD UNIQUE KEY `ore_type_id` (`ore_type_id`);

--
-- 表的索引 `miner_garden_plants`
--
ALTER TABLE `miner_garden_plants`
  ADD PRIMARY KEY (`plant_id`),
  ADD UNIQUE KEY `plant_name` (`plant_name`);

--
-- 表的索引 `miner_item_types`
--
ALTER TABLE `miner_item_types`
  ADD PRIMARY KEY (`item_type_id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- 表的索引 `miner_maps`
--
ALTER TABLE `miner_maps`
  ADD PRIMARY KEY (`map_id`),
  ADD UNIQUE KEY `map_name` (`map_name`),
  ADD KEY `idx_miner_maps_difficulty` (`difficulty_level`);

--
-- 表的索引 `miner_map_ore_rules`
--
ALTER TABLE `miner_map_ore_rules`
  ADD PRIMARY KEY (`rule_id`),
  ADD UNIQUE KEY `uk_map_ore_rule` (`map_id`,`ore_type_id`),
  ADD KEY `idx_map_ore_rules_map` (`map_id`),
  ADD KEY `idx_map_ore_rules_ore` (`ore_type_id`);

--
-- 表的索引 `miner_matches`
--
ALTER TABLE `miner_matches`
  ADD PRIMARY KEY (`match_id`),
  ADD KEY `idx_matches_map` (`map_id`),
  ADD KEY `idx_matches_status` (`status`);

--
-- 表的索引 `miner_match_ores`
--
ALTER TABLE `miner_match_ores`
  ADD PRIMARY KEY (`match_ore_id`),
  ADD KEY `idx_match_ores_match` (`match_id`),
  ADD KEY `idx_match_ores_type` (`ore_type_id`);

--
-- 表的索引 `miner_match_ore_words`
--
ALTER TABLE `miner_match_ore_words`
  ADD PRIMARY KEY (`ore_word_id`),
  ADD UNIQUE KEY `uk_match_ore_word` (`match_ore_id`,`sequence_no`),
  ADD KEY `idx_match_ore_words_match_ore` (`match_ore_id`),
  ADD KEY `idx_match_ore_words_word` (`word_id`);

--
-- 表的索引 `miner_match_players`
--
ALTER TABLE `miner_match_players`
  ADD PRIMARY KEY (`match_player_id`),
  ADD UNIQUE KEY `uk_match_player` (`match_id`,`user_id`),
  ADD UNIQUE KEY `uk_match_slot` (`match_id`,`player_slot`),
  ADD KEY `idx_match_players_match` (`match_id`),
  ADD KEY `idx_match_players_user` (`user_id`);

--
-- 表的索引 `miner_ore_types`
--
ALTER TABLE `miner_ore_types`
  ADD PRIMARY KEY (`ore_type_id`),
  ADD UNIQUE KEY `ore_name` (`ore_name`),
  ADD KEY `idx_ore_types_rarity` (`rarity`),
  ADD KEY `idx_ore_types_hidden` (`is_hidden`);

--
-- 表的索引 `miner_player_collections`
--
ALTER TABLE `miner_player_collections`
  ADD PRIMARY KEY (`player_collection_id`),
  ADD UNIQUE KEY `uk_player_collection` (`user_id`,`collection_id`),
  ADD KEY `fk_player_collections_collection` (`collection_id`),
  ADD KEY `idx_player_collections_user` (`user_id`);

--
-- 表的索引 `miner_player_garden_plants`
--
ALTER TABLE `miner_player_garden_plants`
  ADD PRIMARY KEY (`player_plant_id`),
  ADD KEY `fk_player_garden_plant` (`plant_id`),
  ADD KEY `idx_player_garden_user` (`user_id`);

--
-- 表的索引 `miner_player_items`
--
ALTER TABLE `miner_player_items`
  ADD PRIMARY KEY (`player_item_id`),
  ADD UNIQUE KEY `uk_player_item` (`user_id`,`item_type_id`),
  ADD KEY `fk_player_items_type` (`item_type_id`),
  ADD KEY `idx_player_items_user` (`user_id`);

--
-- 表的索引 `miner_player_ore_attempts`
--
ALTER TABLE `miner_player_ore_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD UNIQUE KEY `uk_player_ore_attempt` (`match_id`,`user_id`,`match_ore_id`),
  ADD KEY `fk_player_ore_attempts_user` (`user_id`),
  ADD KEY `idx_player_ore_attempts_match_user` (`match_id`,`user_id`),
  ADD KEY `idx_player_ore_attempts_match_ore` (`match_ore_id`);

--
-- 表的索引 `miner_player_profiles`
--
ALTER TABLE `miner_player_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_player_profiles_user` (`user_id`);

--
-- 表的索引 `miner_player_upgrades`
--
ALTER TABLE `miner_player_upgrades`
  ADD PRIMARY KEY (`player_upgrade_id`),
  ADD UNIQUE KEY `uk_player_upgrade` (`user_id`,`upgrade_type_id`),
  ADD KEY `fk_player_upgrades_type` (`upgrade_type_id`),
  ADD KEY `idx_player_upgrades_user` (`user_id`);

--
-- 表的索引 `miner_upgrade_level_costs`
--
ALTER TABLE `miner_upgrade_level_costs`
  ADD PRIMARY KEY (`cost_id`),
  ADD UNIQUE KEY `uk_upgrade_level` (`upgrade_type_id`,`level_no`);

--
-- 表的索引 `miner_upgrade_types`
--
ALTER TABLE `miner_upgrade_types`
  ADD PRIMARY KEY (`upgrade_type_id`),
  ADD UNIQUE KEY `upgrade_code` (`upgrade_code`);

--
-- 表的索引 `notebook_word_records`
--
ALTER TABLE `notebook_word_records`
  ADD PRIMARY KEY (`notebook_word_id`),
  ADD UNIQUE KEY `uk_notebook_word` (`notebook_id`,`word_id`),
  ADD KEY `idx_notebook_id` (`notebook_id`),
  ADD KEY `idx_word_id` (`word_id`);

--
-- 表的索引 `outfits`
--
ALTER TABLE `outfits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_user_outfit_name` (`user_id`,`name`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_favorite` (`is_favorite`);

--
-- 表的索引 `outfit_items`
--
ALTER TABLE `outfit_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_outfit_layer` (`outfit_id`,`layer_code`),
  ADD KEY `image_id` (`image_id`);

--
-- 表的索引 `private_messages`
--
ALTER TABLE `private_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_private_messages_sender` (`sender_id`),
  ADD KEY `idx_private_messages_receiver` (`receiver_id`),
  ADD KEY `idx_private_messages_created_at` (`created_at`);

--
-- 表的索引 `private_message_media`
--
ALTER TABLE `private_message_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `fk_private_message_media_message` (`message_id`);

--
-- 表的索引 `ted_blank_answers`
--
ALTER TABLE `ted_blank_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD UNIQUE KEY `uk_gapfill_question` (`gapfill_text_id`,`question_no`);

--
-- 表的索引 `ted_gapfill_texts`
--
ALTER TABLE `ted_gapfill_texts`
  ADD PRIMARY KEY (`gapfill_text_id`),
  ADD UNIQUE KEY `transcript_id` (`transcript_id`);

--
-- 表的索引 `ted_talks`
--
ALTER TABLE `ted_talks`
  ADD PRIMARY KEY (`ted_id`),
  ADD UNIQUE KEY `uk_ted_title_subtitle_mode` (`title`,`subtitle_mode`),
  ADD KEY `idx_ted_title` (`title`),
  ADD KEY `idx_ted_speaker` (`speaker`);

--
-- 表的索引 `ted_transcripts`
--
ALTER TABLE `ted_transcripts`
  ADD PRIMARY KEY (`transcript_id`),
  ADD UNIQUE KEY `ted_id` (`ted_id`);

--
-- 表的索引 `ted_watch_history`
--
ALTER TABLE `ted_watch_history`
  ADD PRIMARY KEY (`watch_id`),
  ADD KEY `idx_watch_user` (`user_id`),
  ADD KEY `idx_watch_ted` (`ted_id`);

--
-- 表的索引 `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `user_annotations`
--
ALTER TABLE `user_annotations`
  ADD PRIMARY KEY (`annotation_id`),
  ADD KEY `idx_annotations_user` (`user_id`),
  ADD KEY `idx_annotations_article` (`article_id`);

--
-- 表的索引 `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `uk_user_article_fav` (`user_id`,`article_id`),
  ADD KEY `idx_favorites_user` (`user_id`),
  ADD KEY `idx_favorites_article` (`article_id`);

--
-- 表的索引 `user_follows`
--
ALTER TABLE `user_follows`
  ADD PRIMARY KEY (`follow_id`),
  ADD UNIQUE KEY `uk_user_follow` (`follower_id`,`followed_id`),
  ADD KEY `idx_user_follows_follower` (`follower_id`),
  ADD KEY `idx_user_follows_followed` (`followed_id`);

--
-- 表的索引 `user_friendships`
--
ALTER TABLE `user_friendships`
  ADD PRIMARY KEY (`friendship_id`),
  ADD UNIQUE KEY `uk_user_friendship` (`user_id_1`,`user_id_2`),
  ADD KEY `idx_user_friendships_user1` (`user_id_1`),
  ADD KEY `idx_user_friendships_user2` (`user_id_2`);

--
-- 表的索引 `user_game_unlocks`
--
ALTER TABLE `user_game_unlocks`
  ADD PRIMARY KEY (`unlock_id`),
  ADD UNIQUE KEY `uk_user_unlock` (`user_id`,`game_code`,`unlock_type`,`unlock_key`);

--
-- 表的索引 `user_ielts_attempts`
--
ALTER TABLE `user_ielts_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_ielts_attempt_user` (`user_id`),
  ADD KEY `idx_ielts_attempt_part` (`part_id`);

--
-- 表的索引 `user_reading_progress`
--
ALTER TABLE `user_reading_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD UNIQUE KEY `uk_user_article` (`user_id`,`article_id`),
  ADD KEY `idx_progress_user` (`user_id`),
  ADD KEY `idx_progress_article` (`article_id`);

--
-- 表的索引 `user_speaking_attempts`
--
ALTER TABLE `user_speaking_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_speaking_user` (`user_id`);

--
-- 表的索引 `user_ted_attempts`
--
ALTER TABLE `user_ted_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `idx_ted_attempt_user` (`user_id`),
  ADD KEY `idx_ted_attempt_transcript` (`transcript_id`);

--
-- 表的索引 `vocabulary_notebooks`
--
ALTER TABLE `vocabulary_notebooks`
  ADD PRIMARY KEY (`notebook_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- 表的索引 `words`
--
ALTER TABLE `words`
  ADD PRIMARY KEY (`word_id`),
  ADD UNIQUE KEY `english_word` (`english_word`),
  ADD KEY `idx_word_english` (`english_word`);

--
-- 表的索引 `word_search_records`
--
ALTER TABLE `word_search_records`
  ADD PRIMARY KEY (`search_id`),
  ADD KEY `idx_search_user` (`user_id`),
  ADD KEY `idx_search_word` (`word_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `articles`
--
ALTER TABLE `articles`
  MODIFY `article_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `checkin_calendars`
--
ALTER TABLE `checkin_calendars`
  MODIFY `calendar_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `coin_ledger`
--
ALTER TABLE `coin_ledger`
  MODIFY `ledger_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `coin_reward_rules`
--
ALTER TABLE `coin_reward_rules`
  MODIFY `rule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `conflict_rules`
--
ALTER TABLE `conflict_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `daily_checkin_records`
--
ALTER TABLE `daily_checkin_records`
  MODIFY `record_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `daily_talks`
--
ALTER TABLE `daily_talks`
  MODIFY `daily_talk_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `forum_comments`
--
ALTER TABLE `forum_comments`
  MODIFY `comment_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `forum_comment_media`
--
ALTER TABLE `forum_comment_media`
  MODIFY `media_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `post_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `forum_post_media`
--
ALTER TABLE `forum_post_media`
  MODIFY `media_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ielts_answers`
--
ALTER TABLE `ielts_answers`
  MODIFY `answer_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ielts_listening_parts`
--
ALTER TABLE `ielts_listening_parts`
  MODIFY `part_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ielts_part_images`
--
ALTER TABLE `ielts_part_images`
  MODIFY `image_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `images`
--
ALTER TABLE `images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `layers`
--
ALTER TABLE `layers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `memory_flip_logs`
--
ALTER TABLE `memory_flip_logs`
  MODIFY `flip_log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `memory_game_modes`
--
ALTER TABLE `memory_game_modes`
  MODIFY `mode_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `memory_matches`
--
ALTER TABLE `memory_matches`
  MODIFY `match_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `memory_match_players`
--
ALTER TABLE `memory_match_players`
  MODIFY `match_player_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `memory_match_word_pairs`
--
ALTER TABLE `memory_match_word_pairs`
  MODIFY `pair_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `memory_player_cards`
--
ALTER TABLE `memory_player_cards`
  MODIFY `card_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `memory_player_profiles`
--
ALTER TABLE `memory_player_profiles`
  MODIFY `profile_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `memory_player_turns`
--
ALTER TABLE `memory_player_turns`
  MODIFY `turn_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_answer_logs`
--
ALTER TABLE `miner_answer_logs`
  MODIFY `answer_log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_collection_entries`
--
ALTER TABLE `miner_collection_entries`
  MODIFY `collection_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_garden_plants`
--
ALTER TABLE `miner_garden_plants`
  MODIFY `plant_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_item_types`
--
ALTER TABLE `miner_item_types`
  MODIFY `item_type_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_maps`
--
ALTER TABLE `miner_maps`
  MODIFY `map_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_map_ore_rules`
--
ALTER TABLE `miner_map_ore_rules`
  MODIFY `rule_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_matches`
--
ALTER TABLE `miner_matches`
  MODIFY `match_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_match_ores`
--
ALTER TABLE `miner_match_ores`
  MODIFY `match_ore_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_match_ore_words`
--
ALTER TABLE `miner_match_ore_words`
  MODIFY `ore_word_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_match_players`
--
ALTER TABLE `miner_match_players`
  MODIFY `match_player_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_ore_types`
--
ALTER TABLE `miner_ore_types`
  MODIFY `ore_type_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_player_collections`
--
ALTER TABLE `miner_player_collections`
  MODIFY `player_collection_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_player_garden_plants`
--
ALTER TABLE `miner_player_garden_plants`
  MODIFY `player_plant_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_player_items`
--
ALTER TABLE `miner_player_items`
  MODIFY `player_item_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_player_ore_attempts`
--
ALTER TABLE `miner_player_ore_attempts`
  MODIFY `attempt_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_player_profiles`
--
ALTER TABLE `miner_player_profiles`
  MODIFY `profile_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_player_upgrades`
--
ALTER TABLE `miner_player_upgrades`
  MODIFY `player_upgrade_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_upgrade_level_costs`
--
ALTER TABLE `miner_upgrade_level_costs`
  MODIFY `cost_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `miner_upgrade_types`
--
ALTER TABLE `miner_upgrade_types`
  MODIFY `upgrade_type_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `notebook_word_records`
--
ALTER TABLE `notebook_word_records`
  MODIFY `notebook_word_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `outfits`
--
ALTER TABLE `outfits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `outfit_items`
--
ALTER TABLE `outfit_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `private_messages`
--
ALTER TABLE `private_messages`
  MODIFY `message_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `private_message_media`
--
ALTER TABLE `private_message_media`
  MODIFY `media_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ted_blank_answers`
--
ALTER TABLE `ted_blank_answers`
  MODIFY `answer_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ted_gapfill_texts`
--
ALTER TABLE `ted_gapfill_texts`
  MODIFY `gapfill_text_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ted_talks`
--
ALTER TABLE `ted_talks`
  MODIFY `ted_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ted_transcripts`
--
ALTER TABLE `ted_transcripts`
  MODIFY `transcript_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ted_watch_history`
--
ALTER TABLE `ted_watch_history`
  MODIFY `watch_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_annotations`
--
ALTER TABLE `user_annotations`
  MODIFY `annotation_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `favorite_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_follows`
--
ALTER TABLE `user_follows`
  MODIFY `follow_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_friendships`
--
ALTER TABLE `user_friendships`
  MODIFY `friendship_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_game_unlocks`
--
ALTER TABLE `user_game_unlocks`
  MODIFY `unlock_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_ielts_attempts`
--
ALTER TABLE `user_ielts_attempts`
  MODIFY `attempt_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_reading_progress`
--
ALTER TABLE `user_reading_progress`
  MODIFY `progress_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_speaking_attempts`
--
ALTER TABLE `user_speaking_attempts`
  MODIFY `attempt_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `user_ted_attempts`
--
ALTER TABLE `user_ted_attempts`
  MODIFY `attempt_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `vocabulary_notebooks`
--
ALTER TABLE `vocabulary_notebooks`
  MODIFY `notebook_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `words`
--
ALTER TABLE `words`
  MODIFY `word_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `word_search_records`
--
ALTER TABLE `word_search_records`
  MODIFY `search_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- 限制导出的表
--

--
-- 限制表 `checkin_calendars`
--
ALTER TABLE `checkin_calendars`
  ADD CONSTRAINT `fk_calendar_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `coin_ledger`
--
ALTER TABLE `coin_ledger`
  ADD CONSTRAINT `fk_coin_ledger_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `coin_wallets`
--
ALTER TABLE `coin_wallets`
  ADD CONSTRAINT `fk_coin_wallets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `daily_checkin_records`
--
ALTER TABLE `daily_checkin_records`
  ADD CONSTRAINT `fk_checkin_calendar` FOREIGN KEY (`calendar_id`) REFERENCES `checkin_calendars` (`calendar_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `forum_comments`
--
ALTER TABLE `forum_comments`
  ADD CONSTRAINT `fk_forum_comments_parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `forum_comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_forum_comments_post` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_forum_comments_reply_to_user` FOREIGN KEY (`reply_to_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_forum_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `forum_comment_media`
--
ALTER TABLE `forum_comment_media`
  ADD CONSTRAINT `fk_forum_comment_media_comment` FOREIGN KEY (`comment_id`) REFERENCES `forum_comments` (`comment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD CONSTRAINT `fk_forum_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `forum_post_media`
--
ALTER TABLE `forum_post_media`
  ADD CONSTRAINT `fk_forum_post_media_post` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `ielts_answers`
--
ALTER TABLE `ielts_answers`
  ADD CONSTRAINT `fk_ielts_answer_part` FOREIGN KEY (`part_id`) REFERENCES `ielts_listening_parts` (`part_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `ielts_part_images`
--
ALTER TABLE `ielts_part_images`
  ADD CONSTRAINT `fk_ielts_image_part` FOREIGN KEY (`part_id`) REFERENCES `ielts_listening_parts` (`part_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`layer_code`) REFERENCES `layers` (`code`) ON DELETE CASCADE;

--
-- 限制表 `memory_flip_logs`
--
ALTER TABLE `memory_flip_logs`
  ADD CONSTRAINT `fk_memory_flip_card` FOREIGN KEY (`card_id`) REFERENCES `memory_player_cards` (`card_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memory_flip_match_player` FOREIGN KEY (`match_player_id`) REFERENCES `memory_match_players` (`match_player_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memory_flip_turn` FOREIGN KEY (`turn_id`) REFERENCES `memory_player_turns` (`turn_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `memory_matches`
--
ALTER TABLE `memory_matches`
  ADD CONSTRAINT `fk_memory_matches_mode` FOREIGN KEY (`mode_id`) REFERENCES `memory_game_modes` (`mode_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memory_matches_winner` FOREIGN KEY (`winner_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- 限制表 `memory_match_players`
--
ALTER TABLE `memory_match_players`
  ADD CONSTRAINT `fk_memory_match_players_match` FOREIGN KEY (`match_id`) REFERENCES `memory_matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memory_match_players_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `memory_match_word_pairs`
--
ALTER TABLE `memory_match_word_pairs`
  ADD CONSTRAINT `fk_memory_pairs_match` FOREIGN KEY (`match_id`) REFERENCES `memory_matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memory_pairs_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`word_id`) ON UPDATE CASCADE;

--
-- 限制表 `memory_player_cards`
--
ALTER TABLE `memory_player_cards`
  ADD CONSTRAINT `fk_memory_cards_match_player` FOREIGN KEY (`match_player_id`) REFERENCES `memory_match_players` (`match_player_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memory_cards_pair` FOREIGN KEY (`pair_id`) REFERENCES `memory_match_word_pairs` (`pair_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `memory_player_profiles`
--
ALTER TABLE `memory_player_profiles`
  ADD CONSTRAINT `fk_memory_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `memory_player_turns`
--
ALTER TABLE `memory_player_turns`
  ADD CONSTRAINT `fk_memory_turns_first_card` FOREIGN KEY (`first_card_id`) REFERENCES `memory_player_cards` (`card_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memory_turns_match_player` FOREIGN KEY (`match_player_id`) REFERENCES `memory_match_players` (`match_player_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_memory_turns_second_card` FOREIGN KEY (`second_card_id`) REFERENCES `memory_player_cards` (`card_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- 限制表 `miner_answer_logs`
--
ALTER TABLE `miner_answer_logs`
  ADD CONSTRAINT `fk_answer_logs_match` FOREIGN KEY (`match_id`) REFERENCES `miner_matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answer_logs_match_ore` FOREIGN KEY (`match_ore_id`) REFERENCES `miner_match_ores` (`match_ore_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answer_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_answer_logs_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`word_id`) ON UPDATE CASCADE;

--
-- 限制表 `miner_collection_entries`
--
ALTER TABLE `miner_collection_entries`
  ADD CONSTRAINT `fk_collection_ore` FOREIGN KEY (`ore_type_id`) REFERENCES `miner_ore_types` (`ore_type_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_map_ore_rules`
--
ALTER TABLE `miner_map_ore_rules`
  ADD CONSTRAINT `fk_map_ore_rules_map` FOREIGN KEY (`map_id`) REFERENCES `miner_maps` (`map_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_map_ore_rules_ore` FOREIGN KEY (`ore_type_id`) REFERENCES `miner_ore_types` (`ore_type_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_matches`
--
ALTER TABLE `miner_matches`
  ADD CONSTRAINT `fk_miner_matches_map` FOREIGN KEY (`map_id`) REFERENCES `miner_maps` (`map_id`) ON UPDATE CASCADE;

--
-- 限制表 `miner_match_ores`
--
ALTER TABLE `miner_match_ores`
  ADD CONSTRAINT `fk_match_ores_match` FOREIGN KEY (`match_id`) REFERENCES `miner_matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_match_ores_type` FOREIGN KEY (`ore_type_id`) REFERENCES `miner_ore_types` (`ore_type_id`) ON UPDATE CASCADE;

--
-- 限制表 `miner_match_ore_words`
--
ALTER TABLE `miner_match_ore_words`
  ADD CONSTRAINT `fk_ore_words_match_ore` FOREIGN KEY (`match_ore_id`) REFERENCES `miner_match_ores` (`match_ore_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ore_words_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`word_id`) ON UPDATE CASCADE;

--
-- 限制表 `miner_match_players`
--
ALTER TABLE `miner_match_players`
  ADD CONSTRAINT `fk_match_players_match` FOREIGN KEY (`match_id`) REFERENCES `miner_matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_match_players_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_player_collections`
--
ALTER TABLE `miner_player_collections`
  ADD CONSTRAINT `fk_player_collections_collection` FOREIGN KEY (`collection_id`) REFERENCES `miner_collection_entries` (`collection_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_player_collections_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_player_garden_plants`
--
ALTER TABLE `miner_player_garden_plants`
  ADD CONSTRAINT `fk_player_garden_plant` FOREIGN KEY (`plant_id`) REFERENCES `miner_garden_plants` (`plant_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_player_garden_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_player_items`
--
ALTER TABLE `miner_player_items`
  ADD CONSTRAINT `fk_player_items_type` FOREIGN KEY (`item_type_id`) REFERENCES `miner_item_types` (`item_type_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_player_items_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_player_ore_attempts`
--
ALTER TABLE `miner_player_ore_attempts`
  ADD CONSTRAINT `fk_player_ore_attempts_match` FOREIGN KEY (`match_id`) REFERENCES `miner_matches` (`match_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_player_ore_attempts_match_ore` FOREIGN KEY (`match_ore_id`) REFERENCES `miner_match_ores` (`match_ore_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_player_ore_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_player_profiles`
--
ALTER TABLE `miner_player_profiles`
  ADD CONSTRAINT `fk_miner_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_player_upgrades`
--
ALTER TABLE `miner_player_upgrades`
  ADD CONSTRAINT `fk_player_upgrades_type` FOREIGN KEY (`upgrade_type_id`) REFERENCES `miner_upgrade_types` (`upgrade_type_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_player_upgrades_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `miner_upgrade_level_costs`
--
ALTER TABLE `miner_upgrade_level_costs`
  ADD CONSTRAINT `fk_upgrade_level_costs_type` FOREIGN KEY (`upgrade_type_id`) REFERENCES `miner_upgrade_types` (`upgrade_type_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `notebook_word_records`
--
ALTER TABLE `notebook_word_records`
  ADD CONSTRAINT `fk_notebook_word_notebook` FOREIGN KEY (`notebook_id`) REFERENCES `vocabulary_notebooks` (`notebook_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notebook_word_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`word_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `outfits`
--
ALTER TABLE `outfits`
  ADD CONSTRAINT `outfits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `outfit_items`
--
ALTER TABLE `outfit_items`
  ADD CONSTRAINT `outfit_items_ibfk_1` FOREIGN KEY (`outfit_id`) REFERENCES `outfits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `outfit_items_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`);

--
-- 限制表 `private_messages`
--
ALTER TABLE `private_messages`
  ADD CONSTRAINT `fk_private_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_private_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `private_message_media`
--
ALTER TABLE `private_message_media`
  ADD CONSTRAINT `fk_private_message_media_message` FOREIGN KEY (`message_id`) REFERENCES `private_messages` (`message_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `ted_blank_answers`
--
ALTER TABLE `ted_blank_answers`
  ADD CONSTRAINT `fk_answer_gapfill_text` FOREIGN KEY (`gapfill_text_id`) REFERENCES `ted_gapfill_texts` (`gapfill_text_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `ted_gapfill_texts`
--
ALTER TABLE `ted_gapfill_texts`
  ADD CONSTRAINT `fk_gapfill_transcript` FOREIGN KEY (`transcript_id`) REFERENCES `ted_transcripts` (`transcript_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `ted_transcripts`
--
ALTER TABLE `ted_transcripts`
  ADD CONSTRAINT `fk_transcript_ted` FOREIGN KEY (`ted_id`) REFERENCES `ted_talks` (`ted_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `ted_watch_history`
--
ALTER TABLE `ted_watch_history`
  ADD CONSTRAINT `fk_watch_ted` FOREIGN KEY (`ted_id`) REFERENCES `ted_talks` (`ted_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_watch_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `user_annotations`
--
ALTER TABLE `user_annotations`
  ADD CONSTRAINT `user_annotations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_annotations_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE;

--
-- 限制表 `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE;

--
-- 限制表 `user_follows`
--
ALTER TABLE `user_follows`
  ADD CONSTRAINT `fk_user_follows_followed` FOREIGN KEY (`followed_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_follows_follower` FOREIGN KEY (`follower_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `user_friendships`
--
ALTER TABLE `user_friendships`
  ADD CONSTRAINT `fk_user_friendships_user1` FOREIGN KEY (`user_id_1`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_friendships_user2` FOREIGN KEY (`user_id_2`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `user_game_unlocks`
--
ALTER TABLE `user_game_unlocks`
  ADD CONSTRAINT `fk_user_unlocks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- 限制表 `user_ielts_attempts`
--
ALTER TABLE `user_ielts_attempts`
  ADD CONSTRAINT `fk_user_ielts_attempt_part` FOREIGN KEY (`part_id`) REFERENCES `ielts_listening_parts` (`part_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_ielts_attempt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `user_reading_progress`
--
ALTER TABLE `user_reading_progress`
  ADD CONSTRAINT `user_reading_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_reading_progress_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE;

--
-- 限制表 `user_speaking_attempts`
--
ALTER TABLE `user_speaking_attempts`
  ADD CONSTRAINT `fk_speaking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `user_ted_attempts`
--
ALTER TABLE `user_ted_attempts`
  ADD CONSTRAINT `fk_user_ted_attempt_transcript` FOREIGN KEY (`transcript_id`) REFERENCES `ted_transcripts` (`transcript_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_ted_attempt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `vocabulary_notebooks`
--
ALTER TABLE `vocabulary_notebooks`
  ADD CONSTRAINT `fk_notebook_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- 限制表 `word_search_records`
--
ALTER TABLE `word_search_records`
  ADD CONSTRAINT `fk_search_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_search_word` FOREIGN KEY (`word_id`) REFERENCES `words` (`word_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
