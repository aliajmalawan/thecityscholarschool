<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Add new review
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_review'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $passing_year = intval($_POST['passing_year']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    $rating = intval($_POST['rating']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "INSERT INTO alumni_reviews (name, passing_year, review, rating, status)
            VALUES ('$name', $passing_year, '$review', $rating, '$status')";

    if (mysqli_query($conn, $sql)) {
        $message = 'Review added successfully!';
    } else {
        $error = 'Error: ' . mysqli_error($conn);
    }
}

// Approve/Reject/Delete reviews
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        mysqli_query($conn, "UPDATE alumni_reviews SET status = 'approved' WHERE id = $id");
        $message = 'Review approved!';
    } elseif ($action == 'reject') {
        mysqli_query($conn, "UPDATE alumni_reviews SET status = 'rejected' WHERE id = $id");
        $message = 'Review rejected!';
    } elseif ($action == 'delete') {
        mysqli_query($conn, "DELETE FROM alumni_reviews WHERE id = $id");
        $message = 'Review deleted!';
    }
}

// Fetch all reviews
$reviews = mysqli_query($conn, "SELECT * FROM alumni_reviews ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Alumni Reviews - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_alumni_reviews.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;">Manage Alumni Reviews</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: #28a745; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #dc3545; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Add Review Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Add New Review</h2>
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Name *</label>
                        <input type="text" name="name" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Passing Year</label>
                        <input type="number" name="passing_year" min="1990" max="2030" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Rating *</label>
                        <select name="rating" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                            <option value="5">★★★★★ (5 Stars)</option>
                            <option value="4">★★★★☆ (4 Stars)</option>
                            <option value="3">★★★☆☆ (3 Stars)</option>
                            <option value="2">★★☆☆☆ (2 Stars)</option>
                            <option value="1">★☆☆☆☆ (1 Star)</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Review *</label>
                        <textarea name="review" required rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Status *</label>
                        <select name="status" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; height: fit-content;">
                            <option value="approved">Approved</option>
                            <option value="pending">Pending</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <button type="submit" name="add_review" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Review
                </button>
            </form>
        </div>

        <!-- Reviews List -->
        <div class="card" style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">Name</th>
                        <th style="padding: 15px; text-align: left;">Passing Year</th>
                        <th style="padding: 15px; text-align: left;">Rating</th>
                        <th style="padding: 15px; text-align: left;">Review</th>
                        <th style="padding: 15px; text-align: left;">Date</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($reviews && mysqli_num_rows($reviews) > 0) {
                        while ($review = mysqli_fetch_assoc($reviews)) {
                            $status_color = $review['status'] == 'approved' ? '#28a745' : ($review['status'] == 'pending' ? '#ffc107' : '#dc3545');
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($review['name']) . '</td>';
                            echo '<td style="padding: 15px;">' . ($review['passing_year'] ?: 'N/A') . '</td>';
                            echo '<td style="padding: 15px;">';
                            for ($i = 0; $i < $review['rating']; $i++) {
                                echo '<i class="fas fa-star" style="color: #F9C900;"></i>';
                            }
                            echo '</td>';
                            echo '<td style="padding: 15px;">' . htmlspecialchars(substr($review['review'], 0, 100)) . '...</td>';
                            echo '<td style="padding: 15px;">' . date('M d, Y', strtotime($review['created_at'])) . '</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . $status_color . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($review['status']) . '</span></td>';
                            echo '<td style="padding: 15px;">';
                            if ($review['status'] == 'pending') {
                                echo '<a href="?action=approve&id=' . $review['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #28a745;"><i class="fas fa-check"></i></a> ';
                                echo '<a href="?action=reject&id=' . $review['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;"><i class="fas fa-times"></i></a> ';
                            }
                            echo '<a href="?action=delete&id=' . $review['id'] . '" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #6c757d;" onclick="return confirm(\'Delete?\')"><i class="fas fa-trash"></i></a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" style="padding: 30px; text-align: center; color: var(--text-light);">No reviews yet</td></tr>';
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
