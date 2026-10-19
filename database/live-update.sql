-- =====================================================================
-- Tameer-e-Nau Public School: live database update
--
-- How to run: cPanel > phpMyAdmin > click the website's database on the
-- left > "SQL" tab > paste this whole file > "Go".
--
-- Safe to run more than once: every step checks first and only changes
-- what is still missing or still says "Shaheen". Nothing is deleted.
-- Works on MySQL 5.7 / 8 and MariaDB.
-- Tip: Export the database first (phpMyAdmin > Export > Go) as a backup.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- PART 1: New tables and columns the updated code needs
-- ---------------------------------------------------------------------

-- Courses: stage (Early Years / Primary / Middle / Matric) and subject list
SET @add_col = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `level` VARCHAR(20) NOT NULL DEFAULT \'\' AFTER `duration`',
    'SELECT ''courses.level already exists''')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'level');
PREPARE stmt FROM @add_col; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_col = (SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `courses` ADD COLUMN `subjects` TEXT NULL AFTER `level`',
    'SELECT ''courses.subjects already exists''')
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME = 'subjects');
PREPARE stmt FROM @add_col; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- YouTube videos on the Photo Gallery page (Admin > Gallery Videos)
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

-- Admin login protection (blocks an address after 5 wrong passwords in 15 minutes)
CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ip` VARCHAR(45) NOT NULL,
    `username` VARCHAR(50) NOT NULL DEFAULT '',
    `attempted_at` DATETIME NOT NULL,
    KEY `ip_time` (`ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PART 2: School name, address, colours and logo
-- ---------------------------------------------------------------------

UPDATE `settings` SET `setting_value` = 'Tameer-e-Nau Public School' WHERE `setting_key` = 'site_name';
UPDATE `settings` SET `setting_value` = 'Tameer-e-Nau Public School. All rights reserved.' WHERE `setting_key` = 'copyright_text';
UPDATE `settings` SET `setting_value` = 'Canal View, Hasilpur
Pakistan'
    WHERE `setting_key` = 'site_address' AND `setting_value` LIKE '%Sadiqabad%';

-- Old school name inside texts (about, mission, vision, footer, messages, news, reviews, slider)
UPDATE `settings` SET `setting_value` = REPLACE(REPLACE(REPLACE(`setting_value`,
    'SHAHEEN PUBLIC HIGH SCHOOL', 'Tameer-e-Nau Public School'),
    'Shaheen Public High School', 'Tameer-e-Nau Public School'),
    'Shaheen Public High Schoo', 'Tameer-e-Nau Public School')
    WHERE `setting_value` LIKE '%Shaheen Public High%' AND `setting_key` NOT IN ('facebook_url', 'site_email');
UPDATE `leadership` SET `message` = REPLACE(REPLACE(`message`,
    'SHAHEEN PUBLIC HIGH SCHOOL', 'Tameer-e-Nau Public School'),
    'Shaheen Public High School', 'Tameer-e-Nau Public School')
    WHERE `message` LIKE '%Shaheen Public High%';
UPDATE `news` SET
    `title` = REPLACE(REPLACE(`title`, 'SHAHEEN PUBLIC HIGH SCHOOL', 'Tameer-e-Nau Public School'), 'Shaheen Public High School', 'Tameer-e-Nau Public School'),
    `content` = REPLACE(REPLACE(`content`, 'SHAHEEN PUBLIC HIGH SCHOOL', 'Tameer-e-Nau Public School'), 'Shaheen Public High School', 'Tameer-e-Nau Public School')
    WHERE `title` LIKE '%Shaheen Public High%' OR `content` LIKE '%Shaheen Public High%';
UPDATE `alumni_reviews` SET `review` = REPLACE(`review`, 'Shaheen Public High School', 'Tameer-e-Nau Public School')
    WHERE `review` LIKE '%Shaheen Public High School%';
UPDATE `hero_carousel` SET `title` = 'Welcome to Tameer-e-Nau Public School'
    WHERE `title` LIKE '%Shaheen%';

-- Home slider and hero photos: the Tameer-e-Nau photos that came with the new code
UPDATE `hero_carousel` SET `image_path` = CONCAT('images/hero/home-slide-', `display_order`, '.jpg')
    WHERE `display_order` BETWEEN 1 AND 3 AND `image_path` NOT LIKE 'images/hero/home-slide-%';
UPDATE `settings` SET `setting_value` = 'images/hero/home-slide-1.jpg'
    WHERE `setting_key` = 'hero_image' AND `setting_value` NOT LIKE 'images/hero/home-slide-%';

-- Colour theme and logo of Tameer-e-Nau (olive and maroon)
INSERT INTO `themes` (`theme_name`, `primary_color`, `secondary_color`, `accent_color`, `logo_path`, `is_active`)
    SELECT 'Tameer-e-Nau Olive', '#78864F', '#FFFFFF', '#B3262D', 'images/tameer-e-nau-logo.png', 0
    FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `themes` WHERE `theme_name` = 'Tameer-e-Nau Olive');
UPDATE `themes` SET `is_active` = IF(`theme_name` = 'Tameer-e-Nau Olive', 1, 0);
UPDATE `themes` SET `logo_path` = 'images/tameer-e-nau-logo.png' WHERE `theme_name` = 'Tameer-e-Nau Olive';

-- ---------------------------------------------------------------------
-- PART 3: Photo gallery - add the new Tameer-e-Nau photos
-- (photos already on the live site are kept; see the optional part below)
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `gallery_categories` (`name`, `slug`, `status`, `display_order`) VALUES
    ('Classroom Activities', 'classroom-activities', 'active', 1),
    ('School Events', 'school-events', 'active', 2);

-- (same column types and collation as the gallery table, so the comparison below works on any server)
CREATE TEMPORARY TABLE `tmp_new_photos` SELECT `title`, `image_path`, `category` FROM `gallery` LIMIT 0;
INSERT INTO `tmp_new_photos` (`title`, `image_path`, `category`) VALUES
    ('Classroom Activity', 'uploads/gallery/1791259525_6ac47385839c3.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259526_6ac473866fb78.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259527_6ac47387500a6.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259528_6ac4738829d2d.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259529_6ac473890f47c.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259529_6ac47389dc65b.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259530_6ac4738ad48ea.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259531_6ac4738bb1c26.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259532_6ac4738c816e9.jpg', 'classroom-activities'),
    ('School Event', 'uploads/gallery/1791259622_6ac473e617f04.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259624_6ac473e844936.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259625_6ac473e95129a.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259626_6ac473ea7328f.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259627_6ac473eb4fdf8.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259628_6ac473ec7007c.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259629_6ac473ed9d579.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259630_6ac473ee8b4bf.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259631_6ac473ef5cace.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259632_6ac473f03bafa.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259632_6ac473f0d9bdf.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259633_6ac473f1b6722.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259635_6ac473f32e6dd.jpg', 'school-events'),
    ('School Event', 'uploads/gallery/1791259636_6ac473f449e85.jpg', 'school-events'),
    ('Classroom Activity', 'uploads/gallery/1791259717_6ac474451959b.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259718_6ac474463f0ca.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259719_6ac474478cafb.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259721_6ac474492cef2.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259721_6ac47449dfc70.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259722_6ac4744aa950e.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259723_6ac4744b9a8cb.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259724_6ac4744cb5d99.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259725_6ac4744d73a9d.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259726_6ac4744e3a166.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259727_6ac4744f2b616.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259727_6ac4744fe3180.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259728_6ac47450a8252.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259729_6ac474516f3b2.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259730_6ac474523d6d8.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259731_6ac474532a304.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259731_6ac47453e8c50.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259840_6ac474c0ab2a0.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259841_6ac474c1f06ed.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259843_6ac474c34aa7f.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259844_6ac474c4d72d0.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259845_6ac474c5a03a4.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259846_6ac474c69ad23.jpg', 'classroom-activities'),
    ('Classroom Activity', 'uploads/gallery/1791259847_6ac474c7af4c6.jpg', 'classroom-activities');
INSERT INTO `gallery` (`title`, `image_path`, `category`, `status`)
    SELECT t.`title`, t.`image_path`, t.`category`, 'active' FROM `tmp_new_photos` t
    WHERE NOT EXISTS (SELECT 1 FROM `gallery` g WHERE g.`image_path` = t.`image_path`);
DROP TEMPORARY TABLE `tmp_new_photos`;

-- ---------------------------------------------------------------------
-- OPTIONAL: hide the old Shaheen gallery albums (Annual Trip, Magic Show,
-- Cambridge orientation). They are only hidden, not deleted: you can show
-- them again from Admin > Gallery. Remove the two dashes at the start of
-- the two lines below to use this.
-- ---------------------------------------------------------------------
-- UPDATE `gallery_categories` SET `status` = 'inactive' WHERE `slug` IN ('annual-trip', 'magic-show-fun-day', 'cambridge-edtech-orientation');
-- UPDATE `gallery` SET `status` = 'inactive' WHERE `category` IN ('annual-trip', 'magic-show-fun-day', 'cambridge-edtech-orientation');

-- ---------------------------------------------------------------------
-- Check: what the site now uses
-- ---------------------------------------------------------------------
SELECT
    (SELECT `setting_value` FROM `settings` WHERE `setting_key` = 'site_name') AS school_name,
    (SELECT `theme_name` FROM `themes` WHERE `is_active` = 1 LIMIT 1) AS active_theme,
    (SELECT COUNT(*) FROM `gallery` WHERE `status` = 'active') AS gallery_photos,
    (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' AND COLUMN_NAME IN ('level', 'subjects')) AS new_course_columns,
    (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('gallery_videos', 'admin_login_attempts')) AS new_tables;
