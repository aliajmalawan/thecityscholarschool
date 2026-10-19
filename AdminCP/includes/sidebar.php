<?php
/**
 * Shared admin sidebar - included by every AdminCP page so navigation
 * is identical and always available, no matter which page you're on.
 * Highlights the current page automatically (override with $active_page
 * before including this file if a page needs a different match).
 */
$active_page = $active_page ?? basename($_SERVER['PHP_SELF']);

$admin_nav_links = [
    ['dashboard.php', 'fa-home', 'Dashboard'],
    ['analytics.php', 'fa-chart-line', 'Website Analytics'],
    ['manage_content.php', 'fa-pen-ruler', 'Page Content'],
    ['manage_hero_slides.php', 'fa-images', 'Home Slider'],
    ['manage_courses.php', 'fa-book', 'Courses'],
    ['manage_campuses.php', 'fa-school', 'Campuses'],
    ['manage_faculty.php', 'fa-chalkboard-teacher', 'Faculty'],
    ['manage_admissions.php', 'fa-user-graduate', 'Admissions'],
    ['manage_contacts.php', 'fa-envelope', 'Contact Messages'],
    ['manage_events.php', 'fa-calendar-alt', 'Events'],
    ['manage_news.php', 'fa-newspaper', 'News'],
    ['manage_gallery.php', 'fa-images', 'Gallery'],
    ['manage_videos.php', 'fa-video', 'Gallery Videos'],
    ['manage_downloads.php', 'fa-download', 'Downloads'],
    ['manage_alumni.php', 'fa-user-graduate', 'Alumni'],
    ['manage_alumni_reviews.php', 'fa-star', 'Alumni Reviews'],
    ['manage_datesheets.php', 'fa-calendar-check', 'Exam Datesheets'],
    ['manage_board_results.php', 'fa-trophy', 'Board Results'],
    ['manage_notifications.php', 'fa-bullhorn', 'Notifications'],
    ['manage_leadership.php', 'fa-users', 'Leadership Messages'],
    ['manage_feature_cards.php', 'fa-th-large', 'Feature Cards'],
    ['manage_fee_items.php', 'fa-money-bill-wave', 'Fee Information'],
    ['manage_faqs.php', 'fa-question-circle', 'FAQs'],
    ['settings.php', 'fa-cog', 'Settings'],
];
?>
<div class="sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <img src="../<?php echo htmlspecialchars(getLogoSmallPath()); ?>" alt="" class="sidebar-logo">
        <div>
            <h2><?php echo SITE_NAME; ?></h2>
            <p>Admin Panel</p>
        </div>
        <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">&times;</button>
    </div>

    <div class="sidebar-menu">
        <?php foreach ($admin_nav_links as [$href, $icon, $label]): ?>
        <a href="<?php echo $href; ?>" class="<?php echo $active_page === $href ? 'active' : ''; ?>">
            <i class="fas <?php echo $icon; ?>"></i>
            <span><?php echo $label; ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="menu-divider"></div>

    <div class="logout-section">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>
