<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Every card section this page can manage, with a friendly label and
// whether it uses an icon or a plain step number, and whether the
// description is a single paragraph or a one-item-per-line checklist.
$sections = [
    'why_choose_us' => ['label' => 'Why Choose Us (Homepage)', 'mode' => 'icon', 'desc_mode' => 'paragraph'],
    'digital_features' => ['label' => 'Digital School Features (Homepage)', 'mode' => 'icon', 'desc_mode' => 'paragraph'],
    'learner_attributes' => ['label' => 'Learner Attributes (Homepage)', 'mode' => 'icon', 'desc_mode' => 'paragraph'],
    'assessment_features' => ['label' => 'Assessment Highlights (Homepage)', 'mode' => 'icon', 'desc_mode' => 'paragraph'],
    'facilities' => ['label' => 'Facilities (Homepage)', 'mode' => 'icon', 'desc_mode' => 'paragraph'],
    'core_values' => ['label' => 'Core Values (About Page)', 'mode' => 'icon', 'desc_mode' => 'paragraph'],
    'faculty_highlights' => ['label' => 'Why Our Faculty Stands Out (Faculty Page)', 'mode' => 'icon', 'desc_mode' => 'paragraph'],
    'admission_requirements' => ['label' => 'Admission Requirements (Courses Page)', 'mode' => 'icon', 'desc_mode' => 'checklist'],
    'admission_process' => ['label' => 'Admission Process Steps (Admission Page)', 'mode' => 'number', 'desc_mode' => 'paragraph'],
];

$active_section = isset($_GET['section']) && isset($sections[$_GET['section']]) ? $_GET['section'] : 'why_choose_us';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (mysqli_query($conn, "DELETE FROM feature_cards WHERE id = $id")) {
        $message = "Card deleted successfully!";
    }
}

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $section = mysqli_real_escape_string($conn, $_POST['section']);
    $icon = mysqli_real_escape_string($conn, trim($_POST['icon']));
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $card_id = isset($_POST['card_id']) && !empty($_POST['card_id']) ? intval($_POST['card_id']) : null;

    if ($card_id) {
        $query = "UPDATE feature_cards SET section='$section', icon='$icon', title='$title', description='$description', display_order=$display_order, status='$status' WHERE id=$card_id";
    } else {
        $query = "INSERT INTO feature_cards (section, icon, title, description, display_order, status) VALUES ('$section', '$icon', '$title', '$description', $display_order, '$status')";
    }

    if (mysqli_query($conn, $query)) {
        $message = "Card saved successfully!";
    } else {
        $message = "Error saving card.";
    }

    $active_section = $_POST['section'];
}

// Fetch card for editing
$edit_card = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM feature_cards WHERE id = $edit_id");
    $edit_card = mysqli_fetch_assoc($result);
    if ($edit_card) {
        $active_section = $edit_card['section'];
    }
}

$section_info = $sections[$active_section];

// Fetch cards for the active section
$section_escaped = mysqli_real_escape_string($conn, $active_section);
$cards = mysqli_query($conn, "SELECT * FROM feature_cards WHERE section = '$section_escaped' ORDER BY display_order ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Manage Feature Cards - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        .section-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 25px; }
        .section-tabs a {
            padding: 10px 16px; border-radius: var(--radius-sm); font-size: 13px; font-weight: 600;
            background: white; border: 1px solid var(--border-color); color: var(--text-dark); text-decoration: none;
        }
        .section-tabs a.active { background: var(--primary-color); color: white; border-color: var(--primary-color); }
    </style>
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_feature_cards.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-th-large"></i> Manage Feature Cards</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Section Tabs -->
        <div class="section-tabs">
            <?php foreach ($sections as $key => $info): ?>
                <a href="?section=<?php echo $key; ?>" class="<?php echo $active_section === $key ? 'active' : ''; ?>"><?php echo htmlspecialchars($info['label']); ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Add/Edit Form -->
        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $edit_card ? 'Edit Card' : 'Add New Card'; ?> - <?php echo htmlspecialchars($section_info['label']); ?></h2>
            <form method="POST">
                <input type="hidden" name="section" value="<?php echo htmlspecialchars($active_section); ?>">
                <?php if ($edit_card): ?>
                    <input type="hidden" name="card_id" value="<?php echo $edit_card['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" required value="<?php echo $edit_card ? htmlspecialchars($edit_card['title']) : ''; ?>" placeholder="<?php echo $section_info['mode'] === 'number' ? 'e.g., Fill Application' : 'e.g., Expert Faculty'; ?>">
                </div>

                <?php if ($section_info['mode'] === 'icon'): ?>
                <div class="form-group">
                    <label>Icon (Font Awesome class) *</label>
                    <input type="text" name="icon" required value="<?php echo $edit_card ? htmlspecialchars($edit_card['icon']) : ''; ?>" placeholder="e.g., fas fa-chalkboard-teacher">
                    <small style="color: var(--text-light); font-size: 12px;">Find icons at fontawesome.com - include the "fas"/"fab" prefix, e.g. "fas fa-star"</small>
                </div>
                <?php else: ?>
                    <input type="hidden" name="icon" value="">
                <?php endif; ?>

                <div class="form-group">
                    <label><?php echo $section_info['desc_mode'] === 'checklist' ? 'Checklist Items (one per line) *' : 'Description *'; ?></label>
                    <textarea name="description" required rows="<?php echo $section_info['desc_mode'] === 'checklist' ? 5 : 3; ?>" placeholder="<?php echo $section_info['desc_mode'] === 'checklist' ? "Previous class result card\nBirth certificate or B-Form\n..." : 'Short description shown on the card'; ?>"><?php echo $edit_card ? htmlspecialchars($edit_card['description']) : ''; ?></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label><?php echo $section_info['mode'] === 'number' ? 'Step Number (Display Order) *' : 'Display Order'; ?></label>
                        <input type="number" name="display_order" value="<?php echo $edit_card ? intval($edit_card['display_order']) : 0; ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo (!$edit_card || $edit_card['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_card && $edit_card['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $edit_card ? 'Update Card' : 'Add Card'; ?>
                </button>
                <?php if ($edit_card): ?>
                    <a href="manage_feature_cards.php?section=<?php echo $active_section; ?>" class="btn btn-primary" style="background: var(--text-light); margin-left: 10px;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Cards List -->
        <div class="card">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Cards in "<?php echo htmlspecialchars($section_info['label']); ?>"</h2>
            <?php if ($cards && mysqli_num_rows($cards) > 0): ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--primary-color); color: white;">
                            <th style="padding: 12px; text-align: left;">Order</th>
                            <?php if ($section_info['mode'] === 'icon'): ?><th style="padding: 12px; text-align: left;">Icon</th><?php endif; ?>
                            <th style="padding: 12px; text-align: left;">Title</th>
                            <th style="padding: 12px; text-align: left;">Description</th>
                            <th style="padding: 12px; text-align: left;">Status</th>
                            <th style="padding: 12px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($card = mysqli_fetch_assoc($cards)): ?>
                            <tr style="border-bottom: 1px solid #e0e0e0;">
                                <td style="padding: 12px;"><?php echo intval($card['display_order']); ?></td>
                                <?php if ($section_info['mode'] === 'icon'): ?>
                                    <td style="padding: 12px;"><i class="<?php echo htmlspecialchars($card['icon']); ?>" style="color: var(--primary-color); font-size: 18px;"></i></td>
                                <?php endif; ?>
                                <td style="padding: 12px;"><strong><?php echo htmlspecialchars($card['title']); ?></strong></td>
                                <td style="padding: 12px; max-width: 320px; font-size: 13px; color: var(--text-light);"><?php echo htmlspecialchars(mb_strimwidth(str_replace("\n", ' / ', $card['description']), 0, 100, '...')); ?></td>
                                <td style="padding: 12px;"><span style="background: <?php echo $card['status'] == 'active' ? '#28a745' : '#dc3545'; ?>; color: white; padding: 4px 10px; border-radius: 20px; font-size: 11px;"><?php echo ucfirst($card['status']); ?></span></td>
                                <td style="padding: 12px;">
                                    <a href="?section=<?php echo $active_section; ?>&edit=<?php echo $card['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; margin-right: 5px;">Edit</a>
                                    <a href="?section=<?php echo $active_section; ?>&action=delete&id=<?php echo $card['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 12px; background: #dc3545;" onclick="return confirm('Delete this card?')">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; padding: 30px; color: var(--text-light);">No cards yet in this section.</p>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
