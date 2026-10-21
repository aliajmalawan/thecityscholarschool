-- ---------------------------------------------------------------------------
-- Homepage photos from the school's own gallery
--
-- Run once in phpMyAdmin (SQL tab). Safe to run again. Both photos are gallery
-- uploads that already exist on the server (uploads/gallery/), so nothing has to
-- be uploaded. Change them any time in AdminCP -> Page Content:
--   "Home: Hero & About" -> About section photo
--   "Home: Sections"     -> Photo band: photo
-- Note: if one of these photos is deleted from the gallery, choose another one there.
-- ---------------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('home_about_image', 'uploads/gallery/1792469848_6ad6eb585ad51.jpg'),   -- Independence Day: students with flags
    ('home_band_image',  'uploads/gallery/1792469912_6ad6eb98ef5ae.jpg')    -- Student Achievers in front of the school banner
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
