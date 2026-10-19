<?php
/**
 * Page Content: edit the text, photos and logo of the public website.
 * Everything listed here comes from includes/content_fields.php; saved values go
 * to the settings table and the public pages read them through content().
 */
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['content_csrf'])) {
    $_SESSION['content_csrf'] = bin2hex(random_bytes(16));
}

$groups = contentRegistry();
$upload_notes = [];
$active_tab = isset($_GET['tab']) && isset($groups[$_GET['tab']]) ? $_GET['tab'] : 'global';
$message = '';
$message_type = 'success';

/** Save an uploaded photo for an image field; returns the stored relative path or null. */
function savePageImage($file, $key) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($info === false || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || $file['size'] > 8 * 1024 * 1024) {
        return false;
    }
    $dir = '../uploads/site/';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $base = preg_replace('/[^a-z0-9_]/i', '', $key) . '_' . time() . '_' . substr(uniqid(), -5);
    $max = (strpos($key, 'banner_') === 0 || $key === 'hero_image') ? 1920 : 1400;
    $name = compressUploadedPhotoAsJpeg($file['tmp_name'], $dir, $base, $max, 85);
    if ($name) {
        $GLOBALS['upload_notes'][] = describeCompressionSavings($file['tmp_name'], $dir . $name);
    }
    return $name ? 'uploads/site/' . $name : false;
}

/** Save an uploaded logo (PNG/JPG/WebP up to 8 MB, resized and compressed in its own format); returns the relative path or false/null. */
function saveLogoUpload($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($info === false || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) || $file['size'] > 8 * 1024 * 1024) {
        return false;
    }
    $dir = '../uploads/site/';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    // keeps the logo's own format (PNG transparency stays) but caps the size and re-encodes it
    $name = compressUploadedImageKeepFormat($file['tmp_name'], $dir, 'logo_' . time() . '_' . substr(uniqid(), -5), 800, 90);
    if ($name) {
        $GLOBALS['upload_notes'][] = describeCompressionSavings($file['tmp_name'], $dir . $name);
    }
    return $name ? 'uploads/site/' . $name : false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['content_csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $message = 'Your session expired. Please reload the page and try again.';
        $message_type = 'error';
    } else {
        $posted_tab = $_POST['tab'] ?? 'global';
        $active_tab = isset($groups[$posted_tab]) ? $posted_tab : 'global';
        $saved = 0;
        $problems = [];
        $max_len = ['text' => 300, 'url' => 300, 'textarea' => 4000, 'lines' => 4000];

        // Only the fields of the tab that was open are submitted, so only those are processed
        foreach ($groups[$active_tab]['fields'] as $field) {
            $key = $field['key'];
            $type = $field['type'];

            if ($type === 'logo') {
                if (!empty($_POST['reset__' . $key])) {
                    mysqli_query($conn, "UPDATE themes SET logo_path = '" . mysqli_real_escape_string($conn, $field['default']) . "' WHERE is_active = 1");
                    $saved++;
                } else {
                    $path = saveLogoUpload($_FILES['file__' . $key] ?? null);
                    if ($path === false) {
                        $problems[] = 'Logo: use a PNG, JPG or WebP image up to 8 MB.';
                    } elseif ($path !== null) {
                        mysqli_query($conn, "UPDATE themes SET logo_path = '" . mysqli_real_escape_string($conn, $path) . "' WHERE is_active = 1");
                        $saved++;
                    }
                }
                continue;
            }

            if ($type === 'image') {
                if (!empty($_POST['reset__' . $key])) {
                    deleteSetting($key);
                    $saved++;
                } else {
                    $path = savePageImage($_FILES['file__' . $key] ?? null, $key);
                    if ($path === false) {
                        $problems[] = $field['label'] . ': use a JPG, PNG or WebP photo up to 8 MB.';
                    } elseif ($path !== null) {
                        saveSetting($key, $path);
                        $saved++;
                    }
                }
                continue;
            }

            if (!array_key_exists('f__' . $key, $_POST)) {
                continue;
            }
            $value = str_replace(["\r\n", "\r"], "\n", (string) $_POST['f__' . $key]);
            $value = preg_replace('/[^\P{C}\n\t]+/u', '', $value) ?? '';
            $value = trim($value);
            if ($type === 'text' || $type === 'url') {
                $value = str_replace("\n", ' ', $value);
            }
            if ($type === 'url' && preg_match('/^\s*(javascript|data|vbscript):/i', $value)) {
                $problems[] = $field['label'] . ': that kind of link is not allowed.';
                continue;
            }
            $value = mb_substr($value, 0, $max_len[$type] ?? 4000);
            if (saveSetting($key, $value)) {
                $saved++;
            }
        }

        $message = $saved > 0 ? 'Saved. The website now shows your changes.' : 'Nothing to save.';
        $notes = implode('', array_filter($upload_notes));
        if ($notes !== '') {
            $message .= ' Photo compressed automatically' . $notes . '.';
        }
        if (!empty($problems)) {
            $message .= ' ' . implode(' ', $problems);
            $message_type = $saved > 0 ? 'success' : 'error';
        }
        $_SESSION['content_flash'] = [$message, $message_type];
        header('Location: manage_content.php?tab=' . urlencode($active_tab));
        exit;
    }
}

if (!empty($_SESSION['content_flash'])) {
    [$message, $message_type] = $_SESSION['content_flash'];
    unset($_SESSION['content_flash']);
}

$all_settings = loadSettings();
$current_logo = getLogoPath();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <meta name="robots" content="noindex, nofollow">
    <title>Page Content - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        .pc-layout { display: grid; grid-template-columns: 230px 1fr; gap: 24px; align-items: start; }
        .pc-tabs { background: #fff; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); padding: 8px; position: sticky; top: 16px; }
        .pc-tabs a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; color: var(--text-dark); font-weight: 600; font-size: 14px; }
        .pc-tabs a i { width: 18px; text-align: center; color: var(--primary-color); }
        .pc-tabs a:hover { background: var(--primary-soft); }
        .pc-tabs a.active { background: var(--primary-dark); color: #fff; }
        .pc-tabs a.active i { color: #fff; }
        .pc-panel { background: #fff; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); padding: 26px; }
        .pc-panel h2 { color: var(--primary-color); margin-bottom: 6px; }
        .pc-intro { color: var(--text-light); margin-bottom: 22px; font-size: 14.5px; }
        .pc-field { padding: 14px 0; border-top: 1px solid var(--border-color); }
        .pc-field:first-of-type { border-top: 0; }
        .pc-field label { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-weight: 600; margin-bottom: 6px; }
        .pc-badge { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; background: var(--primary-soft); color: var(--primary-dark); }
        .pc-badge.custom { background: #fdecec; color: var(--accent-dark); }
        .pc-hint { display: block; margin-top: 5px; font-size: 12.5px; color: var(--muted); }
        .pc-field input[type=text], .pc-field textarea { width: 100%; }
        .pc-image { display: flex; gap: 18px; align-items: flex-start; flex-wrap: wrap; }
        .pc-image img { width: 190px; max-height: 130px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border-color); background: var(--cream); }
        .pc-image.logo img { width: 110px; height: 110px; object-fit: contain; border-radius: 50%; }
        .pc-image .pc-controls { flex: 1; min-width: 220px; }
        .pc-reset { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 13px; color: var(--text-light); }
        .pc-savebar { position: sticky; bottom: 0; background: #fff; border-top: 1px solid var(--border-color); margin: 24px -26px -26px; padding: 14px 26px; border-radius: 0 0 10px 10px; display: flex; gap: 14px; align-items: center; flex-wrap: wrap; }
        .pc-note { background: var(--soft-sage); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px 18px; margin-bottom: 22px; font-size: 14px; color: var(--text); }
        .pc-note a { color: var(--primary-dark); font-weight: 700; text-decoration: underline; }
        @media (max-width: 900px) { .pc-layout { grid-template-columns: 1fr; } .pc-tabs { position: static; display: flex; flex-wrap: wrap; gap: 4px; } }
    </style>
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_content.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-pen-ruler"></i> Page Content</h1>
            </div>
            <?php
            // Open the page this tab edits (home tabs and site-wide settings open the home page)
            $tab_page = in_array($active_tab, ['about', 'courses', 'admission', 'faculty', 'campuses', 'gallery', 'events', 'examination', 'alumni', 'downloads', 'contact'], true) ? $active_tab : '';
            ?>
            <a href="../<?php echo $tab_page; ?>" target="_blank" class="btn btn-outline-dark" style="padding: 9px 20px;"><i class="fas fa-up-right-from-square"></i> <?php echo $tab_page !== '' ? 'View ' . htmlspecialchars($groups[$active_tab]['label']) : 'View website'; ?></a>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo $message_type === 'error' ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo $message_type === 'error' ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="pc-note">
            Edit the words and photos of every page here. Lists that come from a database table have their own screens:
            <a href="manage_feature_cards.php">Feature Cards</a> (Why Choose, Facilities, Core Values...),
            <a href="manage_gallery.php">Gallery</a>, <a href="manage_news.php">News</a>, <a href="manage_events.php">Events</a>,
            <a href="manage_faculty.php">Faculty</a>, <a href="manage_courses.php">Courses</a>, <a href="manage_faqs.php">FAQs</a>
            and <a href="settings.php">Settings</a> (phone, email, address, social links, statistics numbers).
        </div>

        <div class="pc-layout">
            <nav class="pc-tabs" aria-label="Page sections">
                <?php foreach ($groups as $gkey => $group): ?>
                    <a href="manage_content.php?tab=<?php echo urlencode($gkey); ?>" class="<?php echo $gkey === $active_tab ? 'active' : ''; ?>">
                        <i class="fas <?php echo htmlspecialchars($group['icon']); ?>"></i> <?php echo htmlspecialchars($group['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <form method="POST" enctype="multipart/form-data" class="pc-panel">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['content_csrf']); ?>">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                <h2><?php echo htmlspecialchars($groups[$active_tab]['label']); ?></h2>
                <p class="pc-intro">Change anything below and press Save. A field marked "default" still shows the original text; clear a text field to hide that line.</p>

                <?php foreach ($groups[$active_tab]['fields'] as $field):
                    $key = $field['key'];
                    $type = $field['type'];
                    $is_custom = array_key_exists($key, $all_settings) && trim((string) $all_settings[$key]) !== '';
                    if ($type === 'logo') { $is_custom = ($current_logo !== $field['default']); }
                    $raw = array_key_exists($key, $all_settings) ? (string) $all_settings[$key] : (string) $field['default'];
                    if (($type === 'image') && trim($raw) === '') { $raw = (string) $field['default']; }
                    ?>
                    <div class="pc-field">
                        <label for="f_<?php echo htmlspecialchars($key); ?>">
                            <?php echo htmlspecialchars($field['label']); ?>
                            <span class="pc-badge <?php echo $is_custom ? 'custom' : ''; ?>"><?php echo $is_custom ? 'edited' : 'default'; ?></span>
                        </label>

                        <?php if ($type === 'text' || $type === 'url'): ?>
                            <input type="text" id="f_<?php echo htmlspecialchars($key); ?>" name="f__<?php echo htmlspecialchars($key); ?>" maxlength="300" value="<?php echo htmlspecialchars($raw); ?>">
                        <?php elseif ($type === 'textarea' || $type === 'lines'): ?>
                            <textarea id="f_<?php echo htmlspecialchars($key); ?>" name="f__<?php echo htmlspecialchars($key); ?>" rows="<?php echo $type === 'lines' ? 5 : 3; ?>" maxlength="4000"><?php echo htmlspecialchars($raw); ?></textarea>
                        <?php elseif ($type === 'image' || $type === 'logo'): ?>
                            <?php $preview = $type === 'logo' ? $current_logo : $raw; ?>
                            <div class="pc-image <?php echo $type; ?>">
                                <?php if (trim($preview) !== ''): ?>
                                    <img src="../<?php echo htmlspecialchars($preview); ?>?v=<?php echo time(); ?>" alt="Current <?php echo htmlspecialchars($field['label']); ?>">
                                <?php endif; ?>
                                <div class="pc-controls">
                                    <input type="file" id="f_<?php echo htmlspecialchars($key); ?>" name="file__<?php echo htmlspecialchars($key); ?>" accept="image/jpeg,image/png,image/webp">
                                    <?php if ($is_custom || $type === 'logo'): ?>
                                        <label class="pc-reset"><input type="checkbox" name="reset__<?php echo htmlspecialchars($key); ?>" value="1"> Go back to the original image</label>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($field['hint'])): ?>
                            <span class="pc-hint"><?php echo htmlspecialchars($field['hint']); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div class="pc-savebar">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save changes</button>
                    <span style="color: var(--text-light); font-size: 13.5px;">Only the section you are viewing is saved.</span>
                </div>
            </form>
        </div>
    </div>
</div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
