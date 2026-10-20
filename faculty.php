<?php
require_once 'includes/config.php';
$page_title = content('seo_faculty_title');
$meta_description = content('seo_faculty_description');

// Teachers from AdminCP -> Faculty (only real entries; nothing is shown in their place when there are none)
$teachers = [];
$faculty_result = mysqli_query($conn, "SELECT DISTINCT * FROM faculty WHERE status = 'active' ORDER BY display_order ASC, id ASC");
while ($faculty_result && ($row = mysqli_fetch_assoc($faculty_result))) {
    $teachers[] = $row;
}
?>
<?php include 'includes/header.php'; ?>

<?php renderPageBanner('faculty', false, '', ['Academics', 'courses']); ?>

<section class="page-intro bg-light">
    <div class="container">
        <?php sectionHeader(content('faculty_list_eyebrow'), content('faculty_list_heading')); ?>
        <?php if (!empty($teachers)): ?>
        <div class="faculty-grid">
            <?php foreach ($teachers as $teacher): ?>
            <article class="faculty-card">
                <div class="faculty-photo">
                    <?php if (!empty($teacher['photo'])): ?>
                        <img src="<?php echo htmlspecialchars($teacher['photo']); ?>" alt="<?php echo htmlspecialchars($teacher['name']); ?>" loading="lazy" onerror="this.remove()">
                    <?php endif; ?>
                    <i class="fas fa-user" aria-hidden="true"></i>
                </div>
                <div class="faculty-body">
                    <h3><?php echo htmlspecialchars($teacher['name']); ?></h3>
                    <?php if (!empty($teacher['designation'])): ?>
                        <p class="faculty-role"><?php echo htmlspecialchars($teacher['designation']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($teacher['subjects']) || !empty($teacher['qualification']) || !empty($teacher['experience'])): ?>
                    <dl class="faculty-facts">
                        <?php if (!empty($teacher['subjects'])): ?>
                            <div><dt><i class="fas fa-book" aria-hidden="true"></i> Subjects</dt><dd><?php echo htmlspecialchars($teacher['subjects']); ?></dd></div>
                        <?php endif; ?>
                        <?php if (!empty($teacher['qualification'])): ?>
                            <div><dt><i class="fas fa-graduation-cap" aria-hidden="true"></i> Qualification</dt><dd><?php echo htmlspecialchars($teacher['qualification']); ?></dd></div>
                        <?php endif; ?>
                        <?php if (!empty($teacher['experience'])): ?>
                            <div><dt><i class="fas fa-briefcase" aria-hidden="true"></i> Experience</dt><dd><?php echo htmlspecialchars($teacher['experience']); ?><?php echo ctype_digit(trim((string) $teacher['experience'])) ? ' years' : ''; ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <?php renderEmptyState('fa-chalkboard-user', 'Faculty profiles coming soon', 'We are preparing introductions to our teachers. Please check back soon.'); ?>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty(getFeatureCards('faculty_highlights'))): ?>
<section>
    <div class="container">
        <?php sectionHeader(content('faculty_why_eyebrow'), content('faculty_why_heading'), content('faculty_why_subtitle')); ?>
        <?php renderFeatureCardGrid('faculty_highlights'); ?>
    </div>
</section>
<?php endif; ?>

<?php admissionsCta(content('faculty_cta_heading'), content('faculty_cta_text')); ?>

<?php include 'includes/footer.php'; ?>
