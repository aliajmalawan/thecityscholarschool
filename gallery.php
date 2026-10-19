<?php
require_once 'includes/config.php';
$page_title = content('seo_gallery_title');
$meta_description = content('seo_gallery_description');

$gallery_categories = mysqli_query($conn, "SELECT DISTINCT * FROM gallery_categories WHERE status = 'active' ORDER BY display_order ASC, name ASC");
$categories = [];
while ($gallery_categories && ($cat = mysqli_fetch_assoc($gallery_categories))) {
    $categories[$cat['slug']] = $cat['name'];
}

$gallery_result = mysqli_query($conn, "SELECT DISTINCT * FROM gallery WHERE status = 'active' ORDER BY created_at DESC, id DESC");
$photos = [];
while ($gallery_result && ($row = mysqli_fetch_assoc($gallery_result))) {
    $photos[] = $row;
}

// Videos are optional: the table only exists once database/upgrade-gallery-videos.sql has been run
$videos = [];
try {
    $videos_result = mysqli_query($conn, "SELECT * FROM gallery_videos WHERE status = 'active' ORDER BY display_order ASC, id DESC");
} catch (mysqli_sql_exception $e) {
    $videos_result = false;   // upgrade script not run yet: show the photos without a Videos section
}
while ($videos_result && ($row = mysqli_fetch_assoc($videos_result))) {
    $videos[] = $row;
}

// Count photos per category so empty categories don't get a filter button
$photo_counts = array_count_values(array_column($photos, 'category'));
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<?php renderPageBanner('gallery'); ?>

<!-- Photos -->
<section class="bg-light" id="photos">
    <div class="container">
        <?php if (!empty($photos) && count(array_intersect_key($categories, $photo_counts)) > 1): ?>
        <div class="gallery-filters" role="toolbar" aria-label="Filter photos by category">
            <button type="button" class="gallery-filter is-active" data-filter="all" aria-pressed="true">All <span><?php echo count($photos); ?></span></button>
            <?php foreach ($categories as $slug => $name): if (empty($photo_counts[$slug])) continue; ?>
                <button type="button" class="gallery-filter" data-filter="<?php echo htmlspecialchars($slug); ?>" aria-pressed="false"><?php echo htmlspecialchars($name); ?> <span><?php echo intval($photo_counts[$slug]); ?></span></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($photos)): ?>
        <div id="galleryGrid" class="gallery-grid">
            <?php foreach ($photos as $i => $photo):
                $thumb = mediaThumb($photo['image_path']);
                $category_name = $categories[$photo['category']] ?? '';
                $caption = trim((string) $photo['title']);
                $alt = $caption !== '' ? $caption : ($category_name !== '' ? $category_name : 'Campus life') . ' at ' . getSiteName();
            ?>
            <figure class="gallery-item" data-category="<?php echo htmlspecialchars($photo['category']); ?>">
                <a href="<?php echo htmlspecialchars($photo['image_path']); ?>" class="gallery-link" data-caption="<?php echo htmlspecialchars($caption !== '' ? $caption : $category_name); ?>" aria-label="Open photo: <?php echo htmlspecialchars($alt); ?>">
                    <img src="<?php echo htmlspecialchars($thumb['src']); ?>" alt="<?php echo htmlspecialchars($alt); ?>"
                         <?php if ($thumb['width']): ?>width="<?php echo $thumb['width']; ?>" height="<?php echo $thumb['height']; ?>"<?php endif; ?>
                         <?php echo $i < 8 ? '' : 'loading="lazy"'; ?> decoding="async" class="gallery-img">
                    <span class="gallery-zoom" aria-hidden="true"><i class="fas fa-expand"></i></span>
                </a>
                <?php if ($caption !== '' || $category_name !== ''): ?>
                <figcaption class="gallery-caption"><?php echo htmlspecialchars($caption !== '' ? $caption : $category_name); ?></figcaption>
                <?php endif; ?>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <?php renderEmptyState('fa-images', 'Photos coming soon', 'We are adding photos of our classes, events and achievements. Please check back soon.'); ?>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($videos)): ?>
<!-- Videos: a thumbnail and play button first; the YouTube player only loads when pressed -->
<section id="videos">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('gallery_videos_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('gallery_videos_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('gallery_videos_subtitle')); ?></p>
        </div>
        <div class="video-grid">
            <?php foreach ($videos as $video): $vid = htmlspecialchars($video['youtube_id']); ?>
            <article class="video-card reveal">
                <button type="button" class="video-frame" data-youtube="<?php echo $vid; ?>" aria-label="Play video: <?php echo htmlspecialchars($video['title']); ?>">
                    <img src="https://i.ytimg.com/vi/<?php echo $vid; ?>/hqdefault.jpg" alt="" width="480" height="360" loading="lazy" decoding="async">
                    <span class="video-play" aria-hidden="true"><i class="fas fa-play"></i></span>
                </button>
                <div class="video-body">
                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                    <?php if (!empty($video['description'])): ?><p><?php echo htmlspecialchars($video['description']); ?></p><?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
