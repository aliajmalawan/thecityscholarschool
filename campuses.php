<?php
require_once 'includes/config.php';
$page_title = content('seo_campuses_title');
$meta_description = content('seo_campuses_description');

// Campuses from AdminCP -> Campuses
$campuses_list = [];
$campuses_result = mysqli_query($conn, "SELECT DISTINCT * FROM campuses WHERE status = 'active' ORDER BY display_order ASC, id ASC");
while ($campuses_result && ($row = mysqli_fetch_assoc($campuses_result))) {
    $campuses_list[] = $row;
}
?>
<?php include 'includes/header.php'; ?>

<?php renderPageBanner('campuses', false, 'fa-school', ['Campus Life', 'campus-life']); ?>

<section class="page-intro bg-light">
    <div class="container">
        <?php if (!empty($campuses_list)): ?>
            <?php foreach ($campuses_list as $index => $c): ?>
            <article class="campus-feature<?php echo $index % 2 === 1 ? ' is-reverse' : ''; ?>">
                <div class="campus-feature-media">
                    <?php if (!empty($c['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($c['image_path']); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <span class="campus-feature-icon"><i class="fas <?php echo htmlspecialchars($c['icon'] ?: 'fa-school'); ?>" aria-hidden="true"></i></span>
                    <?php endif; ?>
                </div>
                <div class="campus-feature-body">
                    <?php if (!empty($c['tagline'])): ?><span class="eyebrow"><?php echo htmlspecialchars($c['tagline']); ?></span><?php endif; ?>
                    <h2><?php echo htmlspecialchars($c['name']); ?></h2>
                    <?php if (!empty($c['description'])): ?>
                        <p class="prose"><?php echo nl2br(htmlspecialchars($c['description'])); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($c['address']) || !empty($c['phone']) || !empty($c['email'])): ?>
                    <ul class="campus-contact">
                        <?php if (!empty($c['address'])): ?><li><i class="fas fa-location-dot" aria-hidden="true"></i> <span><?php echo htmlspecialchars($c['address']); ?></span></li><?php endif; ?>
                        <?php if (!empty($c['phone'])): ?><li><i class="fas fa-phone" aria-hidden="true"></i> <a href="<?php echo htmlspecialchars(telHref($c['phone'])); ?>"><?php echo htmlspecialchars($c['phone']); ?></a></li><?php endif; ?>
                        <?php if (!empty($c['email'])): ?><li><i class="fas fa-envelope" aria-hidden="true"></i> <a href="mailto:<?php echo htmlspecialchars($c['email']); ?>"><?php echo htmlspecialchars($c['email']); ?></a></li><?php endif; ?>
                    </ul>
                    <?php endif; ?>
                    <?php if (!empty($c['address'])): ?>
                        <a href="https://www.google.com/maps/search/?api=1&amp;query=<?php echo urlencode($c['name'] . ' ' . $c['address']); ?>" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm"><i class="fas fa-diamond-turn-right" aria-hidden="true"></i> Get Directions<span class="sr-only"> (opens Google Maps in a new tab)</span></a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        <?php else: ?>
            <?php renderEmptyState('fa-school', 'Campus details coming soon', 'Information about our campus and how to find us will be added here shortly.'); ?>
        <?php endif; ?>
    </div>
</section>

<?php admissionsCta(content('campuses_cta_heading'), content('campuses_cta_text')); ?>

<?php include 'includes/footer.php'; ?>
