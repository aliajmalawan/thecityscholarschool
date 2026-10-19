-- Removes page views recorded from search-engine crawlers, link previewers and
-- scripts before the tracker started ignoring them, so the Website Analytics
-- numbers only count real visitors. Uses the same rules as isBotUserAgent()
-- in includes/analytics_helper.php.
--
-- Optional, run once in phpMyAdmin (select the site's database > SQL tab > Go).
-- It permanently deletes those rows; export the two tables first if you want a copy.

DELETE FROM `website_analytics`
WHERE `user_agent` IS NULL OR TRIM(`user_agent`) = ''
   OR `user_agent` REGEXP 'bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|monitor|uptime|headless|lighthouse|curl|wget|python|java/|go-http|okhttp|axios|node-fetch|postman|scrapy|httpclient|semrush|ahrefs|mj12|dotbot|petalbot|yandex|baiduspider|bingpreview';

DELETE FROM `unique_visitors`
WHERE `user_agent` IS NULL OR TRIM(`user_agent`) = ''
   OR `user_agent` REGEXP 'bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|monitor|uptime|headless|lighthouse|curl|wget|python|java/|go-http|okhttp|axios|node-fetch|postman|scrapy|httpclient|semrush|ahrefs|mj12|dotbot|petalbot|yandex|baiduspider|bingpreview';
