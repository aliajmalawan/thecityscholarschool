<?php
/**
 * sitemap.xml (served through .htaccess). Built for whichever domain the site
 * runs on, so it never points search engines at localhost. Each page's
 * <lastmod> is the newest of the page file itself and the database content it shows.
 */
require_once 'includes/config.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

// Newest change in a table (0 when the table is empty or missing)
$latest = function ($table, $column = 'updated_at') use ($conn) {
    try {
        $result = mysqli_query($conn, "SELECT UNIX_TIMESTAMP(MAX(`$column`)) AS t FROM `$table`");
    } catch (mysqli_sql_exception $e) {
        return 0;   // table or column missing on this database
    }
    $row = $result ? mysqli_fetch_assoc($result) : null;
    return (int) ($row['t'] ?? 0);
};
$settings_changed = $latest('settings');

// slug => [priority, change frequency, tables whose content appears on the page]
$pages = [
    'index'       => ['1.0', 'weekly',  [['news', 'created_at'], ['events', 'updated_at'], ['hero_carousel', 'updated_at']]],
    'about'       => ['0.8', 'monthly', [['leadership', 'updated_at']]],
    'admission'   => ['0.9', 'monthly', [['fee_items', 'updated_at']]],
    'courses'     => ['0.9', 'monthly', [['courses', 'updated_at']]],
    'faculty'     => ['0.7', 'monthly', [['faculty', 'updated_at']]],
    'campuses'    => ['0.6', 'monthly', [['campuses', 'updated_at']]],
    'gallery'     => ['0.7', 'weekly',  [['gallery', 'updated_at']]],
    'events'      => ['0.8', 'weekly',  [['news', 'updated_at'], ['events', 'updated_at']]],
    'examination' => ['0.7', 'weekly',  [['exam_datesheets', 'created_at'], ['board_results', 'created_at']]],
    'downloads'   => ['0.5', 'monthly', [['downloads', 'created_at']]],
    'alumni'      => ['0.5', 'monthly', [['alumni', 'created_at']]],
    'contact'     => ['0.8', 'yearly',  []],
];

header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $slug => [$priority, $freq, $sources]) {
    $modified = max(@filemtime(__DIR__ . "/$slug.php") ?: 0, $settings_changed);
    foreach ($sources as [$table, $column]) {
        $modified = max($modified, $latest($table, $column));
    }
    echo "    <url>\n";
    echo '        <loc>' . htmlspecialchars($base . '/' . ($slug === 'index' ? '' : $slug)) . "</loc>\n";
    echo '        <lastmod>' . date('Y-m-d', $modified ?: time()) . "</lastmod>\n";
    echo "        <changefreq>$freq</changefreq>\n";
    echo "        <priority>$priority</priority>\n";
    echo "    </url>\n";
}
echo '</urlset>' . "\n";
