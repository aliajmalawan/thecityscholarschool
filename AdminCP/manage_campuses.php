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

    $old_img_query = mysqli_query($conn, "SELECT image_path FROM campuses WHERE id = $id");
    if ($old_img_row = mysqli_fetch_assoc($old_img_query)) {
        if (!empty($old_img_row['image_path']) && file_exists('../' . $old_img_row['image_path'])) {
            unlink('../' . $old_img_row['image_path']);
        }
    }

    if (mysqli_query($conn, "DELETE FROM campuses WHERE id = $id")) {
        $message = "Campus deleted successfully!";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $tagline = mysqli_real_escape_string($conn, $_POST['tagline']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $icon = mysqli_real_escape_string($conn, $_POST['icon']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $campus_id = isset($_POST['campus_id']) && !empty($_POST['campus_id']) ? intval($_POST['campus_id']) : null;

    // Handle optional image upload
    $image_path = null;
    $image_savings = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/campuses/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($file_extension, $allowed_extensions)) {
            $base_filename = 'campus_' . time() . '_' . uniqid();
            $new_filename = compressUploadedPhotoAsJpeg($_FILES['image']['tmp_name'], $upload_dir, $base_filename, 1600, 85);
            if ($new_filename) {
                $image_path = 'uploads/campuses/' . $new_filename;
                $image_savings = describeCompressionSavings($_FILES['image']['tmp_name'], $upload_dir . $new_filename);

                // Remove old image when replacing on edit
                if ($campus_id) {
                    $old_img_query = mysqli_query($conn, "SELECT image_path FROM campuses WHERE id = $campus_id");
                    if ($old_img_row = mysqli_fetch_assoc($old_img_query)) {
                        if (!empty($old_img_row['image_path']) && file_exists('../' . $old_img_row['image_path'])) {
                            unlink('../' . $old_img_row['image_path']);
                        }
                    }
                }
            }
        }
    }

    if ($campus_id) {
        // Update
        $image_sql = $image_path ? ", image_path='$image_path'" : "";
        $query = "UPDATE campuses SET name='$name', tagline='$tagline', description='$description', address='$address', phone='$phone', email='$email', icon='$icon', display_order=$display_order, status='$status'$image_sql WHERE id=$campus_id";
    } else {
        // Insert
        $image_value = $image_path ? "'$image_path'" : "NULL";
        $query = "INSERT INTO campuses (name, tagline, description, address, phone, email, icon, image_path, display_order, status) VALUES ('$name', '$tagline', '$description', '$address', '$phone', '$email', '$icon', $image_value, $display_order, '$status')";
    }

    if (mysqli_query($conn, $query)) {
        $message = "Campus saved successfully!" . ($image_savings ? " Image compressed{$image_savings}." : "");
    } else {
        $message = "Error saving campus.";
    }
}

// Fetch campus for editing
$edit_campus = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM campuses WHERE id = $edit_id");
    $edit_campus = mysqli_fetch_assoc($result);
}

// Fetch all campuses
$campuses = mysqli_query($conn, "SELECT * FROM campuses ORDER BY display_order ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Campuses - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_campuses.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;">Manage Campuses</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_campus ? 'Edit Campus' : 'Add New Campus'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_campus): ?>
                    <input type="hidden" name="campus_id" value="<?php echo $edit_campus['id']; ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Campus Name *</label>
                        <input type="text" name="name" required placeholder="e.g., Computer College" value="<?php echo $edit_campus ? htmlspecialchars($edit_campus['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Tagline</label>
                        <input type="text" name="tagline" placeholder="e.g., IT & Computer Sciences Campus" value="<?php echo $edit_campus ? htmlspecialchars($edit_campus['tagline']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" placeholder="Optional - add details later"><?php echo $edit_campus ? htmlspecialchars($edit_campus['description']) : ''; ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" placeholder="Optional" value="<?php echo $edit_campus ? htmlspecialchars($edit_campus['address']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Icon (Font Awesome class)</label>
                        <input type="text" name="icon" placeholder="e.g., fa-laptop-code" value="<?php echo $edit_campus ? htmlspecialchars($edit_campus['icon']) : ''; ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" placeholder="Optional" value="<?php echo $edit_campus ? htmlspecialchars($edit_campus['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Optional" value="<?php echo $edit_campus ? htmlspecialchars($edit_campus['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Campus Photo</label>
                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Images are automatically compressed on upload.</small>
                    <?php if ($edit_campus && !empty($edit_campus['image_path'])): ?>
                        <small style="color: var(--text-light); display: block; margin-top: 5px;">Current photo will be kept unless you upload a new one.</small>
                    <?php endif; ?>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" value="<?php echo $edit_campus ? intval($edit_campus['display_order']) : 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo (!$edit_campus || $edit_campus['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_campus && $edit_campus['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_campus ? 'Update Campus' : 'Add Campus'; ?>
                </button>
                <?php if ($edit_campus): ?>
                    <a href="manage_campuses.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Campuses List -->
        <div class="card" style="overflow-x: auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Campuses</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">Photo</th>
                        <th style="padding: 15px; text-align: left;">Name</th>
                        <th style="padding: 15px; text-align: left;">Tagline</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($campuses && mysqli_num_rows($campuses) > 0) {
                        while ($campus = mysqli_fetch_assoc($campuses)) {
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px;">';
                            if (!empty($campus['image_path'])) {
                                echo '<img src="../' . htmlspecialchars($campus['image_path']) . '" style="width: 56px; height: 56px; object-fit: cover; border-radius: 8px;">';
                            } else {
                                echo '<div style="width: 56px; height: 56px; border-radius: 8px; background: var(--bg-light); display: flex; align-items: center; justify-content: center; color: var(--text-light);"><i class="fas ' . ($campus['icon'] ?: 'fa-school') . '"></i></div>';
                            }
                            echo '</td>';
                            echo '<td style="padding: 15px;"><strong>' . htmlspecialchars($campus['name']) . '</strong></td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($campus['tagline']) . '</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . ($campus['status'] == 'active' ? '#28a745' : '#dc3545') . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($campus['status']) . '</span></td>';
                            echo '<td style="padding: 15px;">';
                            echo '<a href="?edit=' . $campus['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>';
                            echo '<a href="?action=delete&id=' . $campus['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Delete this campus?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5" style="padding: 30px; text-align: center; color: var(--text-light);">No campuses yet</td></tr>';
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
