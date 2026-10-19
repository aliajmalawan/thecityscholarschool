<?php
require_once 'includes/config.php';
$page_title = content('seo_campuses_title');
$meta_description = content('seo_campuses_description');

// Renders one campus's detail column - shared by both left/right layout slots
function renderCampusDetails($c) {
    echo '<div>';
    echo '<span class="eyebrow">' . htmlspecialchars($c['tagline'] ?: 'Sister Campus') . '</span>';
    echo '<h2 style="color: var(--ink); margin-bottom: 16px;">' . htmlspecialchars($c['name']) . '</h2>';

    if (!empty($c['description'])) {
        echo '<p style="line-height: 1.8;">' . nl2br(htmlspecialchars($c['description'])) . '</p>';
    }

    $has_contact_info = !empty($c['address']) || !empty($c['phone']) || !empty($c['email']);
    if ($has_contact_info) {
        echo '<div style="display: flex; flex-direction: column; gap: 14px; margin-top: 22px; padding-top: 22px; border-top: 1px solid var(--border-color);">';
        if (!empty($c['address'])) {
            echo '<div style="display: flex; align-items: flex-start; gap: 12px;"><i class="fas fa-map-marker-alt" style="color: var(--primary-color); width: 18px; margin-top: 4px;"></i><span>' . htmlspecialchars($c['address']) . '</span></div>';
        }
        if (!empty($c['phone'])) {
            $tel = str_replace([' ', '-'], '', $c['phone']);
            echo '<div style="display: flex; align-items: center; gap: 12px;"><i class="fas fa-phone" style="color: var(--primary-color); width: 18px;"></i><a href="tel:' . htmlspecialchars($tel) . '">' . htmlspecialchars($c['phone']) . '</a></div>';
        }
        if (!empty($c['email'])) {
            echo '<div style="display: flex; align-items: center; gap: 12px;"><i class="fas fa-envelope" style="color: var(--primary-color); width: 18px;"></i><a href="mailto:' . htmlspecialchars($c['email']) . '">' . htmlspecialchars($c['email']) . '</a></div>';
        }
        echo '</div>';
    }

    if (!empty($c['address'])) {
        $maps_query = urlencode($c['name'] . ' ' . $c['address']);
        echo '<a href="https://www.google.com/maps/search/?api=1&query=' . $maps_query . '" target="_blank" rel="noopener" class="btn btn-outline-dark mt-30"><i class="fas fa-directions"></i> Get Directions</a>';
    }

    echo '</div>';
}

$campuses_result = mysqli_query($conn, "SELECT DISTINCT * FROM campuses WHERE status = 'active' ORDER BY display_order ASC, id ASC");
$campuses_list = [];
if ($campuses_result) {
    while ($row = mysqli_fetch_assoc($campuses_result)) {
        $campuses_list[] = $row;
    }
}
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<?php renderPageBanner('campuses'); ?>

<?php if (!empty($campuses_list)): ?>
<section class="bg-light">
    <div class="container">
        <?php foreach ($campuses_list as $index => $c): $is_reverse = $index % 2 === 1; ?>
        <div class="campus-row<?php echo $is_reverse ? ' campus-row-reverse' : ''; ?> reveal" style="display: grid; grid-template-columns: <?php echo $is_reverse ? '1fr 380px' : '380px 1fr'; ?>; gap: 50px; align-items: center; background: var(--surface); padding: 50px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); margin-bottom: 40px;">

            <?php if ($is_reverse): ?>
                <?php renderCampusDetails($c); ?>
            <?php endif; ?>

            <div>
                <?php if (!empty($c['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($c['image_path']); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" loading="lazy" style="width: 100%; height: 280px; object-fit: cover; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm);">
                <?php else: ?>
                    <div style="width: 100%; height: 280px; border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); display: flex; align-items: center; justify-content: center;">
                        <i class="fas <?php echo htmlspecialchars($c['icon'] ?: 'fa-school'); ?>" style="font-size: 80px; color: #fff;"></i>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$is_reverse): ?>
                <?php renderCampusDetails($c); ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php else: ?>
<section>
    <div class="container text-center">
        <p style="color: var(--text-light); font-size: 18px;">Campus details are coming soon. Please check back later.</p>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section>
    <div class="container">
        <div class="cta-banner reveal">
            <h2><?php echo htmlspecialchars(content('campuses_cta_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('campuses_cta_text')); ?></p>
            <div class="btn-group" style="justify-content: center;">
                <a href="contact" class="btn btn-secondary">Contact Us</a>
                <a href="admission" class="btn btn-outline">Apply Now</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
