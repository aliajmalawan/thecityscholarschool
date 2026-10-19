<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Mark as read
if (isset($_GET['action']) && $_GET['action'] == 'mark_read' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($conn, "UPDATE contacts SET status = 'read' WHERE id = $id");
    header('Location: manage_contacts.php');
    exit;
}

// Fetch all contacts
$contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Contacts - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_contacts.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;">Manage Contact Messages</h1>
            </div>
        </div>

        <div class="card" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">ID</th>
                        <th style="padding: 15px; text-align: left;">Name</th>
                        <th style="padding: 15px; text-align: left;">Email</th>
                        <th style="padding: 15px; text-align: left;">Phone</th>
                        <th style="padding: 15px; text-align: left;">Subject</th>
                        <th style="padding: 15px; text-align: left;">Message</th>
                        <th style="padding: 15px; text-align: left;">Date</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($contacts && mysqli_num_rows($contacts) > 0) {
                        while ($contact = mysqli_fetch_assoc($contacts)) {
                            $status_color = $contact['status'] == 'unread' ? '#dc3545' : '#28a745';
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px;">#' . $contact['id'] . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($contact['name']) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($contact['email']) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($contact['phone']) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($contact['subject']) . '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars(substr($contact['message'], 0, 50)) . '...</td>';
                            echo '<td style="padding: 15px;">' . date('M d, Y', strtotime($contact['created_at'])) . '</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . $status_color . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($contact['status']) . '</span></td>';
                            echo '<td style="padding: 15px;">';
                            if ($contact['status'] == 'unread') {
                                echo '<a href="?action=mark_read&id=' . $contact['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px;">Mark Read</a>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="9" style="padding: 30px; text-align: center; color: var(--text-light);">No contact messages yet</td></tr>';
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
