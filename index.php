<?php
require_once 'includes/config.php';
$page_title = 'Home';
$meta_description = content('seo_home_description');

// ---------------------------------------------------------------------------
// Forms on this page: the admission inquiry and the contact form. Both are saved
// to Contact Messages (AdminCP) through the same code as the Contact page; a
// thank-you message only appears after the message was actually stored.
// ---------------------------------------------------------------------------
$inquiry_classes = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', content('home_inquiry_classes')))));
$inquiry_classes[] = 'Not sure yet';

$form_flash = formFlash();
$form_flash_for = $_SESSION['form_flash_for'] ?? '';
unset($_SESSION['form_flash_for']);
$inquiry_error = '';
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $which_form = $_POST['form_name'] ?? '';
    $guard_problem = formGuardError();

    if ($which_form === 'inquiry') {
        $inquiry_error = $guard_problem ?: formValidate([
            'inq_guardian' => ['Parent / guardian name', 'required'],
            'inq_student' => ["Child's name", 'required'],
            'inq_phone' => ['phone number', 'required', 'phone'],
            'inq_email' => ['Email', 'email'],
            'inq_class' => ['Desired class', 'required'],
        ]);
        if ($inquiry_error === '' && !in_array(formText('inq_class'), $inquiry_classes, true)) {
            $inquiry_error = 'Please choose a class from the list.';
        }
        if ($inquiry_error === '') {
            require_once __DIR__ . '/includes/contact_handler.php';
            $note = formText('inq_message', 3000);
            $saved = saveContactMessage([
                'name' => formText('inq_guardian', 100),
                'email' => formText('inq_email', 150),
                'phone' => formText('inq_phone', 30),
                'subject' => 'Admission Inquiry: ' . formText('inq_class', 60),
                'message' => "Child's name: " . formText('inq_student', 100) . "\nDesired class: " . formText('inq_class', 60) . ($note !== '' ? "\n\n" . $note : ''),
            ], 'A family has sent an admission inquiry from the homepage.');
            if ($saved) {
                $form_sent = true;
                $_SESSION['form_flash_for'] = 'inquiry';
                formDone('Thank you! Your admission inquiry has been received. Our admissions office will contact you soon.', 'inquiry');
            }
            $inquiry_error = 'Sorry, your inquiry could not be sent right now. Please try again in a few minutes.';
        }
    } elseif ($which_form === 'contact') {
        $contact_error = $guard_problem ?: formValidate([
            'c_name' => ['Your name', 'required'],
            'c_email' => ['Email', 'required', 'email'],
            'c_phone' => ['phone number', 'phone'],
            'c_message' => ['Message', 'required'],
        ]);
        if ($contact_error === '') {
            require_once __DIR__ . '/includes/contact_handler.php';
            $saved = saveContactMessage([
                'name' => formText('c_name', 100),
                'email' => formText('c_email', 150),
                'phone' => formText('c_phone', 30),
                'subject' => 'General Question',
                'message' => formText('c_message', 5000),
            ], 'Someone has sent a message through the homepage contact form.');
            if ($saved) {
                $form_sent = true;
                $_SESSION['form_flash_for'] = 'contact';
                formDone('Thank you for your message! We have received it and will get back to you soon.', 'contact');
            }
            $contact_error = 'Sorry, your message could not be sent right now. Please try again in a few minutes.';
        }
    }
}

// "Inspiring Young Minds. Building Bright Futures." -> second sentence in gold
$hero = getHeroContent();
$hero_title_html = htmlspecialchars($hero['title']);
if (preg_match('/^(.+?[.!?])\s+(.+)$/u', $hero['title'], $m)) {
    $hero_title_html = htmlspecialchars($m[1]) . ' <span class="text-gold">' . htmlspecialchars($m[2]) . '</span>';
}
?>
<?php include 'includes/header.php'; ?>

<!-- =====================================================================
     Hero: background photos from Home Slider (crossfade), one fixed headline
     ===================================================================== -->
<?php
$hero_slides = getHeroCarouselSlides();
if (empty($hero_slides) && $hero['image'] !== '') {
    $hero_slides = [['image_path' => $hero['image'], 'title' => '']];
}
$slide_count = count($hero_slides);
$hero_points = array_slice(array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', content('home_hero_points'))))), 0, 3);
$hero_point_icons = ['fa-book-open', 'fa-hand-holding-heart', 'fa-star'];
?>
<section class="home-hero<?php echo $slide_count > 1 ? ' hero-slider' : ''; ?>" id="hero"<?php echo $slide_count > 1 ? ' aria-roledescription="carousel" aria-label="School photos"' : ''; ?>>
    <div class="hero-slides" aria-hidden="<?php echo $slide_count ? 'false' : 'true'; ?>">
        <?php foreach ($hero_slides as $i => $slide):
            $slide_src = $slide['image_path'];
            $slide_webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $slide_src);
            $has_webp = $slide_webp !== $slide_src && is_file(__DIR__ . '/' . $slide_webp);
            $alt = trim((string) ($slide['title'] ?? '')) !== '' ? trim((string) $slide['title']) : 'Students at ' . getSiteName();
        ?>
        <div class="hero-slide<?php echo $i === 0 ? ' is-active' : ''; ?>"<?php echo $slide_count > 1 ? ' role="group" aria-roledescription="slide" aria-label="' . ($i + 1) . ' of ' . $slide_count . '"' : ''; ?>>
            <picture>
                <?php if ($has_webp): ?><source type="image/webp" srcset="<?php echo htmlspecialchars($slide_webp); ?>"><?php endif; ?>
                <img src="<?php echo htmlspecialchars($slide_src); ?>" alt="<?php echo htmlspecialchars($alt); ?>" width="1920" height="800"<?php echo $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"'; ?>>
            </picture>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="hero-shade" aria-hidden="true"></div>

    <div class="container hero-inner">
        <div class="hero-copy">
            <span class="hero-eyebrow"><img src="<?php echo getLogoSmallPath(); ?>" alt="" width="26" height="24"> <?php echo htmlspecialchars(content('home_hero_eyebrow')); ?></span>
            <h1 class="hero-title"><?php echo $hero_title_html; ?></h1>
            <?php if ($hero['description'] !== ''): ?>
            <p class="hero-text"><?php echo htmlspecialchars($hero['description']); ?></p>
            <?php endif; ?>
            <div class="hero-actions">
                <a href="<?php echo htmlspecialchars(content('home_hero_btn1_link')); ?>" class="btn btn-secondary btn-lg"><?php echo htmlspecialchars(content('home_hero_btn1_label')); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                <a href="<?php echo htmlspecialchars(content('home_hero_btn2_link')); ?>" class="btn btn-outline btn-lg"><?php echo htmlspecialchars(content('home_hero_btn2_label')); ?></a>
            </div>
            <?php if (!empty($hero_points)): ?>
            <ul class="hero-points">
                <?php foreach ($hero_points as $i => $point): ?>
                <li><i class="fas <?php echo $hero_point_icons[$i]; ?>" aria-hidden="true"></i> <?php echo htmlspecialchars($point); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($slide_count > 1): ?>
    <div class="hero-controls">
        <button type="button" class="hero-arrow hero-prev" aria-label="Previous photo"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
        <div class="hero-dots" role="tablist" aria-label="Choose photo">
            <?php for ($i = 0; $i < $slide_count; $i++): ?>
            <button type="button" class="hero-dot<?php echo $i === 0 ? ' is-active' : ''; ?>" role="tab" aria-label="Photo <?php echo $i + 1; ?>" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"></button>
            <?php endfor; ?>
        </div>
        <button type="button" class="hero-arrow hero-next" aria-label="Next photo"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
    </div>
    <div class="hero-progress" aria-hidden="true"><span></span></div>
    <?php endif; ?>
</section>

<!-- Quick links overlapping the bottom of the hero (Page Content -> Home: Sections) -->
<?php
$quick_cards = [];
foreach ([1 => 'fa-user-plus', 2 => 'fa-book-open', 3 => 'fa-images', 4 => 'fa-envelope-open-text'] as $n => $icon) {
    $q_title = content("home_quick_{$n}_title");
    if ($q_title !== '') {
        $quick_cards[] = ['icon' => $icon, 'title' => $q_title, 'text' => content("home_quick_{$n}_text"), 'link' => content("home_quick_{$n}_link")];
    }
}
?>
<?php if (!empty($quick_cards)): ?>
<section class="quick-strip" aria-label="Quick links">
    <div class="container">
        <div class="quick-grid">
            <?php foreach ($quick_cards as $qc): ?>
            <a href="<?php echo htmlspecialchars($qc['link']); ?>" class="quick-card">
                <span class="quick-icon"><i class="fas <?php echo htmlspecialchars($qc['icon']); ?>" aria-hidden="true"></i></span>
                <span class="quick-text"><strong><?php echo htmlspecialchars($qc['title']); ?></strong><span><?php echo htmlspecialchars($qc['text']); ?></span></span>
                <i class="fas fa-arrow-right quick-arrow" aria-hidden="true"></i>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Scrolling notices (AdminCP -> Notifications), only when there are active ones -->
<?php
$notifications = mysqli_query($conn, "SELECT DISTINCT * FROM notifications WHERE status = 'active' ORDER BY display_order ASC LIMIT 10");
if ($notifications && mysqli_num_rows($notifications) > 0):
?>
<div class="notice-bar">
    <div class="container notice-inner">
        <div class="notice-label"><i class="fas fa-bullhorn" aria-hidden="true"></i> Notices</div>
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
</div>
<?php endif; ?>

<!-- =====================================================================
     Welcome / About
     ===================================================================== -->
<?php
$about_home = getAboutContent();
$first_sentence = function ($text) {
    $parts = preg_split('/(?<=[.!?])\s+/', trim($text), 2);
    return $parts[0];
};
?>
<section class="home-about">
    <div class="container">
        <div class="home-about-grid">
            <div class="home-about-media">
                <div class="home-about-photo">
                    <img src="<?php echo htmlspecialchars(content('home_about_image')); ?>" alt="A student learning at <?php echo htmlspecialchars(getSiteName()); ?>" loading="lazy">
                </div>
                <div class="home-about-badge" aria-hidden="true">
                    <img src="<?php echo getLogoSmallPath(); ?>" alt="" width="64" height="60">
                    <span><strong><?php echo htmlspecialchars(getSiteName()); ?></strong><?php echo htmlspecialchars(content('site_tagline')); ?></span>
                </div>
            </div>
            <div class="home-about-copy">
                <span class="eyebrow"><?php echo htmlspecialchars(content('home_about_eyebrow')); ?></span>
                <h2 class="section-title"><?php echo htmlspecialchars(content('home_about_heading')); ?></h2>
                <p class="lead"><?php echo htmlspecialchars(content('home_about_text')); ?></p>

                <div class="home-about-points">
                    <?php if (trim($about_home['mission']) !== ''): ?>
                    <div class="home-about-point">
                        <span class="point-icon"><i class="fas fa-bullseye" aria-hidden="true"></i></span>
                        <div>
                            <h3>Our Mission</h3>
                            <p><?php echo htmlspecialchars($first_sentence($about_home['mission'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (trim($about_home['vision']) !== ''): ?>
                    <div class="home-about-point">
                        <span class="point-icon"><i class="fas fa-eye" aria-hidden="true"></i></span>
                        <div>
                            <h3>Our Vision</h3>
                            <p><?php echo htmlspecialchars($first_sentence($about_home['vision'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <a href="about" class="btn btn-primary"><?php echo htmlspecialchars(content('home_about_btn_label')); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
</section>

<!-- Statistics: only figures entered in Settings -> Statistics (nothing is estimated or counted automatically) -->
<?php
$stat_items = array_values(array_filter([
    [trim(getSetting('stats_students', '')), content('stats_label_students')],
    [trim(getSetting('stats_teachers', '')), content('stats_label_faculty')],
    [trim(getSetting('stats_years', '')), content('stats_label_years')],
    [trim(getSetting('stats_courses', '')), content('stats_label_programs')],
], fn($item) => $item[0] !== ''));
?>
<?php if (!empty($stat_items)): ?>
<section class="stats" aria-label="School in numbers">
    <div class="container">
        <div class="stats-grid">
            <?php foreach ($stat_items as $item): ?>
            <div class="stat-item">
                <p class="stat-value"><?php echo htmlspecialchars($item[0]); ?></p>
                <p class="stat-label"><?php echo htmlspecialchars($item[1]); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =====================================================================
     Academic programs (Page Content -> Home: Sections; empty title hides a card)
     ===================================================================== -->
<?php
$academic_levels = [];
foreach ([1 => 'fa-seedling', 2 => 'fa-book-reader', 3 => 'fa-lightbulb', 4 => 'fa-graduation-cap'] as $n => $icon) {
    $level_title = content("academics_{$n}_title");
    if ($level_title !== '') {
        $academic_levels[] = ['icon' => $icon, 'title' => $level_title, 'desc' => content("academics_{$n}_desc")];
    }
}
?>
<?php if (!empty($academic_levels)): ?>
<section class="home-programs bg-light">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_academics_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_academics_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_academics_subtitle')); ?></p>
        </div>
        <div class="program-grid">
            <?php foreach ($academic_levels as $i => $level): ?>
            <article class="program-card">
                <div class="program-card-top">
                    <span class="program-icon"><i class="fas <?php echo htmlspecialchars($level['icon']); ?>" aria-hidden="true"></i></span>
                    <span class="program-stage" aria-hidden="true"><?php echo sprintf('%02d', $i + 1); ?></span>
                </div>
                <h3><?php echo htmlspecialchars($level['title']); ?></h3>
                <p><?php echo htmlspecialchars($level['desc']); ?></p>
                <a href="courses" class="text-link">Explore programs<span class="sr-only">: <?php echo htmlspecialchars($level['title']); ?></span> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =====================================================================
     Why choose us (Feature Cards -> Why Choose Us)
     ===================================================================== -->
<?php $why_cards = getFeatureCards('why_choose_us'); ?>
<?php if (!empty($why_cards)): ?>
<section class="home-why">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_why_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_why_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_why_subtitle')); ?></p>
        </div>
        <div class="why-grid">
            <?php foreach ($why_cards as $card): ?>
            <article class="why-card">
                <span class="why-icon"><i class="<?php echo htmlspecialchars($card['icon']); ?>" aria-hidden="true"></i></span>
                <h3><?php echo htmlspecialchars($card['title']); ?></h3>
                <p><?php echo htmlspecialchars($card['description']); ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =====================================================================
     Facilities & campus life: active facility cards (only the ones the school has
     switched on) + one photo card per gallery category
     ===================================================================== -->
<?php
$facility_cards = getFeatureCards('facilities');
$life_cards = [];
$life_result = mysqli_query($conn, "SELECT DISTINCT * FROM gallery_categories WHERE status = 'active' ORDER BY display_order ASC, name ASC");
while ($life_result && ($cat = mysqli_fetch_assoc($life_result))) {
    $slug = mysqli_real_escape_string($conn, $cat['slug']);
    $photos = mysqli_query($conn, "SELECT DISTINCT image_path FROM gallery WHERE category = '$slug' AND status = 'active' ORDER BY image_path ASC");
    $paths = [];
    while ($photos && ($ph = mysqli_fetch_assoc($photos))) { $paths[] = $ph['image_path']; }
    if (!empty($paths)) {
        $life_cards[] = ['name' => $cat['name'], 'slug' => $cat['slug'], 'cover' => $paths[0], 'count' => count($paths)];
    }
}
?>
<?php if (!empty($facility_cards) || !empty($life_cards)): ?>
<section class="home-facilities bg-light" id="facilities">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow"><?php echo htmlspecialchars(content('home_facilities_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('home_facilities_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('home_facilities_subtitle')); ?></p>
        </div>

        <?php if (!empty($facility_cards)): ?>
        <div class="facility-grid">
            <?php foreach ($facility_cards as $card): ?>
            <article class="facility-card">
                <span class="facility-icon"><i class="<?php echo htmlspecialchars($card['icon']); ?>" aria-hidden="true"></i></span>
                <div>
                    <h3><?php echo htmlspecialchars($card['title']); ?></h3>
                    <p><?php echo htmlspecialchars($card['description']); ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($life_cards)): ?>
        <div class="life-grid">
            <?php foreach ($life_cards as $card): $cover = mediaThumb($card['cover'], 960); ?>
            <a href="gallery" class="life-card">
                <img src="<?php echo htmlspecialchars($cover['src']); ?>"<?php if ($cover['width']): ?> width="<?php echo $cover['width']; ?>" height="<?php echo $cover['height']; ?>"<?php endif; ?> alt="" loading="lazy" decoding="async">
                <span class="life-card-caption">
                    <strong><?php echo htmlspecialchars($card['name']); ?></strong>
                    <small><?php echo intval($card['count']); ?> photos <i class="fas fa-arrow-right" aria-hidden="true"></i></small>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<!-- =====================================================================
     Admissions: introduction + steps, and the admission inquiry form
     ===================================================================== -->
<?php
$adm_steps = [];
foreach (getFeatureCards('admission_process') as $step) {
    $adm_steps[$step['title']] = $step['description'];   // the DB can hold duplicate rows, so key by title
}
$inq_sent = $form_flash !== '' && $form_flash_for === 'inquiry';
?>
<section class="home-admissions" id="admissions">
    <div class="container">
        <div class="admissions-shell">
            <div class="admissions-copy">
                <span class="eyebrow on-dark"><i class="fas fa-graduation-cap" aria-hidden="true"></i> <?php echo htmlspecialchars(content('home_adm_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('home_adm_heading')); ?></h2>
                <p class="lead"><?php echo htmlspecialchars(content('home_adm_text')); ?></p>

                <?php if (!empty($adm_steps)): ?>
                <ol class="admission-steps">
                    <?php foreach ($adm_steps as $step_title => $step_desc): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($step_title); ?></strong>
                        <span><?php echo htmlspecialchars($step_desc); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>

                <div class="btn-group">
                    <a href="admission" class="btn btn-secondary"><?php echo htmlspecialchars(content('home_adm_btn1_label')); ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    <?php if (hasSitePhone()): ?>
                    <a href="<?php echo htmlspecialchars(telHref(primaryPhone())); ?>" class="btn btn-outline"><i class="fas fa-phone" aria-hidden="true"></i> <?php echo htmlspecialchars(primaryPhone()); ?></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-card" id="inquiry">
                <h3><?php echo htmlspecialchars(content('home_inquiry_heading')); ?></h3>
                <p class="form-card-text"><?php echo htmlspecialchars(content('home_inquiry_text')); ?></p>

                <?php if ($inq_sent): ?>
                    <div class="alert alert-success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i> <?php echo htmlspecialchars($form_flash); ?></div>
                <?php elseif ($inquiry_error !== ''): ?>
                    <div class="alert alert-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <?php echo htmlspecialchars($inquiry_error); ?></div>
                <?php endif; ?>

                <form method="POST" action="./#inquiry" id="inquiryForm" data-validate novalidate>
                    <?php echo formGuardFields(); ?>
                    <input type="hidden" name="form_name" value="inquiry">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="inq_guardian">Parent / guardian name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="inq_guardian" name="inq_guardian" required maxlength="100" autocomplete="name" value="<?php echo old('inq_guardian'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="inq_student">Child's name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="inq_student" name="inq_student" required maxlength="100" value="<?php echo old('inq_student'); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="inq_phone">Phone number <span class="req" aria-hidden="true">*</span></label>
                            <input type="tel" id="inq_phone" name="inq_phone" required maxlength="30" autocomplete="tel" inputmode="tel" placeholder="03001234567" value="<?php echo old('inq_phone'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="inq_email">Email <span class="optional">(optional)</span></label>
                            <input type="email" id="inq_email" name="inq_email" maxlength="150" autocomplete="email" value="<?php echo old('inq_email'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="inq_class">Desired class <span class="req" aria-hidden="true">*</span></label>
                        <select id="inq_class" name="inq_class" required>
                            <option value="">Choose a class</option>
                            <?php foreach ($inquiry_classes as $class_name): ?>
                            <option value="<?php echo htmlspecialchars($class_name); ?>"<?php echo old('inq_class') === htmlspecialchars($class_name, ENT_QUOTES) ? ' selected' : ''; ?>><?php echo htmlspecialchars($class_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="inq_message">Message <span class="optional">(optional)</span></label>
                        <textarea id="inq_message" name="inq_message" rows="3" maxlength="3000"><?php echo old('inq_message'); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send Inquiry</button>
                    <p class="form-note"><span aria-hidden="true">*</span> Required fields</p>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- =====================================================================
     News & events: upcoming events list + latest news
     ===================================================================== -->
<?php
$up_events = [];
$res = mysqli_query($conn, "SELECT DISTINCT * FROM events WHERE event_date >= CURDATE() AND status = 'active' ORDER BY event_date ASC LIMIT 4");
while ($res && ($row = mysqli_fetch_assoc($res))) { $up_events[] = $row; }
$latest_news = [];
$res = mysqli_query($conn, "SELECT DISTINCT * FROM news WHERE status = 'active' ORDER BY created_at DESC LIMIT 3");
while ($res && ($row = mysqli_fetch_assoc($res))) { $latest_news[] = $row; }
$excerpt = function ($text, $len = 130) {
    $plain = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));
    return mb_strlen($plain) > $len ? rtrim(mb_substr($plain, 0, $len)) . '…' : $plain;
};
?>
<?php if (!empty($up_events) || !empty($latest_news)): ?>
<section class="home-news">
    <div class="container">
        <div class="section-header section-header-split">
            <div>
                <span class="eyebrow"><?php echo htmlspecialchars(content('home_news_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('home_news_heading')); ?></h2>
                <p><?php echo htmlspecialchars(content('home_news_subtitle')); ?></p>
            </div>
            <a href="events" class="btn btn-outline-dark">View All News &amp; Events <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>

        <div class="news-layout<?php echo empty($up_events) || empty($latest_news) ? ' is-single' : ''; ?>">
            <?php if (!empty($latest_news)): ?>
            <div class="news-list">
                <h3 class="news-col-title">Latest News</h3>
                <?php foreach ($latest_news as $i => $post): ?>
                <article class="news-card<?php echo $i === 0 ? ' is-featured' : ''; ?>">
                    <a href="events#news-<?php echo intval($post['id']); ?>" class="news-media" tabindex="-1" aria-hidden="true">
                        <?php if (!empty($post['image'])): ?>
                            <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <span class="news-placeholder"><img src="<?php echo getLogoSmallPath(); ?>" alt="" width="64" height="60" loading="lazy"></span>
                        <?php endif; ?>
                    </a>
                    <div class="news-body">
                        <span class="news-date"><i class="far fa-calendar" aria-hidden="true"></i> <?php echo date('d M Y', strtotime($post['created_at'])); ?></span>
                        <h4><a href="events#news-<?php echo intval($post['id']); ?>"><?php echo htmlspecialchars($post['title']); ?></a></h4>
                        <?php if ($i === 0): ?><p><?php echo htmlspecialchars($excerpt($post['content'])); ?></p><?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="events-panel">
                <h3 class="news-col-title">Upcoming Events</h3>
                <?php if (!empty($up_events)): ?>
                <ul class="event-list">
                    <?php foreach ($up_events as $event): $ts = strtotime($event['event_date']); ?>
                    <li class="event-item">
                        <span class="event-date" aria-hidden="true"><strong><?php echo date('d', $ts); ?></strong><?php echo date('M', $ts); ?></span>
                        <div>
                            <h4><a href="events#event-<?php echo intval($event['id']); ?>"><?php echo htmlspecialchars($event['title']); ?></a></h4>
                            <p class="event-meta">
                                <span><i class="far fa-calendar" aria-hidden="true"></i> <?php echo date('l, d F Y', $ts); ?></span>
                                <?php if (!empty($event['time'])): ?><span><i class="far fa-clock" aria-hidden="true"></i> <?php echo htmlspecialchars($event['time']); ?></span><?php endif; ?>
                                <?php if (!empty($event['location'])): ?><span><i class="fas fa-location-dot" aria-hidden="true"></i> <?php echo htmlspecialchars($event['location']); ?></span><?php endif; ?>
                            </p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="events-empty"><i class="far fa-calendar-check" aria-hidden="true"></i> No upcoming events are scheduled right now. Please check back soon.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =====================================================================
     Photo gallery: the most recent photos (AdminCP -> Gallery)
     ===================================================================== -->
<?php
$gallery_photos = [];
$res = mysqli_query($conn, "SELECT DISTINCT id, title, image_path FROM gallery WHERE status = 'active' ORDER BY created_at DESC, id DESC LIMIT 5");
while ($res && ($row = mysqli_fetch_assoc($res))) { $gallery_photos[] = $row; }
?>
<?php if (count($gallery_photos) >= 5): ?>
<section class="home-gallery bg-light">
    <div class="container">
        <div class="section-header section-header-split">
            <div>
                <span class="eyebrow"><?php echo htmlspecialchars(content('home_gallery_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('home_gallery_heading')); ?></h2>
                <p><?php echo htmlspecialchars(content('home_gallery_subtitle')); ?></p>
            </div>
            <a href="gallery" class="btn btn-outline-dark">Open Gallery <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="mosaic">
            <?php foreach ($gallery_photos as $i => $photo): $thumb = mediaThumb($photo['image_path'], $i === 0 ? 960 : 640); ?>
            <a href="gallery" class="mosaic-item">
                <img src="<?php echo htmlspecialchars($thumb['src']); ?>"<?php if ($thumb['width']): ?> width="<?php echo $thumb['width']; ?>" height="<?php echo $thumb['height']; ?>"<?php endif; ?> alt="<?php echo htmlspecialchars(trim((string) $photo['title']) !== '' ? $photo['title'] : 'School photo'); ?>" loading="lazy" decoding="async">
                <span class="mosaic-zoom" aria-hidden="true"><i class="fas fa-expand"></i></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- =====================================================================
     Contact: details from Settings (or a short "coming soon" note), map when an
     embed has been saved in Settings, and the contact form
     ===================================================================== -->
<?php
$contact_sent = $form_flash !== '' && $form_flash_for === 'contact';
$map_src = '';
if (preg_match('#src=["\'](https://(?:www\.)?google\.com/maps/embed\?[^"\']+)["\']#i', getSetting('google_maps_embed', ''), $map_match)) {
    $map_src = html_entity_decode($map_match[1]);
}
$contact_social = array_filter(getSocialMedia());
$contact_social_icons = ['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'twitter' => 'fa-x-twitter'];
?>
<section class="home-contact" id="contact">
    <div class="container">
        <div class="contact-shell">
            <div class="contact-details">
                <span class="eyebrow"><?php echo htmlspecialchars(content('home_contact_eyebrow')); ?></span>
                <h2 class="section-title"><?php echo htmlspecialchars(content('home_contact_heading')); ?></h2>
                <p class="lead"><?php echo htmlspecialchars(content('home_contact_text')); ?></p>

                <ul class="contact-list">
                    <li>
                        <span class="contact-icon"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                        <div><strong>Address</strong>
                            <?php if (hasSiteAddress()): ?><span><?php echo nl2br(htmlspecialchars(getSiteAddress())); ?></span>
                            <?php else: ?><span class="is-placeholder">To be announced</span><?php endif; ?>
                        </div>
                    </li>
                    <li>
                        <span class="contact-icon"><i class="fas fa-phone" aria-hidden="true"></i></span>
                        <div><strong>Phone</strong>
                            <?php if (hasSitePhone()): ?><a href="<?php echo htmlspecialchars(telHref(primaryPhone())); ?>"><?php echo htmlspecialchars(getSitePhone()); ?></a>
                            <?php else: ?><span class="is-placeholder">Coming soon</span><?php endif; ?>
                        </div>
                    </li>
                    <li>
                        <span class="contact-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                        <div><strong>Email</strong>
                            <?php if (hasSiteEmail()): ?><a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><?php echo htmlspecialchars(getSiteEmail()); ?></a>
                            <?php else: ?><span class="is-placeholder">Coming soon</span><?php endif; ?>
                        </div>
                    </li>
                    <li>
                        <span class="contact-icon"><i class="fas fa-clock" aria-hidden="true"></i></span>
                        <div><strong>Office hours</strong>
                            <?php if (hasOfficeHours()): ?><span><?php echo htmlspecialchars(getOfficeHours()); ?></span>
                            <?php else: ?><span class="is-placeholder">To be announced</span><?php endif; ?>
                        </div>
                    </li>
                </ul>

                <?php if (!empty($contact_social)): ?>
                <div class="contact-social" aria-label="Social media">
                    <?php foreach ($contact_social_icons as $key => $icon):
                        if (empty($contact_social[$key])) continue; ?>
                        <a href="<?php echo htmlspecialchars($contact_social[$key]); ?>" target="_blank" rel="noopener" aria-label="<?php echo ucfirst($key); ?> (opens in a new tab)"><i class="fab <?php echo $icon; ?>" aria-hidden="true"></i></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($map_src !== ''): ?>
                <div class="contact-map">
                    <iframe src="<?php echo htmlspecialchars($map_src); ?>" title="Map showing the location of <?php echo htmlspecialchars(getSiteName()); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-card">
                <h3>Send Us a Message</h3>
                <p class="form-card-text">We usually reply within a few working days.</p>

                <?php if ($contact_sent): ?>
                    <div class="alert alert-success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i> <?php echo htmlspecialchars($form_flash); ?></div>
                <?php elseif ($contact_error !== ''): ?>
                    <div class="alert alert-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <?php echo htmlspecialchars($contact_error); ?></div>
                <?php endif; ?>

                <form method="POST" action="./#contact" id="homeContactForm" data-validate novalidate>
                    <?php echo formGuardFields(); ?>
                    <input type="hidden" name="form_name" value="contact">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="c_name">Your name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="c_name" name="c_name" required maxlength="100" autocomplete="name" value="<?php echo old('c_name'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="c_email">Email <span class="req" aria-hidden="true">*</span></label>
                            <input type="email" id="c_email" name="c_email" required maxlength="150" autocomplete="email" value="<?php echo old('c_email'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="c_phone">Phone number <span class="optional">(optional)</span></label>
                        <input type="tel" id="c_phone" name="c_phone" maxlength="30" autocomplete="tel" inputmode="tel" placeholder="03001234567" value="<?php echo old('c_phone'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="c_message">Message <span class="req" aria-hidden="true">*</span></label>
                        <textarea id="c_message" name="c_message" rows="5" required maxlength="5000"><?php echo old('c_message'); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send Message</button>
                    <p class="form-note"><span aria-hidden="true">*</span> Required fields</p>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
