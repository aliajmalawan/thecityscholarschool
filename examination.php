<?php
require_once 'includes/config.php';
require_once 'includes/settings_helper.php';

// Fetch active datesheets
$datesheets = mysqli_query($conn, "SELECT DISTINCT * FROM exam_datesheets WHERE status = 'active' ORDER BY exam_year DESC");

// Fetch active board results
$matric_results = mysqli_query($conn, "SELECT DISTINCT * FROM board_results WHERE status = 'active' AND board_type = 'Matric' ORDER BY year DESC, display_order ASC");
$inter_results = mysqli_query($conn, "SELECT DISTINCT * FROM board_results WHERE status = 'active' AND board_type = 'Intermediate' ORDER BY year DESC, display_order ASC");

$page_title = content('seo_examination_title');
$meta_description = content('seo_examination_description');
?>
<?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <?php renderPageBanner('examination', true, 'fa-file-alt'); ?>

    <!-- Datesheets Section -->
    <section>
        <div class="container">
            <div class="section-header reveal">
                <span class="eyebrow"><?php echo htmlspecialchars(content('exam_datesheet_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('exam_datesheet_heading')); ?></h2>
            </div>

            <?php
            if ($datesheets && mysqli_num_rows($datesheets) > 0) {
                while ($datesheet = mysqli_fetch_assoc($datesheets)) {
                    echo '<div class="card" style="margin-bottom: 40px;">';
                    echo '<h3 style="color: var(--primary-color); margin-bottom: 20px; text-align: center;">' . htmlspecialchars($datesheet['exam_name']) . '</h3>';

                    // Fetch details
                    $details = mysqli_query($conn, "SELECT DISTINCT * FROM datesheet_details WHERE datesheet_id = {$datesheet['id']} ORDER BY exam_date ASC, class ASC");

                    if ($details && mysqli_num_rows($details) > 0) {
                        // Group by date
                        $dates = [];
                        while ($detail = mysqli_fetch_assoc($details)) {
                            $dates[$detail['exam_date']][] = $detail;
                        }

                        echo '<div style="overflow-x: auto;">';
                        echo '<table style="width: 100%; border-collapse: collapse;">';
                        echo '<thead><tr style="background: var(--primary-color); color: white;">';

                        // Get unique dates
                        $date_keys = array_keys($dates);
                        echo '<th style="padding: 15px; text-align: left; vertical-align: top;">Class</th>';
                        foreach ($date_keys as $date) {
                            $day = $dates[$date][0]['day_name'];
                            echo '<th style="padding: 15px; text-align: center;">' . date('d.m.Y', strtotime($date)) . '<br><small>' . $day . '</small></th>';
                        }

                        echo '</tr></thead><tbody>';

                        // Get unique classes
                        $classes = [];
                        foreach ($dates as $date => $entries) {
                            foreach ($entries as $entry) {
                                $classes[$entry['class']][$date] = $entry['subject'];
                            }
                        }

                        foreach ($classes as $class => $subjects_by_date) {
                            echo '<tr style="border-bottom: 1px solid var(--border-color);">';
                            echo '<td style="padding: 15px; font-weight: bold; background: var(--bg-light);">' . htmlspecialchars($class) . '</td>';
                            foreach ($date_keys as $date) {
                                echo '<td style="padding: 15px; text-align: center;">';
                                echo isset($subjects_by_date[$date]) ? htmlspecialchars($subjects_by_date[$date]) : '-';
                                echo '</td>';
                            }
                            echo '</tr>';
                        }

                        echo '</tbody></table></div>';
                    } else {
                        echo '<p style="text-align: center; color: var(--text-light);">Schedule not available yet</p>';
                    }

                    echo '</div>';
                }
            } else {
                renderEmptyState('fa-calendar-alt', 'No datesheets yet', 'Exam schedules will appear here as soon as they are announced.');
            }
            ?>
        </div>
    </section>

    <!-- Board Results Section -->
    <section class="bg-light">
        <div class="container">
            <div class="section-header reveal">
                <span class="eyebrow"><?php echo htmlspecialchars(content('exam_results_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('exam_results_heading')); ?></h2>
            </div>

            <!-- Intermediate Results -->
            <div style="margin-bottom: 50px;">
                <h3 style="color: var(--primary-color); margin-bottom: 25px; border-left: 5px solid var(--primary-color); padding-left: 15px;">Intermediate Results</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 30px;">
                    <?php
                    if ($inter_results && mysqli_num_rows($inter_results) > 0) {
                        while ($result = mysqli_fetch_assoc($inter_results)) {
                            echo '<div class="card">';
                            echo '<img src="' . htmlspecialchars($result['image_path']) . '" alt="' . htmlspecialchars($result['title']) . '" loading="lazy" style="width: 100%; height: 250px; object-fit: cover; border-radius: var(--radius-sm); margin-bottom: 15px; cursor: pointer; box-shadow: var(--shadow-xs);" onclick="window.open(\'' . htmlspecialchars($result['image_path'], ENT_QUOTES) . '\', \'_blank\')">';
                            echo '<h4 style="color: var(--primary-color); margin-bottom: 10px;">' . htmlspecialchars($result['title']) . '</h4>';
                            echo '<p style="color: var(--text-light);">Year: ' . $result['year'] . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div style="grid-column: 1 / -1;">'; renderEmptyState('fa-trophy', 'No Intermediate results yet', 'Results will be posted here once the board announces them.'); echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Matric Results -->
            <div>
                <h3 style="color: var(--primary-color); margin-bottom: 25px; border-left: 5px solid var(--primary-color); padding-left: 15px;">Matric Results</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 30px;">
                    <?php
                    if ($matric_results && mysqli_num_rows($matric_results) > 0) {
                        while ($result = mysqli_fetch_assoc($matric_results)) {
                            echo '<div class="card">';
                            echo '<img src="' . htmlspecialchars($result['image_path']) . '" alt="' . htmlspecialchars($result['title']) . '" loading="lazy" style="width: 100%; height: 250px; object-fit: cover; border-radius: var(--radius-sm); margin-bottom: 15px; cursor: pointer; box-shadow: var(--shadow-xs);" onclick="window.open(\'' . htmlspecialchars($result['image_path'], ENT_QUOTES) . '\', \'_blank\')">';
                            echo '<h4 style="color: var(--primary-color); margin-bottom: 10px;">' . htmlspecialchars($result['title']) . '</h4>';
                            echo '<p style="color: var(--text-light);">Year: ' . $result['year'] . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<div style="grid-column: 1 / -1;">'; renderEmptyState('fa-trophy', 'No Matric results yet', 'Results will be posted here once the board announces them.'); echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
