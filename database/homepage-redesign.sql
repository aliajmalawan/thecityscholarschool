-- ---------------------------------------------------------------------------
-- Homepage redesign content (The City Scholars School)
--
-- Run once in phpMyAdmin (SQL tab) after rebrand-city-scholars.sql. Safe to run
-- again: rows are only added when missing, and values are only cleared while they
-- still hold the figures and contact details inherited from the previous school.
--
--   1. Hero headline and introduction text.
--   2. "Why Choose Us": the old cards (Proven Results, Affordable Fees, Small Class
--      Sizes, ...) are switched off, not deleted, and six priority cards are added.
--   3. Facilities: suggested cards are added SWITCHED OFF. Turn on only the ones the
--      school really has (AdminCP -> Feature Cards -> Facilities).
--   4. Statistics (500 students, 50 teachers, ...) are cleared; the homepage band
--      appears again once real figures are entered under Settings -> Statistics.
--   5. Address, phone and office hours from the previous school are cleared; the
--      site shows a short "coming soon" note until Settings has the real details.
-- ---------------------------------------------------------------------------

-- 1. Hero -------------------------------------------------------------------
UPDATE `settings` SET `setting_value` = 'Inspiring Young Minds. Building Bright Futures.'
    WHERE `setting_key` = 'hero_title' AND `setting_value` IN ('Inspiring Scholars, Shaping Futures', 'Lighting the Candle of Knowledge', '');
UPDATE `settings` SET `setting_value` = 'At The City Scholars School, children learn in a caring, disciplined environment where strong academics and good character grow together.'
    WHERE `setting_key` = 'hero_description' AND `setting_value` LIKE 'Empowering students with quality education%';

-- 2. Why Choose Us ------------------------------------------------------------
UPDATE `feature_cards` SET `status` = 'inactive'
    WHERE `section` = 'why_choose_us'
      AND `title` IN ('Expert Faculty', 'Modern Facilities', 'Small Class Sizes', 'Quality Education', 'Proven Results', 'Affordable Fees');

INSERT INTO `feature_cards` (`section`, `icon`, `title`, `description`, `display_order`, `status`)
SELECT * FROM (
    SELECT 'why_choose_us' s, 'fas fa-children' i, 'Student-Centred Learning' t, 'Lessons planned around how children learn best, with room for questions, curiosity and individual attention.' d, 1 o, 'active' st UNION ALL
    SELECT 'why_choose_us', 'fas fa-chalkboard-user', 'Caring, Committed Teachers', 'Teachers who know their students well and guide each one with patience, encouragement and clear expectations.', 2, 'active' UNION ALL
    SELECT 'why_choose_us', 'fas fa-medal', 'Focus on Academic Excellence', 'Strong teaching in core subjects, regular assessment and clear feedback that helps every student keep improving.', 3, 'active' UNION ALL
    SELECT 'why_choose_us', 'fas fa-hand-holding-heart', 'Character Development', 'Honesty, respect, discipline and responsibility are part of everyday school life, not just a lesson.', 4, 'active' UNION ALL
    SELECT 'why_choose_us', 'fas fa-shield-heart', 'Safe Learning Environment', 'A welcoming, well-supervised campus where children feel secure, respected and ready to learn.', 5, 'active' UNION ALL
    SELECT 'why_choose_us', 'fas fa-futbol', 'Co-Curricular Activities', 'Sports, events and activities beyond the classroom that build confidence, teamwork and new interests.', 6, 'active'
) AS new_cards
WHERE NOT EXISTS (SELECT 1 FROM `feature_cards` f WHERE f.`section` = 'why_choose_us' AND f.`title` = new_cards.t);

-- 3. Facilities (added switched off) -------------------------------------------
INSERT INTO `feature_cards` (`section`, `icon`, `title`, `description`, `display_order`, `status`)
SELECT * FROM (
    SELECT 'facilities' s, 'fas fa-school' i, 'Classrooms' t, 'Bright, organised classrooms designed for focused learning.' d, 1 o, 'inactive' st UNION ALL
    SELECT 'facilities', 'fas fa-book', 'Library', 'A reading space with books for every age to build a love of reading.', 2, 'inactive' UNION ALL
    SELECT 'facilities', 'fas fa-flask', 'Science Laboratory', 'Hands-on experiments that bring science lessons to life.', 3, 'inactive' UNION ALL
    SELECT 'facilities', 'fas fa-computer', 'Computer Lab', 'Practical computing and digital skills for the modern world.', 4, 'inactive' UNION ALL
    SELECT 'facilities', 'fas fa-person-running', 'Sports & Games', 'Space and time for sport, fitness and team games.', 5, 'inactive' UNION ALL
    SELECT 'facilities', 'fas fa-palette', 'Arts & Activities', 'Art, competitions and events that let every talent shine.', 6, 'inactive'
) AS new_cards
WHERE NOT EXISTS (SELECT 1 FROM `feature_cards` f WHERE f.`section` = 'facilities' AND f.`title` = new_cards.t);

-- 4. Statistics inherited from the previous school --------------------------------
UPDATE `settings` SET `setting_value` = ''
    WHERE (`setting_key`, `setting_value`) IN (('stats_students', '500'), ('stats_teachers', '50'), ('stats_courses', '15'), ('stats_years', '10'));

-- 5. Contact details inherited from the previous school --------------------------
UPDATE `settings` SET `setting_value` = '' WHERE `setting_key` = 'site_address' AND `setting_value` LIKE 'Canal View, Hasilpur%';
UPDATE `settings` SET `setting_value` = '' WHERE `setting_key` = 'site_phone' AND `setting_value` = '03006700956';
UPDATE `settings` SET `setting_value` = '' WHERE `setting_key` = 'office_hours' AND `setting_value` = 'Mon - Sat: 8:00 AM - 5:00 PM';

-- 6. Home slider: slide titles are now photo descriptions for screen readers ------
UPDATE `hero_carousel` SET `title` = 'Students gathered for a school assembly' WHERE `image_path` = 'images/hero/home-slide-1.jpg' AND `title` IN ('Welcome to The City Scholars School', 'Welcome to Tameer-e-Nau Public School');
UPDATE `hero_carousel` SET `title` = 'Young students during a classroom activity' WHERE `image_path` = 'images/hero/home-slide-2.jpg' AND `title` = 'Excellence in Education';
UPDATE `hero_carousel` SET `title` = 'Students at a school celebration' WHERE `image_path` = 'images/hero/home-slide-3.jpg' AND `title` = 'Modern Learning Environment';
