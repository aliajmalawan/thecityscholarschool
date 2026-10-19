<?php
require_once 'includes/config.php';
$page_title = content('seo_events_title');
$meta_description = content('seo_events_description');
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<?php renderPageBanner('events'); ?>

<!-- Upcoming Events -->
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('events_upcoming_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('events_upcoming_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('events_upcoming_subtitle')); ?></p>
        </div>

        <div class="card-grid">
            <?php
            $events_query = "SELECT DISTINCT * FROM events WHERE event_date >= CURDATE() AND status = 'active' ORDER BY event_date ASC LIMIT 6";
            $events_result = mysqli_query($conn, $events_query);

            if ($events_result && mysqli_num_rows($events_result) > 0) {
                while ($event = mysqli_fetch_assoc($events_result)) {
                    echo '<div class="card" id="event-' . intval($event['id']) . '" style="position: relative; overflow: hidden;">';
                    if (!empty($event['image'])) {
                        echo '<img src="' . htmlspecialchars($event['image']) . '" alt="' . htmlspecialchars($event['title']) . '" style="width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin-bottom: 20px;">';
                    }
                    echo '<div style="background: var(--primary-color); color: white; display: inline-block; padding: 5px 15px; border-radius: 20px; margin-bottom: 15px; font-size: 14px;">';
                    echo date('M d, Y', strtotime($event['event_date']));
                    echo '</div>';
                    echo '<h3>' . htmlspecialchars($event['title']) . '</h3>';
                    echo '<p>' . nl2br(htmlspecialchars($event['description'])) . '</p>';
                    if (!empty($event['location'])) {
                        echo '<p style="margin-top: 15px;"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($event['location']) . '</p>';
                    }
                    if (!empty($event['time'])) {
                        echo '<p><i class="fas fa-clock"></i> ' . htmlspecialchars($event['time']) . '</p>';
                    }
                    echo '</div>';
                }
            } else {
                // No upcoming events message
                echo '<div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">';
                echo '<i class="fas fa-calendar-times" style="font-size: 80px; color: var(--text-light); margin-bottom: 20px;"></i>';
                echo '<h3 style="color: var(--text-dark); margin-bottom: 10px;">No Upcoming Events</h3>';
                echo '<p style="color: var(--text-light);">Check back soon for exciting events and activities!</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- Latest News -->
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('events_news_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('events_news_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('events_news_subtitle')); ?></p>
        </div>

        <div style="max-width: 900px; margin: 0 auto;">
            <?php
            $news_query = "SELECT DISTINCT * FROM news WHERE status = 'active' ORDER BY created_at DESC LIMIT 5";
            $news_result = mysqli_query($conn, $news_query);

            if ($news_result && mysqli_num_rows($news_result) > 0) {
                while ($news = mysqli_fetch_assoc($news_result)) {
                    echo '<div class="card" id="news-' . intval($news['id']) . '" style="margin-bottom: 30px; padding: 0; overflow: hidden;">';

                    // Display image if exists
                    if (!empty($news['image'])) {
                        echo '<img src="' . htmlspecialchars($news['image']) . '" alt="' . htmlspecialchars($news['title']) . '" style="width: 100%; height: 300px; object-fit: cover;">';
                    }

                    echo '<div class="news-body" style="padding: 30px;">';
                    echo '<div class="news-head" style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">';
                    echo '<h3 style="flex: 1 1 260px; margin: 0; color: var(--primary-color);">' . htmlspecialchars($news['title']) . '</h3>';
                    echo '<div style="display: flex; align-items: center; gap: 15px;">';

                    // Date badge
                    echo '<span style="background: var(--primary-soft); color: var(--primary-dark); padding: 5px 15px; border-radius: 20px; font-size: 13px; font-weight: 600;">';
                    echo '<i class="fas fa-calendar"></i> ' . date('M d, Y', strtotime($news['created_at']));
                    echo '</span>';

                    echo '</div>';
                    echo '</div>';

                    // Content
                    echo '<p style="line-height: 1.8; color: var(--text-dark); margin-bottom: 15px;">' . nl2br(htmlspecialchars($news['content'])) . '</p>';

                    // Author
                    if (!empty($news['author'])) {
                        echo '<div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid var(--bg-light);">';
                        echo '<p style="font-size: 14px; color: var(--text-light); font-style: italic;">';
                        echo '<i class="fas fa-user-circle"></i> Published by <strong>' . htmlspecialchars($news['author']) . '</strong>';
                        echo '</p>';
                        echo '</div>';
                    }

                    echo '</div>';
                    echo '</div>';
                }
            } else {
                // No news message
                echo '<div style="text-align: center; padding: 80px 20px; background: white; border-radius: 15px; box-shadow: var(--shadow);">';
                echo '<i class="fas fa-newspaper" style="font-size: 100px; color: var(--text-light); margin-bottom: 30px; opacity: 0.3;"></i>';
                echo '<h3 style="color: var(--text-dark); margin-bottom: 15px; font-size: 28px;">No News Available</h3>';
                echo '<p style="color: var(--text-light); font-size: 18px; max-width: 500px; margin: 0 auto;">Check back soon for latest updates and announcements from Tameer-e-Nau Public School!</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section>
    <div class="container">
        <div class="cta-banner reveal">
            <h2><?php echo htmlspecialchars(content('events_cta_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('events_cta_text')); ?></p>
            <div class="social-links" style="justify-content: center;">
                <?php $social = getSocialMedia(); ?>
                <?php if (!empty($social['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($social['facebook']); ?>" target="_blank" style="width: 50px; height: 50px; font-size: 20px;"><i class="fab fa-facebook"></i></a>
                <?php endif; ?>
                <?php if (!empty($social['instagram'])): ?>
                    <a href="<?php echo htmlspecialchars($social['instagram']); ?>" target="_blank" style="width: 50px; height: 50px; font-size: 20px;"><i class="fab fa-instagram"></i></a>
                <?php endif; ?>
                <?php if (!empty($social['youtube'])): ?>
                    <a href="<?php echo htmlspecialchars($social['youtube']); ?>" target="_blank" style="width: 50px; height: 50px; font-size: 20px;"><i class="fab fa-youtube"></i></a>
                <?php endif; ?>
                <?php if (!empty($social['twitter'])): ?>
                    <a href="<?php echo htmlspecialchars($social['twitter']); ?>" target="_blank" style="width: 50px; height: 50px; font-size: 20px;"><i class="fab fa-twitter"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
