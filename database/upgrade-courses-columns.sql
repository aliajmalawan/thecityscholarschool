-- Adds the course "stage" and "subjects" columns that courses.php and
-- AdminCP > Courses use. Without them the Courses page shows PHP warnings
-- and saving a course fails. (Also included in database/live-update.sql.)
--
-- Run once in phpMyAdmin (select the site's database > SQL tab > Go).
-- Safe to run again; works on MySQL 5.7 / 8 and MariaDB.

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
