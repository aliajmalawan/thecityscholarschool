<?php
/**
 * robots.txt (served through .htaccess). The sitemap line uses the domain the
 * site is actually running on, so it is correct on localhost and on the live server.
 */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

header('Content-Type: text/plain; charset=UTF-8');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: $path/AdminCP/\n";
echo "Disallow: $path/includes/\n";
echo "Disallow: $path/database/\n";
echo "\n";
echo "Sitemap: $base/sitemap.xml\n";
