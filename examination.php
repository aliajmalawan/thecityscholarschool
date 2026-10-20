<?php
require_once 'includes/config.php';
$page_title = content('seo_examination_title');
$meta_description = content('seo_examination_description');

// Datesheets with their papers (AdminCP -> Datesheets): one table per datesheet, classes down, dates across
$datesheets = [];
$res = mysqli_query($conn, "SELECT DISTINCT * FROM exam_datesheets WHERE status = 'active' ORDER BY exam_year DESC, id DESC");
while ($res && ($sheet = mysqli_fetch_assoc($res))) {
    $papers = mysqli_query($conn, "SELECT DISTINCT * FROM datesheet_details WHERE datesheet_id = " . intval($sheet['id']) . " ORDER BY exam_date ASC, class ASC");
    $dates = [];
    $classes = [];
    while ($papers && ($p = mysqli_fetch_assoc($papers))) {
        $dates[$p['exam_date']] = $p['day_name'];
        $classes[$p['class']][$p['exam_date']] = $p['subject'];
    }
    $sheet['dates'] = $dates;
    $sheet['classes'] = $classes;
    $datesheets[] = $sheet;
}

// Board results (AdminCP -> Board Results), grouped by board type in the order they first appear
$results_by_type = [];
$res = mysqli_query($conn, "SELECT DISTINCT * FROM board_results WHERE status = 'active' ORDER BY year DESC, display_order ASC, id DESC");
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $results_by_type[trim((string) $row['board_type']) ?: 'Results'][] = $row;
}
?>
<?php include 'includes/header.php'; ?>

<?php renderPageBanner('examination', true, 'fa-file-lines', ['Academics', 'courses']); ?>

<!-- Datesheets -->
<section class="page-intro">
    <div class="container">
        <?php sectionHeader(content('exam_datesheet_eyebrow'), content('exam_datesheet_heading')); ?>
        <?php if (!empty($datesheets)): ?>
            <?php foreach ($datesheets as $sheet): ?>
            <div class="table-card">
                <div class="table-card-head">
                    <h3><?php echo htmlspecialchars($sheet['exam_name']); ?></h3>
                    <?php if (!empty($sheet['exam_year'])): ?><span class="pill"><?php echo htmlspecialchars($sheet['exam_year']); ?></span><?php endif; ?>
                </div>
                <?php if (!empty($sheet['dates'])): ?>
                <div class="table-scroll" role="region" aria-label="<?php echo htmlspecialchars($sheet['exam_name']); ?> schedule" tabindex="0">
                    <table class="data-table">
                        <caption class="sr-only"><?php echo htmlspecialchars($sheet['exam_name']); ?>: papers by class and date</caption>
                        <thead>
                            <tr>
                                <th scope="col">Class</th>
                                <?php foreach ($sheet['dates'] as $date => $day): ?>
                                <th scope="col"><?php echo date('d M Y', strtotime($date)); ?><small><?php echo htmlspecialchars($day); ?></small></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sheet['classes'] as $class => $by_date): ?>
                            <tr>
                                <th scope="row"><?php echo htmlspecialchars($class); ?></th>
                                <?php foreach (array_keys($sheet['dates']) as $date): ?>
                                <td><?php echo isset($by_date[$date]) ? htmlspecialchars($by_date[$date]) : '<span class="muted" aria-label="No paper">–</span>'; ?></td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="table-card-empty">The paper-by-paper schedule will be added soon.</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php renderEmptyState('fa-calendar-days', 'No datesheets published yet', 'Exam schedules will appear here as soon as they are announced.'); ?>
        <?php endif; ?>
    </div>
</section>

<!-- Board results -->
<section class="bg-light">
    <div class="container">
        <?php sectionHeader(content('exam_results_eyebrow'), content('exam_results_heading')); ?>
        <?php if (!empty($results_by_type)): ?>
            <?php foreach ($results_by_type as $type => $results): ?>
            <h3 class="group-title"><?php echo htmlspecialchars($type); ?></h3>
            <div class="result-grid">
                <?php foreach ($results as $result): ?>
                <a class="result-card" href="<?php echo htmlspecialchars($result['image_path']); ?>" target="_blank" rel="noopener">
                    <span class="result-media"><img src="<?php echo htmlspecialchars($result['image_path']); ?>" alt="" loading="lazy"></span>
                    <span class="result-body">
                        <strong><?php echo htmlspecialchars($result['title']); ?></strong>
                        <small><?php echo htmlspecialchars($result['year']); ?> · View full size <i class="fas fa-up-right-from-square" aria-hidden="true"></i><span class="sr-only"> (opens in a new tab)</span></small>
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php renderEmptyState('fa-trophy', 'No results published yet', 'Board results will be posted here once they are announced.'); ?>
        <?php endif; ?>
    </div>
</section>

<?php admissionsCta('Questions About Exams?', 'Our office can help with schedules, results and anything else related to examinations.'); ?>

<?php include 'includes/footer.php'; ?>
