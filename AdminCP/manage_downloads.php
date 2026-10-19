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

// Handle file upload and add download
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_download'])) {
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $display_order = intval($_POST['display_order']);

    // File upload
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file_tmp = $_FILES['file']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        // The visitor-facing name keeps only safe characters (no quotes, slashes or spaces),
        // so it can't break the SQL below, the page's HTML or the file path
        $safe_base = trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($_FILES['file']['name'], PATHINFO_FILENAME)), '-');
        $file_name = mysqli_real_escape_string($conn, ($safe_base !== '' ? mb_substr($safe_base, 0, 80) : 'file') . '.' . $file_ext);
        $file_type = mysqli_real_escape_string($conn, $file_ext);

        $allowed = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip');

        if (in_array($file_ext, $allowed) && $_FILES['file']['size'] <= 20 * 1024 * 1024) {
            $new_file_name = time() . '_' . $file_name;
            $upload_path = '../uploads/downloads/' . $new_file_name;
            $db_path = 'uploads/downloads/' . $new_file_name; // Path for database (frontend use)

            $is_image = in_array($file_ext, ['jpg', 'jpeg', 'png']);
            $uploaded_ok = $is_image
                ? compressUploadedImage($file_tmp, $upload_path, 2000, 88)
                : move_uploaded_file($file_tmp, $upload_path);

            if ($uploaded_ok) {
                $savings = $is_image ? describeCompressionSavings($file_tmp, $upload_path) : '';
                $sql = "INSERT INTO downloads (date, description, file_path, file_name, file_type, display_order)
                        VALUES ('$date', '$description', '$db_path', '$file_name', '$file_type', $display_order)";

                if (mysqli_query($conn, $sql)) {
                    $message = 'Download added successfully!' . ($savings ? " Image compressed{$savings}." : '');
                } else {
                    $error = 'Database error: ' . mysqli_error($conn);
                }
            } else {
                $error = 'Failed to upload file';
            }
        } else {
            $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP';
        }
    } else {
        $error = 'Please select a file';
    }
}

// Delete download
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    // Get file path
    $result = mysqli_query($conn, "SELECT file_path FROM downloads WHERE id = $id");
    if ($row = mysqli_fetch_assoc($result)) {
        $file_path = $row['file_path'];
        // Adjust path for file deletion from admin panel
        if (strpos($file_path, '../') !== 0) {
            $file_path = '../' . $file_path;
        }
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    mysqli_query($conn, "DELETE FROM downloads WHERE id = $id");
    $message = 'Download deleted successfully!';
}

// Toggle status
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    mysqli_query($conn, "UPDATE downloads SET status = IF(status='active','inactive','active') WHERE id = $id");
    $message = 'Status updated!';
}

// Fetch all downloads
$downloads = mysqli_query($conn, "SELECT * FROM downloads ORDER BY display_order ASC, date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Downloads - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_downloads.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;">Manage Downloads</h1>
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

        <!-- Add Download Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Add New Download</h2>
            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Date:</label>
                        <input type="date" name="date" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Display Order:</label>
                        <input type="number" name="display_order" value="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Description:</label>
                    <input type="text" name="description" required placeholder="e.g., Homework for winter vacations" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">File (PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP):</label>
                    <input type="file" name="file" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Image files (JPG/PNG) are automatically compressed; other file types upload as-is.</small>
                </div>

                <button type="submit" name="add_download" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Download
                </button>
            </form>
        </div>

        <!-- Downloads List -->
        <div class="card" style="overflow-x: auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Downloads</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">Date</th>
                        <th style="padding: 15px; text-align: left;">Description</th>
                        <th style="padding: 15px; text-align: left;">File</th>
                        <th style="padding: 15px; text-align: left;">Order</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($downloads && mysqli_num_rows($downloads) > 0) {
                        while ($download = mysqli_fetch_assoc($downloads)) {
                            $status_color = $download['status'] == 'active' ? '#28a745' : '#dc3545';
                            // Adjust path for admin panel (add ../ if not present)
                            $display_path = $download['file_path'];
                            if (strpos($display_path, '../') !== 0) {
                                $display_path = '../' . $display_path;
                            }
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px;">' . date('d M Y', strtotime($download['date'])) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($download['description']) . '</td>';
                            echo '<td style="padding: 15px;"><a href="' . $display_path . '" target="_blank">' . htmlspecialchars($download['file_name']) . '</a></td>';
                            echo '<td style="padding: 15px;">' . $download['display_order'] . '</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . $status_color . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($download['status']) . '</span></td>';
                            echo '<td style="padding: 15px;">';
                            echo '<a href="?toggle_status=' . $download['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;" title="Toggle Status"><i class="fas fa-toggle-on"></i></a>';
                            echo '<a href="?delete=' . $download['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Are you sure?\')" title="Delete"><i class="fas fa-trash"></i></a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" style="padding: 30px; text-align: center; color: var(--text-light);">No downloads yet</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
