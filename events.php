<?php
require_once 'includes/config.php';
$page_title = content('seo_events_title');
$meta_description = content('seo_events_description');

// AdminCP -> Events and AdminCP -> News
$fetch = function ($sql) use ($conn) {
    $rows = [];
    $res = mysqli_query($conn, $sql);
    while ($res && ($row = mysqli_fetch_assoc($res))) { $rows[] = $row; }
    return $rows;
};
$upcoming = $fetch("SELECT DISTINCT * FROM events WHERE event_date >= CURDATE() AND status = 'active' ORDER BY event_date ASC LIMIT 12");
$past = $fetch("SELECT DISTINCT * FROM events WHERE event_date < CURDATE() AND status = 'active' ORDER BY event_date DESC LIMIT 6");
$news = $fetch("SELECT DISTINCT * FROM news WHERE status = 'active' ORDER BY created_at DESC LIMIT 10");
$has_social = !empty(array_filter(getSocialMedia()));
?>
<?php include 'includes/header.php'; ?>

<?php renderPageBanner('events', false, 'fa-calendar-days'); ?>

<!-- Upcoming events -->
<section class="page-intro bg-light">
    <div class="container">
        <?php sectionHeader(content('events_upcoming_eyebrow'), content('events_upcoming_heading'), content('events_upcoming_subtitle')); ?>
        <?php if (!empty($upcoming)): ?>
        <div class="event-cards is-full">
            <?php foreach ($upcoming as $event) { renderEventCard($event, true); } ?>
        </div>
        <?php else: ?>
            <?php renderEmptyState('fa-calendar-check', 'No upcoming events right now', 'New dates will be announced here. Please check back soon.'); ?>
        <?php endif; ?>
    </div>
</section>

<!-- Latest news -->
<section>
    <div class="container">
        <?php sectionHeader(content('events_news_eyebrow'), content('events_news_heading'), content('events_news_subtitle')); ?>
        <?php if (!empty($news)): ?>
        <div class="news-feed">
            <?php foreach ($news as $post) { renderNewsArticle($post); } ?>
        </div>
        <?php else: ?>
            <?php renderEmptyState('fa-newspaper', 'No news yet', 'Announcements from ' . getSiteName() . ' will be posted here.'); ?>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($past)): ?>
<!-- Recent (past) events -->
<section class="bg-light">
    <div class="container">
        <?php sectionHeader('Looking Back', content('events_past_heading')); ?>
        <div class="event-cards is-full">
            <?php foreach ($past as $event) { renderEventCard($event, true, true); } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($has_social): ?>
<section class="cta-section">
    <div class="container">
        <div class="cta-panel">
            <div class="cta-panel-copy">
                <h2><?php echo htmlspecialchars(content('events_cta_heading')); ?></h2>
                <p><?php echo htmlspecialchars(content('events_cta_text')); ?></p>
            </div>
            <?php socialIcons('cta-social'); ?>
        </div>
    </div>
</section>
<?php else: ?>
<?php admissionsCta('Have a Question?', 'Our office is happy to help with events, admissions or anything else.'); ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
