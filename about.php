<?php
require_once 'includes/config.php';
$page_title = content('seo_about_title');
$meta_description = content('seo_about_description');
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<?php renderPageBanner('about'); ?>

<!-- About Content -->
<section class="bg-light">
    <div class="container">
        <div class="grid-2 reveal" style="align-items: stretch;">
            <div>
                <?php $about = getAboutContent(); ?>
                <span class="eyebrow"><?php echo htmlspecialchars(content('about_story_eyebrow')); ?></span>
                <h2 style="color: var(--ink); font-size: 32px; margin-bottom: 20px;"><?php echo htmlspecialchars(content('about_story_heading')); ?></h2>
                <p style="margin-bottom: 15px; line-height: 1.8;"><?php echo nl2br(htmlspecialchars($about['description'])); ?></p>
                <p style="margin-bottom: 15px; line-height: 1.8;"><?php echo nl2br(htmlspecialchars(getSetting('about_paragraph_2'))); ?></p>
                <p style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars(getSetting('about_paragraph_3'))); ?></p>
            </div>
            <div style="min-height: 320px;">
                <img src="<?php echo htmlspecialchars(content('about_story_image')); ?>" alt="Students at <?php echo htmlspecialchars(getSiteName()); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg);">
            </div>
        </div>
    </div>
</section>

<!-- Mission & Vision -->
<section>
    <div class="container">
        <div class="card-grid">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3>Our Mission</h3>
                <p><?php echo nl2br(htmlspecialchars($about['mission'])); ?></p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h3>Our Vision</h3>
                <p><?php echo nl2br(htmlspecialchars($about['vision'])); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('about_values_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('about_values_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('about_values_subtitle')); ?></p>
        </div>
        <div class="card-grid">
            <?php renderFeatureCardGrid('core_values'); ?>
        </div>
    </div>
</section>

<!-- Leadership: the same messages as the homepage (AdminCP > Leadership); the older single
     principal's message from Settings is only used when no leadership entries exist -->
<?php if (!empty(getLeadershipMessages())): ?>
<?php include 'includes/leadership_section.php'; ?>
<?php elseif (trim(getSetting('principal_message_quote')) !== ''): ?>
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow">Leadership</span>
            <h2>Message from Our Leadership</h2>
        </div>
        <?php $principal_photo = getSetting('principal_photo'); ?>
        <div class="card reveal" style="max-width: 900px; margin: 0 auto; padding: 40px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <?php if (!empty($principal_photo)): ?>
                    <img src="<?php echo htmlspecialchars($principal_photo); ?>" alt="Principal" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin: 0 auto 20px; box-shadow: var(--shadow-sm); border: 4px solid var(--surface); outline: 1px solid var(--border-color);">
                <?php else: ?>
                    <div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; color: white; font-size: 60px;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                <?php endif; ?>
                <h3 style="color: var(--primary-color); margin-bottom: 5px;">Principal's Message</h3>
                <p style="color: var(--text-light);"><?php echo getSiteName(); ?></p>
            </div>
            <p style="font-size: 18px; line-height: 1.8; font-style: italic; margin-bottom: 20px;">"<?php echo nl2br(htmlspecialchars(getSetting('principal_message_quote'))); ?>"</p>
            <p style="line-height: 1.8; margin-bottom: 15px;"><?php echo nl2br(htmlspecialchars(getSetting('principal_message_para_1'))); ?></p>
            <p style="line-height: 1.8;"><?php echo nl2br(htmlspecialchars(getSetting('principal_message_para_2'))); ?></p>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section>
    <div class="container">
        <div class="cta-banner reveal">
            <h2><?php echo htmlspecialchars(content('about_cta_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('about_cta_text')); ?></p>
            <div class="btn-group" style="justify-content: center;">
                <a href="admission" class="btn btn-secondary">Apply for Admission</a>
                <a href="contact" class="btn btn-outline">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
