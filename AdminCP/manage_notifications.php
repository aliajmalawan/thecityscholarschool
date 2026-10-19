<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Add notification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_notification'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $link = mysqli_real_escape_string($conn, $_POST['link']);
    $display_order = intval($_POST['display_order']);

    $sql = "INSERT INTO notifications (title, link, display_order) VALUES ('$title', '$link', $display_order)";
    if (mysqli_query($conn, $sql)) {
        $message = 'Notification added successfully!';
    }
}

// Delete notification
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM notifications WHERE id = $id");
    $message = 'Notification deleted!';
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    mysqli_query($conn, "UPDATE notifications SET status = IF(status='active','inactive','active') WHERE id = $id");
    $message = 'Status updated!';
}

// Fetch all notifications
$notifications = mysqli_query($conn, "SELECT * FROM notifications ORDER BY display_order ASC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Notifications - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_notifications.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;">Manage Notifications</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #28a745; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Add Notification Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Add New Notification</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 3fr 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Notification Title:</label>
                        <input type="text" name="title" required placeholder="e.g., Admissions Open for Session 2025" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Link (Optional):</label>
                        <input type="text" name="link" placeholder="admission.php" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Display Order:</label>
                        <input type="number" name="display_order" value="0" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                </div>

                <button type="submit" name="add_notification" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Notification
                </button>
            </form>
        </div>

        <!-- Notifications List -->
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Notifications</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">Title</th>
                        <th style="padding: 15px; text-align: left;">Link</th>
                        <th style="padding: 15px; text-align: left;">Order</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Created</th>
                        <th style="padding: 15px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($notifications && mysqli_num_rows($notifications) > 0) {
                        while ($notif = mysqli_fetch_assoc($notifications)) {
                            $status_color = $notif['status'] == 'active' ? '#28a745' : '#dc3545';
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($notif['title']) . '</td>';
                            echo '<td style="padding: 15px;">' . ($notif['link'] ? htmlspecialchars($notif['link']) : '-') . '</td>';
                            echo '<td style="padding: 15px;">' . $notif['display_order'] . '</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . $status_color . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($notif['status']) . '</span></td>';
                            echo '<td style="padding: 15px;">' . date('M d, Y', strtotime($notif['created_at'])) . '</td>';
                            echo '<td style="padding: 15px;">';
                            echo '<a href="?toggle=' . $notif['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;"><i class="fas fa-toggle-on"></i></a>';
                            echo '<a href="?delete=' . $notif['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm(\'Delete?\')"><i class="fas fa-trash"></i></a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" style="padding: 30px; text-align: center; color: var(--text-light);">No notifications yet</td></tr>';
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
