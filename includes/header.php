<?php
$site_name = getSiteName();
$seo_location = content('seo_title_suffix');
$is_home = basename($_SERVER['SCRIPT_NAME']) === 'index.php';
// Home: "The City Scholars School | Excellence in Education"; inner pages: "About Us | The City Scholars School"
$full_title = $is_home ? $site_name . ' | ' . $seo_location : ((!empty($page_title) ? $page_title . ' | ' : '') . $site_name);
$meta_description = $meta_description ?? $site_name . ' in ' . $seo_location . ' provides quality education with caring teachers and a focus on character building.';
$site_scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_base_url = $site_scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
// One clean address per page (no .php, no query string): https://site/ for home, https://site/about for the rest.
// Used for the canonical link and og:url, so /about.php, /about/ and ?utm=... all point search engines to the same URL.
$page_slug = basename($_SERVER['SCRIPT_NAME'], '.php');
$current_url = $site_base_url . '/' . ($page_slug === 'index' ? '' : $page_slug);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#082B50">
    <?php if (basename($_SERVER['SCRIPT_NAME']) === '404.php'): ?><base href="<?php echo htmlspecialchars($site_base_url); ?>/"><?php // the 404 page can be shown for any nested address, so resolve its links from the site root ?><?php endif; ?>
    <title><?php echo htmlspecialchars($full_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($site_name); ?>, City Scholars School, City Scholars, private school, quality education, admissions open">
    <meta name="author" content="<?php echo htmlspecialchars($site_name); ?>">
    <?php if (!empty($noindex)): ?><meta name="robots" content="noindex, follow"><?php endif; ?>
    <link rel="canonical" href="<?php echo htmlspecialchars($current_url); ?>">

    <!-- Open Graph / Social Sharing -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($full_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($site_base_url . '/' . getLogoPath()); ?>">
    <meta property="og:image:width" content="640">
    <meta property="og:image:height" content="595">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($site_name); ?> logo">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($full_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($site_base_url . '/' . getLogoPath()); ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <!-- Web fonts (text shows in the fallback font until they arrive): loaded without blocking the first paint -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap"></noscript>

    <!-- Structured Data (helps Google show rich results / knowledge panel) -->
    <script type="application/ld+json">
    <?php
        $schema_social = array_values(array_filter(getSocialMedia(), function ($url) {
            return !empty($url) && $url[0] !== '#';
        }));
        // The address setting is free-form text, one part per line, e.g.
        // "Near Railway Tower, Bandhi\nShaheed Benazirabad, Sindh 67400\nPakistan":
        // first line = street, last line = country, lines between = town/region,
        // and a 5-digit number anywhere in those lines = postal code.
        $address_lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', getSiteAddress()))));
        $schema_address = ['@type' => 'PostalAddress'];
        if (!empty($address_lines)) {
            $schema_address['streetAddress'] = $address_lines[0];
        }
        if (count($address_lines) >= 2) {
            $schema_address['addressCountry'] = end($address_lines);
        }
        if (count($address_lines) >= 3) {
            $region = implode(', ', array_slice($address_lines, 1, -1));
            if (preg_match('/\b(\d{5})\b/', $region, $postal)) {
                $schema_address['postalCode'] = $postal[1];
                $region = trim(str_replace($postal[1], '', $region), " ,");
            }
            $schema_address['addressRegion'] = $region;
        }
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'School',
            'name' => getSiteName(),
            'description' => $meta_description,
            'url' => $site_base_url . '/',
            'logo' => $site_base_url . '/' . getLogoPath(),
            'telephone' => trim(getSitePhone()),
            'email' => hasSiteEmail() ? getSiteEmail() : '',
            'address' => $schema_address,
            'sameAs' => $schema_social,
        ];
        // leave out anything not filled in yet instead of publishing empty or placeholder values
        echo json_encode(array_filter($schema, function ($v) { return $v !== '' && $v !== []; }), JSON_UNESCAPED_SLASHES);
    ?>
    </script>

    <script>document.documentElement.classList.add('js');</script>

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">

    <!-- Dynamic Theme Colors -->
    <style>
        <?php echo getThemeCSS(); ?>
    </style>

    <!-- Font Awesome for Icons -->
    <!-- Icons: loaded without blocking the first paint -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>

    <!-- Favicon -->
    <?php echo faviconLinks(); // AdminCP -> Logo & Favicon, or the default crest icons ?>
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <!-- Header: optional navy utility strip (contact details / social links, only what is filled in under Settings)
         above a white bar with the crest, the main menu and the gold Apply button. Sticky on every page. -->
    <?php
    $nav_page = basename($_SERVER['SCRIPT_NAME']);
    $nav_active = static function (array $pages) use ($nav_page) {
        return in_array($nav_page, $pages, true);
    };
    // Class + aria-current for a top-level link
    $nav_attr = static function (array $pages) use ($nav_active) {
        return $nav_active($pages) ? ' class="is-active" aria-current="page"' : '';
    };
    $social_links = array_filter(getSocialMedia());
    $social_icons = ['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'twitter' => 'fa-x-twitter'];
    $has_utility = hasSitePhone() || hasSiteEmail() || hasOfficeHours() || !empty($social_links);
    ?>
    <header class="site-header" id="site-header">
        <?php if ($has_utility): ?>
        <div class="utility-bar">
            <div class="container utility-inner">
                <ul class="utility-info">
                    <?php if (hasSitePhone()): ?>
                    <li><a href="<?php echo htmlspecialchars(telHref(primaryPhone())); ?>"><i class="fas fa-phone" aria-hidden="true"></i> <?php echo htmlspecialchars(primaryPhone()); ?></a></li>
                    <?php endif; ?>
                    <?php if (hasSiteEmail()): ?>
                    <li><a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><i class="fas fa-envelope" aria-hidden="true"></i> <?php echo htmlspecialchars(getSiteEmail()); ?></a></li>
                    <?php endif; ?>
                    <?php if (hasOfficeHours()): ?>
                    <li class="utility-hours"><i class="fas fa-clock" aria-hidden="true"></i> <?php echo htmlspecialchars(getOfficeHours()); ?></li>
                    <?php endif; ?>
                </ul>
                <?php if (!empty($social_links)): ?>
                <nav class="utility-social" aria-label="Social media">
                    <?php foreach ($social_icons as $key => $icon):
                        if (empty($social_links[$key])) continue; ?>
                        <a href="<?php echo htmlspecialchars($social_links[$key]); ?>" target="_blank" rel="noopener" aria-label="<?php echo ucfirst($key); ?> (opens in a new tab)"><i class="fab <?php echo $icon; ?>" aria-hidden="true"></i></a>
                    <?php endforeach; ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="main-bar">
            <div class="container main-bar-inner">
                <a class="brand" href="./" aria-label="<?php echo htmlspecialchars(getSiteName()); ?> - Home">
                    <img src="<?php echo getLogoSmallPath(); ?>" alt="" class="brand-logo" width="60" height="56" onerror="this.style.display='none'">
                    <span class="brand-text">
                        <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                        <small><?php echo htmlspecialchars(content('site_tagline')); ?></small>
                    </span>
                </a>

                <div class="nav-backdrop" id="nav-backdrop"></div>

                <nav class="site-nav" id="site-nav" aria-label="Primary navigation">
                    <div class="nav-drawer-head">
                        <img src="<?php echo getLogoSmallPath(); ?>" alt="" width="43" height="40">
                        <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                    </div>

                    <a href="./"<?php echo $nav_attr(['index.php']); ?>>Home</a>
                    <a href="about"<?php echo $nav_attr(['about.php']); ?>>About Us</a>

                    <div class="nav-dropdown<?php echo $nav_active(['courses.php', 'faculty.php', 'examination.php', 'downloads.php']) ? ' is-active' : ''; ?>">
                        <button type="button" class="nav-dropdown-trigger" aria-haspopup="true" aria-expanded="false">Academics <i class="fas fa-chevron-down nav-caret" aria-hidden="true"></i></button>
                        <div class="nav-dropdown-panel">
                            <a href="courses"<?php echo $nav_attr(['courses.php']); ?>><i class="fas fa-book-open" aria-hidden="true"></i> Academic Programs</a>
                            <a href="faculty"<?php echo $nav_attr(['faculty.php']); ?>><i class="fas fa-chalkboard-user" aria-hidden="true"></i> Faculty</a>
                            <a href="examination"<?php echo $nav_attr(['examination.php']); ?>><i class="fas fa-file-lines" aria-hidden="true"></i> Examination</a>
                            <a href="downloads"<?php echo $nav_attr(['downloads.php']); ?>><i class="fas fa-download" aria-hidden="true"></i> Downloads</a>
                        </div>
                    </div>

                    <a href="admission"<?php echo $nav_attr(['admission.php']); ?>>Admissions</a>

                    <div class="nav-dropdown<?php echo $nav_active(['campus-life.php', 'campuses.php', 'alumni.php']) ? ' is-active' : ''; ?>">
                        <button type="button" class="nav-dropdown-trigger" aria-haspopup="true" aria-expanded="false">Campus Life <i class="fas fa-chevron-down nav-caret" aria-hidden="true"></i></button>
                        <div class="nav-dropdown-panel">
                            <a href="campus-life"<?php echo $nav_attr(['campus-life.php']); ?>><i class="fas fa-people-group" aria-hidden="true"></i> Life at School</a>
                            <a href="campuses"<?php echo $nav_attr(['campuses.php']); ?>><i class="fas fa-school" aria-hidden="true"></i> Our Campuses</a>
                            <a href="alumni"<?php echo $nav_attr(['alumni.php']); ?>><i class="fas fa-user-graduate" aria-hidden="true"></i> Alumni</a>
                        </div>
                    </div>

                    <a href="gallery"<?php echo $nav_attr(['gallery.php']); ?>>Gallery</a>
                    <a href="events"<?php echo $nav_attr(['events.php']); ?>>News &amp; Events</a>
                    <a href="contact"<?php echo $nav_attr(['contact.php']); ?>>Contact Us</a>

                    <a class="btn btn-secondary nav-drawer-apply" href="admission"><i class="fas fa-pen-to-square" aria-hidden="true"></i> <?php echo htmlspecialchars(content('apply_button_label')); ?></a>
                </nav>

                <div class="main-bar-actions">
                    <a class="apply-btn" href="admission"><?php echo htmlspecialchars(content('apply_button_label')); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>

                    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="site-nav">
                        <span class="nav-toggle-bar"></span>
                        <span class="nav-toggle-bar"></span>
                        <span class="nav-toggle-bar"></span>
                    </button>
                </div>
            </div>
        </div>
    </header>
    <main id="main">
