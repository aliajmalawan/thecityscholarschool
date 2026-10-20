-- ---------------------------------------------------------------------------
-- The City Scholars School, Bandhi: contact details and location for search
--
-- Run once in phpMyAdmin (SQL tab). Safe to run again. The same values can also
-- be edited later in AdminCP -> Settings and AdminCP -> Page Content.
-- Office hours are left empty until the school's real timings are known.
-- ---------------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('site_address', 'Near Railway Tower, Bandhi\nShaheed Benazirabad, Sindh 67400\nPakistan'),
    ('site_phone', '0304 0900000'),
    ('site_email', 'thecssofficial@gmail.com'),
    ('seo_title_suffix', 'Bandhi, Sindh'),
    ('home_hero_eyebrow', 'Welcome to The City Scholars School, Bandhi'),
    ('seo_home_description', 'The City Scholars School, Bandhi (Shaheed Benazirabad, Sindh): quality education, caring teachers and a strong focus on character and academic excellence.')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
