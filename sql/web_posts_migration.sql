-- ============================================
-- Web Posts Migration Script
-- Version: 2.1 (Added Sub-Categories)
-- Created: 2026-02-27
-- ============================================

-- Create web_post_categories table
CREATE TABLE IF NOT EXISTS `web_post_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Category ID',
    `parent_id` INT NULL DEFAULT NULL COMMENT 'Parent Category ID (NULL = top-level)',
    `name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Category Name',
    `slug` VARCHAR(100) NOT NULL UNIQUE COMMENT 'URL-friendly slug',
    `description` TEXT COMMENT 'Category Description',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Timestamp',
    KEY `idx_parent_id` (`parent_id`),
    CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `web_post_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Post Categories';

-- Upgrade from v2.0: add parent_id if column not yet added
-- (skip if running fresh install above already created it)
ALTER TABLE `web_post_categories`
    ADD COLUMN IF NOT EXISTS `parent_id` INT NULL DEFAULT NULL AFTER `id`,
    ADD KEY IF NOT EXISTS `idx_parent_id` (`parent_id`);
-- Note: add FK manually if needed:
-- ALTER TABLE `web_post_categories` ADD CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `web_post_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Create web_posts table for blog/news posts management
CREATE TABLE IF NOT EXISTS `web_posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY COMMENT 'Post ID',
    `title` VARCHAR(255) NOT NULL COMMENT 'Post Title',
    `content` LONGTEXT NOT NULL COMMENT 'Post Content (supports HTML)',
    `category_id` INT COMMENT 'Category ID (Foreign Key)',
    `status` ENUM('published', 'draft') DEFAULT 'published' COMMENT 'Post Status',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation Timestamp',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last Update Timestamp',
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_category_id` (`category_id`),
    CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `web_post_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Posts and News Management';

-- Default categories
INSERT IGNORE INTO `web_post_categories` (`id`, `name`, `slug`, `description`) VALUES
(1, 'Update Patches', 'update-patches', 'ประกาศอัปเดตแพทช์และการปรับปรุงเซิร์ฟเวอร์'),
(2, 'Event Activities', 'event-activities', 'กิจกรรมและอีเวนต์ภายในเกม');

-- Insert sample data (optional - remove if not needed)
-- INSERT INTO `web_posts` (`title`, `content`, `status`, `created_at`) VALUES
-- ('ยินดีต้อนรับ', 'คำต้อนรับสู่ RO Village\n\nนี่เป็นระบบจัดการข่าวสารของเซิร์ฟเวอร์ Ragnarok Online ส่วนตัว', 'published', NOW()),
-- ('ประกาศการแจกไอเทม', 'ทุกวันศุกร์เวลา 20:00 จะมีการแจกไอเทมให้กับผู้เล่น', 'published', NOW());

-- Verify the table creation
-- SELECT TABLE_NAME, ENGINE, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'web_posts' AND TABLE_SCHEMA = DATABASE();