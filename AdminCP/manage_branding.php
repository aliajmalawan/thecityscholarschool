<?php
/**
 * Logo & Favicon: change the school logo (header, footer, emails, share previews) and
 * the small icon shown on browser tabs and phone home screens. Uploading a new image
 * keeps the old files; "Back to default" switches to the City Scholars crest again.
 */
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

$message = '';
$error = '';
$default_logo = 'images/city-scholars-crest.png';
$root = dirname(__DIR__) . '/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['branding_action'] ?? '';

    if ($action === 'upload_logo') {
        $path = saveBrandLogo($_FILES['logo'] ?? null);
        if ($path === null) {
            $error = 'Please choose a logo image to upload.';
        } elseif ($path === false) {
            $error = 'The logo could not be used. Please upload a PNG, JPG or WebP image up to 8 MB.';
        } else {
            mysqli_query($conn, "UPDATE themes SET logo_path = '" . mysqli_real_escape_string($conn, $path) . "' WHERE is_active = 1");
            $message = 'The new logo is now shown across the website.';
            if (!empty($_POST['also_favicon'])) {
                $dir = generateFaviconSet($root . $path);
                if ($dir) {
                    deleteFaviconSet(getSetting('site_favicon_dir', ''));
                    saveSetting('site_favicon_dir', $dir);
                    $message = 'The new logo is now shown across the website, and the browser-tab icon was made from it.';
                }
            }
        }
    } elseif ($action === 'reset_logo') {
        mysqli_query($conn, "UPDATE themes SET logo_path = '" . mysqli_real_escape_string($conn, $default_logo) . "' WHERE is_active = 1");
        $message = 'The default City Scholars crest is shown again.';
    } elseif ($action === 'upload_favicon') {
        $file = $_FILES['favicon'] ?? null;
        $info = ($file && $file['error'] === UPLOAD_ERR_OK && $file['size'] <= 8 * 1024 * 1024) ? @getimagesize($file['tmp_name']) : false;
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Please choose an image for the icon.';
        } elseif (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            $error = 'The icon could not be used. Please upload a PNG, JPG or WebP image up to 8 MB.';
        } elseif (!($dir = generateFaviconSet($file['tmp_name']))) {
            $error = 'The icon could not be created. Please try another image.';
        } else {
            deleteFaviconSet(getSetting('site_favicon_dir', ''));
            saveSetting('site_favicon_dir', $dir);
            $message = 'The new browser-tab icon is saved. Browsers may show the old one until the page is reloaded.';
        }
    } elseif ($action === 'favicon_from_logo') {
        $dir = generateFaviconSet($root . getLogoPath());
        if ($dir) {
            deleteFaviconSet(getSetting('site_favicon_dir', ''));
            saveSetting('site_favicon_dir', $dir);
            $message = 'The browser-tab icon was made from the current logo.';
        } else {
            $error = 'The icon could not be made from the current logo.';
        }
    } elseif ($action === 'reset_favicon') {
        deleteFaviconSet(getSetting('site_favicon_dir', ''));
        deleteSetting('site_favicon_dir');
        $message = 'The default crest icon is used again.';
    }

    // the logo/icon helpers cache settings for this request; reload them for the preview below
    $GLOBALS['_settings_cache'] = null;
}

$logo = getLogoPath();
$logo_is_default = $logo === $default_logo;
$favicon_dir = trim((string) getSetting('site_favicon_dir', ''));
$favicon_custom = $favicon_dir !== '' && is_file($root . $favicon_dir . '/favicon-32.png');
$icon32 = $favicon_custom ? $favicon_dir . '/favicon-32.png' : 'images/icons/favicon-32.png';
$icon180 = $favicon_custom ? $favicon_dir . '/apple-touch-icon.png' : 'images/icons/apple-touch-icon.png';
$v = function ($path) use ($root) { return htmlspecialchars('../' . $path . (is_file($root . $path) ? '?v=' . filemtime($root . $path) : '')); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <title>Logo &amp; Favicon - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'manage_branding.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
<div class="ad-wrap">

    <div class="ad-head">
        <div class="ad-head-left">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
            <div>
                <h1>Logo &amp; Favicon</h1>
                <p>Change the school logo and the small icon shown on browser tabs and phone home screens.</p>
            </div>
        </div>
        <a href="../" target="_blank" rel="noopener" class="ad-btn ghost"><i class="fas fa-arrow-up-right-from-square"></i> View website</a>
    </div>

    <?php if ($message): ?>
        <div class="ad-alert ok" role="status"><i class="fas fa-circle-check"></i> <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="ad-alert bad" role="alert"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Logo -->
    <div class="ad-panel">
        <div class="ad-panel-head">
            <h2><i class="fas fa-shield-halved"></i> School logo</h2>
            <span class="ad-pill <?php echo $logo_is_default ? 'approved' : 'pending'; ?>"><?php echo $logo_is_default ? 'Default crest' : 'Custom logo'; ?></span>
        </div>
        <div class="ad-panel-body">
            <div class="brand-preview">
                <figure>
                    <div class="brand-preview-box is-light"><img src="<?php echo $v($logo); ?>" alt="Current logo on a light background"></div>
                    <figcaption>On white (header)</figcaption>
                </figure>
                <figure>
                    <div class="brand-preview-box is-dark"><img src="<?php echo $v($logo); ?>" alt="Current logo on a dark background"></div>
                    <figcaption>On navy (footer)</figcaption>
                </figure>
                <figure>
                    <div class="brand-preview-header">
                        <img src="<?php echo $v(getLogoSmallPath()); ?>" alt="">
                        <span><strong><?php echo htmlspecialchars(getSiteName()); ?></strong><small><?php echo htmlspecialchars(content('site_tagline')); ?></small></span>
                    </div>
                    <figcaption>How the header shows it</figcaption>
                </figure>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="branding_action" value="upload_logo">
                <div class="ad-form-section">Upload a new logo</div>
                <div class="ad-field brand-field">
                    <label for="logo">Logo image (PNG, JPG or WebP, up to 8 MB)</label>
                    <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" required>
                    <small><i class="fas fa-lightbulb"></i> Best: a PNG with a transparent background, at least 400 px wide. It is resized and compressed automatically.</small>
                    <label class="ad-check"><input type="checkbox" name="also_favicon" value="1"> Also make the browser-tab icon from this logo</label>
                </div>
                <div class="ad-form-actions">
                    <button type="submit" class="ad-btn primary"><i class="fas fa-upload"></i> Upload logo</button>
                </div>
            </form>

            <?php if (!$logo_is_default): ?>
            <form method="POST" class="brand-reset" onsubmit="return confirm('Show the default City Scholars crest again?');">
                <input type="hidden" name="branding_action" value="reset_logo">
                <button type="submit" class="ad-btn ghost"><i class="fas fa-rotate-left"></i> Back to the default crest</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Favicon -->
    <div class="ad-panel">
        <div class="ad-panel-head">
            <h2><i class="fas fa-star"></i> Favicon (browser-tab icon)</h2>
            <span class="ad-pill <?php echo $favicon_custom ? 'pending' : 'approved'; ?>"><?php echo $favicon_custom ? 'Custom icon' : 'Default crest icon'; ?></span>
        </div>
        <div class="ad-panel-body">
            <div class="brand-preview">
                <figure>
                    <div class="brand-tab">
                        <img src="<?php echo $v($icon32); ?>" alt="" width="16" height="16">
                        <span><?php echo htmlspecialchars(getSiteName()); ?></span>
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                    </div>
                    <figcaption>Browser tab</figcaption>
                </figure>
                <figure>
                    <div class="brand-preview-box is-light brand-icon-sizes">
                        <img src="<?php echo $v($icon32); ?>" alt="Icon at 16 pixels" width="16" height="16">
                        <img src="<?php echo $v($icon32); ?>" alt="Icon at 32 pixels" width="32" height="32">
                        <img src="<?php echo $v($icon180); ?>" alt="Phone home-screen icon" width="64" height="64" class="brand-app-icon">
                    </div>
                    <figcaption>16 px, 32 px and phone home screen</figcaption>
                </figure>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="branding_action" value="upload_favicon">
                <div class="ad-form-section">Upload a new icon</div>
                <div class="ad-field brand-field">
                    <label for="favicon">Icon image (PNG, JPG or WebP, up to 8 MB)</label>
                    <input type="file" id="favicon" name="favicon" accept="image/png,image/jpeg,image/webp" required>
                    <small><i class="fas fa-lightbulb"></i> Best: a square PNG, at least 512 &times; 512 px, with a simple shape that is still clear when tiny. All sizes (tab, iPhone, Android) are made automatically.</small>
                </div>
                <div class="ad-form-actions">
                    <button type="submit" class="ad-btn primary"><i class="fas fa-upload"></i> Upload icon</button>
                </div>
            </form>

            <div class="brand-reset">
                <form method="POST">
                    <input type="hidden" name="branding_action" value="favicon_from_logo">
                    <button type="submit" class="ad-btn ghost"><i class="fas fa-wand-magic-sparkles"></i> Make the icon from the current logo</button>
                </form>
                <?php if ($favicon_custom): ?>
                <form method="POST" onsubmit="return confirm('Use the default crest icon again?');">
                    <input type="hidden" name="branding_action" value="reset_favicon">
                    <button type="submit" class="ad-btn ghost"><i class="fas fa-rotate-left"></i> Back to the default icon</button>
                </form>
                <?php endif; ?>
            </div>
            <p class="brand-note"><i class="fas fa-circle-info"></i> Browsers keep icons for a while. If the old icon still shows, reload the website once (Ctrl + F5).</p>
        </div>
    </div>

</div>
</div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
