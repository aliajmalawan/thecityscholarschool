<?php
require_once 'includes/config.php';
$page_title = content('seo_about_title');
$meta_description = content('seo_about_description');
$about = getAboutContent();
$story = array_values(array_filter([trim($about['description']), trim(getSetting('about_paragraph_2')), trim(getSetting('about_paragraph_3'))]));
?>
<?php include 'includes/header.php'; ?>

<?php renderPageBanner('about'); ?>

<!-- Our story -->
<section class="page-intro">
    <div class="container">
        <div class="home-about-grid">
            <div class="home-about-media">
                <div class="home-about-photo">
                    <?php echo pictureImg(content('about_story_image'), 'Students at ' . getSiteName(), ['loading' => 'lazy']); ?>
                </div>
                <div class="home-about-badge" aria-hidden="true">
                    <img src="<?php echo getLogoSmallPath(); ?>" alt="" width="64" height="60">
                    <span><strong><?php echo htmlspecialchars(getSiteName()); ?></strong><?php echo htmlspecialchars(content('site_tagline')); ?></span>
                </div>
            </div>
            <div class="home-about-copy">
                <span class="eyebrow"><?php echo htmlspecialchars(content('about_story_eyebrow')); ?></span>
                <h2 class="section-title"><?php echo htmlspecialchars(content('about_story_heading')); ?></h2>
                <?php if (!empty($story)): ?>
                    <?php foreach ($story as $i => $paragraph): ?>
                    <p class="<?php echo $i === 0 ? 'lead' : 'prose'; ?>"><?php echo nl2br(htmlspecialchars($paragraph)); ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="lead is-placeholder">Our story will be shared here soon.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Mission & vision -->
<?php if (trim($about['mission']) !== '' || trim($about['vision']) !== ''): ?>
<section class="bg-light">
    <div class="container">
        <div class="mv-grid">
            <?php if (trim($about['mission']) !== ''): ?>
            <article class="mv-card is-dark">
                <span class="mv-icon"><i class="fas fa-bullseye" aria-hidden="true"></i></span>
                <h2>Our Mission</h2>
                <p><?php echo nl2br(htmlspecialchars($about['mission'])); ?></p>
            </article>
            <?php endif; ?>
            <?php if (trim($about['vision']) !== ''): ?>
            <article class="mv-card">
                <span class="mv-icon"><i class="fas fa-eye" aria-hidden="true"></i></span>
                <h2>Our Vision</h2>
                <p><?php echo nl2br(htmlspecialchars($about['vision'])); ?></p>
            </article>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- The crest: what each part stands for -->
<section class="crest-section">
    <div class="container">
        <div class="crest-grid">
            <div class="crest-figure">
                <?php echo pictureImg('images/city-scholars-crest.png', 'The ' . getSiteName() . ' crest', ['loading' => 'lazy']); ?>
            </div>
            <div>
                <span class="eyebrow">Our Crest</span>
                <h2 class="section-title">What Our Crest Stands For</h2>
                <p class="lead">Every part of our crest reflects something we want each student to carry with them.</p>
                <ul class="crest-symbols">
                    <li><span class="crest-symbol-icon"><i class="fas fa-book-open" aria-hidden="true"></i></span><div><strong>The Open Book</strong><span>Knowledge and a lifelong love of learning</span></div></li>
                    <li><span class="crest-symbol-icon"><i class="fas fa-graduation-cap" aria-hidden="true"></i></span><div><strong>The Graduation Cap</strong><span>Achievement through effort and perseverance</span></div></li>
                    <li><span class="crest-symbol-icon"><i class="fas fa-fire-flame-curved" aria-hidden="true"></i></span><div><strong>The Torch</strong><span>The light of learning, passed from teacher to student</span></div></li>
                    <li><span class="crest-symbol-icon"><i class="fas fa-star" aria-hidden="true"></i></span><div><strong>The Star</strong><span>Striving for excellence in all that we do</span></div></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Core values (AdminCP -> Feature Cards -> Core Values) -->
<?php if (!empty(getFeatureCards('core_values'))): ?>
<section class="bg-light">
    <div class="container">
        <?php sectionHeader(content('about_values_eyebrow'), content('about_values_heading'), content('about_values_subtitle')); ?>
        <?php renderFeatureCardGrid('core_values'); ?>
    </div>
</section>
<?php endif; ?>

<!-- Leadership messages (AdminCP -> Leadership); the older single principal's message from
     Settings is only used when no leadership entries exist -->
<?php if (!empty(getLeadershipMessages())): ?>
<?php include 'includes/leadership_section.php'; ?>
<?php elseif (trim(getSetting('principal_message_quote')) !== ''): ?>
<section>
    <div class="container">
        <?php sectionHeader('Leadership', "Principal's Message"); ?>
        <?php $principal_photo = getSetting('principal_photo'); ?>
        <article class="principal-card">
            <div class="principal-photo">
                <?php if (!empty($principal_photo)): ?>
                    <img src="<?php echo htmlspecialchars($principal_photo); ?>" alt="Principal of <?php echo htmlspecialchars(getSiteName()); ?>" loading="lazy">
                <?php else: ?>
                    <i class="fas fa-user-tie" aria-hidden="true"></i>
                <?php endif; ?>
            </div>
            <blockquote>&ldquo;<?php echo nl2br(htmlspecialchars(getSetting('principal_message_quote'))); ?>&rdquo;</blockquote>
            <?php foreach (['principal_message_para_1', 'principal_message_para_2'] as $key): if (trim(getSetting($key)) === '') continue; ?>
                <p><?php echo nl2br(htmlspecialchars(getSetting($key))); ?></p>
            <?php endforeach; ?>
        </article>
    </div>
</section>
<?php endif; ?>

<?php admissionsCta(content('about_cta_heading'), content('about_cta_text')); ?>

<?php include 'includes/footer.php'; ?>
