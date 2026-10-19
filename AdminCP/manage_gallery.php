<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../includes/image_helper.php';

$message = '';

// Handle delete image
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete image file
    $img_query = mysqli_query($conn, "SELECT image_path FROM gallery WHERE id = $id");
    if ($img_row = mysqli_fetch_assoc($img_query)) {
        if (file_exists('../' . $img_row['image_path'])) {
            unlink('../' . $img_row['image_path']);
        }
    }

    if (mysqli_query($conn, "DELETE FROM gallery WHERE id = $id")) {
        $message = "Image deleted successfully!";
    }
}

// Handle delete category
if (isset($_GET['action']) && $_GET['action'] == 'delete_category' && isset($_GET['cat_id'])) {
    $cat_id = intval($_GET['cat_id']);
    $cat_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT slug FROM gallery_categories WHERE id = $cat_id"));

    if ($cat_row) {
        $slug_esc = mysqli_real_escape_string($conn, $cat_row['slug']);
        $in_use = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM gallery WHERE category = '$slug_esc'"));

        if ($in_use['cnt'] > 0) {
            $message = "Cannot delete this category - {$in_use['cnt']} image(s) are still using it. Reassign or delete those images first.";
        } elseif (mysqli_query($conn, "DELETE FROM gallery_categories WHERE id = $cat_id")) {
            $message = "Category deleted successfully!";
        }
    }
}

// Handle add category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_category_name'])) {
    $cat_name = trim($_POST['new_category_name']);
    if ($cat_name !== '') {
        $cat_name_esc = mysqli_real_escape_string($conn, $cat_name);
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($cat_name)), '-'));
        $slug_esc = mysqli_real_escape_string($conn, $slug);

        $exists = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM gallery_categories WHERE slug = '$slug_esc'"));
        if ($exists) {
            $message = "A category with that name already exists.";
        } elseif (mysqli_query($conn, "INSERT INTO gallery_categories (name, slug) VALUES ('$cat_name_esc', '$slug_esc')")) {
            $message = "Category added successfully!";
        } else {
            $message = "Error adding category.";
        }
    }
}

// Fetch categories (used by the image form dropdown and the category manager list)
$categories = mysqli_query($conn, "SELECT * FROM gallery_categories WHERE status = 'active' ORDER BY display_order ASC, name ASC");
$categories_list = [];
if ($categories) {
    while ($cat_row = mysqli_fetch_assoc($categories)) {
        $categories_list[] = $cat_row;
    }
}

// slug => display name lookup (includes inactive, so old items still show a real label)
$category_names = [];
$all_categories = mysqli_query($conn, "SELECT slug, name FROM gallery_categories");
if ($all_categories) {
    while ($cat_row = mysqli_fetch_assoc($all_categories)) {
        $category_names[$cat_row['slug']] = $cat_row['name'];
    }
}

// Handle add/edit image
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['category']) && !isset($_POST['new_category_name'])) {
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $gallery_id = isset($_POST['gallery_id']) && !empty($_POST['gallery_id']) ? intval($_POST['gallery_id']) : null;

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $upload_dir = '../uploads/gallery/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Collect the selected files (name="images[]", works whether 0, 1, or many were chosen)
    $selected_files = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $idx => $name) {
            if ($_FILES['images']['error'][$idx] == 0) {
                $selected_files[] = [
                    'name' => $name,
                    'tmp_name' => $_FILES['images']['tmp_name'][$idx],
                ];
            }
        }
    }

    if ($gallery_id) {
        // Editing an existing item - at most one replacement file is relevant
        if (!empty($selected_files)) {
            $file = $selected_files[0];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (in_array($file_extension, $allowed_extensions)) {
                $base_filename = time() . '_' . uniqid();
                $new_filename = compressUploadedPhotoAsJpeg($file['tmp_name'], $upload_dir, $base_filename, 1600, 85);

                if ($new_filename) {
                    $target_file = $upload_dir . $new_filename;
                    $image_path = 'uploads/gallery/' . $new_filename;
                    mediaThumb($image_path);   // make the gallery thumbnail now, not on the first visitor's page load
                    $savings = describeCompressionSavings($file['tmp_name'], $target_file);

                    $old_img_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM gallery WHERE id = $gallery_id"));
                    if ($old_img_row && file_exists('../' . $old_img_row['image_path'])) {
                        unlink('../' . $old_img_row['image_path']);
                    }

                    $query = "UPDATE gallery SET image_path='$image_path', category='$category', display_order=$display_order, status='$status' WHERE id=$gallery_id";
                    $message = mysqli_query($conn, $query) ? "Gallery item updated successfully! Image compressed{$savings}." : "Error updating gallery item.";
                } else {
                    $message = "Error compressing and uploading file.";
                }
            } else {
                $message = "Invalid file format. Only JPG, PNG, GIF, WEBP allowed.";
            }
        } else {
            $query = "UPDATE gallery SET category='$category', display_order=$display_order, status='$status' WHERE id=$gallery_id";
            $message = mysqli_query($conn, $query) ? "Gallery item updated successfully!" : "Error updating gallery item.";
        }
    } else {
        // Adding new - every selected file becomes its own gallery row
        if (empty($selected_files)) {
            $message = "Please select at least one image to upload.";
        } else {
            $saved_count = 0;
            $order = $display_order;
            $bytes_before = 0;
            $bytes_after = 0;

            foreach ($selected_files as $file) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($file_extension, $allowed_extensions)) {
                    continue;
                }

                $base_filename = time() . '_' . uniqid();
                $new_filename = compressUploadedPhotoAsJpeg($file['tmp_name'], $upload_dir, $base_filename, 1600, 85);

                if ($new_filename) {
                    $target_file = $upload_dir . $new_filename;
                    $image_path = 'uploads/gallery/' . $new_filename;
                    mediaThumb($image_path);   // make the gallery thumbnail now, not on the first visitor's page load
                    $query = "INSERT INTO gallery (image_path, category, display_order, status) VALUES ('$image_path', '$category', $order, '$status')";
                    if (mysqli_query($conn, $query)) {
                        $saved_count++;
                        $bytes_before += filesize($file['tmp_name']);
                        $bytes_after += filesize($target_file);
                    }
                }

                $order++;
            }

            $savings = ($bytes_before > 0)
                ? ' (' . formatFileSize($bytes_before) . ' -> ' . formatFileSize($bytes_after) . ', ' . round((1 - $bytes_after / $bytes_before) * 100) . '% smaller)'
                : '';

            $total_selected = count($selected_files);
            if ($saved_count === 0) {
                $message = "Error uploading images. Only JPG, PNG, GIF, WEBP allowed.";
            } elseif ($saved_count < $total_selected) {
                $message = "$saved_count of $total_selected image(s) uploaded and compressed{$savings} (some had an invalid format).";
            } else {
                $message = ($saved_count == 1 ? "Image uploaded and compressed{$savings}!" : "$saved_count images uploaded and compressed{$savings}!");
            }
        }
    }
}

// Fetch gallery item for editing
$edit_item = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM gallery WHERE id = $edit_id");
    $edit_item = mysqli_fetch_assoc($result);
}

// Fetch all gallery items
$gallery = mysqli_query($conn, "SELECT * FROM gallery ORDER BY display_order ASC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Gallery - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 250px), 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .gallery-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .gallery-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .gallery-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .gallery-item-info {
            padding: 15px;
        }
        .gallery-item-actions {
            padding: 10px 15px;
            background: var(--bg-light);
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_gallery.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-images"></i> Manage Gallery</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false || strpos($message, 'Invalid') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false || strpos($message, 'Invalid') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_item ? 'Edit Gallery Item' : 'Add New Images'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_item): ?>
                    <input type="hidden" name="gallery_id" value="<?php echo $edit_item['id']; ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Category <?php echo empty($categories_list) ? '' : '*'; ?></label>
                        <?php if (empty($categories_list)): ?>
                            <select name="category" disabled>
                                <option value="">No categories yet</option>
                            </select>
                            <small style="color: var(--text-light); font-size: 12px;">Add a category below first</small>
                        <?php else: ?>
                            <select name="category" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo ($edit_item && $edit_item['category'] == $cat['slug']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" value="<?php echo $edit_item ? $edit_item['display_order'] : '0'; ?>" placeholder="0">
                        <small style="color: var(--text-light); font-size: 12px;">Lower numbers appear first</small>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo ($edit_item && $edit_item['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_item && $edit_item['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <?php if ($edit_item): ?>
                        <label>Replace Image (optional - leave empty to keep current)</label>
                        <input type="file" name="images[]" accept="image/*">
                        <?php if (!empty($edit_item['image_path'])): ?>
                            <div style="margin-top: 15px;">
                                <img src="../<?php echo htmlspecialchars($edit_item['image_path']); ?>" alt="Current Image" style="max-width: 300px; border-radius: 8px; box-shadow: var(--shadow);">
                                <p style="margin-top: 5px; font-size: 13px; color: var(--text-light);">Current image</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <label>Upload Images *</label>
                        <input type="file" name="images[]" accept="image/*" multiple required>
                        <small style="color: var(--text-light); font-size: 12px; display: block; margin-top: 5px;">You can select multiple images at once - each becomes its own gallery entry.</small>
                    <?php endif; ?>
                    <small style="color: var(--primary-color); font-size: 12px; display: flex; align-items: center; gap: 6px; margin-top: 8px;"><i class="fas fa-compress-alt"></i> Images are automatically compressed on upload - no need to resize them yourself first.</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_item ? 'Update Image' : 'Upload Images'; ?>
                </button>
                <?php if ($edit_item): ?>
                    <a href="manage_gallery.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Manage Categories -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-tags"></i> Manage Categories</h2>

            <form method="POST" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 25px;">
                <div class="form-group" style="flex: 1; min-width: 220px; margin-bottom: 0;">
                    <label>New Category Name</label>
                    <input type="text" name="new_category_name" placeholder="e.g., Sports Day" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Category</button>
            </form>

            <?php if (empty($categories_list)): ?>
                <p style="color: var(--text-light);">No categories yet. Add your first one above.</p>
            <?php else: ?>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($categories_list as $cat): ?>
                        <span style="display: inline-flex; align-items: center; gap: 10px; background: var(--bg-light); border-radius: 20px; padding: 8px 8px 8px 16px; font-size: 14px;">
                            <?php echo htmlspecialchars($cat['name']); ?>
                            <a href="?action=delete_category&cat_id=<?php echo $cat['id']; ?>" onclick="return confirm('Delete category &quot;<?php echo htmlspecialchars(addslashes($cat['name'])); ?>&quot;?');" style="width: 22px; height: 22px; border-radius: 50%; background: white; display: inline-flex; align-items: center; justify-content: center; color: #dc3545;" title="Delete category">
                                <i class="fas fa-times" style="font-size: 11px;"></i>
                            </a>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Gallery Grid -->
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Gallery Images</h2>

            <?php if ($gallery && mysqli_num_rows($gallery) > 0): ?>
                <div class="gallery-grid">
                    <?php
                    while ($item = mysqli_fetch_assoc($gallery)) {
                        echo '<div class="gallery-item">';
                        echo '<img src="../' . htmlspecialchars($item['image_path']) . '" alt="Gallery image">';
                        echo '<div class="gallery-item-info">';
                        $cat_label = !empty($item['category']) && isset($category_names[$item['category']]) ? $category_names[$item['category']] : 'Uncategorized';
                        echo '<p style="margin: 0; font-size: 13px; color: var(--text-light);"><i class="fas fa-tag"></i> ' . htmlspecialchars($cat_label) . '</p>';
                        echo '<p style="margin: 5px 0 0 0; font-size: 13px; color: var(--text-light);"><i class="fas fa-sort"></i> Order: ' . $item['display_order'] . '</p>';
                        echo '<span style="background: ' . ($item['status'] == 'active' ? '#28a745' : '#dc3545') . '; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; display: inline-block; margin-top: 5px;">' . ucfirst($item['status']) . '</span>';
                        echo '</div>';
                        echo '<div class="gallery-item-actions">';
                        echo '<a href="?edit=' . $item['id'] . '" class="btn btn-primary" style="padding: 8px 15px; font-size: 13px; flex: 1; text-align: center;"><i class="fas fa-edit"></i> Edit</a>';
                        echo '<a href="?action=delete&id=' . $item['id'] . '" class="btn btn-primary" style="padding: 8px 15px; font-size: 13px; background: #dc3545; flex: 1; text-align: center;" onclick="return confirm(\'Delete this image?\')"><i class="fas fa-trash"></i> Delete</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                    <i class="fas fa-images" style="font-size: 80px; margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3 style="margin-bottom: 10px;">No Images Yet</h3>
                    <p>Upload your first image to get started!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
