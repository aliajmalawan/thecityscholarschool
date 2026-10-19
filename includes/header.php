<?php
$site_name = getSiteName();
$seo_location = content('seo_title_suffix');
$is_home = basename($_SERVER['SCRIPT_NAME']) === 'index.php';
// Home: "Tameer-e-Nau Public School | Canal View Hasilpur"; inner pages: "About Us | Tameer-e-Nau Public School"
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
    <meta name="theme-color" content="#5F6D3D">
    <?php if (basename($_SERVER['SCRIPT_NAME']) === '404.php'): ?><base href="<?php echo htmlspecialchars($site_base_url); ?>/"><?php // the 404 page can be shown for any nested address, so resolve its links from the site root ?><?php endif; ?>
    <title><?php echo htmlspecialchars($full_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="keywords" content="Tameer-e-Nau Public School, school in Hasilpur, Canal View Hasilpur school, quality education in Hasilpur, Tameer-e-Nau School">
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
    <meta property="og:image:width" content="635">
    <meta property="og:image:height" content="639">
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
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap"></noscript>

    <!-- Structured Data (helps Google show rich results / knowledge panel) -->
    <script type="application/ld+json">
    <?php
        $schema_social = array_values(array_filter(getSocialMedia(), function ($url) {
            return !empty($url) && $url[0] !== '#';
        }));
        // The address setting is free-form text (e.g. "Street, City\nPakistan") -
        // split it into street/country so the schema fields aren't just one
        // field with a raw line break jammed inside it.
        $address_lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', getSiteAddress()))));
        $schema_address = ['@type' => 'PostalAddress'];
        if (!empty($address_lines)) {
            $schema_address['streetAddress'] = $address_lines[0];
        }
        if (isset($address_lines[1])) {
            $schema_address['addressCountry'] = $address_lines[1];
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
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="images/icons/favicon-32.png">
    <link rel="apple-touch-icon" href="images/icons/apple-touch-icon.png">
</head>
<body>
    <!-- Header: two tiers (contact/apply strip on top, dark centred menu below), sticky on every page -->
    <?php
    $nav_page = basename($_SERVER['SCRIPT_NAME']);
    $nav_active = static function (array $pages) use ($nav_page) {
        return in_array($nav_page, $pages, true);
    };
    $topbar_phone = trim(explode(',', getSitePhone())[0]);
    $social_links = array_filter(getSocialMedia());
    ?>
    <header class="site-header" id="site-header">
        <div class="edu-topbar">
            <div class="container edu-topbar-inner">
                <a class="brand" href="./" aria-label="<?php echo htmlspecialchars(getSiteName()); ?> - Home">
                    <img src="<?php echo getLogoSmallPath(); ?>" alt="" class="brand-logo" width="56" height="56" onerror="this.style.display='none'">
                    <span class="brand-text">
                        <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                        <small><?php echo htmlspecialchars(content('site_tagline')); ?></small>
                    </span>
                </a>

                <div class="edu-topbar-info-group">
                    <?php if ($topbar_phone !== ''): ?>
                    <a class="edu-topbar-info" href="tel:<?php echo htmlspecialchars(preg_replace('/[^+0-9]/', '', $topbar_phone)); ?>">
                        <span class="edu-topbar-info-icon"><i class="fas fa-phone"></i></span>
                        <span><small>Call Us</small><strong><?php echo htmlspecialchars($topbar_phone); ?></strong></span>
                    </a>
                    <?php else: ?>
                    <a class="edu-topbar-info" href="contact">
                        <span class="edu-topbar-info-icon"><i class="fas fa-location-dot"></i></span>
                        <span><small>Visit Us</small><strong><?php echo htmlspecialchars(trim(preg_split('/\r\n|\r|\n/', getSiteAddress())[0])); ?></strong></span>
                    </a>
                    <?php endif; ?>
                    <?php if (hasSiteEmail()): ?>
                    <a class="edu-topbar-info" href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>">
                        <span class="edu-topbar-info-icon"><i class="fas fa-envelope"></i></span>
                        <span><small>Email Us</small><strong><?php echo htmlspecialchars(getSiteEmail()); ?></strong></span>
                    </a>
                    <?php else: ?>
                    <a class="edu-topbar-info" href="contact">
                        <span class="edu-topbar-info-icon"><i class="fas fa-clock"></i></span>
                        <span><small>Office Hours</small><strong><?php echo htmlspecialchars(getOfficeHours()); ?></strong></span>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="edu-topbar-actions">
                    <?php if (!empty($social_links)): ?>
                    <nav class="edu-topbar-social" aria-label="Social media">
                        <?php
                        $social_icons = ['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'twitter' => 'fa-x-twitter'];
                        foreach ($social_icons as $key => $icon):
                            if (empty($social_links[$key])) continue; ?>
                            <a href="<?php echo htmlspecialchars($social_links[$key]); ?>" target="_blank" rel="noopener" aria-label="<?php echo ucfirst($key); ?>"><i class="fab <?php echo $icon; ?>"></i></a>
                        <?php endforeach; ?>
                    </nav>
                    <?php endif; ?>

                    <a class="edu-apply-btn" href="admission"><i class="fas fa-pen-to-square"></i> <?php echo htmlspecialchars(content('apply_button_label')); ?></a>

                    <button type="button" class="nav-toggle" id="nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="site-nav">
                        <span class="nav-toggle-bar"></span>
                        <span class="nav-toggle-bar"></span>
                        <span class="nav-toggle-bar"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="nav-backdrop" id="nav-backdrop"></div>

        <nav class="edu-navbar" aria-label="Primary navigation">
            <div class="container edu-navbar-inner">
                <div class="site-nav" id="site-nav">
                    <a href="./"<?php echo $nav_active(['index.php']) ? ' class="is-active" aria-current="page"' : ''; ?>>Home</a>
                    <a href="about"<?php echo $nav_active(['about.php']) ? ' class="is-active" aria-current="page"' : ''; ?>>About Us</a>

                    <div class="nav-dropdown<?php echo $nav_active(['courses.php', 'faculty.php', 'examination.php', 'downloads.php']) ? ' is-active' : ''; ?>">
                        <span class="nav-dropdown-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">Academics <i class="fas fa-chevron-down nav-caret"></i></span>
                        <div class="nav-dropdown-panel">
                            <a href="courses"<?php echo $nav_active(['courses.php']) ? ' class="is-active"' : ''; ?>>Courses &amp; Programs</a>
                            <a href="faculty"<?php echo $nav_active(['faculty.php']) ? ' class="is-active"' : ''; ?>>Faculty</a>
                            <a href="examination"<?php echo $nav_active(['examination.php']) ? ' class="is-active"' : ''; ?>>Examination</a>
                            <a href="downloads"<?php echo $nav_active(['downloads.php']) ? ' class="is-active"' : ''; ?>>Downloads</a>
                        </div>
                    </div>

                    <a href="admission"<?php echo $nav_active(['admission.php']) ? ' class="is-active" aria-current="page"' : ''; ?>>Admissions</a>

                    <div class="nav-dropdown<?php echo $nav_active(['gallery.php', 'campuses.php', 'alumni.php']) ? ' is-active' : ''; ?>">
                        <span class="nav-dropdown-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">Campus Life <i class="fas fa-chevron-down nav-caret"></i></span>
                        <div class="nav-dropdown-panel">
                            <a href="gallery"<?php echo $nav_active(['gallery.php']) ? ' class="is-active"' : ''; ?>>Photo Gallery</a>
                            <a href="campuses"<?php echo $nav_active(['campuses.php']) ? ' class="is-active"' : ''; ?>>Our Campuses</a>
                            <a href="alumni"<?php echo $nav_active(['alumni.php']) ? ' class="is-active"' : ''; ?>>Alumni</a>
                        </div>
                    </div>

                    <a href="events"<?php echo $nav_active(['events.php']) ? ' class="is-active" aria-current="page"' : ''; ?>>News &amp; Events</a>
                    <a href="contact"<?php echo $nav_active(['contact.php']) ? ' class="is-active" aria-current="page"' : ''; ?>>Contact Us</a>

                    <a class="btn btn-secondary header-apply-btn" href="admission"><?php echo htmlspecialchars(content('apply_button_label')); ?></a>
                </div>
            </div>
        </nav>
    </header>
