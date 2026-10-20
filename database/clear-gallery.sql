-- ---------------------------------------------------------------------------
-- Clear the photo gallery: removes every gallery photo record and every gallery
-- category. School videos (gallery_videos) are not touched.
--
-- Run once in phpMyAdmin (SQL tab). The photo files themselves were removed from
-- uploads/gallery in the code; on the live server also delete the files in
-- uploads/gallery (and its thumbs folder) with the cPanel File Manager.
--
-- After this the Gallery page, the homepage and the Campus Life page show their
-- "photos coming soon" states until new categories and photos are added in
-- AdminCP -> Gallery.
-- ---------------------------------------------------------------------------
DELETE FROM `gallery`;
DELETE FROM `gallery_categories`;
