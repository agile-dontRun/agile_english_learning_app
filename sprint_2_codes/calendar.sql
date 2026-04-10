SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `daily_checkin_records`;
DROP TABLE IF EXISTS `checkin_calendars`;

-- 1. 创建日历本表 (每个用户一个)
CREATE TABLE `checkin_calendars` (
  `calendar_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `calendar_name` varchar(100) DEFAULT 'My Check-in Calendar',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`calendar_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_calendar_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. 创建每日打卡记录表 (关联到具体的日历本)
CREATE TABLE `daily_checkin_records` (
  `record_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `calendar_id` bigint(20) NOT NULL,
  `checkin_date` date NOT NULL,
  `login_time` datetime DEFAULT NULL,
  `logout_time` datetime DEFAULT NULL,
  `study_minutes` int(11) NOT NULL DEFAULT '0',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`record_id`),
  UNIQUE KEY `uk_calendar_date` (`calendar_id`,`checkin_date`),
  KEY `idx_daily_checkin_date` (`checkin_date`),
  CONSTRAINT `fk_checkin_calendar` FOREIGN KEY (`calendar_id`) REFERENCES `checkin_calendars` (`calendar_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;