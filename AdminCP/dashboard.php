<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Fetch comprehensive statistics
$stats = [
    'total_courses' => 0,
    'total_faculty' => 0,
    'pending_admissions' => 0,
    'unread_contacts' => 0,
    'total_events' => 0,
    'total_news' => 0,
    'gallery_images' => 0,
    'approved_admissions' => 0
];

$courses_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
if ($courses_count) {
    $stats['total_courses'] = mysqli_fetch_assoc($courses_count)['count'];
}

$faculty_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM faculty WHERE status = 'active'");
if ($faculty_count) {
    $stats['total_faculty'] = mysqli_fetch_assoc($faculty_count)['count'];
}

$admissions_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM admissions WHERE status = 'pending'");
if ($admissions_count) {
    $stats['pending_admissions'] = mysqli_fetch_assoc($admissions_count)['count'];
}

$approved_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM admissions WHERE status = 'approved'");
if ($approved_count) {
    $stats['approved_admissions'] = mysqli_fetch_assoc($approved_count)['count'];
}

$contacts_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM contacts WHERE status = 'unread'");
if ($contacts_count) {
    $stats['unread_contacts'] = mysqli_fetch_assoc($contacts_count)['count'];
}

$events_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM events WHERE status = 'active'");
if ($events_count) {
    $stats['total_events'] = mysqli_fetch_assoc($events_count)['count'];
}

$news_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM news WHERE status = 'active'");
if ($news_count) {
    $stats['total_news'] = mysqli_fetch_assoc($news_count)['count'];
}

$gallery_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM gallery WHERE status = 'active'");
if ($gallery_count) {
    $stats['gallery_images'] = mysqli_fetch_assoc($gallery_count)['count'];
}

// Get recent admissions
$recent_admissions = mysqli_query($conn, "SELECT * FROM admissions ORDER BY created_at DESC LIMIT 5");

// Get recent contacts
$recent_contacts = mysqli_query($conn, "SELECT * FROM contacts ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Admin Dashboard - Tameer-e-Nau Public School</title>
    <link rel="icon" type="image/png" href="../<?php echo htmlspecialchars(getLogoPath()); ?>">
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .welcome-text h1 {
            color: #78864F;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .welcome-text p {
            color: #666;
            font-size: 14px;
        }

        .top-bar-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .notification-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f7fa;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .notification-btn:hover {
            background: #78864F;
            color: white;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #f44336;
            color: white;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 240px), 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-dark));
        }

        .stat-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-info h3 {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: white;
            background: linear-gradient(135deg, var(--card-color), var(--card-color-dark));
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f5f7fa;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .view-all-btn {
            color: #78864F;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            color: #B3262D;
        }

        /* Table */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table thead tr {
            background: #f5f7fa;
        }

        .data-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            color: #333;
        }

        .data-table tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-unread {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-approved {
            background: #d4edda;
            color: #155724;
        }

        .btn-view {
            padding: 6px 16px;
            background: #78864F;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-view:hover {
            background: #5F6D3D;
        }

        /* Quick Actions */
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 200px), 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            padding: 30px 20px;
            border-radius: 15px;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: #78864F;
        }

        .action-card i {
            font-size: 40px;
            color: #78864F;
            margin-bottom: 15px;
        }

        .action-card h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .action-card p {
            color: #666;
            font-size: 13px;
        }

        /* Responsive (dashboard-specific widgets only - shell/sidebar
           responsive rules live in assets/admin.css) */
        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="admin-shell">
    <?php $active_page = 'dashboard.php'; include 'includes/sidebar.php'; ?>

    <!-- Backdrop for mobile sidebar -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</h1>
                    <p><?php echo date('l, F d, Y'); ?></p>
                </div>
            </div>

            <div class="top-bar-actions">
                <a href="manage_admissions.php" class="notification-btn" title="Pending Admissions" style="text-decoration: none; color: inherit;">
                    <i class="fas fa-user-graduate"></i>
                    <?php if ($stats['pending_admissions'] > 0): ?>
                        <span class="notification-badge"><?php echo $stats['pending_admissions']; ?></span>
                    <?php endif; ?>
                </a>

                <a href="manage_contacts.php" class="notification-btn" title="Unread Messages" style="text-decoration: none; color: inherit;">
                    <i class="fas fa-envelope"></i>
                    <?php if ($stats['unread_contacts'] > 0): ?>
                        <span class="notification-badge"><?php echo $stats['unread_contacts']; ?></span>
                    <?php endif; ?>
                </a>

                <a href="../index.php" target="_blank" class="btn-view" style="padding: 10px 20px;">
                    <i class="fas fa-external-link-alt"></i> View Website
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <a href="manage_courses.php" class="stat-card" style="--card-color: #4CAF50; --card-color-dark: #45a049;">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_courses']; ?></h3>
                        <p>Active Courses</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </a>

            <a href="manage_faculty.php" class="stat-card" style="--card-color: #2196F3; --card-color-dark: #1976D2;">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_faculty']; ?></h3>
                        <p>Faculty Members</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>
            </a>

            <a href="manage_admissions.php" class="stat-card" style="--card-color: #FF9800; --card-color-dark: #F57C00;">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo $stats['pending_admissions']; ?></h3>
                        <p>Pending Admissions</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </a>

            <a href="manage_admissions.php" class="stat-card" style="--card-color: #9C27B0; --card-color-dark: #7B1FA2;">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo $stats['approved_admissions']; ?></h3>
                        <p>Approved Students</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </a>

            <a href="manage_contacts.php" class="stat-card" style="--card-color: #E91E63; --card-color-dark: #C2185B;">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo $stats['unread_contacts']; ?></h3>
                        <p>Unread Messages</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
            </a>

            <a href="manage_events.php" class="stat-card" style="--card-color: #00BCD4; --card-color-dark: #0097A7;">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_events']; ?></h3>
                        <p>Active Events</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </a>

            <a href="manage_news.php" class="stat-card" style="--card-color: #FF5722; --card-color-dark: #E64A19;">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo $stats['total_news']; ?></h3>
                        <p>Published News</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                </div>
            </a>

            <a href="manage_gallery.php" class="stat-card" style="--card-color: #607D8B; --card-color-dark: #455A64;">
                <div class="stat-content">
                    <div class="stat-info">
                        <h3><?php echo $stats['gallery_images']; ?></h3>
                        <p>Gallery Images</p>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Admissions -->
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-graduate"></i> Recent Admissions</h2>
                    <a href="manage_admissions.php" class="view-all-btn">View All →</a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($recent_admissions && mysqli_num_rows($recent_admissions) > 0) {
                            while ($admission = mysqli_fetch_assoc($recent_admissions)) {
                                echo '<tr>';
                                echo '<td><strong>' . htmlspecialchars($admission['student_name']) . '</strong></td>';
                                echo '<td>' . htmlspecialchars($admission['course_id']) . '</td>';
                                echo '<td>' . date('M d, Y', strtotime($admission['created_at'])) . '</td>';
                                echo '<td><span class="badge badge-' . strtolower($admission['status']) . '">' . ucfirst($admission['status']) . '</span></td>';
                                echo '<td><a href="manage_admissions.php?view=' . $admission['id'] . '" class="btn-view">View</a></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="5" style="text-align: center; color: #999; padding: 40px;">No admission applications yet</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Contacts -->
            <div class="card-container">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-envelope"></i> Recent Messages</h2>
                    <a href="manage_contacts.php" class="view-all-btn">View All →</a>
                </div>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($recent_contacts && mysqli_num_rows($recent_contacts) > 0) {
                            while ($contact = mysqli_fetch_assoc($recent_contacts)) {
                                echo '<tr>';
                                echo '<td><strong>' . htmlspecialchars($contact['name']) . '</strong></td>';
                                echo '<td>' . htmlspecialchars(substr($contact['subject'], 0, 30)) . '...</td>';
                                echo '<td><span class="badge badge-' . strtolower($contact['status']) . '">' . ucfirst($contact['status']) . '</span></td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="3" style="text-align: center; color: #999; padding: 40px;">No messages yet</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card-container">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
            </div>

            <div class="quick-actions-grid">
                <a href="manage_courses.php" class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Add Course</h3>
                    <p>Create new course</p>
                </a>

                <a href="manage_faculty.php" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>Add Faculty</h3>
                    <p>Add new teacher</p>
                </a>

                <a href="manage_events.php" class="action-card">
                    <i class="fas fa-calendar-plus"></i>
                    <h3>Add Event</h3>
                    <p>Create new event</p>
                </a>

                <a href="manage_news.php" class="action-card">
                    <i class="fas fa-newspaper"></i>
                    <h3>Publish News</h3>
                    <p>Add announcement</p>
                </a>

                <a href="manage_gallery.php" class="action-card">
                    <i class="fas fa-image"></i>
                    <h3>Upload Image</h3>
                    <p>Add to gallery</p>
                </a>

                <a href="settings.php" class="action-card">
                    <i class="fas fa-cog"></i>
                    <h3>Settings</h3>
                    <p>Manage website</p>
                </a>
            </div>
        </div>
    </div>
    </div>

    <script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
