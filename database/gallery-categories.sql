-- ---------------------------------------------------------------------------
-- Gallery categories for The City Scholars School
--
-- Run once in phpMyAdmin (SQL tab), after clear-gallery.sql. Safe to run again.
-- Slugs follow the same rule as AdminCP -> Gallery (lowercase, words joined by
-- hyphens). A category appears on the Gallery page, the homepage and the Campus
-- Life page as soon as it has at least one photo.
-- ---------------------------------------------------------------------------
INSERT INTO `gallery_categories` (`name`, `slug`, `display_order`, `status`) VALUES
    ('Awareness Classroom', 'awareness-classroom', 1, 'active'),
    ('Independence Day',    'independence-day',    2, 'active'),
    ('Result Day',          'result-day',          3, 'active'),
    ('Sindhi Culture Day',  'sindhi-culture-day',  4, 'active'),
    ('Student Achiever',    'student-achiever',    5, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `display_order` = VALUES(`display_order`), `status` = 'active';
