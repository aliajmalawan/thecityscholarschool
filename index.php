<?php
require_once 'includes/config.php';
$page_title = 'Home';
$meta_description = content('seo_home_description');
?>
<?php include 'includes/header.php'; ?>

<!-- Hero: auto-playing photo slider (slides managed under Home Slider in AdminCP). Each slide can have its own
     headline and text; when left empty the homepage headline/text from Page Content is used. -->
<?php
$hero = getHeroContent();
$hero_slides = getHeroCarouselSlides();
$has_slider = !empty($hero_slides);
$slide_count = count($hero_slides);
$hero_fallback_bg = (!$has_slider && $hero['image'] !== '') ? ' style="background-image: url(\'' . htmlspecialchars($hero['image']) . '\');"' : '';
$hero_eyebrow = content('home_hero_eyebrow');
$hero_btn1 = ['label' => content('home_hero_btn1_label'), 'link' => content('home_hero_btn1_link')];
$hero_btn2 = ['label' => content('home_hero_btn2_label'), 'link' => content('home_hero_btn2_link')];

// One caption (small label, headline, text, buttons); only the first headline is the page's <h1>
$render_caption = function ($index, $headline, $text) use ($hero_eyebrow, $hero_btn1, $hero_btn2, $slide_count) {
    $tag = $index === 0 ? 'h1' : 'p';
    echo '<div class="hero-caption' . ($index === 0 ? ' is-active' : '') . '"' . ($index === 0 ? '' : ' aria-hidden="true"') . '>';
    if ($hero_eyebrow !== '') {
        echo '<span class="eyebrow on-dark">' . htmlspecialchars($hero_eyebrow) . '</span>';
    }
    echo '<' . $tag . ' class="hero-title">' . htmlspecialchars($headline) . '</' . $tag . '>';
    if ($text !== '') {
        echo '<p class="hero-text">' . htmlspecialchars($text) . '</p>';
    }
    echo '<div class="btn-group">';
    echo '<a href="' . htmlspecialchars($hero_btn1['link']) . '" class="btn btn-secondary"' . ($index === 0 ? '' : ' tabindex="-1"') . '>' . htmlspecialchars($hero_btn1['label']) . '</a>';
    echo '<a href="' . htmlspecialchars($hero_btn2['link']) . '" class="btn btn-outline"' . ($index === 0 ? '' : ' tabindex="-1"') . '>' . htmlspecialchars($hero_btn2['label']) . '</a>';
    echo '</div></div>';
};
?>
<section class="hero-main<?php echo $has_slider ? ' hero-slider' : ''; ?>" id="hero"<?php echo $has_slider ? ' aria-roledescription="carousel" aria-label="School highlights"' : $hero_fallback_bg; ?>>
    <?php if ($has_slider): ?>
    <div class="hero-slides">
        <?php foreach ($hero_slides as $i => $slide):
            $slide_src = $slide['image_path'];
            $slide_webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $slide_src);
            $has_webp = $slide_webp !== $slide_src && is_file(__DIR__ . '/' . $slide_webp);
            $alt = trim((string) $slide['title']) !== '' ? trim((string) $slide['title']) : $hero['title'];
        ?>
        <div class="hero-slide<?php echo $i === 0 ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo ($i + 1) . ' of ' . $slide_count; ?>">
            <picture>
                <?php if ($has_webp): ?><source type="image/webp" srcset="<?php echo htmlspecialchars($slide_webp); ?>"><?php endif; ?>
                <img src="<?php echo htmlspecialchars($slide_src); ?>" alt="<?php echo htmlspecialchars($alt); ?>"<?php echo $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"'; ?>>
            </picture>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="container hero-main-inner">
        <div class="hero-main-content<?php echo $has_slider ? ' hero-captions' : ''; ?>" <?php echo $has_slider ? 'aria-live="off"' : ''; ?>>
            <?php if ($has_slider): ?>
                <?php foreach ($hero_slides as $i => $slide):
                    $headline = trim((string) $slide['title']) !== '' ? trim((string) $slide['title']) : $hero['title'];
                    $text = trim((string) $slide['subtitle']) !== '' ? trim((string) $slide['subtitle']) : $hero['description'];
                    $render_caption($i, $headline, $text);
                endforeach; ?>
            <?php else: ?>
                <?php $render_caption(0, $hero['title'], $hero['description']); ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($has_slider && $slide_count > 1): ?>
    <button type="button" class="hero-arrow hero-prev" aria-label="Previous slide"><i class="fas fa-chevron-left"></i></button>
    <button type="button" class="hero-arrow hero-next" aria-label="Next slide"><i class="fas fa-chevron-right"></i></button>

    <div class="hero-controls">
        <div class="hero-dots" role="tablist" aria-label="Choose slide">
            <?php foreach ($hero_slides as $i => $slide): ?>
            <button type="button" class="hero-dot<?php echo $i === 0 ? ' is-active' : ''; ?>" role="tab" aria-label="Slide <?php echo $i + 1; ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="hero-progress" aria-hidden="true"><span></span></div>
    <?php endif; ?>
</section>

<!-- Quick links under the slider (texts editable in Page Content) -->
<?php
$quick_cards = [];
foreach ([1 => 'fa-user-plus', 2 => 'fa-book-open', 3 => 'fa-images', 4 => 'fa-location-dot'] as $n => $icon) {
    $q_title = content("home_quick_{$n}_title");
    if ($q_title !== '') {
        $quick_cards[] = ['icon' => $icon, 'title' => $q_title, 'text' => content("home_quick_{$n}_text"), 'link' => content("home_quick_{$n}_link")];
    }
}
?>
<?php if (!empty($quick_cards)): ?>
<section class="quick-strip">
    <div class="container">
        <div class="quick-grid">
            <?php foreach ($quick_cards as $qc): ?>
            <a href="<?php echo htmlspecialchars($qc['link']); ?>" class="quick-card">
                <span class="quick-icon"><i class="fas <?php echo htmlspecialchars($qc['icon']); ?>"></i></span>
                <span class="quick-text"><strong><?php echo htmlspecialchars($qc['title']); ?></strong><span><?php echo htmlspecialchars($qc['text']); ?></span></span>
                <i class="fas fa-arrow-right quick-arrow" aria-hidden="true"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Moving Notifications -->
<?php
$notifications = mysqli_query($conn, "SELECT DISTINCT * FROM notifications WHERE status = 'active' ORDER BY display_order ASC LIMIT 10");
if ($notifications && mysqli_num_rows($notifications) > 0):
?>
<div class="notice-bar">
    <div class="notice-label"><i class="fas fa-bullhorn"></i> Notices</div>
    <div class="notice-viewport">
        <div class="notification-ticker">
            <?php
            $notice_items = [];
            while ($notif = mysqli_fetch_assoc($notifications)) {
                $title = '<i class="fas fa-circle" aria-hidden="true"></i>' . htmlspecialchars($notif['title']);
                $notice_items[] = $notif['link']
                    ? '<a href="' . htmlspecialchars($notif['link']) . '" class="notice-item">' . $title . '</a>'
                    : '<span class="notice-item">' . $title . '</span>';
            }
            // Printed twice so the loop scrolls seamlessly
            echo implode('', $notice_items) . implode('', $notice_items);
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- About Section -->
<?php
$about_home = getAboutContent();
$first_sentence = function ($text) {
    $parts = preg_split('/(?<=[.!?])\s+/', trim($text), 2);
    return $parts[0];
};
$home_values = [];
foreach (getFeatureCards('core_values') as $value_card) {
    $home_values[$value_card['title']] = $value_card['icon'];
}
?>
<section class="about-home">
    <div class="container">
        <div class="about-split">
            <div class="about-media reveal">
                <img src="<?php echo htmlspecialchars(content('home_about_image')); ?>" alt="<?php echo htmlspecialchars(getSiteName()); ?>" loading="lazy">
            </div>
            <div class="about-copy reveal">
                <span class="eyebrow"><?php echo htmlspecialchars(content('home_about_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('home_about_heading')); ?></h2>
                <p class="about-lead"><?php echo htmlspecialchars(content('home_about_text')); ?></p>

                <div class="about-points">
                    <div class="about-point">
                        <span class="about-point-icon"><i class="fas fa-bullseye"></i></span>
                        <div>
                            <h3>Our Mission</h3>
                            <p><?php echo htmlspecialchars($first_sentence($about_home['mission'])); ?></p>
                        </div>
                    </div>
                    <div class="about-point">
                        <span class="about-point-icon"><i class="fas fa-eye"></i></span>
                        <div>
                            <h3>Our Vision</h3>
                            <p><?php echo htmlspecialchars($first_sentence($about_home['vision'])); ?></p>
                        </div>
                    </div>
                </div>

                <?php if (!empty($home_values)): ?>
                <div class="about-values">
                    <h3>Our Core Values</h3>
                    <ul>
                        <?php foreach ($home_values as $value_title => $value_icon): ?>
                            <li><i class="<?php echo htmlspecialchars($value_icon); ?>"></i> <?php echo htmlspecialchars($value_title); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <a href="about" class="btn btn-primary"><?php echo htmlspecialchars(content('home_about_btn_label')); ?> <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<?php
$stats = getStatistics();
// [value, label, approximate?]: settings-entered numbers get a "+", live table counts are exact
$stat_items = [
    [$stats['students'], content('stats_label_students'), true],
    [$stats['teachers'], content('stats_label_faculty'), trim(getSetting('stats_teachers', '')) !== ''],
    [$stats['years'], content('stats_label_years'), true],
    [$stats['courses'], content('stats_label_programs'), trim(getSetting('stats_courses', '')) !== ''],
];
$stat_items = array_values(array_filter($stat_items, function ($item) {
    return trim((string) $item[0]) !== '';
}));
?>
<?php if (!empty($stat_items)): ?>
<section class="stats">
    <div class="container">
        <div class="stats-grid">
            <?php foreach ($stat_items as $item): ?>
            <div class="stat-item">
                <h3><?php echo htmlspecialchars($item[0]) . ($item[2] ? '+' : ''); ?></h3>
                <p><?php echo htmlspecialchars($item[1]); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Academics Section -->
<?php
// Level names only: no grade ranges are stated except Secondary, which comes from the
// existing Matric (9th & 10th) programme in the courses data.
$academic_levels = [];
foreach ([1 => 'fa-seedling', 2 => 'fa-book-reader', 3 => 'fa-lightbulb', 4 => 'fa-graduation-cap'] as $n => $icon) {
    $academic_levels[] = ['icon' => $icon, 'title' => content("academics_{$n}_title"), 'desc' => content("academics_{$n}_desc")];
}
?>
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_academics_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_academics_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_academics_subtitle')); ?></p>
        </div>
        <div class="card-grid level-grid">
            <?php foreach ($academic_levels as $i => $level): ?>
            <div class="card level-card reveal">
                <div class="level-card-top">
                    <div class="card-icon"><i class="fas <?php echo htmlspecialchars($level['icon']); ?>"></i></div>
                    <span class="level-stage">Stage <?php echo sprintf('%02d', $i + 1); ?></span>
                </div>
                <h3><?php echo htmlspecialchars($level['title']); ?></h3>
                <p><?php echo htmlspecialchars($level['desc']); ?></p>
                <a href="courses" class="level-link">Learn More <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_why_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_why_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_why_subtitle')); ?></p>
        </div>
        <div class="card-grid why-grid">
            <?php renderFeatureCardGrid('why_choose_us'); ?>
        </div>
    </div>
</section>

<!-- Facilities -->
<?php if (!empty(getFeatureCards('facilities'))): ?>
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_facilities_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_facilities_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_facilities_subtitle')); ?></p>
        </div>
        <div class="card-grid why-grid">
            <?php renderFeatureCardGrid('facilities'); ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Leadership -->
<?php include 'includes/leadership_section.php'; ?>

<!-- Learning Philosophy Intro -->
<section class="philosophy-band">
    <div class="container">
        <div class="philosophy-inner reveal">
            <i class="fas fa-quote-left philosophy-mark" aria-hidden="true"></i>
            <span class="eyebrow on-dark"><?php echo htmlspecialchars(content('home_philosophy_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_philosophy_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_philosophy_text')); ?></p>
        </div>
    </div>
</section>

<!-- Our Approach to Assessment -->
<section>
    <div class="container">
        <div class="grid-2 reveal">
            <div class="assessment-copy">
                <span class="eyebrow"><?php echo htmlspecialchars(content('home_assessment_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('home_assessment_heading')); ?></h2>
                <p><?php echo nl2br(htmlspecialchars(content('assessment_paragraph_1'))); ?></p>
                <p><?php echo nl2br(htmlspecialchars(content('assessment_paragraph_2'))); ?></p>
                <a href="about" class="btn btn-primary">Learn More</a>
            </div>
            <div class="assessment-panel">
                <?php
                $assessment_features = mysqli_query($conn, "SELECT DISTINCT * FROM feature_cards WHERE section = 'assessment_features' AND status = 'active' ORDER BY display_order ASC, id ASC");
                while ($assessment_features && ($f = mysqli_fetch_assoc($assessment_features))): ?>
                <div class="assessment-item">
                    <span class="assessment-icon"><i class="<?php echo htmlspecialchars($f['icon']); ?>"></i></span>
                    <div>
                        <strong><?php echo htmlspecialchars($f['title']); ?></strong>
                        <span><?php echo htmlspecialchars($f['description']); ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</section>

<!-- Learner Attributes -->
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_attributes_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_attributes_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_attributes_subtitle')); ?></p>
        </div>
        <div class="grid-4">
            <?php renderFeatureCardGrid('learner_attributes', true); ?>
        </div>
    </div>
</section>

<!-- Digital School Management System -->
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><i class="fas fa-laptop-code"></i> <?php echo htmlspecialchars(content('home_digital_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_digital_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_digital_subtitle')); ?></p>
        </div>

        <div class="grid-auto mb-30">
            <?php foreach (getFeatureCards('digital_features') as $f): ?>
                <div class="feature-row reveal">
                    <div class="feature-icon"><i class="<?php echo htmlspecialchars($f['icon']); ?>"></i></div>
                    <div>
                        <h3><?php echo htmlspecialchars($f['title']); ?></h3>
                        <p><?php echo htmlspecialchars($f['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Our Campuses -->
<?php $home_campuses = mysqli_query($conn, "SELECT DISTINCT * FROM campuses WHERE status = 'active' ORDER BY display_order ASC, id ASC"); ?>
<?php if ($home_campuses && mysqli_num_rows($home_campuses) > 0): ?>
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_campuses_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_campuses_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_campuses_subtitle')); ?></p>
        </div>
        <div class="card-grid campus-grid">
            <?php while ($c = mysqli_fetch_assoc($home_campuses)): ?>
            <div class="card campus-card reveal">
                <?php if (!empty($c['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($c['image_path']); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" loading="lazy" class="campus-card-img">
                <?php else: ?>
                    <div class="card-icon"><i class="fas <?php echo htmlspecialchars($c['icon'] ?: 'fa-school'); ?>"></i></div>
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($c['name']); ?></h3>
                <?php if (!empty($c['tagline'])): ?>
                    <p class="campus-card-tagline"><?php echo htmlspecialchars($c['tagline']); ?></p>
                <?php endif; ?>
                <?php if (!empty($c['description'])): ?>
                    <p><?php echo htmlspecialchars($c['description']); ?></p>
                <?php endif; ?>
                <?php if (!empty($c['address']) || !empty($c['phone'])): ?>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-color); text-align: left; font-size: 14px; color: var(--text-light);">
                    <?php if (!empty($c['address'])): ?>
                        <p style="margin-bottom: 6px;"><i class="fas fa-map-marker-alt" style="color: var(--primary-color); width: 16px;"></i> <?php echo htmlspecialchars($c['address']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($c['phone'])): ?>
                        <p><i class="fas fa-phone" style="color: var(--primary-color); width: 16px;"></i> <?php echo htmlspecialchars($c['phone']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <a href="campuses" class="level-link campus-card-link">Visit Campus Page <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Campus Life: one photo card per gallery category (cover = oldest photo in the category); icon tiles if the gallery is empty -->
<?php
$life_cards = [];
$life_result = mysqli_query($conn, "SELECT DISTINCT * FROM gallery_categories WHERE status = 'active' ORDER BY display_order ASC, name ASC");
while ($life_result && ($cat = mysqli_fetch_assoc($life_result))) {
    $slug = mysqli_real_escape_string($conn, $cat['slug']);
    $photos = mysqli_query($conn, "SELECT DISTINCT image_path FROM gallery WHERE category = '$slug' AND status = 'active' ORDER BY image_path ASC");
    $paths = [];
    while ($photos && ($ph = mysqli_fetch_assoc($photos))) { $paths[] = $ph['image_path']; }
    if (!empty($paths)) {
        $life_cards[] = ['name' => $cat['name'], 'cover' => $paths[0], 'count' => count($paths)];
    }
}
$campus_life = [
    ['icon' => 'fa-users', 'title' => 'Student Activities', 'desc' => 'Hands-on activities that help students collaborate, take the lead and discover their interests.'],
    ['icon' => 'fa-futbol', 'title' => 'Sports', 'desc' => 'Sports days and games that build fitness, teamwork and sportsmanship.'],
    ['icon' => 'fa-calendar-alt', 'title' => 'Events', 'desc' => 'School events, exhibitions and parent-teacher meetings that bring our community together.'],
    ['icon' => 'fa-trophy', 'title' => 'Competitions', 'desc' => 'Debates and inter-school contests where students showcase their talent.'],
    ['icon' => 'fa-star', 'title' => 'Celebrations', 'desc' => 'Prize distributions and school celebrations that recognise effort and achievement.'],
    ['icon' => 'fa-microscope', 'title' => 'Educational Activities', 'desc' => 'Exhibitions, trips and learning experiences that bring lessons to life.'],
];
?>
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_life_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_life_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_life_subtitle')); ?></p>
        </div>
        <?php if (!empty($life_cards)): ?>
        <div class="life-photo-grid">
            <?php foreach ($life_cards as $card): ?>
            <a href="gallery" class="life-photo reveal">
                <?php $cover = mediaThumb($card['cover']); ?><img src="<?php echo htmlspecialchars($cover['src']); ?>"<?php if ($cover['width']): ?> width="<?php echo $cover['width']; ?>" height="<?php echo $cover['height']; ?>"<?php endif; ?> decoding="async" alt="<?php echo htmlspecialchars($card['name']); ?> at <?php echo htmlspecialchars(getSiteName()); ?>" loading="lazy">
                <span class="life-photo-caption">
                    <strong><?php echo htmlspecialchars($card['name']); ?></strong>
                    <small><?php echo intval($card['count']); ?> photos</small>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="campus-life-grid">
            <?php foreach ($campus_life as $item): ?>
            <div class="campus-tile reveal">
                <i class="fas <?php echo htmlspecialchars($item['icon']); ?> campus-tile-bg" aria-hidden="true"></i>
                <div class="campus-tile-icon"><i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i></div>
                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                <p><?php echo htmlspecialchars($item['desc']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="btn-group campus-life-actions">
            <a href="gallery" class="btn btn-primary">View Photo Gallery</a>
            <a href="events" class="btn btn-outline-dark">News &amp; Events</a>
        </div>
    </div>
</section>

<!-- Testimonials: only shown once real, approved reviews exist (submitted on the Alumni page, approved in AdminCP) -->
<?php $home_reviews = mysqli_query($conn, "SELECT DISTINCT * FROM alumni_reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 6"); ?>
<?php if ($home_reviews && mysqli_num_rows($home_reviews) > 0): ?>
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_testimonials_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_testimonials_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_testimonials_subtitle')); ?></p>
        </div>
        <div class="testimonial-carousel reveal">
            <div class="testimonial-track">
                <?php while ($rev = mysqli_fetch_assoc($home_reviews)): ?>
                <figure class="testimonial-card">
                    <i class="fas fa-quote-left testimonial-mark" aria-hidden="true"></i>
                    <div class="testimonial-stars" aria-label="<?php echo intval($rev['rating']); ?> out of 5 stars">
                        <?php for ($i = 0; $i < intval($rev['rating']); $i++) echo '<i class="fas fa-star"></i>'; ?>
                    </div>
                    <blockquote class="testimonial-quote"><?php echo htmlspecialchars($rev['review']); ?></blockquote>
                    <figcaption class="testimonial-author">
                        <div class="testimonial-avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr(trim($rev['name']), 0, 1))); ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars($rev['name']); ?></strong>
                            <span>Alumni<?php echo !empty($rev['passing_year']) ? ' · Batch ' . htmlspecialchars($rev['passing_year']) : ''; ?></span>
                        </div>
                    </figcaption>
                </figure>
                <?php endwhile; ?>
            </div>
            <button type="button" class="testimonial-nav prev" aria-label="Previous review"><i class="fas fa-chevron-left"></i></button>
            <button type="button" class="testimonial-nav next" aria-label="Next review"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="text-center mt-30">
            <a href="alumni#review" class="btn btn-outline-dark">Share Your Experience</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- News & Events -->
<?php
$home_events = mysqli_query($conn, "SELECT DISTINCT * FROM events WHERE event_date >= CURDATE() AND status = 'active' ORDER BY event_date ASC LIMIT 3");
// One tidy row of three: upcoming events first, then the latest news fills the remaining slots
$event_count = $home_events ? mysqli_num_rows($home_events) : 0;
$news_slots = 3 - $event_count;
$home_news = $news_slots > 0 ? mysqli_query($conn, "SELECT DISTINCT * FROM news WHERE status = 'active' ORDER BY created_at DESC LIMIT $news_slots") : false;
$has_updates = $event_count > 0 || ($home_news && mysqli_num_rows($home_news) > 0);
?>
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_news_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_news_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_news_subtitle')); ?></p>
        </div>
        <?php if ($has_updates): ?>
        <div class="card-grid update-grid">
            <?php
            if ($home_events) { while ($event = mysqli_fetch_assoc($home_events)) { renderUpdateCard('event', $event); } }
            if ($home_news) { while ($post = mysqli_fetch_assoc($home_news)) { renderUpdateCard('news', $post); } }
            ?>
        </div>
        <?php else: ?>
        <div class="text-center reveal" style="padding: 20px;">
            <i class="fas fa-newspaper" style="font-size: 60px; color: var(--border-color); margin-bottom: 20px;"></i>
            <p style="color: var(--text-light); font-size: 18px;">New stories and events are on their way — check back soon!</p>
        </div>
        <?php endif; ?>
        <div class="text-center mt-30">
            <a href="events" class="btn btn-primary">View All News &amp; Events</a>
        </div>
    </div>
</section>

<!-- FAQs -->
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_faq_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_faq_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_faq_subtitle')); ?></p>
        </div>
        <div class="faq-contact">
            <div class="faq-col reveal">
                <?php
                // Same admin-managed FAQs as the Contact page (AdminCP > FAQs)
                $home_faqs = mysqli_query($conn, "SELECT DISTINCT * FROM faqs WHERE status = 'active' ORDER BY display_order ASC, id ASC LIMIT 5");
                ?>
                <?php if ($home_faqs && mysqli_num_rows($home_faqs) > 0): ?>
                <div class="faq-list">
                    <?php while ($faq = mysqli_fetch_assoc($home_faqs)): ?>
                    <div class="faq-item">
                        <button type="button" class="faq-question">
                            <span><?php echo htmlspecialchars($faq['question']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="faq-answer">
                            <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php endif; ?>
                <ul class="faq-contact-list">
                    <li><span class="info-icon"><i class="fas fa-map-marker-alt"></i></span> <span><?php echo nl2br(htmlspecialchars(getSiteAddress())); ?></span></li>
                    <li><span class="info-icon"><i class="fas fa-phone"></i></span> <span><?php echo htmlspecialchars(getSitePhone()); ?></span></li>
                    <li><span class="info-icon"><i class="fas fa-envelope"></i></span> <span><?php echo htmlspecialchars(getSiteEmail()); ?></span></li>
                </ul>
            </div>
            <div class="card faq-form-card reveal">
                <span class="eyebrow"><?php echo htmlspecialchars(content('home_contact_eyebrow')); ?></span>
                <h3><?php echo htmlspecialchars(content('home_contact_heading')); ?></h3>
                <p class="faq-form-text"><?php echo htmlspecialchars(content('home_contact_text')); ?></p>
                <form method="POST" action="contact">
                    <?php echo formGuardFields(); ?>
                    <div class="form-group">
                        <label for="home_name">Your Name *</label>
                        <input type="text" id="home_name" name="name" required placeholder="Enter your name">
                    </div>
                    <div class="form-group">
                        <label for="home_email">Email Address *</label>
                        <input type="email" id="home_email" name="email" required placeholder="your.email@example.com">
                    </div>
                    <div class="form-group">
                        <label for="home_phone">Phone Number *</label>
                        <input type="tel" id="home_phone" name="phone" required placeholder="03001234567">
                    </div>
                    <input type="hidden" name="subject" value="General Question">
                    <div class="form-group">
                        <label for="home_message">Your Message *</label>
                        <textarea id="home_message" name="message" required placeholder="Write your message here..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Admissions -->
<?php
// Process steps come from the admission_process cards; the DB can hold duplicate rows, so key by title
$adm_steps = [];
foreach (getFeatureCards('admission_process') as $step) {
    $adm_steps[$step['title']] = $step['description'];
}
$adm_docs = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', content('home_adm_docs')))));
$adm_phone = trim(explode(',', getSitePhone())[0]);
?>
<section class="admissions-section">
    <div class="container">
        <div class="admissions-panel reveal">
            <div class="admissions-intro">
                <span class="eyebrow on-dark"><?php echo htmlspecialchars(content('home_adm_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('home_adm_heading')); ?></h2>
                <p><?php echo htmlspecialchars(content('home_adm_text')); ?></p>

                <div class="btn-group">
                    <a href="admission" class="btn btn-secondary"><?php echo htmlspecialchars(content('home_adm_btn1_label')); ?></a>
                    <a href="contact" class="btn btn-outline"><?php echo htmlspecialchars(content('home_adm_btn2_label')); ?></a>
                </div>

                <ul class="admissions-contact">
                    <li><i class="fas fa-map-marker-alt"></i> <span><?php echo htmlspecialchars(trim((string) strtok(getSiteAddress(), "\n"))); ?></span></li>
                    <?php if ($adm_phone !== ''): ?>
                    <li><i class="fas fa-phone"></i> <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $adm_phone)); ?>"><?php echo htmlspecialchars($adm_phone); ?></a></li>
                    <?php endif; ?>
                    <?php if (hasSiteEmail()): ?>
                    <li><i class="fas fa-envelope"></i> <a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><?php echo htmlspecialchars(getSiteEmail()); ?></a></li>
                    <?php endif; ?>
                    <li><i class="fas fa-clock"></i> <span><?php echo htmlspecialchars(getOfficeHours()); ?></span></li>
                </ul>
            </div>

            <div class="admissions-details">
                <?php if (!empty($adm_steps)): ?>
                <h3>How to Apply</h3>
                <ol class="admissions-steps">
                    <?php foreach ($adm_steps as $step_title => $step_desc): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($step_title); ?></strong>
                        <span><?php echo htmlspecialchars($step_desc); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>

                <h3>Documents Required</h3>
                <ul class="admissions-docs">
                    <?php foreach ($adm_docs as $doc): ?>
                    <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($doc); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="admissions-note"><?php echo htmlspecialchars(content('home_adm_docs_note')); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Closing call to action -->
<section class="cta-band">
    <div class="container">
        <div class="cta-band-inner reveal">
            <h2><?php echo htmlspecialchars(content('home_cta_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_cta_text')); ?></p>
            <div class="btn-group">
                <a href="contact" class="btn btn-outline"><?php echo htmlspecialchars(content('home_cta_btn1_label')); ?></a>
                <a href="admission" class="btn btn-secondary"><?php echo htmlspecialchars(content('home_cta_btn2_label')); ?></a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
