<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$upload_dir = '../uploads/faculty/';

/** Delete a stored faculty photo file (path as saved in the database, relative to the site root). */
function deleteFacultyPhoto($path) {
    if ($path !== '' && $path !== null && strpos($path, '..') === false && is_file('../' . $path)) {
        unlink('../' . $path);
    }
}

// Handle delete (the photo file goes too)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM faculty WHERE id = $id"));
    if (mysqli_query($conn, "DELETE FROM faculty WHERE id = $id")) {
        deleteFacultyPhoto($old['photo'] ?? '');
        $message = "Faculty member deleted successfully!";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);
    $subjects = mysqli_real_escape_string($conn, $_POST['subjects']);
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification']);
    $experience = intval($_POST['experience']);
    $status = ($_POST['status'] ?? '') === 'inactive' ? 'inactive' : 'active';
    $id = intval($_POST['faculty_id'] ?? 0);

    // Current photo (when editing)
    $current_photo = '';
    if ($id > 0) {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM faculty WHERE id = $id"));
        $current_photo = (string) ($row['photo'] ?? '');
    }
    $photo = $current_photo;
    $photo_note = '';

    // New photo chosen: compress, save, and replace the old one
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['photo']['size'] > 8 * 1024 * 1024) {
            $photo_note = ' The photo was not saved: it is larger than 8 MB.';
        } else {
            $saved = compressUploadedPhotoAsJpeg($_FILES['photo']['tmp_name'], $upload_dir, 'teacher_' . time() . '_' . substr(uniqid(), -6), 800, 85);
            if ($saved) {
                deleteFacultyPhoto($current_photo);
                $photo = 'uploads/faculty/' . $saved;
                $photo_note = ' Photo updated.';
            } else {
                $photo_note = ' The photo was not saved: please use a JPG, PNG or WebP image.';
            }
        }
    } elseif (!empty($_POST['remove_photo']) && $current_photo !== '') {
        // "Remove this photo" ticked: the website shows the default icon instead
        deleteFacultyPhoto($current_photo);
        $photo = '';
        $photo_note = ' Photo removed.';
    }
    $photo_sql = mysqli_real_escape_string($conn, $photo);

    if ($id > 0) {
        $query = "UPDATE faculty SET name='$name', designation='$designation', subjects='$subjects', qualification='$qualification', experience=$experience, photo='$photo_sql', status='$status' WHERE id=$id";
    } else {
        $query = "INSERT INTO faculty (name, designation, subjects, qualification, experience, photo, status) VALUES ('$name', '$designation', '$subjects', '$qualification', $experience, '$photo_sql', '$status')";
    }

    if (mysqli_query($conn, $query)) {
        $message = "Faculty member saved successfully!" . $photo_note;
    } else {
        $message = "Error saving faculty member.";
    }
}

// Fetch faculty for editing
$edit_faculty = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM faculty WHERE id = $edit_id");
    $edit_faculty = mysqli_fetch_assoc($result);
}

// Fetch all faculty
$faculty = mysqli_query($conn, "SELECT * FROM faculty ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Faculty - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_faculty.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;">Manage Faculty</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_faculty ? 'Edit Faculty Member' : 'Add New Faculty Member'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_faculty): ?>
                    <input type="hidden" name="faculty_id" value="<?php echo $edit_faculty['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['name']) : ''; ?>">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Designation *</label>
                        <input type="text" name="designation" required value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['designation']) : ''; ?>" placeholder="e.g., Senior Teacher">
                    </div>

                    <div class="form-group">
                        <label>Subjects *</label>
                        <input type="text" name="subjects" required value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['subjects']) : ''; ?>" placeholder="e.g., Physics, Mathematics">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Qualification *</label>
                        <input type="text" name="qualification" required value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['qualification']) : ''; ?>" placeholder="e.g., MSc Physics">
                    </div>

                    <div class="form-group">
                        <label>Experience (Years) *</label>
                        <input type="number" name="experience" required value="<?php echo $edit_faculty ? htmlspecialchars($edit_faculty['experience']) : ''; ?>" placeholder="e.g., 10">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo ($edit_faculty && $edit_faculty['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_faculty && $edit_faculty['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="faculty_photo">Photo <?php echo ($edit_faculty && !empty($edit_faculty['photo'])) ? '(choose a new file to replace it)' : '(optional)'; ?></label>
                    <?php if ($edit_faculty && !empty($edit_faculty['photo'])): ?>
                        <img src="../<?php echo htmlspecialchars($edit_faculty['photo']); ?>" alt="Current photo" class="faculty-photo-preview">
                    <?php endif; ?>
                    <input type="file" id="faculty_photo" name="photo" accept="image/jpeg,image/png,image/webp">
                    <small style="color: var(--text-light); display: block; margin-top: 6px;">JPG, PNG or WebP, up to 8 MB. A square photo with the face in the middle looks best; it is compressed automatically.</small>
                    <?php if ($edit_faculty && !empty($edit_faculty['photo'])): ?>
                        <label class="remove-image">
                            <input type="checkbox" name="remove_photo" value="1"> <i class="fas fa-trash-alt"></i> Remove this photo (the website shows a default icon instead)
                        </label>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_faculty ? 'Update Faculty' : 'Add Faculty'; ?>
                </button>
                <?php if ($edit_faculty): ?>
                    <a href="manage_faculty.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Faculty List -->
        <div class="card" style="overflow-x: auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Faculty Members</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">Photo</th>
                        <th style="padding: 15px; text-align: left;">Name</th>
                        <th style="padding: 15px; text-align: left;">Designation</th>
                        <th style="padding: 15px; text-align: left;">Subjects</th>
                        <th style="padding: 15px; text-align: left;">Qualification</th>
                        <th style="padding: 15px; text-align: left;">Experience</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($faculty && mysqli_num_rows($faculty) > 0) {
                        while ($member = mysqli_fetch_assoc($faculty)) {
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 10px 15px;">' . (!empty($member['photo'])
                                ? '<img src="../' . htmlspecialchars($member['photo']) . '" alt="" class="faculty-photo-thumb">'
                                : '<span class="faculty-photo-thumb is-empty"><i class="fas fa-user"></i></span>') . '</td>';
                            echo '<td style="padding: 15px;"><strong>' . htmlspecialchars($member['name']) . '</strong></td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($member['designation']) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($member['subjects']) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($member['qualification']) . '</td>';
                            echo '<td style="padding: 15px;">' . $member['experience'] . ' years</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . ($member['status'] == 'active' ? '#28a745' : '#dc3545') . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($member['status']) . '</span></td>';
                            echo '<td style="padding: 15px;">';
                            echo '<a href="?edit=' . $member['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>';
                            echo '<a href="?action=delete&id=' . $member['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Delete this faculty member?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" style="padding: 30px; text-align: center; color: var(--text-light);">No faculty members yet</td></tr>';
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
