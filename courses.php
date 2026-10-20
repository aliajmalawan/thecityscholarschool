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
    echo '<article class="course-card' . ($wide ? ' is-wide' : '') . '">';
    echo '<div class="course-main">';
    echo '<header class="course-head">';
    echo '<span class="course-icon"><i class="fas ' . htmlspecialchars($course['icon'] ?: 'fa-book') . '" aria-hidden="true"></i></span>';
    echo '<div><h3>' . htmlspecialchars($course['name']) . '</h3>';
    if (!empty($course['duration'])) {
        echo '<span class="course-classes"><i class="fas fa-layer-group" aria-hidden="true"></i> ' . htmlspecialchars($course['duration']) . '</span>';
    }
    echo '</div></header>';
    if (!empty($course['description'])) {
        echo '<p class="course-desc">' . htmlspecialchars($course['description']) . '</p>';
    }
    echo '<a href="admission?course=' . intval($course['id']) . '" class="course-apply">' . htmlspecialchars(content('courses_card_btn')) . '<span class="sr-only">: ' . htmlspecialchars($course['name']) . '</span> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>';
    echo '</div>';
    if (!empty($subjects)) {
        echo '<div class="course-side"><h4>Subjects</h4><ul class="course-subjects">';
        foreach ($subjects as $subject) {
            echo '<li><i class="fas fa-check" aria-hidden="true"></i> ' . htmlspecialchars($subject) . '</li>';
        }
        echo '</ul></div>';
    }
    echo '</article>';
}

// School levels: the same four cards as the homepage (Page Content -> Home: Sections)
$academic_levels = [];
foreach ([1 => 'fa-seedling', 2 => 'fa-book-reader', 3 => 'fa-lightbulb', 4 => 'fa-graduation-cap'] as $n => $icon) {
    if (content("academics_{$n}_title") !== '') {
        $academic_levels[] = ['icon' => $icon, 'title' => content("academics_{$n}_title"), 'desc' => content("academics_{$n}_desc")];
    }
}
$requirement_groups = getFeatureCards('admission_requirements');
?>
<?php include 'includes/header.php'; ?>

<?php renderPageBanner('courses', false, 'fa-graduation-cap'); ?>

<?php if (!empty($academic_levels)): ?>
<section class="page-intro">
    <div class="container">
        <?php sectionHeader(content('courses_levels_eyebrow'), content('courses_levels_heading'), content('courses_levels_subtitle')); ?>
        <div class="program-grid">
            <?php foreach ($academic_levels as $i => $level): ?>
            <article class="program-card">
                <div class="program-card-top">
                    <span class="program-icon"><i class="fas <?php echo $level['icon']; ?>" aria-hidden="true"></i></span>
                    <span class="program-stage" aria-hidden="true"><?php echo sprintf('%02d', $i + 1); ?></span>
                </div>
                <h3><?php echo htmlspecialchars($level['title']); ?></h3>
                <p><?php echo htmlspecialchars($level['desc']); ?></p>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($active_stages)): ?>
<!-- Academic path: one step per stage, linking to its section below -->
<section class="path-section bg-light" id="programs">
    <div class="container">
        <?php sectionHeader(content('courses_path_eyebrow'), content('courses_path_heading'), content('courses_path_subtitle')); ?>
        <ol class="path-steps">
            <?php foreach ($active_stages as $n => $stage):
                $first = $courses_by_stage[$stage][0];
                $label = $stage === 'secondary' ? 'Class 9 – 10' : $first['duration'];
            ?>
            <li>
                <a href="#stage-<?php echo htmlspecialchars($stage); ?>" class="path-step">
                    <span class="path-number"><?php echo $n + 1; ?></span>
                    <span class="path-icon"><i class="fas <?php echo $stage_icons[$stage]; ?>" aria-hidden="true"></i></span>
                    <strong><?php echo htmlspecialchars(content("courses_{$stage}_heading")); ?></strong>
                    <small><?php echo htmlspecialchars($label); ?></small>
                </a>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<?php foreach ($active_stages as $n => $stage): ?>
<section id="stage-<?php echo htmlspecialchars($stage); ?>" class="stage-section<?php echo $n % 2 === 1 ? ' bg-light' : ''; ?>">
    <div class="container">
        <div class="section-header">
            <span class="eyebrow"><i class="fas <?php echo $stage_icons[$stage]; ?>" aria-hidden="true"></i> Stage <?php echo $n + 1; ?></span>
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

<?php if (!empty($unplaced) || empty($active_stages)): ?>
<!-- Programs without a stage, or the empty state while no courses are published -->
<section class="stage-section bg-light"<?php echo empty($active_stages) ? ' id="programs"' : ''; ?>>
    <div class="container">
        <?php sectionHeader(content('courses_list_eyebrow'), content('courses_list_heading'), content('courses_list_subtitle')); ?>
        <?php if (!empty($unplaced)): ?>
        <div class="course-list is-multi">
            <?php foreach ($unplaced as $course) { renderCourseCard($course); } ?>
        </div>
        <?php else: ?>
            <?php renderEmptyState('fa-book-open', 'Program details coming soon', 'Class-by-class program details will be published here shortly. Our admissions office is happy to answer your questions in the meantime.'); ?>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($requirement_groups)): ?>
<!-- Admission requirements (AdminCP -> Feature Cards -> Admission Requirements) -->
<section>
    <div class="container">
        <?php sectionHeader(content('courses_req_eyebrow'), content('courses_req_heading'), content('courses_req_subtitle')); ?>
        <div class="req-grid">
            <?php foreach ($requirement_groups as $req): ?>
            <?php $items = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $req['description'])))); ?>
            <div class="card req-card">
                <div class="req-head">
                    <div class="card-icon"><i class="<?php echo htmlspecialchars($req['icon']); ?>" aria-hidden="true"></i></div>
                    <h3><?php echo htmlspecialchars($req['title']); ?></h3>
                </div>
                <ul class="req-list">
                    <?php foreach ($items as $item): ?>
                        <li><i class="fas fa-check-circle" aria-hidden="true"></i> <?php echo htmlspecialchars($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- The other Academics pages -->
<section class="<?php echo empty($requirement_groups) ? '' : 'bg-light'; ?>">
    <div class="container">
        <?php sectionHeader('', content('courses_explore_heading')); ?>
        <?php linkCards([
            ['fa-chalkboard-user', 'Our Faculty', 'Meet the teachers', 'faculty'],
            ['fa-file-lines', 'Examination', 'Datesheets and results', 'examination'],
            ['fa-download', 'Downloads', 'Forms and documents', 'downloads'],
            ['fa-pen-to-square', 'Admissions', 'How to apply', 'admission'],
        ]); ?>
    </div>
</section>

<?php admissionsCta(content('courses_cta_heading'), content('courses_cta_text')); ?>

<?php include 'includes/footer.php'; ?>
