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

/** Delete an alumnus' photo file (only files inside uploads/alumni/ are ever touched). */
function removeAlumniPhotoFile($path) {
    if ($path && strpos($path, 'uploads/alumni/') === 0 && is_file('../' . $path)) {
        unlink('../' . $path);
    }
}

// Add a new alumnus or update an existing one (same form; each person has their own photo)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_alumni']) || isset($_POST['update_alumni']))) {
    $alumni_id = isset($_POST['update_alumni']) ? intval($_POST['alumni_id'] ?? 0) : 0;
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $father_name = mysqli_real_escape_string($conn, $_POST['father_name']);
    $current_job = mysqli_real_escape_string($conn, $_POST['current_job']);
    $job_department = mysqli_real_escape_string($conn, $_POST['job_department']);
    $job_city = mysqli_real_escape_string($conn, $_POST['job_city']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $passing_year = intval($_POST['passing_year']);
    $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $whatsapp_number = mysqli_real_escape_string($conn, $_POST['whatsapp_number']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    $status = in_array($_POST['status'] ?? '', ['approved', 'pending', 'rejected'], true) ? $_POST['status'] : 'pending';

    // The photo this person currently has (when editing)
    $old_photo = '';
    if ($alumni_id) {
        $old_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM alumni WHERE id = $alumni_id LIMIT 1"));
        $old_photo = $old_row['photo'] ?? '';
    }
    $photo_path = $old_photo;
    $photo_savings = '';

    if (!empty($_POST['remove_photo'])) {
        $photo_path = '';                                   // back to the default picture
    }
    // A newly chosen photo: checked, resized and compressed, saved under a random name
    $saved = savePublicPhoto($_FILES['photo'] ?? null, '../uploads/alumni/', 'alumni_' . time() . '_' . substr(uniqid(), -5), 800, 85);
    if (!empty($saved['error'])) {
        $error = $saved['error'];
    } elseif (!empty($saved['name'])) {
        $photo_path = 'uploads/alumni/' . $saved['name'];
        $photo_savings = describeCompressionSavings($_FILES['photo']['tmp_name'], '../' . $photo_path);
    }

    if ($error === '') {
        $photo_sql = mysqli_real_escape_string($conn, $photo_path);
        if ($alumni_id) {
            $sql = "UPDATE alumni SET student_name='$student_name', father_name='$father_name', current_job='$current_job', job_department='$job_department',
                    job_city='$job_city', course='$course', passing_year=$passing_year, photo='$photo_sql', mobile_number='$mobile_number',
                    whatsapp_number='$whatsapp_number', review='$review', status='$status' WHERE id=$alumni_id";
            $done = 'Alumni updated successfully!';
        } else {
            $new_id = nextRowId('alumni');
            $sql = "INSERT INTO alumni (id, student_name, father_name, current_job, job_department, job_city, course, passing_year, photo, mobile_number, whatsapp_number, review, status)
                    VALUES ($new_id, '$student_name', '$father_name', '$current_job', '$job_department', '$job_city', '$course', $passing_year, '$photo_sql', '$mobile_number', '$whatsapp_number', '$review', '$status')";
            $done = 'Alumni added successfully!';
        }

        if (mysqli_query($conn, $sql)) {
            if ($alumni_id && $old_photo !== '' && $old_photo !== $photo_path) {
                removeAlumniPhotoFile($old_photo);          // the replaced photo no longer needs its file
            }
            $message = $done . ($photo_savings ? " Photo compressed{$photo_savings}." : '');
        } else {
            $error = 'Error: ' . mysqli_error($conn);
            if ($photo_path !== $old_photo) {
                removeAlumniPhotoFile($photo_path);         // the save failed, so drop the file we just wrote
            }
        }
    }
}

// Approve/Reject/Delete alumni
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        mysqli_query($conn, "UPDATE alumni SET status = 'approved' WHERE id = $id");
        $message = 'Alumni approved!';
    } elseif ($action == 'reject') {
        mysqli_query($conn, "UPDATE alumni SET status = 'rejected' WHERE id = $id");
        $message = 'Alumni rejected!';
    } elseif ($action == 'delete') {
        $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM alumni WHERE id = $id LIMIT 1"));
        removeAlumniPhotoFile($row['photo'] ?? '');
        mysqli_query($conn, "DELETE FROM alumni WHERE id = $id");
        $message = 'Alumni deleted!';
    }
}

// Alumnus being edited
$edit = null;
if (isset($_GET['edit'])) {
    $edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM alumni WHERE id = " . intval($_GET['edit']) . " LIMIT 1"));
}
$field = function ($name, $default = '') use ($edit) {
    return htmlspecialchars($edit ? (string) ($edit[$name] ?? $default) : $default);
};
$default_avatar = content('alumni_default_photo');

// Counts per status for the summary cards (rows can be doubled in this DB, so count distinct ids)
$counts = ['all' => 0, 'approved' => 0, 'pending' => 0, 'rejected' => 0];
$cr = mysqli_query($conn, "SELECT status, COUNT(DISTINCT id) AS n FROM alumni GROUP BY status");
while ($cr && ($c = mysqli_fetch_assoc($cr))) {
    if (isset($counts[$c['status']])) { $counts[$c['status']] = (int) $c['n']; }
    $counts['all'] += (int) $c['n'];
}
$filter = isset($_GET['status']) && isset($counts[$_GET['status']]) ? $_GET['status'] : 'all';
$where = $filter === 'all' ? '' : "WHERE status = '" . $filter . "'";
$alumni = mysqli_query($conn, "SELECT DISTINCT * FROM alumni $where ORDER BY created_at DESC, id DESC");
$form_photo = $edit && !empty($edit['photo']) ? $edit['photo'] : $default_avatar;
$editing_id = $edit ? (int) $edit['id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Alumni - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_alumni.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
<div class="ad-wrap">

    <div class="ad-head">
        <div class="ad-head-left">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
            <div>
                <h1>Manage Alumni</h1>
                <p>Add alumni, give each one their own photo, and approve what appears on the Alumni page.</p>
            </div>
        </div>
        <a href="../alumni.php" target="_blank" rel="noopener" class="ad-btn ghost"><i class="fas fa-arrow-up-right-from-square"></i> View page</a>
    </div>

    <?php if ($message): ?>
        <div class="ad-alert ok"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="ad-alert bad"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="ad-stats">
        <a href="manage_alumni.php" class="ad-stat <?php echo $filter === 'all' ? 'is-active' : ''; ?>"><i class="fas fa-user-graduate"></i><div><b><?php echo $counts['all']; ?></b><span>All alumni</span></div></a>
        <a href="?status=approved" class="ad-stat good <?php echo $filter === 'approved' ? 'is-active' : ''; ?>"><i class="fas fa-circle-check"></i><div><b><?php echo $counts['approved']; ?></b><span>Approved (shown)</span></div></a>
        <a href="?status=pending" class="ad-stat warn <?php echo $filter === 'pending' ? 'is-active' : ''; ?>"><i class="fas fa-hourglass-half"></i><div><b><?php echo $counts['pending']; ?></b><span>Pending review</span></div></a>
        <a href="?status=rejected" class="ad-stat bad <?php echo $filter === 'rejected' ? 'is-active' : ''; ?>"><i class="fas fa-ban"></i><div><b><?php echo $counts['rejected']; ?></b><span>Rejected</span></div></a>
    </div>

    <!-- Add / Edit -->
    <div class="ad-panel" id="alumni-form">
        <div class="ad-panel-head">
            <h2><i class="fas <?php echo $edit ? 'fa-pen-to-square' : 'fa-user-plus'; ?>"></i> <?php echo $edit ? 'Edit: ' . $field('student_name') : 'Add new alumni'; ?></h2>
            <?php if ($edit): ?><a href="manage_alumni.php" class="ad-btn ghost"><i class="fas fa-xmark"></i> Cancel</a><?php endif; ?>
        </div>
        <div class="ad-panel-body">
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit): ?><input type="hidden" name="alumni_id" value="<?php echo $editing_id; ?>"><?php endif; ?>

                <div class="ad-form-section">Photo</div>
                <div class="ad-photo">
                    <img src="../<?php echo htmlspecialchars($form_photo); ?>" alt="Current photo" id="photoPreview">
                    <div class="ad-photo-body ad-field">
                        <label for="photo">This person's own photo (JPG, PNG or WebP)</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                        <?php if ($edit && !empty($edit['photo'])): ?>
                            <label class="ad-check"><input type="checkbox" name="remove_photo" value="1"> Remove this photo (show the default picture)</label>
                        <?php else: ?>
                            <small>Without a photo, the default picture is shown. Change it in Page Content &rarr; Alumni page.</small>
                        <?php endif; ?>
                        <small><i class="fas fa-compress-alt"></i> Resized and compressed automatically on upload.</small>
                    </div>
                </div>

                <div class="ad-form-section">Personal details</div>
                <div class="ad-grid c2">
                    <div class="ad-field"><label>Student name *</label><input type="text" name="student_name" required value="<?php echo $field('student_name'); ?>"></div>
                    <div class="ad-field"><label>Father name *</label><input type="text" name="father_name" required value="<?php echo $field('father_name'); ?>"></div>
                    <div class="ad-field"><label>Class / course *</label><input type="text" name="course" required value="<?php echo $field('course'); ?>" placeholder="e.g. Matric"></div>
                    <div class="ad-field"><label>Passing year *</label><input type="number" name="passing_year" required min="1990" max="2035" value="<?php echo $field('passing_year'); ?>"></div>
                </div>

                <div class="ad-form-section">Career</div>
                <div class="ad-grid c3">
                    <div class="ad-field"><label>Current job</label><input type="text" name="current_job" value="<?php echo $field('current_job'); ?>"></div>
                    <div class="ad-field"><label>Department</label><input type="text" name="job_department" value="<?php echo $field('job_department'); ?>"></div>
                    <div class="ad-field"><label>City</label><input type="text" name="job_city" value="<?php echo $field('job_city'); ?>"></div>
                </div>

                <div class="ad-form-section">Contact &amp; visibility</div>
                <div class="ad-grid c3">
                    <div class="ad-field"><label>Mobile number *</label><input type="text" name="mobile_number" required value="<?php echo $field('mobile_number'); ?>"></div>
                    <div class="ad-field"><label>WhatsApp number</label><input type="text" name="whatsapp_number" value="<?php echo $field('whatsapp_number'); ?>"></div>
                    <div class="ad-field"><label>Status *</label>
                        <select name="status" required>
                            <?php foreach (['approved' => 'Approved (visible on site)', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $val => $label): ?>
                                <option value="<?php echo $val; ?>" <?php echo ($edit ? $edit['status'] === $val : $val === 'approved') ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="ad-form-section">Review</div>
                <div class="ad-grid">
                    <div class="ad-field"><label>Review / message *</label><textarea name="review" required rows="3"><?php echo $field('review'); ?></textarea></div>
                </div>

                <div class="ad-form-actions">
                    <?php if ($edit): ?>
                        <button type="submit" name="update_alumni" class="ad-btn primary"><i class="fas fa-floppy-disk"></i> Save changes</button>
                        <a href="manage_alumni.php" class="ad-btn ghost">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_alumni" class="ad-btn primary"><i class="fas fa-plus"></i> Add alumni</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="ad-panel">
        <div class="ad-panel-head">
            <h2><i class="fas fa-list"></i> Alumni list</h2>
            <div class="ad-tabs">
                <?php foreach (['all' => 'All', 'approved' => 'Approved', 'pending' => 'Pending', 'rejected' => 'Rejected'] as $key => $label): ?>
                    <a href="<?php echo $key === 'all' ? 'manage_alumni.php' : '?status=' . $key; ?>" class="<?php echo $filter === $key ? 'is-active' : ''; ?>"><?php echo $label; ?> (<?php echo $counts[$key]; ?>)</a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="ad-table-wrap">
            <table class="ad-table">
                <thead>
                    <tr><th>Alumnus</th><th>Class</th><th>Career</th><th>Contact</th><th>Status</th><th style="text-align:right;">Actions</th></tr>
                </thead>
                <tbody>
                <?php if ($alumni && mysqli_num_rows($alumni) > 0): while ($alum = mysqli_fetch_assoc($alumni)):
                    $aid = (int) $alum['id'];
                    $row_photo = !empty($alum['photo']) ? $alum['photo'] : $default_avatar;
                    $st = in_array($alum['status'], ['approved', 'pending', 'rejected'], true) ? $alum['status'] : 'pending';
                    $career_sub = trim(($alum['job_department'] ?? '') . ' ' . (!empty($alum['job_city']) ? '· ' . $alum['job_city'] : ''));
                ?>
                    <tr class="<?php echo $aid === $editing_id ? 'is-editing' : ''; ?>">
                        <td>
                            <div class="ad-person">
                                <a href="?edit=<?php echo $aid; ?>#alumni-form" title="Change photo"><img src="../<?php echo htmlspecialchars($row_photo); ?>" alt=""></a>
                                <div>
                                    <strong><?php echo htmlspecialchars($alum['student_name']); ?></strong>
                                    <span class="muted">S/O <?php echo htmlspecialchars($alum['father_name']); ?></span>
                                    <?php if (!empty($alum['review'])): ?><div class="ad-quote"><?php echo htmlspecialchars($alum['review']); ?></div><?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($alum['course']); ?><br><span class="muted"><?php echo intval($alum['passing_year']); ?></span></td>
                        <td><?php echo htmlspecialchars($alum['current_job'] ?: '-'); ?><br><span class="muted"><?php echo htmlspecialchars($career_sub); ?></span></td>
                        <td><?php echo htmlspecialchars($alum['mobile_number']); ?><?php if (!empty($alum['whatsapp_number']) && $alum['whatsapp_number'] !== $alum['mobile_number']): ?><br><span class="muted">WhatsApp: <?php echo htmlspecialchars($alum['whatsapp_number']); ?></span><?php endif; ?></td>
                        <td><span class="ad-pill <?php echo $st; ?>"><?php echo ucfirst($st); ?></span></td>
                        <td>
                            <div class="ad-actions">
                                <a href="?edit=<?php echo $aid; ?>#alumni-form" class="ad-icon-btn" title="Edit / change photo"><i class="fas fa-pen"></i> Edit</a>
                                <?php if ($st !== 'approved'): ?><a href="?action=approve&id=<?php echo $aid; ?>" class="ad-icon-btn ok" title="Approve"><i class="fas fa-check"></i> Approve</a><?php endif; ?>
                                <?php if ($st !== 'rejected'): ?><a href="?action=reject&id=<?php echo $aid; ?>" class="ad-icon-btn no" title="Reject"><i class="fas fa-ban"></i> Reject</a><?php endif; ?>
                                <a href="?action=delete&id=<?php echo $aid; ?>" class="ad-icon-btn del" title="Delete" onclick="return confirm('Delete this alumni?')"><i class="fas fa-trash"></i> Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6"><div class="ad-empty"><i class="fas fa-user-graduate"></i>No alumni found here yet.</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
<script>
// Show the chosen photo in the preview circle before saving
document.getElementById('photo').addEventListener('change', function () {
    if (this.files && this.files[0]) { document.getElementById('photoPreview').src = URL.createObjectURL(this.files[0]); }
});
</script>
</body>
</html>
