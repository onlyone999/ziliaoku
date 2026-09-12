-- ============================================
-- Admin Panel Migration
-- Ensures admin_users table exists with correct schema
-- MySQL 5.7 compatible, utf8mb4
-- ============================================

USE `ziliaoku`;

-- Admin users table (create if not exists)
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL COMMENT 'bcrypt hash',
  `realname` varchar(50) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `role` enum('super','admin','editor') NOT NULL DEFAULT 'admin',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '1=enabled 0=disabled',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin users table';

-- Default admin account (admin / admin123) - only if not exists
INSERT IGNORE INTO `admin_users` (`username`, `password`, `realname`, `role`, `status`)
VALUES ('admin', '$2y$10$HfzJhPXM.3M/D7AqIOTseOJGYgLPNd/VCKOwGJbKMIjVJkNIwyWQe', 'Super Admin', 'super', 1);
