<?php
// Visitors never see PHP error details (file paths, queries) on the live site; they go to the error log.
// On this computer (localhost) errors stay visible to help development.
$is_local_request = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) || PHP_SAPI === 'cli';
if (!$is_local_request) {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Session cookie: not readable by JavaScript, not sent with cross-site requests, HTTPS-only when the site uses HTTPS
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
}

// Database Configuration
// On Windows (XAMPP) "localhost" first tries IPv6 and can add ~2 seconds to every page;
// 127.0.0.1 connects straight away. Linux hosting (cPanel) keeps "localhost", its fast local socket.
// The live server keeps its own database name, user and password in includes/config.local.php.
// That file is never committed to Git, so deploying new code never overwrites it and the
// password never appears on GitHub. Copy includes/config.local.example.php to create it.
if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}
// Defaults for this computer (XAMPP), used when config.local.php doesn't set a value
defined('DB_HOST') || define('DB_HOST', PHP_OS_FAMILY === 'Windows' ? '127.0.0.1' : 'localhost');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');
defined('DB_NAME') || define('DB_NAME', 'cityscholars');

// Create connection. If the database server is not running, show a short friendly page
// instead of PHP's raw error (which would reveal file paths and the database user).
try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    $conn = false;
}

if (!$conn) {
    http_response_code(503);
    header('Retry-After: 120');
    $on_this_computer = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex"><title>Temporarily unavailable</title>'
        . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F8F7F0;color:#252823;font-family:Segoe UI,Arial,sans-serif;text-align:center;padding:24px}'
        . '.box{max-width:520px}h1{color:#5F6D3D;font-size:28px;margin:0 0 12px}p{line-height:1.7;color:#565c4d;margin:0 0 12px}'
        . '.hint{background:#EEF1DF;border:1px solid #DDE2CB;border-radius:10px;padding:14px 16px;font-size:14.5px;color:#252823}</style></head><body><div class="box">'
        . '<h1>We will be right back</h1>'
        . '<p>The website is temporarily unavailable. Please try again in a few minutes.</p>'
        . ($on_this_computer ? '<p class="hint"><strong>For the site owner:</strong> the MySQL database is not running. Open the XAMPP Control Panel, press <strong>Start</strong> next to MySQL, then refresh this page.</p>' : '')
        . '</div></body></html>';
    exit;
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Site Configuration
define('SITE_NAME', 'Tameer-e-Nau Public School');
define('SITE_URL', 'http://localhost/tameer-e-noe/');
define('ADMIN_EMAIL', 'aliajmalawan@gmail.com');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include settings helper functions
require_once __DIR__ . '/settings_helper.php';

// Include theme helper functions
require_once __DIR__ . '/theme_helper.php';

// Spam protection and server-side checks for the public forms
require_once __DIR__ . '/form_helper.php';
?>