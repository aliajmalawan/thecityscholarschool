<?php
/**
 * Settings Helper Functions
 * Use these functions to fetch dynamic settings from database
 */

// Global settings cache
$_settings_cache = null;

/**
 * Load all settings from database into cache
 */
function loadSettings() {
    global $conn, $_settings_cache;

    if ($_settings_cache !== null) {
        return $_settings_cache;
    }

    $_settings_cache = [];
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $_settings_cache[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $_settings_cache;
}

/**
 * Get a setting value by key
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value
 */
function getSetting($key, $default = '') {
    global $_settings_cache;

    if ($_settings_cache === null) {
        loadSettings();
    }

    return isset($_settings_cache[$key]) ? $_settings_cache[$key] : $default;
}

/**
 * Get site name
 */
function getSiteName() {
    return getSetting('site_name', 'Tameer-e-Nau Public School');
}

/**
 * Get site email
 */
function getSiteEmail() {
    return getSetting('site_email', 'aliajmalawan@gmail.com');
}

/**
 * Get site phone
 */
function getSitePhone() {
    return getSetting('site_phone', '');
}

/**
 * Get site address
 */
function getSiteAddress() {
    return getSetting('site_address', 'Canal View, Hasilpur, Pakistan');
}

/**
 * Get office hours
 */
function getOfficeHours() {
    return getSetting('office_hours', 'Mon - Sat: 8:00 AM - 5:00 PM');
}

/**
 * Get social media URLs
 */
function getSocialMedia() {
    // "#" and bare placeholders count as "not set" so no dead icons are rendered
    $clean = function ($url) {
        $url = trim((string) $url);
        return ($url === '' || $url === '#') ? '' : $url;
    };
    return [
        'facebook' => $clean(getSetting('facebook_url')),
        'instagram' => $clean(getSetting('instagram_url')),
        'youtube' => $clean(getSetting('youtube_url')),
        'twitter' => $clean(getSetting('twitter_url'))
    ];
}

/**
 * True when a real contact email is configured (empty or *@example.com means "not set yet")
 */
function hasSiteEmail() {
    $email = trim(getSiteEmail());
    return $email !== '' && !preg_match('/@example\.com$/i', $email);
}

/**
 * Get hero section content
 */
function getHeroContent() {
    return [
        'title' => getSetting('hero_title', 'Building Strong Foundations for a Brighter Future'),
        'description' => getSetting('hero_description', 'At Tameer-e-Nau Public School, we are committed to providing quality education, character development and a nurturing environment where every student can learn, grow and succeed.'),
        'button_text' => getSetting('hero_button_text', 'Apply Now'),
        'button_link' => getSetting('hero_button_link', 'admission'),
        'image' => content('hero_image')
    ];
}

/**
 * Get statistics
 */
function getStatistics() {
    global $conn;

    $count = function ($table) use ($conn) {
        $result = mysqli_query($conn, "SELECT COUNT(DISTINCT id) AS c FROM $table WHERE status = 'active'");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return $row ? (int) $row['c'] : 0;
    };

    // Faculty and programmes are counted from the real records unless an override is entered in Settings.
    // Students and years have no table to count from, so they only show once real values are entered.
    $teachers = getSetting('stats_teachers', '');
    $courses = getSetting('stats_courses', '');

    return [
        'students' => getSetting('stats_students', ''),
        'teachers' => $teachers !== '' ? $teachers : ($count('faculty') ?: ''),
        'courses' => $courses !== '' ? $courses : ($count('courses') ?: ''),
        'years' => getSetting('stats_years', '')
    ];
}

/**
 * Get about page content
 */
function getAboutContent() {
    return [
        'description' => getSetting('about_description', 'Tameer-e-Nau Public School is a leading educational institution committed to excellence in education...'),
        'mission' => getSetting('mission_statement', 'To provide quality education and empower students to achieve their full potential.'),
        'vision' => getSetting('vision_statement', 'To be a center of educational excellence that nurtures future leaders and innovators.')
    ];
}

/**
 * Get footer content
 */
function getFooterContent() {
    return [
        'about_text' => getSetting('footer_about_text', 'Tameer-e-Nau Public School is dedicated to providing quality education and nurturing the potential of every student.'),
        'copyright' => getSetting('copyright_text', 'Tameer-e-Nau Public School. All rights reserved.')
    ];
}

/**
 * Get leadership messages
 */
function getLeadershipMessages() {
    global $conn;
    static $leaders = null;   // pages ask twice (check + render); one query is enough
    if ($leaders !== null) {
        return $leaders;
    }

    $query = "SELECT DISTINCT * FROM leadership WHERE status = 'active' ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);

    $leaders = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $leaders[] = $row;
        }
    }

    return $leaders;
}

/**
 * Get hero carousel slides
 */
function getHeroCarouselSlides() {
    global $conn;

    $query = "SELECT DISTINCT * FROM hero_carousel WHERE status = 'active' ORDER BY display_order ASC, id ASC";
    $result = mysqli_query($conn, $query);

    $slides = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $slides[] = $row;
        }
    }

    return $slides;
}

/**
 * Get active feature_cards rows for a given section (why_choose_us,
 * digital_features, learner_attributes, assessment_features, core_values,
 * faculty_highlights, admission_requirements, admission_process)
 */
function getFeatureCards($section) {
    global $conn;

    $section_escaped = mysqli_real_escape_string($conn, $section);
    $query = "SELECT DISTINCT * FROM feature_cards WHERE section = '$section_escaped' AND status = 'active' ORDER BY display_order ASC, id ASC";
    $result = mysqli_query($conn, $query);

    $cards = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cards[] = $row;
        }
    }

    return $cards;
}

/**
 * Render a list of feature_cards as standard .card / .card-icon markup
 * (used by "Why Choose Us", "Learner Attributes", "Core Values",
 * "Why Our Faculty Stands Out"). Pass $centered = true to also center
 * the card text (used by grid-4 sections like Learner Attributes).
 */
function renderFeatureCardGrid($section, $centered = false) {
    $cards = getFeatureCards($section);
    $class = 'card reveal' . ($centered ? ' text-center' : '');
    $icon_style = $centered ? ' style="margin: 0 auto 22px;"' : '';

    foreach ($cards as $card) {
        echo '<div class="' . $class . '">';
        echo '<div class="card-icon"' . $icon_style . '><i class="' . htmlspecialchars($card['icon']) . '"></i></div>';
        echo '<h3>' . htmlspecialchars($card['title']) . '</h3>';
        echo '<p>' . htmlspecialchars($card['description']) . '</p>';
        echo '</div>';
    }
}

/**
 * Render one News / Event card (image, date, category, title, excerpt, Read More).
 * $type is 'news' or 'event'; $row is the matching DB row. Read More points at the
 * entry's anchor on events.php (there is no separate detail page).
 */
function renderUpdateCard($type, $row) {
    $is_event = ($type === 'event');
    $date = $is_event ? $row['event_date'] : $row['created_at'];
    $body = $is_event ? ($row['description'] ?? '') : ($row['content'] ?? '');
    $plain = trim(strip_tags($body));
    $excerpt = mb_strlen($plain) > 120 ? rtrim(mb_substr($plain, 0, 120)) . '…' : $plain;
    $anchor = 'events#' . ($is_event ? 'event-' : 'news-') . intval($row['id']);

    echo '<article class="card update-card reveal">';
    echo '<a href="' . htmlspecialchars($anchor) . '" class="update-media" tabindex="-1" aria-hidden="true">';
    if (!empty($row['image'])) {
        echo '<img src="' . htmlspecialchars($row['image']) . '" alt="" loading="lazy">';
    } else {
        echo '<span class="update-placeholder"><i class="fas ' . ($is_event ? 'fa-calendar-alt' : 'fa-newspaper') . '"></i></span>';
    }
    echo '</a>';
    echo '<div class="update-body">';
    echo '<div class="update-meta">';
    echo '<span class="update-category ' . ($is_event ? 'is-event' : 'is-news') . '">' . ($is_event ? 'Event' : 'News') . '</span>';
    echo '<span class="update-date"><i class="far fa-calendar"></i> ' . date('M d, Y', strtotime($date)) . '</span>';
    echo '</div>';
    echo '<h3><a href="' . htmlspecialchars($anchor) . '">' . htmlspecialchars($row['title']) . '</a></h3>';
    if ($excerpt !== '') {
        echo '<p>' . htmlspecialchars($excerpt) . '</p>';
    }
    echo '<a href="' . htmlspecialchars($anchor) . '" class="level-link">Read More <i class="fas fa-arrow-right"></i></a>';
    echo '</div>';
    echo '</article>';
}

/* ------------------------------------------------------------------
 * Editable page content (AdminCP -> Page Content)
 * The registry in content_fields.php lists every field with its default;
 * saved values live in the settings table.
 * ------------------------------------------------------------------ */

/** All content groups from the registry. */
function contentRegistry() {
    static $registry = null;
    if ($registry === null) {
        $registry = require __DIR__ . '/content_fields.php';
    }
    return $registry;
}

/** Definition (label, type, default...) of one content key, or null. */
function contentDef($key) {
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach (contentRegistry() as $group) {
            foreach ($group['fields'] as $field) {
                $map[$field['key']] = $field;
            }
        }
    }
    return $map[$key] ?? null;
}

/**
 * Value of an editable content field: the saved setting when there is one, otherwise
 * the registry default. {site_name} is replaced with the current school name.
 * A saved empty text hides that line; an empty image falls back to the default image.
 */
function content($key) {
    $def = contentDef($key);
    $all = loadSettings();
    $value = array_key_exists($key, $all) ? (string) $all[$key] : (string) ($def['default'] ?? '');
    if ($def && $def['type'] === 'image' && trim($value) === '') {
        $value = (string) $def['default'];
    }
    return str_replace('{site_name}', getSiteName(), $value);
}

function renderPageHero($image, $eyebrow, $title, $subtitle = '', $crumb = '', $compact = false, $icon = '') {
    echo '<section class="page-hero' . ($compact ? ' is-compact' : '') . '">';
    // JPEG for every browser, then a WebP version (about a third smaller) for those that support it
    $webp_bg = webpBackground($image);
    echo '<div class="page-hero-bg" style="background-image: url(\'' . htmlspecialchars($image) . '\');' . ($webp_bg !== '' ? ' background-image: ' . $webp_bg . ';' : '') . '"></div>';
    echo '<div class="container"><div class="page-hero-content">';
    if ($crumb !== '') {
        // Same trail for search engines, so results can show "Home › About Us"
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base = $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        echo '<script type="application/ld+json">' . json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $base . '/'],
                ['@type' => 'ListItem', 'position' => 2, 'name' => $crumb, 'item' => $base . '/' . basename($_SERVER['SCRIPT_NAME'], '.php')],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        echo '<nav class="breadcrumb" aria-label="Breadcrumb"><a href="./"><i class="fas fa-house" aria-hidden="true"></i> Home</a>'
            . '<i class="fas fa-chevron-right" aria-hidden="true"></i><span aria-current="page">' . htmlspecialchars($crumb) . '</span></nav>';
    }
    if ($eyebrow !== '') {
        echo '<span class="eyebrow on-dark">' . ($icon !== '' ? '<i class="fas ' . htmlspecialchars($icon) . '"></i> ' : '') . htmlspecialchars($eyebrow) . '</span>';
    }
    echo '<h1>' . htmlspecialchars($title) . '</h1>';
    echo '<span class="page-hero-line" aria-hidden="true"></span>';
    if ($subtitle !== '') {
        echo '<p>' . htmlspecialchars($subtitle) . '</p>';
    }
    echo '</div></div></section>';
}

/**
 * Inner-page hero (photo, breadcrumb, small label, title, subtitle) driven by content().
 * $compact = the slightly shorter variant used on Examination, Alumni and Downloads.
 */
function renderPageBanner($slug, $compact = false, $icon = '') {
    renderPageHero(
        content("banner_{$slug}_image"),
        content("banner_{$slug}_eyebrow"),
        content("banner_{$slug}_title"),
        content("banner_{$slug}_subtitle"),
        content("seo_{$slug}_title"),
        $compact,
        $icon
    );
}

/** Insert-or-update one setting (settings has no unique key, so look the key up first). */
function saveSetting($key, $value) {
    global $conn, $_settings_cache;
    $check = mysqli_prepare($conn, "SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($check, 's', $key);
    mysqli_stmt_execute($check);
    mysqli_stmt_bind_result($check, $exists);
    mysqli_stmt_fetch($check);
    mysqli_stmt_close($check);

    if ($exists > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $value, $key);
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
    }
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if ($ok && is_array($_settings_cache)) {
        $_settings_cache[$key] = $value;
    }
    return $ok;
}

/** Remove a saved setting so its registry default applies again. */
function deleteSetting($key) {
    global $conn, $_settings_cache;
    $stmt = mysqli_prepare($conn, "DELETE FROM settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($stmt, 's', $key);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if (is_array($_settings_cache)) {
        unset($_settings_cache[$key]);
    }
    return $ok;
}

/**
 * Next free numeric id for a table whose id column has no auto-numbering (the imported tables lost it).
 * Giving every new row its own id keeps editing and deleting that one row reliable.
 */
function nextRowId($table) {
    global $conn;
    if (!preg_match('/^[a-z_]+$/', $table)) {
        return 1;
    }
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `$table`"));
    return max(1, (int) ($row['next_id'] ?? 1));
}

/** Friendly placeholder for a list that has nothing in it yet (icon, short title, one line of help). */
function renderEmptyState($icon, $title, $text = '') {
    echo '<div class="empty-state">';
    echo '<span class="empty-state-icon"><i class="fas ' . htmlspecialchars($icon) . '" aria-hidden="true"></i></span>';
    echo '<h3>' . htmlspecialchars($title) . '</h3>';
    if ($text !== '') {
        echo '<p>' . htmlspecialchars($text) . '</p>';
    }
    echo '</div>';
}

/**
 * The 11-character video ID from any usual YouTube link (watch?v=, youtu.be/, shorts/, embed/, live/),
 * or '' when the text isn't a YouTube link. A bare ID is accepted too.
 */
function youtubeId($url) {
    $url = trim((string) $url);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $url)) {
        return $url;
    }
    if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m)) {
        return $m[1];
    }
    return '';
}
