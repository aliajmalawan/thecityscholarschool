<?php
require_once 'includes/config.php';
$page_title = content('seo_campus_life_title');
$meta_description = content('seo_campus_life_description');

// Facilities: only cards the school has switched on (AdminCP -> Feature Cards -> Facilities)
$facility_cards = getFeatureCards('facilities');

// One photo card per gallery category (cover = first photo of the category)
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

// Next few events
$up_events = [];
$res = mysqli_query($conn, "SELECT DISTINCT * FROM events WHERE event_date >= CURDATE() AND status = 'active' ORDER BY event_date ASC LIMIT 3");
while ($res && ($row = mysqli_fetch_assoc($res))) { $up_events[] = $row; }
?>
<?php include 'includes/header.php'; ?>

<?php renderPageBanner('campus_life', false, 'fa-people-group'); ?>

<!-- Introduction + the Campus Life pages -->
<section class="page-intro">
    <div class="container">
        <div class="intro-split">
            <div>
                <span class="eyebrow"><?php echo htmlspecialchars(content('campus_life_intro_eyebrow')); ?></span>
                <h2 class="section-title"><?php echo htmlspecialchars(content('campus_life_intro_heading')); ?></h2>
                <p class="lead"><?php echo htmlspecialchars(content('campus_life_intro_text')); ?></p>
            </div>
            <div>
                <h3 class="group-title"><?php echo htmlspecialchars(content('campus_life_explore_heading')); ?></h3>
                <?php linkCards([
                    ['fa-images', 'Photo Gallery', 'Photos and videos from school life', 'gallery'],
                    ['fa-calendar-days', 'News & Events', 'Announcements and upcoming dates', 'events'],
                    ['fa-school', 'Our Campuses', 'Where we are and how to find us', 'campuses'],
                    ['fa-user-graduate', 'Alumni', 'Former students and their stories', 'alumni'],
                ]); ?>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($facility_cards)): ?>
<section class="bg-light">
    <div class="container">
        <?php sectionHeader('Facilities', content('campus_life_facilities_heading')); ?>
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
    </div>
</section>
<?php endif; ?>

<section class="<?php echo empty($facility_cards) ? 'bg-light' : ''; ?>">
    <div class="container">
        <?php sectionHeader('Photos', content('campus_life_photos_heading')); ?>
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
        <?php else: ?>
            <?php renderEmptyState('fa-images', 'Photos coming soon', 'We are adding photos of classes, events and activities. Please check back soon.'); ?>
        <?php endif; ?>
    </div>
</section>

<section class="<?php echo empty($facility_cards) ? '' : 'bg-light'; ?>">
    <div class="container">
        <div class="section-header section-header-split">
            <div>
                <span class="eyebrow">What's Next</span>
                <h2>Upcoming Events</h2>
            </div>
            <a href="events" class="btn btn-outline-dark">All News &amp; Events <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        <?php if (!empty($up_events)): ?>
        <div class="event-cards">
            <?php foreach ($up_events as $event): renderEventCard($event); endforeach; ?>
        </div>
        <?php else: ?>
            <?php renderEmptyState('fa-calendar-check', 'No upcoming events right now', 'New dates will be announced here and on the News & Events page.'); ?>
        <?php endif; ?>
    </div>
</section>

<?php ctaPanel(content('campus_life_cta_heading'), content('campus_life_cta_text'), [['Contact Us', 'contact', 'gold', 'fa-envelope'], ['Apply for Admission', 'admission', 'outline']]); ?>

<?php include 'includes/footer.php'; ?>
