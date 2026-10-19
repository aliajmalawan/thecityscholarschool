<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete image file if exists
    $img_query = mysqli_query($conn, "SELECT image FROM news WHERE id = $id");
    if ($img_row = mysqli_fetch_assoc($img_query)) {
        if (!empty($img_row['image']) && file_exists('../' . $img_row['image'])) {
            unlink('../' . $img_row['image']);
        }
    }

    if (mysqli_query($conn, "DELETE FROM news WHERE id = $id")) {
        $message = "News deleted successfully!";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle image upload
    $image_path = '';
    $image_savings = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/news/';

        // Create directory if not exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            $base_filename = time() . '_' . uniqid();
            $new_filename = compressUploadedPhotoAsJpeg($_FILES['image']['tmp_name'], $upload_dir, $base_filename, 1600, 85);

            if ($new_filename) {
                $target_file = $upload_dir . $new_filename;
                $image_path = 'uploads/news/' . $new_filename;
                $image_savings = describeCompressionSavings($_FILES['image']['tmp_name'], $target_file);
            }
        }
    }

    if (isset($_POST['news_id']) && !empty($_POST['news_id'])) {
        // Update
        $id = intval($_POST['news_id']);

        if ($image_path) {
            // Delete old image
            $old_img_query = mysqli_query($conn, "SELECT image FROM news WHERE id = $id");
            if ($old_img_row = mysqli_fetch_assoc($old_img_query)) {
                if (!empty($old_img_row['image']) && file_exists('../' . $old_img_row['image'])) {
                    unlink('../' . $old_img_row['image']);
                }
            }
            $query = "UPDATE news SET title='$title', content='$content', author='$author', image='$image_path', status='$status' WHERE id=$id";
        } else {
            $query = "UPDATE news SET title='$title', content='$content', author='$author', status='$status' WHERE id=$id";
        }
    } else {
        // Insert
        $query = "INSERT INTO news (title, content, author, image, status) VALUES ('$title', '$content', '$author', '$image_path', '$status')";
    }

    if (mysqli_query($conn, $query)) {
        $message = "News saved successfully!" . ($image_savings ? " Image compressed{$image_savings}." : "");
    } else {
        $message = "Error saving news.";
    }
}

// Fetch news for editing
$edit_news = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM news WHERE id = $edit_id");
    $edit_news = mysqli_fetch_assoc($result);
}

// Fetch all news
$news = mysqli_query($conn, "SELECT * FROM news ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage News & Announcements - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_news.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-newspaper"></i> Manage News & Announcements</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_news ? 'Edit News' : 'Add New News/Announcement'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_news): ?>
                    <input type="hidden" name="news_id" value="<?php echo $edit_news['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required value="<?php echo $edit_news ? htmlspecialchars($edit_news['title']) : ''; ?>" placeholder="e.g., Admissions Open for Session 2024">
                </div>

                <div class="form-group">
                    <label>Content *</label>
                    <textarea name="content" required rows="8" placeholder="Write the full news content here..."><?php echo $edit_news ? htmlspecialchars($edit_news['content']) : ''; ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" value="<?php echo $edit_news ? htmlspecialchars($edit_news['author']) : 'Admin'; ?>" placeholder="e.g., Admin, Principal">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo ($edit_news && $edit_news['status'] == 'active') ? 'selected' : ''; ?>>Active (Published)</option>
                            <option value="inactive" <?php echo ($edit_news && $edit_news['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive (Draft)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Featured Image (Optional)</label>
                    <input type="file" name="image" accept="image/*">
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Images are automatically compressed on upload.</small>
                    <?php if ($edit_news && !empty($edit_news['image'])): ?>
                        <div style="margin-top: 10px;">
                            <img src="../<?php echo htmlspecialchars($edit_news['image']); ?>" alt="Current Image" style="max-width: 300px; border-radius: 8px; box-shadow: var(--shadow);">
                            <p style="margin-top: 5px; font-size: 13px; color: var(--text-light);">Current image (upload new to replace)</p>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_news ? 'Update News' : 'Publish News'; ?>
                </button>
                <?php if ($edit_news): ?>
                    <a href="manage_news.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- News List -->
        <div class="card" style="overflow-x: auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All News & Announcements</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">ID</th>
                        <th style="padding: 15px; text-align: left;">Image</th>
                        <th style="padding: 15px; text-align: left;">Title</th>
                        <th style="padding: 15px; text-align: left;">Author</th>
                        <th style="padding: 15px; text-align: left;">Date</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($news && mysqli_num_rows($news) > 0) {
                        while ($item = mysqli_fetch_assoc($news)) {
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px;">#' . $item['id'] . '</td>';
                            echo '<td style="padding: 15px;">';
                            if (!empty($item['image'])) {
                                echo '<img src="../' . htmlspecialchars($item['image']) . '" alt="News" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">';
                            } else {
                                echo '<div style="width: 60px; height: 60px; background: var(--bg-light); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-newspaper" style="color: var(--text-light);"></i></div>';
                            }
                            echo '</td>';
                            echo '<td style="padding: 15px;"><strong>' . htmlspecialchars($item['title']) . '</strong><br><small style="color: var(--text-light);">' . substr(htmlspecialchars($item['content']), 0, 60) . '...</small></td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($item['author']) . '</td>';
                            echo '<td style="padding: 15px;">' . date('M d, Y', strtotime($item['created_at'])) . '</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . ($item['status'] == 'active' ? '#28a745' : '#ffc107') . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ($item['status'] == 'active' ? 'Published' : 'Draft') . '</span></td>';
                            echo '<td style="padding: 15px;">';
                            echo '<a href="?edit=' . $item['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>';
                            echo '<a href="?action=delete&id=' . $item['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Delete this news?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" style="padding: 30px; text-align: center; color: var(--text-light);">No news yet</td></tr>';
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
