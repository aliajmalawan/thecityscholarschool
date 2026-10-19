<?php
require_once 'includes/config.php';
$page_title = content('seo_courses_title');
$meta_description = content('seo_courses_description');

// Courses grouped by stage; each stage shows in this order. Courses (and their subjects) are managed in AdminCP -> Courses.
$stages = ['early_years', 'primary', 'middle', 'secondary'];
$stage_icons = ['early_years' => 'fa-seedling', 'primary' => 'fa-book-reader', 'middle' => 'fa-lightbulb', 'secondary' => 'fa-graduation-cap'];
$courses_by_stage = array_fill_keys($stages, []);
$unplaced = [];
$courses_result = mysqli_query($conn, "SELECT DISTINCT * FROM courses WHERE status = 'active' ORDER BY display_order ASC, id ASC");
while ($courses_result && ($course = mysqli_fetch_assoc($courses_result))) {
    if (isset($courses_by_stage[$course['level']])) {
        $courses_by_stage[$course['level']][] = $course;
    } else {
        $unplaced[] = $course;
    }
}
$active_stages = array_values(array_filter($stages, function ($stage) use ($courses_by_stage) {
    return !empty($courses_by_stage[$stage]);
}));

/** One course card: icon, name, classes, description, subject list, apply link. */
function renderCourseCard($course, $wide = false) {
    $subjects = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $course['subjects']))));
    echo '<article class="course-card reveal' . ($wide ? ' is-wide' : '') . '">';
    echo '<div class="course-main">';
    echo '<header class="course-head">';
    echo '<span class="course-icon"><i class="fas ' . htmlspecialchars($course['icon'] ?: 'fa-book') . '"></i></span>';
    echo '<div><h3>' . htmlspecialchars($course['name']) . '</h3>';
    if (!empty($course['duration'])) {
        echo '<span class="course-classes"><i class="fas fa-layer-group"></i> ' . htmlspecialchars($course['duration']) . '</span>';
    }
    echo '</div></header>';
    if (!empty($course['description'])) {
        echo '<p class="course-desc">' . htmlspecialchars($course['description']) . '</p>';
    }
    echo '<a href="admission?course=' . intval($course['id']) . '" class="course-apply">' . htmlspecialchars(content('courses_card_btn')) . ' <i class="fas fa-arrow-right"></i></a>';
    echo '</div>';
    if (!empty($subjects)) {
        echo '<div class="course-side"><h4>Subjects</h4><ul class="course-subjects">';
        foreach ($subjects as $subject) {
            echo '<li><i class="fas fa-check"></i> ' . htmlspecialchars($subject) . '</li>';
        }
        echo '</ul></div>';
    }
    echo '</article>';
}
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<?php renderPageBanner('courses'); ?>

<?php if (!empty($active_stages)): ?>
<!-- Academic path: one step per stage, linking to its section below -->
<section class="path-section">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('courses_path_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('courses_path_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('courses_path_subtitle')); ?></p>
        </div>
        <ol class="path-steps">
            <?php foreach ($active_stages as $n => $stage):
                $first = $courses_by_stage[$stage][0];
                $label = $stage === 'secondary' ? 'Class 9 – 10' : $first['duration'];
            ?>
            <li>
                <a href="#stage-<?php echo htmlspecialchars($stage); ?>" class="path-step">
                    <span class="path-number"><?php echo $n + 1; ?></span>
                    <span class="path-icon"><i class="fas <?php echo $stage_icons[$stage]; ?>"></i></span>
                    <strong><?php echo htmlspecialchars(content("courses_{$stage}_heading")); ?></strong>
                    <small><?php echo htmlspecialchars($label); ?></small>
                </a>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<?php foreach ($active_stages as $n => $stage): ?>
<!-- Stage: <?php echo $stage; ?> -->
<section id="stage-<?php echo htmlspecialchars($stage); ?>" class="stage-section<?php echo $n % 2 === 0 ? ' bg-light' : ''; ?>">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><i class="fas <?php echo $stage_icons[$stage]; ?>"></i> Stage <?php echo $n + 1; ?></span>
            <h2><?php echo htmlspecialchars(content("courses_{$stage}_heading")); ?></h2>
            <p><?php echo htmlspecialchars(content("courses_{$stage}_subtitle")); ?></p>
        </div>
        <?php $stage_courses = $courses_by_stage[$stage]; ?>
        <div class="course-list<?php echo count($stage_courses) > 1 ? ' is-multi' : ''; ?>">
            <?php foreach ($stage_courses as $course) { renderCourseCard($course, count($stage_courses) === 1); } ?>
        </div>
    </div>
</section>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($unplaced)): ?>
<!-- Extra courses added in the admin panel without a stage -->
<section class="stage-section">
    <div class="container">
        <h2 class="sr-only">Programmes</h2>
        <div class="course-list is-multi">
            <?php foreach ($unplaced as $course) { renderCourseCard($course); } ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (empty($active_stages) && empty($unplaced)): ?>
<section>
    <div class="container text-center" style="padding: 40px 0;">
        <p style="color: var(--text-light); font-size: 18px;">Our course details will be published here soon.</p>
    </div>
</section>
<?php endif; ?>

<!-- Admission Requirements -->
<?php $requirement_groups = getFeatureCards('admission_requirements'); ?>
<?php if (!empty($requirement_groups)): ?>
<section class="<?php echo count($active_stages) % 2 === 0 ? 'bg-light' : ''; ?>">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('courses_req_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('courses_req_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('courses_req_subtitle')); ?></p>
        </div>
        <div class="req-grid">
            <?php foreach ($requirement_groups as $req): ?>
            <?php $items = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $req['description'])))); ?>
            <div class="card reveal req-card">
                <div class="req-head">
                    <div class="card-icon"><i class="<?php echo htmlspecialchars($req['icon']); ?>"></i></div>
                    <h3><?php echo htmlspecialchars($req['title']); ?></h3>
                </div>
                <ul class="req-list">
                    <?php foreach ($items as $item): ?>
                        <li><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA Section -->
<section>
    <div class="container">
        <div class="cta-banner reveal">
            <h2><?php echo htmlspecialchars(content('courses_cta_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('courses_cta_text')); ?></p>
            <div class="btn-group" style="justify-content: center;">
                <a href="admission" class="btn btn-secondary">Apply Now</a>
                <a href="contact" class="btn btn-outline">Have Questions?</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
