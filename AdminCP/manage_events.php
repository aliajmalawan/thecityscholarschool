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
    $img_query = mysqli_query($conn, "SELECT image FROM events WHERE id = $id");
    if ($img_row = mysqli_fetch_assoc($img_query)) {
        if (!empty($img_row['image']) && file_exists('../' . $img_row['image'])) {
            unlink('../' . $img_row['image']);
        }
    }

    if (mysqli_query($conn, "DELETE FROM events WHERE id = $id")) {
        $message = "Event deleted successfully!";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle image upload
    $image_path = '';
    $image_savings = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = '../uploads/events/';

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
                $image_path = 'uploads/events/' . $new_filename;
                $image_savings = describeCompressionSavings($_FILES['image']['tmp_name'], $target_file);
            }
        }
    }

    if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
        // Update
        $id = intval($_POST['event_id']);

        if ($image_path) {
            // Delete old image
            $old_img_query = mysqli_query($conn, "SELECT image FROM events WHERE id = $id");
            if ($old_img_row = mysqli_fetch_assoc($old_img_query)) {
                if (!empty($old_img_row['image']) && file_exists('../' . $old_img_row['image'])) {
                    unlink('../' . $old_img_row['image']);
                }
            }
            $query = "UPDATE events SET title='$title', description='$description', event_date='$event_date', time='$time', location='$location', image='$image_path', status='$status' WHERE id=$id";
        } else {
            $query = "UPDATE events SET title='$title', description='$description', event_date='$event_date', time='$time', location='$location', status='$status' WHERE id=$id";
        }
    } else {
        // Insert
        $query = "INSERT INTO events (title, description, event_date, time, location, image, status) VALUES ('$title', '$description', '$event_date', '$time', '$location', '$image_path', '$status')";
    }

    if (mysqli_query($conn, $query)) {
        $message = "Event saved successfully!" . ($image_savings ? " Image compressed{$image_savings}." : "");
    } else {
        $message = "Error saving event.";
    }
}

// Fetch event for editing
$edit_event = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM events WHERE id = $edit_id");
    $edit_event = mysqli_fetch_assoc($result);
}

// Fetch all events
$events = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Events - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_events.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-calendar-alt"></i> Manage Events</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_event ? 'Edit Event' : 'Add New Event'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($edit_event): ?>
                    <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Event Title *</label>
                    <input type="text" name="title" required value="<?php echo $edit_event ? htmlspecialchars($edit_event['title']) : ''; ?>" placeholder="e.g., Annual Sports Day 2024">
                </div>

                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" required rows="4" placeholder="Describe the event in detail..."><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" required value="<?php echo $edit_event ? $edit_event['event_date'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Time</label>
                        <input type="text" name="time" value="<?php echo $edit_event ? htmlspecialchars($edit_event['time']) : ''; ?>" placeholder="e.g., 9:00 AM - 5:00 PM">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo ($edit_event && $edit_event['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_event && $edit_event['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" value="<?php echo $edit_event ? htmlspecialchars($edit_event['location']) : ''; ?>" placeholder="e.g., School Main Ground">
                </div>

                <div class="form-group">
                    <label>Event Image</label>
                    <input type="file" name="image" accept="image/*">
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Images are automatically compressed on upload.</small>
                    <?php if ($edit_event && !empty($edit_event['image'])): ?>
                        <div style="margin-top: 10px;">
                            <img src="../<?php echo htmlspecialchars($edit_event['image']); ?>" alt="Current Image" style="max-width: 200px; border-radius: 8px; box-shadow: var(--shadow);">
                            <p style="margin-top: 5px; font-size: 13px; color: var(--text-light);">Current image (upload new to replace)</p>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_event ? 'Update Event' : 'Add Event'; ?>
                </button>
                <?php if ($edit_event): ?>
                    <a href="manage_events.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Events List -->
        <div class="card" style="overflow-x: auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Events</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">ID</th>
                        <th style="padding: 15px; text-align: left;">Image</th>
                        <th style="padding: 15px; text-align: left;">Event Title</th>
                        <th style="padding: 15px; text-align: left;">Date</th>
                        <th style="padding: 15px; text-align: left;">Time</th>
                        <th style="padding: 15px; text-align: left;">Location</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($events && mysqli_num_rows($events) > 0) {
                        while ($event = mysqli_fetch_assoc($events)) {
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px;">#' . $event['id'] . '</td>';
                            echo '<td style="padding: 15px;">';
                            if (!empty($event['image'])) {
                                echo '<img src="../' . htmlspecialchars($event['image']) . '" alt="Event" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">';
                            } else {
                                echo '<div style="width: 60px; height: 60px; background: var(--bg-light); border-radius: 8px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-image" style="color: var(--text-light);"></i></div>';
                            }
                            echo '</td>';
                            echo '<td style="padding: 15px;"><strong>' . htmlspecialchars($event['title']) . '</strong></td>';
                            echo '<td style="padding: 15px;">' . date('M d, Y', strtotime($event['event_date'])) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($event['time']) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($event['location']) . '</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . ($event['status'] == 'active' ? '#28a745' : '#dc3545') . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($event['status']) . '</span></td>';
                            echo '<td style="padding: 15px;">';
                            echo '<a href="?edit=' . $event['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>';
                            echo '<a href="?action=delete&id=' . $event['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Delete this event?\')">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" style="padding: 30px; text-align: center; color: var(--text-light);">No events yet</td></tr>';
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
