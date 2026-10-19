-- Adds YouTube videos to the Photo Gallery (AdminCP > Gallery Videos).
-- Run once in phpMyAdmin (select the site's database > SQL tab > Go).
-- Safe to run again: the table is only created when it doesn't exist.

CREATE TABLE IF NOT EXISTS `gallery_videos` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(200) NOT NULL,
    `youtube_id` VARCHAR(20) NOT NULL,
    `description` VARCHAR(300) NULL,
    `display_order` INT(11) NOT NULL DEFAULT 0,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
