-- ---------------------------------------------------------------------------
-- Rebrand: Tameer-e-Nau Public School -> The City Scholars School
--
-- Run once in phpMyAdmin (SQL tab) on the site's database. Safe to run again:
-- every statement only changes rows that still carry the old branding.
--
-- What it does
--   1. Adds the "The City Scholars School" theme (navy #123F73 + gold #F8B923)
--      and makes it the active theme; every theme now points at the new crest.
--   2. Renames the school in Settings, home slides, leadership messages and news.
--   3. Rewrites text that described the old candle logo, and removes old
--      settings nothing on the site shows any more (candle / Cambridge texts).
--   4. Clears contact details that belonged to another school
--      (the shaheenschool Facebook page and the sphs.pk email).
--
-- Not changed: messages, admissions and reviews that visitors submitted.
-- ---------------------------------------------------------------------------

-- 1. Theme ------------------------------------------------------------------
INSERT INTO `themes` (`theme_name`, `primary_color`, `secondary_color`, `accent_color`, `logo_path`, `is_active`)
    SELECT 'The City Scholars School', '#123F73', '#FFFFFF', '#F8B923', 'images/city-scholars-crest.png', 0
    FROM DUAL
    WHERE NOT EXISTS (SELECT 1 FROM `themes` WHERE `theme_name` = 'The City Scholars School');

UPDATE `themes` SET `is_active` = (`theme_name` = 'The City Scholars School');

UPDATE `themes` SET `logo_path` = 'images/city-scholars-crest.png'
    WHERE `logo_path` IN ('images/tameer-e-nau-logo.png', 'images/tameer-e-nau-logo-sm.png', '');

-- The old olive/maroon theme: its maroon accent doesn't suit the navy-on-gold buttons
DELETE FROM `themes` WHERE `theme_name` = 'Tameer-e-Nau Olive' AND `is_active` = 0;

-- 2. School name everywhere it is stored as text ---------------------------------
UPDATE `settings` SET `setting_value` = 'The City Scholars School' WHERE `setting_key` = 'site_name';

UPDATE `settings`
    SET `setting_value` = REPLACE(REPLACE(`setting_value`, 'Tameer-e-Nau Public School', 'The City Scholars School'), 'Tameer-e-Nau', 'The City Scholars School')
    WHERE `setting_value` LIKE '%Tameer-e-Nau%';

UPDATE `hero_carousel`
    SET `title` = REPLACE(REPLACE(`title`, 'Tameer-e-Nau Public School', 'The City Scholars School'), 'Tameer-e-Nau', 'The City Scholars School'),
        `subtitle` = REPLACE(REPLACE(`subtitle`, 'Tameer-e-Nau Public School', 'The City Scholars School'), 'Tameer-e-Nau', 'The City Scholars School')
    WHERE `title` LIKE '%Tameer-e-Nau%' OR `subtitle` LIKE '%Tameer-e-Nau%';

UPDATE `leadership`
    SET `message` = REPLACE(REPLACE(`message`, 'Tameer-e-Nau Public School', 'The City Scholars School'), 'Tameer-e-Nau', 'The City Scholars School')
    WHERE `message` LIKE '%Tameer-e-Nau%';

UPDATE `news`
    SET `title` = REPLACE(REPLACE(`title`, 'Tameer-e-Nau Public School', 'The City Scholars School'), 'Tameer-e-Nau', 'The City Scholars School'),
        `content` = REPLACE(REPLACE(`content`, 'Tameer-e-Nau Public School', 'The City Scholars School'), 'Tameer-e-Nau', 'The City Scholars School')
    WHERE `title` LIKE '%Tameer-e-Nau%' OR `content` LIKE '%Tameer-e-Nau%';

UPDATE `events`
    SET `title` = REPLACE(REPLACE(`title`, 'Tameer-e-Nau Public School', 'The City Scholars School'), 'Tameer-e-Nau', 'The City Scholars School'),
        `description` = REPLACE(REPLACE(`description`, 'Tameer-e-Nau Public School', 'The City Scholars School'), 'Tameer-e-Nau', 'The City Scholars School')
    WHERE `title` LIKE '%Tameer-e-Nau%' OR `description` LIKE '%Tameer-e-Nau%';

-- 3. Text about the old candle logo -> the City Scholars crest ---------------------
UPDATE `settings`
    SET `setting_value` = 'Our crest says what we stand for: the open book for knowledge, the graduation cap for achievement, the torch for the light of learning and the star for excellence. We believe in a complete education that builds academic strength alongside character, values and life skills.'
    WHERE `setting_key` = 'about_paragraph_2' AND `setting_value` LIKE '%candle%';

UPDATE `settings`
    SET `setting_value` = 'Inspiring Scholars, Shaping Futures'
    WHERE `setting_key` = 'hero_title' AND `setting_value` LIKE '%Candle%';

UPDATE `settings`
    SET `setting_value` = REPLACE(`setting_value`, 'let us light the candle of knowledge and illuminate the path to success', 'let us carry the torch of knowledge and light the path to success')
    WHERE `setting_key` = 'principal_message_para_2';

-- Old texts no page shows any more (the candle symbol section and the Cambridge EdTech paragraphs)
DELETE FROM `settings`
    WHERE `setting_key` IN ('candle_paragraph_1', 'candle_paragraph_2', 'candle_paragraph_3',
                            'cambridge_paragraph_1', 'cambridge_paragraph_2', 'cambridge_paragraph_3', 'cambridge_highlight');

-- 4. Contact details that belonged to another school ---------------------------------
UPDATE `settings` SET `setting_value` = '' WHERE `setting_key` = 'facebook_url' AND `setting_value` LIKE '%shaheenschool%';
UPDATE `settings` SET `setting_value` = '' WHERE `setting_key` = 'site_email' AND `setting_value` LIKE 'sphs.%';
