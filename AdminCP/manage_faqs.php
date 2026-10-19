<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (mysqli_query($conn, "DELETE FROM faqs WHERE id = $id")) {
        $message = "FAQ deleted successfully!";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question = mysqli_real_escape_string($conn, $_POST['question']);
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (isset($_POST['faq_id']) && !empty($_POST['faq_id'])) {
        $id = intval($_POST['faq_id']);
        $query = "UPDATE faqs SET question='$question', answer='$answer', display_order=$display_order, status='$status' WHERE id=$id";
    } else {
        $query = "INSERT INTO faqs (question, answer, display_order, status) VALUES ('$question', '$answer', $display_order, '$status')";
    }

    $message = mysqli_query($conn, $query) ? "FAQ saved successfully!" : "Error saving FAQ.";
}

$edit_faq = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM faqs WHERE id = $edit_id");
    $edit_faq = mysqli_fetch_assoc($result);
}

$faqs = mysqli_query($conn, "SELECT * FROM faqs ORDER BY display_order ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage FAQs - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_faqs.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-question-circle"></i> Manage FAQs</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <p style="color: var(--text-light); margin-bottom: 20px;">These appear in the FAQ accordion on the Contact page.</p>

        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_faq ? 'Edit FAQ' : 'Add New FAQ'; ?></h2>
            <form method="POST">
                <?php if ($edit_faq): ?>
                    <input type="hidden" name="faq_id" value="<?php echo $edit_faq['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Question *</label>
                    <input type="text" name="question" required value="<?php echo $edit_faq ? htmlspecialchars($edit_faq['question']) : ''; ?>" placeholder="e.g., What are the admission requirements?">
                </div>

                <div class="form-group">
                    <label>Answer *</label>
                    <textarea name="answer" required rows="4"><?php echo $edit_faq ? htmlspecialchars($edit_faq['answer']) : ''; ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" value="<?php echo $edit_faq ? intval($edit_faq['display_order']) : 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo (!$edit_faq || $edit_faq['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_faq && $edit_faq['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?php echo $edit_faq ? 'Update' : 'Add'; ?></button>
                <?php if ($edit_faq): ?>
                    <a href="manage_faqs.php" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All FAQs</h2>
            <?php if ($faqs && mysqli_num_rows($faqs) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--primary-color); color: white;">
                            <th style="padding: 12px; text-align: left;">Order</th>
                            <th style="padding: 12px; text-align: left;">Question</th>
                            <th style="padding: 12px; text-align: left;">Status</th>
                            <th style="padding: 12px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($faq = mysqli_fetch_assoc($faqs)): ?>
                            <tr style="border-bottom: 1px solid #e0e0e0;">
                                <td style="padding: 12px;"><?php echo intval($faq['display_order']); ?></td>
                                <td style="padding: 12px;"><strong><?php echo htmlspecialchars($faq['question']); ?></strong></td>
                                <td style="padding: 12px;"><span style="background: <?php echo $faq['status'] == 'active' ? '#28a745' : '#dc3545'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px;"><?php echo ucfirst($faq['status']); ?></span></td>
                                <td style="padding: 12px;">
                                    <a href="?edit=<?php echo $faq['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>
                                    <a href="?action=delete&id=<?php echo $faq['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm('Delete this FAQ?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 30px; color: var(--text-light);">No FAQs yet.</p>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
