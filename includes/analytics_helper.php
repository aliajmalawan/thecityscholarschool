<?php
/**
 * Analytics Helper Functions
 * Website Traffic Monitoring
 */

// Get visitor's IP address
function getVisitorIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Get or create session ID for visitor tracking
function getVisitorSessionID() {
    // Use PHP's native session ID which persists across page loads
    if (!isset($_SESSION['visitor_session_id']) || empty($_SESSION['visitor_session_id'])) {
        // Get current session ID, if empty create new one
        $current_session_id = session_id();
        if (empty($current_session_id)) {
            // Session not started properly, create unique ID
            $current_session_id = md5(uniqid(rand(), true) . getVisitorIP());
        }
        $_SESSION['visitor_session_id'] = $current_session_id;
    }
    return $_SESSION['visitor_session_id'];
}

// Detect device type from user agent
function getDeviceType($user_agent) {
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
        return 'Tablet';
    }
    if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', $user_agent)) {
        return 'Mobile';
    }
    if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', $user_agent)) {
        return 'Tablet';
    }
    return 'Desktop';
}

// Detect browser from user agent
function getBrowser($user_agent) {
    $browsers = array(
        'Edge' => 'Edge',
        'Edg' => 'Edge',
        'OPR' => 'Opera',
        'Opera' => 'Opera',
        'Chrome' => 'Chrome',
        'Safari' => 'Safari',
        'Firefox' => 'Firefox',
        'MSIE' => 'Internet Explorer',
        'Trident' => 'Internet Explorer'
    );

    foreach ($browsers as $key => $value) {
        if (strpos($user_agent, $key) !== false) {
            return $value;
        }
    }
    return 'Unknown';
}

// Detect operating system from user agent
function getOperatingSystem($user_agent) {
    $os_array = array(
        '/windows nt 10/i'      =>  'Windows 10',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    );

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            return $value;
        }
    }
    return 'Unknown OS';
}

// True for search-engine crawlers, link previewers, uptime checkers and scripts,
// so the reports only count people
function isBotUserAgent($user_agent) {
    if (trim($user_agent) === '') {
        return true;
    }
    return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|preview|monitor|uptime|headless|lighthouse|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|postman|scrapy|httpclient|semrush|ahrefs|mj12|dotbot|petalbot|yandex|baiduspider|bingpreview/i', $user_agent);
}

// Track page view
function trackPageView($conn) {
    // Don't track admin pages
    if (strpos($_SERVER['PHP_SELF'], 'AdminCP') !== false) {
        return;
    }

    // Skip bots, and the school's own staff browsing the site while logged in to the admin panel
    if (isBotUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '') || !empty($_SESSION['admin_logged_in'])) {
        return;
    }

    // Analytics is a non-critical side effect - a DB hiccup here must never
    // take down the page for a real visitor.
    try {
        $visitor_ip = getVisitorIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Create unique fingerprint based on IP + User Agent hash (more stable than session)
        $visitor_fingerprint = md5($visitor_ip . substr($user_agent, 0, 200));

        // Use session ID if available, otherwise use fingerprint
        $session_id = getVisitorSessionID();
        if (empty($session_id)) {
            $session_id = $visitor_fingerprint;
        }

        // visitor_ip and user_agent come from client-controlled HTTP headers -
        // escape everything before it touches SQL.
        $visitor_ip_esc = mysqli_real_escape_string($conn, $visitor_ip);
        $user_agent_esc = mysqli_real_escape_string($conn, $user_agent);
        $session_id_esc = mysqli_real_escape_string($conn, $session_id);
        $page_url = mysqli_real_escape_string($conn, $_SERVER['PHP_SELF']);
        // Each page sets $page_title before including the header; fall back to the file name
        $title_raw = $GLOBALS['page_title'] ?? '';
        if (trim((string) $title_raw) === '') {
            $title_raw = ucwords(str_replace(['-', '_'], ' ', pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME)));
        }
        $page_title = mysqli_real_escape_string($conn, mb_substr((string) $title_raw, 0, 250));
        $referrer = mysqli_real_escape_string($conn, isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Direct');
        $device_type = getDeviceType($user_agent);
        $browser = getBrowser($user_agent);
        $os = getOperatingSystem($user_agent);
        $visit_date = date('Y-m-d');
        $visit_time = date('H:i:s');

        // Insert page view
        $sql = "INSERT INTO website_analytics
                (visitor_ip, page_url, page_title, referrer, user_agent, device_type, browser, operating_system, session_id, visit_date, visit_time)
                VALUES
                ('$visitor_ip_esc', '$page_url', '$page_title', '$referrer', '$user_agent_esc', '$device_type', '$browser', '$os', '$session_id_esc', '$visit_date', '$visit_time')";

        mysqli_query($conn, $sql);

        // Upsert unique visitor, keyed on session_id (the table's actual unique
        // constraint) so this can never collide with an existing row - unlike
        // the previous check-by-ip+today's-date pattern, which missed rows
        // from earlier days and then crashed on the INSERT.
        mysqli_query($conn, "INSERT INTO unique_visitors (visitor_ip, session_id, user_agent, total_visits)
                VALUES ('$visitor_ip_esc', '$session_id_esc', '$user_agent_esc', 1)
                ON DUPLICATE KEY UPDATE
                    last_visit = NOW(),
                    total_visits = total_visits + 1,
                    visitor_ip = VALUES(visitor_ip),
                    user_agent = VALUES(user_agent)");

        // Upsert active user (online now), also keyed on session_id for the
        // same reason - the old check-by-ip could miss an existing row (IP
        // changed) and then collide on insert.
        mysqli_query($conn, "INSERT INTO active_users (session_id, visitor_ip, current_page, last_activity)
                VALUES ('$session_id_esc', '$visitor_ip_esc', '$page_url', NOW())
                ON DUPLICATE KEY UPDATE
                    visitor_ip = VALUES(visitor_ip),
                    current_page = VALUES(current_page),
                    last_activity = NOW()");

        // Clean up old active users (inactive for more than 5 minutes). Done on roughly one page
        // view in 20 rather than every view; the "online now" count already only reads recent rows.
        if (mt_rand(1, 20) === 1) {
            mysqli_query($conn, "DELETE FROM active_users WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        }
    } catch (\Throwable $e) {
        error_log('Analytics tracking failed: ' . $e->getMessage());
    }
}

// Get total page views
function getTotalPageViews($conn, $date_range = 'all') {
    $where = "";
    if ($date_range == 'today') {
        $where = "WHERE visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM website_analytics $where");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get unique visitors count
function getUniqueVisitors($conn, $date_range = 'all') {
    $where = "";
    if ($date_range == 'today') {
        $where = "WHERE DATE(last_visit) = CURDATE()";
    } elseif ($date_range == 'week') {
        $where = "WHERE last_visit >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where = "WHERE last_visit >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    // Distinct browsing sessions in the recorded page views for the same period
    $where = str_replace('last_visit', 'visit_date', str_replace('DATE(last_visit)', 'visit_date', $where));
    $result = mysqli_query($conn, "SELECT COUNT(DISTINCT session_id) as total FROM website_analytics $where");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get active users (online now)
function getActiveUsers($conn) {
    // First, clean up old inactive users (more than 5 minutes old)
    mysqli_query($conn, "DELETE FROM active_users WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

    // Then count remaining active users
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM active_users WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

// Get popular pages
function getPopularPages($conn, $limit = 10, $date_range = 'all') {
    $where = "";
    if ($date_range == 'today') {
        $where = "WHERE visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    // Group on the file name so the same page reached under different folders
    // (e.g. /index.php and /school/index.php) counts as one page
    $sql = "SELECT CONCAT('/', SUBSTRING_INDEX(page_url, '/', -1)) AS page_url, COUNT(*) as views
            FROM website_analytics
            $where
            GROUP BY SUBSTRING_INDEX(page_url, '/', -1)
            ORDER BY views DESC
            LIMIT $limit";

    return mysqli_query($conn, $sql);
}

// Get device statistics
function getDeviceStats($conn, $date_range = 'all') {
    $where = "";
    if ($date_range == 'today') {
        $where = "WHERE visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $sql = "SELECT device_type, COUNT(*) as count
            FROM website_analytics
            $where
            GROUP BY device_type
            ORDER BY count DESC";

    return mysqli_query($conn, $sql);
}

// Get browser statistics
function getBrowserStats($conn, $date_range = 'all') {
    $where = "";
    if ($date_range == 'today') {
        $where = "WHERE visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    $sql = "SELECT browser, COUNT(*) as count
            FROM website_analytics
            $where
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 5";

    return mysqli_query($conn, $sql);
}

// Get hourly traffic for today
function getHourlyTraffic($conn) {
    $sql = "SELECT HOUR(visit_time) as hour, COUNT(*) as visits
            FROM website_analytics
            WHERE visit_date = CURDATE()
            GROUP BY HOUR(visit_time)
            ORDER BY hour";

    return mysqli_query($conn, $sql);
}

// Get daily traffic for last 30 days
function getDailyTraffic($conn, $days = 30) {
    $sql = "SELECT visit_date, COUNT(*) as visits
            FROM website_analytics
            WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
            GROUP BY visit_date
            ORDER BY visit_date";

    return mysqli_query($conn, $sql);
}

// Get traffic sources (referrers)
function getTrafficSources($conn, $limit = 10, $date_range = 'all') {
    $where = "";
    if ($date_range == 'today') {
        $where = "WHERE visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    // Clicks from one page of this site to another are navigation, not a traffic source
    $own_host = mysqli_real_escape_string($conn, preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $internal = "referrer LIKE '%://$own_host/%' OR referrer LIKE '%://www.$own_host/%'";
    $where = $where === '' ? "WHERE NOT ($internal)" : "$where AND NOT ($internal)";

    $sql = "SELECT
            CASE
                WHEN referrer = 'Direct' THEN 'Direct Traffic'
                WHEN referrer LIKE '%google%' THEN 'Google Search'
                WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                WHEN referrer LIKE '%youtube%' THEN 'YouTube'
                WHEN referrer LIKE '%instagram%' THEN 'Instagram'
                WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                ELSE 'Other Websites'
            END as source,
            COUNT(*) as visits
            FROM website_analytics
            $where
            GROUP BY source
            ORDER BY visits DESC
            LIMIT $limit";

    return mysqli_query($conn, $sql);
}

// Get bounce rate (visitors who viewed only one page)
function getBounceRate($conn, $date_range = 'all') {
    $where_date = "";
    if ($date_range == 'today') {
        $where_date = "WHERE visit_date = CURDATE()";
    } elseif ($date_range == 'week') {
        $where_date = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_range == 'month') {
        $where_date = "WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }

    // Count visitors who viewed only 1 unique page (bounce)
    // vs total unique visitors
    $sql = "SELECT
            SUM(CASE WHEN page_count = 1 THEN 1 ELSE 0 END) as bounced,
            COUNT(*) as total
            FROM (
                SELECT session_id, COUNT(DISTINCT page_url) as page_count
                FROM website_analytics
                $where_date
                GROUP BY session_id
            ) as visitor_stats";

    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row['total'] > 0 && $row['bounced'] !== null) {
        return round(($row['bounced'] / $row['total']) * 100, 2);
    }
    return 0;
}
?>
