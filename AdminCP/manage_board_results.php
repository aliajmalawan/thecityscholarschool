<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Add board result
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_result'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $board_type = mysqli_real_escape_string($conn, $_POST['board_type']);
    $year = intval($_POST['year']);
    $display_order = intval($_POST['display_order']);

    // Image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = array('jpg', 'jpeg', 'png');
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            $upload_dir = '../uploads/results/';
            $base_filename = time() . '_' . uniqid();
            $image_name = compressUploadedPhotoAsJpeg($_FILES['image']['tmp_name'], $upload_dir, $base_filename, 2000, 88);

            if ($image_name) {
                $image_path = $upload_dir . $image_name;
                $image_savings = describeCompressionSavings($_FILES['image']['tmp_name'], $image_path);
                $sql = "INSERT INTO board_results (title, board_type, year, image_path, display_order)
                        VALUES ('$title', '$board_type', $year, '$image_path', $display_order)";

                if (mysqli_query($conn, $sql)) {
                    $message = "Board result added successfully! Image compressed{$image_savings}.";
                } else {
                    $error = 'Database error!';
                }
            } else {
                $error = 'Failed to upload image';
            }
        } else {
            $error = 'Invalid file type. Only JPG, JPEG, PNG allowed';
        }
    } else {
        $error = 'Please select an image';
    }
}

// Delete result
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $result = mysqli_query($conn, "SELECT image_path FROM board_results WHERE id = $id");
    if ($row = mysqli_fetch_assoc($result)) {
        if (file_exists($row['image_path'])) {
            unlink($row['image_path']);
        }
    }
    mysqli_query($conn, "DELETE FROM board_results WHERE id = $id");
    $message = 'Result deleted!';
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    mysqli_query($conn, "UPDATE board_results SET status = IF(status='active','inactive','active') WHERE id = $id");
    $message = 'Status updated!';
}

// Fetch all results
$results = mysqli_query($conn, "SELECT * FROM board_results ORDER BY year DESC, display_order ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Board Results - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_board_results.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;">Manage Board Results</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #28a745; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #dc3545; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Add Result Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Add Board Result</h2>
            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Title:</label>
                        <input type="text" name="title" required placeholder="e.g., SSC Annual Results 2024" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Board Type:</label>
                        <select name="board_type" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="Matric">Matric</option>
                            <option value="Intermediate">Intermediate</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Year:</label>
                        <input type="number" name="year" required min="2000" max="2050" value="2025" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Display Order:</label>
                        <input type="number" name="display_order" value="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Result Image (JPG, PNG):</label>
                    <input type="file" name="image" required accept="image/*" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Image is automatically compressed on upload.</small>
                </div>

                <button type="submit" name="add_result" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Result
                </button>
            </form>
        </div>

        <!-- Results List -->
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Board Results</h2>

            <!-- Matric Results -->
            <h3 style="color: var(--primary-color); margin: 20px 0 15px;">Matric Results</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 20px; margin-bottom: 30px;">
                <?php
                mysqli_data_seek($results, 0);
                $has_matric = false;
                while ($result = mysqli_fetch_assoc($results)) {
                    if ($result['board_type'] == 'Matric') {
                        $has_matric = true;
                        $status_color = $result['status'] == 'active' ? '#28a745' : '#dc3545';
                        echo '<div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px;">';
                        echo '<img src="' . $result['image_path'] . '" style="width: 100%; height: 200px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">';
                        echo '<h4 style="color: var(--primary-color); margin-bottom: 5px;">' . htmlspecialchars($result['title']) . '</h4>';
                        echo '<p style="color: var(--text-light); margin-bottom: 10px;">Year: ' . $result['year'] . '</p>';
                        echo '<span style="background: ' . $status_color . '; color: white; padding: 5px 10px; border-radius: 20px; font-size: 11px; margin-right: 5px;">' . ucfirst($result['status']) . '</span>';
                        echo '<div style="margin-top: 10px;">';
                        echo '<a href="?toggle=' . $result['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;"><i class="fas fa-toggle-on"></i></a>';
                        echo '<a href="?delete=' . $result['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Delete?\')"><i class="fas fa-trash"></i></a>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                if (!$has_matric) {
                    echo '<p style="color: var(--text-light);">No Matric results yet</p>';
                }
                ?>
            </div>

            <!-- Intermediate Results -->
            <h3 style="color: var(--primary-color); margin: 20px 0 15px;">Intermediate Results</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: 20px;">
                <?php
                mysqli_data_seek($results, 0);
                $has_inter = false;
                while ($result = mysqli_fetch_assoc($results)) {
                    if ($result['board_type'] == 'Intermediate') {
                        $has_inter = true;
                        $status_color = $result['status'] == 'active' ? '#28a745' : '#dc3545';
                        echo '<div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px;">';
                        echo '<img src="' . $result['image_path'] . '" style="width: 100%; height: 200px; object-fit: cover; border-radius: 5px; margin-bottom: 10px;">';
                        echo '<h4 style="color: var(--primary-color); margin-bottom: 5px;">' . htmlspecialchars($result['title']) . '</h4>';
                        echo '<p style="color: var(--text-light); margin-bottom: 10px;">Year: ' . $result['year'] . '</p>';
                        echo '<span style="background: ' . $status_color . '; color: white; padding: 5px 10px; border-radius: 20px; font-size: 11px; margin-right: 5px;">' . ucfirst($result['status']) . '</span>';
                        echo '<div style="margin-top: 10px;">';
                        echo '<a href="?toggle=' . $result['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;"><i class="fas fa-toggle-on"></i></a>';
                        echo '<a href="?delete=' . $result['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Delete?\')"><i class="fas fa-trash"></i></a>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
                if (!$has_inter) {
                    echo '<p style="color: var(--text-light);">No Intermediate results yet</p>';
                }
                ?>
            </div>
        </div>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
