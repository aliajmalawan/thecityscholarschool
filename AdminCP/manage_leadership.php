<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle Add New Leader
if (isset($_POST['add_leader'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $role_title = mysqli_real_escape_string($conn, $_POST['role_title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle photo upload
    $photo_path = '';
    $photo_savings = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../images/leadership/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= 5 * 1024 * 1024) {
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'leader_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (compressUploadedImage($_FILES['photo']['tmp_name'], $filepath, 800, 85)) {
                $photo_path = 'images/leadership/' . $filename;
                $photo_savings = describeCompressionSavings($_FILES['photo']['tmp_name'], $filepath);
            }
        }
    }

    // Handle signature upload
    $signature_path = '';
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] == 0) {
        $upload_dir = '../images/signatures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['signature']['type'], $allowed_types) && $_FILES['signature']['size'] <= 2 * 1024 * 1024) {
            $extension = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
            $filename = 'signature_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (compressUploadedImage($_FILES['signature']['tmp_name'], $filepath, 800, 90)) {
                $signature_path = 'images/signatures/' . $filename;
            }
        }
    }

    $query = "INSERT INTO leadership (name, designation, role_title, photo, signature, message, display_order, status)
              VALUES ('$name', '$designation', '$role_title', '$photo_path', '$signature_path', '$message', $display_order, '$status')";

    if (mysqli_query($conn, $query)) {
        $success_message = "Leadership entry added successfully!" . ($photo_savings ? " Photo compressed{$photo_savings}." : "");
    } else {
        $error_message = "Error adding leadership entry: " . mysqli_error($conn);
    }
}

// Handle Edit Leader
if (isset($_POST['edit_leader'])) {
    $id = intval($_POST['leader_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $role_title = mysqli_real_escape_string($conn, $_POST['role_title']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Get current data
    $current_result = mysqli_query($conn, "SELECT photo, signature FROM leadership WHERE id = $id");
    $current_data = mysqli_fetch_assoc($current_result);
    $photo_path = $current_data['photo'];
    $signature_path = $current_data['signature'];
    $photo_savings = '';

    // Handle photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../images/leadership/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['photo']['type'], $allowed_types) && $_FILES['photo']['size'] <= 5 * 1024 * 1024) {
            $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'leader_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (compressUploadedImage($_FILES['photo']['tmp_name'], $filepath, 800, 85)) {
                $photo_savings = describeCompressionSavings($_FILES['photo']['tmp_name'], $filepath);
                // Delete old photo
                if ($photo_path && file_exists('../' . $photo_path)) {
                    unlink('../' . $photo_path);
                }
                $photo_path = 'images/leadership/' . $filename;
            }
        }
    }

    // Handle signature upload
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] == 0) {
        $upload_dir = '../images/signatures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['signature']['type'], $allowed_types) && $_FILES['signature']['size'] <= 2 * 1024 * 1024) {
            $extension = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
            $filename = 'signature_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (compressUploadedImage($_FILES['signature']['tmp_name'], $filepath, 800, 90)) {
                // Delete old signature
                if ($signature_path && file_exists('../' . $signature_path)) {
                    unlink('../' . $signature_path);
                }
                $signature_path = 'images/signatures/' . $filename;
            }
        }
    }

    // "Remove photo" / "Remove signature" ticked (and no new file chosen): delete the file, clear the field.
    // The website then shows the default icon instead of a photo.
    $removed = [];
    foreach (['photo' => 'photo_path', 'signature' => 'signature_path'] as $field => $var) {
        $new_file_chosen = isset($_FILES[$field]) && $_FILES[$field]['error'] == 0;
        if (!empty($_POST['remove_' . $field]) && !$new_file_chosen && $$var !== '') {
            if (strpos($$var, '..') === false && is_file('../' . $$var)) {
                unlink('../' . $$var);
            }
            $$var = '';
            $removed[] = $field;
        }
    }
    $photo_path = mysqli_real_escape_string($conn, $photo_path);
    $signature_path = mysqli_real_escape_string($conn, $signature_path);

    $query = "UPDATE leadership SET
              name = '$name',
              designation = '$designation',
              role_title = '$role_title',
              photo = '$photo_path',
              signature = '$signature_path',
              message = '$message',
              display_order = $display_order,
              status = '$status'
              WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        $success_message = "Leadership entry updated successfully!" . ($photo_savings ? " Photo compressed{$photo_savings}." : "")
            . ($removed ? ' Removed: ' . implode(' and ', $removed) . '.' : '');
    } else {
        error_log('Leadership update failed: ' . mysqli_error($conn));
        $error_message = "Error updating leadership entry. Please try again.";
    }
}

// Handle Delete Leader
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    // Get file paths before deleting
    $result = mysqli_query($conn, "SELECT photo, signature FROM leadership WHERE id = $id");
    $data = mysqli_fetch_assoc($result);

    // Delete from database
    if (mysqli_query($conn, "DELETE FROM leadership WHERE id = $id")) {
        // Delete files
        if ($data['photo'] && file_exists('../' . $data['photo'])) {
            unlink('../' . $data['photo']);
        }
        if ($data['signature'] && file_exists('../' . $data['signature'])) {
            unlink('../' . $data['signature']);
        }
        $success_message = "Leadership entry deleted successfully!";
    } else {
        $error_message = "Error deleting leadership entry: " . mysqli_error($conn);
    }
}

// Handle Quick Status Toggle
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $current_result = mysqli_query($conn, "SELECT status FROM leadership WHERE id = $id");
    $current_data = mysqli_fetch_assoc($current_result);
    $new_status = ($current_data['status'] == 'active') ? 'inactive' : 'active';

    mysqli_query($conn, "UPDATE leadership SET status = '$new_status' WHERE id = $id");
    $success_message = "Status updated successfully!";
}

// Get all leadership entries
$leaders_query = "SELECT * FROM leadership ORDER BY display_order ASC, id ASC";
$leaders_result = mysqli_query($conn, $leaders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Leadership - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        .admin-header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .admin-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-header h1 {
            color: #78864F;
            font-size: 28px;
        }

        .admin-header h1 i {
            margin-right: 10px;
        }

        .btn-primary {
            padding: 12px 25px;
            background: #78864F;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #083a7a;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .leadership-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .leadership-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .leadership-table th {
            background: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        .leadership-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .leadership-table tr:hover {
            background: #f8f9fa;
        }
        .leader-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-color);
        }
        .action-btn {
            padding: 6px 12px;
            margin: 0 3px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-edit {
            background: #78864F;
            color: white;
        }
        .btn-edit:hover {
            background: #083a7a;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background: #c82333;
        }
        .btn-toggle {
            background: #28a745;
            color: white;
        }
        .btn-toggle:hover {
            background: #218838;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .form-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        .form-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        .btn-submit {
            flex: 1;
            padding: 12px;
            background: #78864F;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-submit:hover {
            background: #083a7a;
        }
        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-cancel:hover {
            background: #5a6268;
        }
        .preview-img {
            max-width: 150px;
            margin-top: 10px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <?php $active_page = 'manage_leadership.php'; include 'includes/sidebar.php'; ?>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main-content">
        <div class="admin-header">
            <div class="admin-header-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h1><i class="fas fa-users"></i> Manage Leadership Messages</h1>
            </div>
            <button class="btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Leader
            </button>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="leadership-table">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Role Title</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($leaders_result) > 0): ?>
                        <?php while ($leader = mysqli_fetch_assoc($leaders_result)): ?>
                            <tr>
                                <td><?php echo $leader['display_order']; ?></td>
                                <td>
                                    <?php if ($leader['photo']): ?>
                                        <img src="../<?php echo htmlspecialchars($leader['photo']); ?>" alt="<?php echo htmlspecialchars($leader['name']); ?>" class="leader-photo">
                                    <?php else: ?>
                                        <div class="leader-photo" style="background: var(--primary-color); display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user" style="color: white;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($leader['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($leader['designation']); ?></td>
                                <td><?php echo htmlspecialchars($leader['role_title']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $leader['status']; ?>">
                                        <?php echo ucfirst($leader['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn btn-edit" onclick='openEditModal(<?php echo json_encode($leader); ?>)'>
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?toggle_status=<?php echo $leader['id']; ?>" class="action-btn btn-toggle" onclick="return confirm('Toggle status for this leader?')">
                                        <i class="fas fa-toggle-on"></i> Toggle
                                    </a>
                                    <a href="?delete_id=<?php echo $leader['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this leadership entry?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                No leadership entries found. Click "Add New Leader" to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Leader Modal -->
    <div id="addModal" class="form-modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                <i class="fas fa-user-plus"></i> Add New Leader
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" required placeholder="Enter leader's full name">
                </div>

                <div class="form-group">
                    <label>Designation *</label>
                    <input type="text" name="designation" required placeholder="e.g., Founder & Managing Director">
                </div>

                <div class="form-group">
                    <label>Role Title *</label>
                    <input type="text" name="role_title" required placeholder="e.g., Founder's Message">
                </div>

                <div class="form-group">
                    <label>Photo (Optional)</label>
                    <input type="file" name="photo" accept="image/*" onchange="previewImage(this, 'add_photo_preview')">
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Photo is automatically compressed on upload.</small>
                    <small style="color: #666;">Max size: 5MB. Recommended: 500x500 pixels</small>
                    <img id="add_photo_preview" class="preview-img" style="display: none;">
                </div>

                <div class="form-group">
                    <label>Signature (Optional)</label>
                    <input type="file" name="signature" accept="image/*" onchange="previewImage(this, 'add_signature_preview')">
                    <small style="color: #666;">Max size: 2MB. PNG with transparent background recommended</small>
                    <img id="add_signature_preview" class="preview-img" style="display: none;">
                </div>

                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" required placeholder="Enter the leadership message..."></textarea>
                </div>

                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" value="0" min="0">
                    <small style="color: #666;">Lower numbers appear first</small>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="add_leader" class="btn-submit">
                        <i class="fas fa-save"></i> Add Leader
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Leader Modal -->
    <div id="editModal" class="form-modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 25px; color: var(--primary-color);">
                <i class="fas fa-edit"></i> Edit Leader
            </h2>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="leader_id" id="edit_leader_id">

                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>

                <div class="form-group">
                    <label>Designation *</label>
                    <input type="text" name="designation" id="edit_designation" required>
                </div>

                <div class="form-group">
                    <label>Role Title *</label>
                    <input type="text" name="role_title" id="edit_role_title" required>
                </div>

                <div class="form-group">
                    <label>Photo (Leave empty to keep current)</label>
                    <input type="file" name="photo" accept="image/*" onchange="previewImage(this, 'edit_photo_preview')">
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Photo is automatically compressed on upload.</small>
                    <small style="color: #666;">Max size: 5MB. Recommended: 500x500 pixels</small>
                    <img id="edit_photo_preview" class="preview-img">
                    <label class="remove-image" id="remove_photo_wrap">
                        <input type="checkbox" name="remove_photo" id="remove_photo" value="1"> <i class="fas fa-trash-alt"></i> Remove this photo (the website shows a default icon instead)
                    </label>
                </div>

                <div class="form-group">
                    <label>Signature (Leave empty to keep current)</label>
                    <input type="file" name="signature" accept="image/*" onchange="previewImage(this, 'edit_signature_preview')">
                    <small style="color: #666;">Max size: 2MB. PNG with transparent background recommended</small>
                    <img id="edit_signature_preview" class="preview-img">
                    <label class="remove-image" id="remove_signature_wrap">
                        <input type="checkbox" name="remove_signature" id="remove_signature" value="1"> <i class="fas fa-trash-alt"></i> Remove this signature
                    </label>
                </div>

                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" id="edit_message" required></textarea>
                </div>

                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="edit_display_order" min="0">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="edit_leader" class="btn-submit">
                        <i class="fas fa-save"></i> Update Leader
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function openEditModal(leader) {
            document.getElementById('edit_leader_id').value = leader.id;
            document.getElementById('edit_name').value = leader.name;
            document.getElementById('edit_designation').value = leader.designation;
            document.getElementById('edit_role_title').value = leader.role_title;
            document.getElementById('edit_message').value = leader.message;
            document.getElementById('edit_display_order').value = leader.display_order;
            document.getElementById('edit_status').value = leader.status;

            // Show current photo and signature if exist
            const photoPreview = document.getElementById('edit_photo_preview');
            const signaturePreview = document.getElementById('edit_signature_preview');

            if (leader.photo) {
                photoPreview.src = '../' + leader.photo;
                photoPreview.style.display = 'block';
            } else {
                photoPreview.style.display = 'none';
            }

            if (leader.signature) {
                signaturePreview.src = '../' + leader.signature;
                signaturePreview.style.display = 'block';
            } else {
                signaturePreview.style.display = 'none';
            }

            // "Remove" options only when there is something to remove; always start unticked
            ['photo', 'signature'].forEach(function (field) {
                document.getElementById('remove_' + field + '_wrap').style.display = leader[field] ? 'flex' : 'none';
                document.getElementById('remove_' + field).checked = false;
                document.getElementById('edit_' + field + '_preview').style.opacity = '1';
            });

            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('form-modal')) {
                event.target.classList.remove('active');
            }
        };
    </script>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
