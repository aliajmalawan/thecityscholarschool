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

    $old_img_query = mysqli_query($conn, "SELECT image_path FROM hero_carousel WHERE id = $id");
    if ($old_img_row = mysqli_fetch_assoc($old_img_query)) {
        // remove the slide's photo and its WebP twin (only files inside images/hero/)
        if (!empty($old_img_row['image_path']) && strpos($old_img_row['image_path'], 'images/hero/') === 0) {
            $webp_twin = preg_replace('/\.(jpe?g|png)$/i', '.webp', $old_img_row['image_path']);
            foreach ([$old_img_row['image_path'], $webp_twin] as $old_file) {
                if (file_exists('../' . $old_file)) {
                    unlink('../' . $old_file);
                }
            }
        }
    }

    if (mysqli_query($conn, "DELETE FROM hero_carousel WHERE id = $id")) {
        $message = "Slide deleted successfully!";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // The headline over the slider is fixed (Page Content); a slide only needs a photo and an optional name
    // that is used as the photo's description for screen readers.
    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
    $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle'] ?? '');
    $text_color = mysqli_real_escape_string($conn, $_POST['text_color'] ?? '#ffffff');
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $slide_id = isset($_POST['slide_id']) && !empty($_POST['slide_id']) ? intval($_POST['slide_id']) : null;

    // Handle image upload
    $image_path = null;
    $image_savings = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../images/hero/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            $base_filename = 'slide_' . time() . '_' . uniqid();
            $new_filename = compressUploadedPhotoAsJpeg($_FILES['image']['tmp_name'], $upload_dir, $base_filename, 1920, 85);

            if ($new_filename) {
                $target_file = $upload_dir . $new_filename;
                $image_path = 'images/hero/' . $new_filename;
                $image_savings = describeCompressionSavings($_FILES['image']['tmp_name'], $target_file);
                createWebpTwin($target_file);          // a smaller WebP copy that modern browsers load instead

                // Remove old image when replacing on edit
                if ($slide_id) {
                    $old_img_query = mysqli_query($conn, "SELECT image_path FROM hero_carousel WHERE id = $slide_id");
                    if ($old_img_row = mysqli_fetch_assoc($old_img_query)) {
                        if (!empty($old_img_row['image_path']) && strpos($old_img_row['image_path'], 'images/hero/') === 0) {
                            foreach ([$old_img_row['image_path'], preg_replace('/\.(jpe?g|png)$/i', '.webp', $old_img_row['image_path'])] as $old_file) {
                                if (file_exists('../' . $old_file)) {
                                    unlink('../' . $old_file);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $message = "Invalid file format. Only JPG, PNG, WEBP allowed.";
        }
    }

    if ($message === '') {
        if ($slide_id) {
            // Update
            $image_sql = $image_path ? ", image_path='$image_path'" : "";
            $query = "UPDATE hero_carousel SET title='$title', subtitle='$subtitle', text_color='$text_color', display_order=$display_order, status='$status'$image_sql WHERE id=$slide_id";
            $ok = mysqli_query($conn, $query);
            $message = $ok ? ("Slide updated successfully!" . ($image_savings ? " Image compressed{$image_savings}." : "")) : "Error updating slide.";
        } else {
            // Insert - image is required for a new slide
            if (!$image_path) {
                $message = "Please select a banner image.";
            } else {
                $query = "INSERT INTO hero_carousel (image_path, title, subtitle, text_color, display_order, status) VALUES ('$image_path', '$title', '$subtitle', '$text_color', $display_order, '$status')";
                $ok = mysqli_query($conn, $query);
                $message = $ok ? ("Slide added successfully! Image compressed{$image_savings}.") : "Error adding slide.";
            }
        }
    }
}

// Fetch slide for editing
$edit_slide = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM hero_carousel WHERE id = $edit_id");
    $edit_slide = mysqli_fetch_assoc($result);
}

// Fetch all slides
$slides = mysqli_query($conn, "SELECT * FROM hero_carousel ORDER BY display_order ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Home Slider - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_hero_slides.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-images"></i> Home Slider</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false || strpos($message, 'Invalid') !== false || strpos($message, 'Please select') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false || strpos($message, 'Invalid') !== false || strpos($message, 'Please select') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <p style="color: var(--text-light); margin-bottom: 20px;">These photos change automatically at the very top of the homepage. Each slide can have its own headline and text; the buttons are edited in Page Content. Best photo size: 1920 x 800 px, with the left side kept calm and dark so the text stays readable.</p>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_slide ? 'Edit Slide' : 'Add New Slide'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_slide): ?>
                    <input type="hidden" name="slide_id" value="<?php echo $edit_slide['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Headline (optional)</label>
                    <input type="text" name="title" maxlength="200" value="<?php echo $edit_slide ? htmlspecialchars($edit_slide['title']) : ''; ?>" placeholder="e.g., Learning Through Activity">
                    <small style="color: var(--text-light); font-size: 12px;">Shown over this slide. Leave empty to use the main homepage headline (Page Content).</small>
                </div>

                <div class="form-group">
                    <label>Text (optional)</label>
                    <textarea name="subtitle" rows="2" maxlength="300" placeholder="One or two short sentences under the headline"><?php echo $edit_slide ? htmlspecialchars($edit_slide['subtitle']) : ''; ?></textarea>
                    <small style="color: var(--text-light); font-size: 12px;">Leave empty to use the main homepage text (Page Content).</small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" value="<?php echo $edit_slide ? intval($edit_slide['display_order']) : 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo (!$edit_slide || $edit_slide['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_slide && $edit_slide['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Slide photo <?php echo $edit_slide ? '(optional - leave empty to keep current)' : '*'; ?></label>
                    <input type="file" name="image" accept="image/*" <?php echo !$edit_slide ? 'required' : ''; ?>>
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Image is automatically compressed on upload. Wide landscape photos work best (e.g. 1920x800).</small>
                    <?php if ($edit_slide && !empty($edit_slide['image_path'])): ?>
                        <div style="margin-top: 15px;">
                            <img src="../<?php echo htmlspecialchars($edit_slide['image_path']); ?>" alt="Current banner" style="max-width: 400px; border-radius: 8px; box-shadow: var(--shadow-sm);">
                            <p style="margin-top: 5px; font-size: 13px; color: var(--text-light);">Current image</p>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_slide ? 'Update Slide' : 'Add Slide'; ?>
                </button>
                <?php if ($edit_slide): ?>
                    <a href="manage_hero_slides.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Slides List -->
        <div class="card" style="overflow-x: auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Slides</h2>
            <?php if ($slides && mysqli_num_rows($slides) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--primary-color); color: white;">
                            <th style="padding: 12px; text-align: left;">Preview</th>
                            <th style="padding: 12px; text-align: left;">Order</th>
                            <th style="padding: 12px; text-align: left;">Headline</th>
                            <th style="padding: 12px; text-align: left;">Status</th>
                            <th style="padding: 12px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($slide = mysqli_fetch_assoc($slides)): ?>
                            <tr style="border-bottom: 1px solid #e0e0e0;">
                                <td style="padding: 12px;">
                                    <img src="../<?php echo htmlspecialchars($slide['image_path']); ?>" style="width: 90px; height: 50px; object-fit: cover; border-radius: 6px;">
                                </td>
                                <td style="padding: 12px;"><?php echo intval($slide['display_order']); ?></td>
                                <td style="padding: 12px;"><?php echo trim((string) $slide['title']) !== '' ? '<strong>' . htmlspecialchars($slide['title']) . '</strong>' : '<span style="color: var(--text-light); font-style: italic;">Main homepage headline</span>'; ?></td>
                                <td style="padding: 12px;"><span style="background: <?php echo $slide['status'] == 'active' ? '#28a745' : '#dc3545'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px;"><?php echo ucfirst($slide['status']); ?></span></td>
                                <td style="padding: 12px;">
                                    <a href="?edit=<?php echo $slide['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>
                                    <a href="?action=delete&id=<?php echo $slide['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm('Delete this slide?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 30px; color: var(--text-light);">No slides yet - the homepage shows a plain olive hero (or the fallback photo set in Page Content) until you add at least one here.</p>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
