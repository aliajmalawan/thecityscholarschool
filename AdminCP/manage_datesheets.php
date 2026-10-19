<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Add new datesheet
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_datesheet'])) {
    $exam_name = mysqli_real_escape_string($conn, $_POST['exam_name']);
    $exam_year = intval($_POST['exam_year']);

    $sql = "INSERT INTO exam_datesheets (exam_name, exam_year) VALUES ('$exam_name', $exam_year)";
    if (mysqli_query($conn, $sql)) {
        $message = 'Datesheet created successfully!';
    }
}

// Add datesheet detail
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_detail'])) {
    $datesheet_id = intval($_POST['datesheet_id']);
    $exam_date = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $day_name = mysqli_real_escape_string($conn, $_POST['day_name']);
    $class = mysqli_real_escape_string($conn, $_POST['class']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);

    $sql = "INSERT INTO datesheet_details (datesheet_id, exam_date, day_name, class, subject)
            VALUES ($datesheet_id, '$exam_date', '$day_name', '$class', '$subject')";
    if (mysqli_query($conn, $sql)) {
        $message = 'Entry added successfully!';
    }
}

// Delete datesheet
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM exam_datesheets WHERE id = $id");
    $message = 'Datesheet deleted!';
}

// Delete detail
if (isset($_GET['delete_detail'])) {
    $id = intval($_GET['delete_detail']);
    mysqli_query($conn, "DELETE FROM datesheet_details WHERE id = $id");
    $message = 'Entry deleted!';
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    mysqli_query($conn, "UPDATE exam_datesheets SET status = IF(status='active','inactive','active') WHERE id = $id");
    $message = 'Status updated!';
}

// Fetch all datesheets
$datesheets = mysqli_query($conn, "SELECT * FROM exam_datesheets ORDER BY exam_year DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Datesheets - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_datesheets.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;">Manage Exam Datesheets</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #28a745; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Datesheet Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Create New Datesheet</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Exam Name:</label>
                        <input type="text" name="exam_name" required placeholder="e.g., Annual Exam 2025" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Year:</label>
                        <input type="number" name="exam_year" required min="2020" max="2050" value="2025" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <button type="submit" name="add_datesheet" class="btn btn-primary"><i class="fas fa-plus"></i> Create</button>
                </div>
            </form>
        </div>

        <!-- Datesheets List -->
        <?php
        if ($datesheets && mysqli_num_rows($datesheets) > 0) {
            while ($datesheet = mysqli_fetch_assoc($datesheets)) {
                $status_color = $datesheet['status'] == 'active' ? '#28a745' : '#dc3545';
                echo '<div class="card" style="margin-bottom: 30px;">';
                echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">';
                echo '<div>';
                echo '<h2 style="color: var(--primary-color); margin-bottom: 5px;">' . htmlspecialchars($datesheet['exam_name']) . '</h2>';
                echo '<span style="background: ' . $status_color . '; color: white; padding: 5px 15px; border-radius: 20px; font-size: 12px;">' . ucfirst($datesheet['status']) . '</span>';
                echo '</div>';
                echo '<div>';
                echo '<a href="?toggle=' . $datesheet['id'] . '" class="btn btn-primary" style="padding: 8px 15px; margin-right: 5px;"><i class="fas fa-toggle-on"></i> Toggle</a>';
                echo '<a href="?delete=' . $datesheet['id'] . '" class="btn btn-primary" style="padding: 8px 15px; background: #dc3545;" onclick="return confirm(\'Delete?\')"><i class="fas fa-trash"></i> Delete</a>';
                echo '</div>';
                echo '</div>';

                // Add detail form
                echo '<form method="POST" style="margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 5px;">';
                echo '<input type="hidden" name="datesheet_id" value="' . $datesheet['id'] . '">';
                echo '<div style="display: grid; grid-template-columns: 150px 120px 100px 1fr auto; gap: 10px; align-items: end;">';
                echo '<div><label style="font-size: 12px; font-weight: bold;">Date:</label><input type="date" name="exam_date" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;"></div>';
                echo '<div><label style="font-size: 12px; font-weight: bold;">Day:</label><input type="text" name="day_name" required placeholder="Monday" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;"></div>';
                echo '<div><label style="font-size: 12px; font-weight: bold;">Class:</label><input type="text" name="class" required placeholder="6" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;"></div>';
                echo '<div><label style="font-size: 12px; font-weight: bold;">Subject:</label><input type="text" name="subject" required placeholder="Science" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;"></div>';
                echo '<button type="submit" name="add_detail" class="btn btn-primary" style="padding: 8px 15px; font-size: 12px;"><i class="fas fa-plus"></i> Add</button>';
                echo '</div>';
                echo '</form>';

                // Fetch and display details
                $details = mysqli_query($conn, "SELECT * FROM datesheet_details WHERE datesheet_id = {$datesheet['id']} ORDER BY exam_date ASC, class ASC");

                if ($details && mysqli_num_rows($details) > 0) {
                    echo '<div style="overflow-x: auto;">';
                    echo '<table style="width: 100%; border-collapse: collapse;">';
                    echo '<thead><tr style="background: var(--primary-color); color: white;">';
                    echo '<th style="padding: 10px; text-align: left;">Date</th>';
                    echo '<th style="padding: 10px; text-align: left;">Day</th>';
                    echo '<th style="padding: 10px; text-align: left;">Class</th>';
                    echo '<th style="padding: 10px; text-align: left;">Subject</th>';
                    echo '<th style="padding: 10px; text-align: center;">Action</th>';
                    echo '</tr></thead><tbody>';

                    while ($detail = mysqli_fetch_assoc($details)) {
                        echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                        echo '<td style="padding: 10px;">' . date('d.m.Y', strtotime($detail['exam_date'])) . '</td>';
                        echo '<td style="padding: 10px;">' . htmlspecialchars($detail['day_name']) . '</td>';
                        echo '<td style="padding: 10px;">' . htmlspecialchars($detail['class']) . '</td>';
                        echo '<td style="padding: 10px;">' . htmlspecialchars($detail['subject']) . '</td>';
                        echo '<td style="padding: 10px; text-align: center;"><a href="?delete_detail=' . $detail['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Delete?\')"><i class="fas fa-trash"></i></a></td>';
                        echo '</tr>';
                    }

                    echo '</tbody></table></div>';
                } else {
                    echo '<p style="text-align: center; color: var(--text-light); padding: 20px;">No entries added yet. Use the form above to add schedule.</p>';
                }

                echo '</div>';
            }
        } else {
            echo '<div class="card"><p style="text-align: center; color: var(--text-light); padding: 20px;">No datesheets created yet</p></div>';
        }
        ?>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
