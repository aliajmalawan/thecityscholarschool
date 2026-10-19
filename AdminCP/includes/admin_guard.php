<?php
/**
 * Shared protection for every admin page (included right after config.php).
 *
 *  - Only a logged-in admin gets past this point; everyone else goes to the login page.
 *  - An admin who has been idle for 2 hours is logged out.
 *  - CSRF protection: every form post and every link that changes something
 *    (delete, toggle, approve...) must carry this session's secret token, so
 *    another website can't make a logged-in admin's browser change data.
 *    The token is added to the page's forms and action links automatically.
 *  - The admin pages can't be framed by other sites or stored in shared caches.
 */

const ADMIN_IDLE_LIMIT = 7200;  // seconds (2 hours)

// GET parameters that change data rather than just show it
const ADMIN_ACTION_PARAMS = ['action', 'delete', 'delete_detail', 'delete_id', 'toggle', 'toggle_status'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Idle timeout
$now = time();
if (!empty($_SESSION['admin_last_seen']) && $now - (int) $_SESSION['admin_last_seen'] > ADMIN_IDLE_LIMIT) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php?expired=1');
    exit;
}
$_SESSION['admin_last_seen'] = $now;

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

/** This session's CSRF token (for pages that build forms or links in JavaScript). */
function adminCsrfToken() {
    return $_SESSION['admin_csrf'];
}

// Reject a post or action link without the right token
$needs_token = $_SERVER['REQUEST_METHOD'] === 'POST'
    || count(array_intersect(array_keys($_GET), ADMIN_ACTION_PARAMS)) > 0;
if ($needs_token) {
    $sent = (string) ($_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['admin_csrf'], $sent)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Request blocked</title>'
            . '<style>body{font-family:Segoe UI,Arial,sans-serif;background:#F8F7F0;color:#252823;display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0;padding:24px;text-align:center}'
            . '.box{max-width:460px}h1{color:#5F6D3D;font-size:24px}a{color:#5F6D3D;font-weight:600}</style></head><body><div class="box">'
            . '<h1>This action was blocked</h1><p>For your safety the change was not made, because the request did not come from an admin page opened in this session (or the page was open too long).</p>'
            . '<p><a href="' . htmlspecialchars(basename($_SERVER['SCRIPT_NAME'])) . '">Reload the page</a> and try again.</p></div></body></html>';
        exit;
    }
}

// Admin pages are never stored in shared caches (framing is blocked site-wide in .htaccess)
header('Cache-Control: no-store, private');
header('Referrer-Policy: same-origin');

// Add the token to every POST form and every action link on the page
ob_start(function ($html) {
    $token = htmlspecialchars($_SESSION['admin_csrf'] ?? '', ENT_QUOTES);
    if ($token === '') {
        return $html;
    }
    $html = preg_replace(
        '~(<form\b[^>]*\bmethod\s*=\s*["\']?post["\']?[^>]*>)~i',
        '$1<input type="hidden" name="csrf_token" value="' . $token . '">',
        $html
    );
    $params = implode('|', ADMIN_ACTION_PARAMS);
    return preg_replace_callback(
        '~\bhref=(["\'])([^"\']*\?[^"\']*\b(?:' . $params . ')=[^"\']*)\1~i',
        function ($m) use ($token) {
            if (strpos($m[2], 'csrf_token=') !== false) {
                return $m[0];
            }
            [$url, $hash] = array_pad(explode('#', $m[2], 2), 2, null);
            return 'href=' . $m[1] . $url . '&amp;csrf_token=' . $token . ($hash !== null ? '#' . $hash : '') . $m[1];
        },
        $html
    );
});
