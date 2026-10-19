<?php
require_once '../includes/config.php';
require_once __DIR__ . '/includes/admin_guard.php';   // login check, idle timeout, CSRF protection
require_once '../includes/image_helper.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = true;
    $social_url_keys = ['facebook_url', 'instagram_url', 'youtube_url', 'twitter_url'];

    // Principal photo upload (optional) - only touch the setting if a new
    // file was actually chosen, so re-saving the rest of the form doesn't
    // wipe out an existing photo.
    $photo_savings = '';
    if (isset($_FILES['principal_photo_upload']) && $_FILES['principal_photo_upload']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['principal_photo_upload']['name'], PATHINFO_EXTENSION));
        if (in_array($file_extension, $allowed_extensions)) {
            $upload_dir = '../uploads/staff/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $base_filename = 'principal_' . time() . '_' . uniqid();
            $new_filename = compressUploadedPhotoAsJpeg($_FILES['principal_photo_upload']['tmp_name'], $upload_dir, $base_filename, 800, 85);
            if ($new_filename) {
                $new_filepath = $upload_dir . $new_filename;
                $_POST['principal_photo'] = 'uploads/staff/' . $new_filename;
                $photo_savings = describeCompressionSavings($_FILES['principal_photo_upload']['tmp_name'], $new_filepath);
            }
        }
    }

    // Cambridge EdTech homepage carousel images (5 fixed slots, optional
    // replacement each save - same pattern as the principal photo above).
    $cambridge_savings_count = 0;
    for ($i = 1; $i <= 5; $i++) {
        $field = "cambridge_image_{$i}_upload";
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0) {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            $file_extension = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (in_array($file_extension, $allowed_extensions)) {
                $upload_dir = '../images/cambridge/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $base_filename = "cambridge_{$i}_" . time() . '_' . uniqid();
                $new_filename = compressUploadedPhotoAsJpeg($_FILES[$field]['tmp_name'], $upload_dir, $base_filename, 1200, 85);
                if ($new_filename) {
                    $_POST["cambridge_image_{$i}"] = 'images/cambridge/' . $new_filename;
                    $cambridge_savings_count++;
                }
            }
        }
    }

    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            // Social links are plain text inputs (not type="url") so a value
            // without a scheme doesn't block submission - add https:// here
            // instead, so "facebook.com/page" still becomes a working link.
            // "#" (or anything starting with it) is left alone - that's an
            // intentional placeholder link for "show the icon, no page yet".
            if (in_array($key, $social_url_keys, true) && $value !== '' && $value[0] !== '#' && !preg_match('#^https?://#i', $value)) {
                $value = 'https://' . ltrim($value, '/');
            }

            $key_escaped = mysqli_real_escape_string($conn, $key);
            $value_escaped = mysqli_real_escape_string($conn, $value);

            // Check if setting exists
            $check_query = "SELECT id FROM settings WHERE setting_key = '$key_escaped'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                // Update existing setting
                $update_query = "UPDATE settings SET setting_value = '$value_escaped' WHERE setting_key = '$key_escaped'";
                if (!mysqli_query($conn, $update_query)) {
                    $success = false;
                }
            } else {
                // Insert new setting
                $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key_escaped', '$value_escaped')";
                if (!mysqli_query($conn, $insert_query)) {
                    $success = false;
                }
            }
        }
    }

    if ($success) {
        $message = "Settings saved successfully!" . ($photo_savings ? " Principal photo compressed{$photo_savings}." : "") . ($cambridge_savings_count > 0 ? " {$cambridge_savings_count} Cambridge EdTech image(s) compressed." : "");
    } else {
        $message = "Error saving some settings.";
    }
}

// Load settings using helper function
loadSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
    <title>Site Settings - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo assetVersion('css/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo assetVersion('AdminCP/assets/admin.css'); ?>">
    <style>
        .settings-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .settings-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body style="background: var(--bg-light);">
<div class="admin-shell">
<?php $active_page = 'settings.php'; include 'includes/sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="main-content">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
            <div class="admin-topbar-left">
                <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Open menu" aria-expanded="false" aria-controls="adminSidebar"><i class="fas fa-bars"></i></button>
                <h1 style="color: var(--primary-color); margin:0;"><i class="fas fa-cog"></i> Site Settings</h1>
            </div>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- General Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-info-circle"></i> General Information</h3>

                <div class="form-group">
                    <label>Site Name *</label>
                    <input type="text" name="site_name" required value="<?php echo htmlspecialchars(getSetting('site_name', 'Tameer-e-Nau Public School')); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Site Email *</label>
                        <input type="email" name="site_email" required value="<?php echo htmlspecialchars(getSetting('site_email', 'info@fort.edu.pk')); ?>">
                    </div>

                    <div class="form-group">
                        <label>Site Phone *</label>
                        <input type="text" name="site_phone" required value="<?php echo htmlspecialchars(getSetting('site_phone', '')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Site Address *</label>
                    <textarea name="site_address" required rows="3"><?php echo htmlspecialchars(getSetting('site_address', 'Canal View, Hasilpur, Pakistan')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Office Hours</label>
                    <input type="text" name="office_hours" value="<?php echo htmlspecialchars(getSetting('office_hours', 'Mon - Sat: 8:00 AM - 5:00 PM')); ?>" placeholder="e.g., Mon - Sat: 8:00 AM - 5:00 PM">
                </div>
            </div>

            <!-- Social Media Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-share-alt"></i> Social Media Links</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-facebook"></i> Facebook URL</label>
                        <input type="text" name="facebook_url" value="<?php echo htmlspecialchars(getSetting('facebook_url')); ?>" placeholder="https://facebook.com/yourpage">
                    </div>

                    <div class="form-group">
                        <label><i class="fab fa-instagram"></i> Instagram URL</label>
                        <input type="text" name="instagram_url" value="<?php echo htmlspecialchars(getSetting('instagram_url')); ?>" placeholder="https://instagram.com/yourpage">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-youtube"></i> YouTube URL</label>
                        <input type="text" name="youtube_url" value="<?php echo htmlspecialchars(getSetting('youtube_url')); ?>" placeholder="https://youtube.com/yourchannel">
                    </div>

                    <div class="form-group">
                        <label><i class="fab fa-twitter"></i> Twitter URL</label>
                        <input type="text" name="twitter_url" value="<?php echo htmlspecialchars(getSetting('twitter_url')); ?>" placeholder="https://twitter.com/yourpage">
                    </div>
                </div>
            </div>

            <!-- Home Page Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-home"></i> Home Page Content</h3>

                <div class="form-group">
                    <label>Hero Section Title</label>
                    <input type="text" name="hero_title" value="<?php echo htmlspecialchars(getSetting('hero_title', 'Building Strong Foundations for a Brighter Future')); ?>" placeholder="Main heading on home page">
                </div>

                <div class="form-group">
                    <label>Hero Section Description</label>
                    <textarea name="hero_description" rows="3" placeholder="Description text below the main heading"><?php echo htmlspecialchars(getSetting('hero_description', 'Empowering students with quality education and nurturing their potential to become future leaders.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Hero Button Text</label>
                    <input type="text" name="hero_button_text" value="<?php echo htmlspecialchars(getSetting('hero_button_text', 'Apply Now')); ?>" placeholder="Button text">
                </div>

                <div class="form-group">
                    <label>Hero Button Link</label>
                    <input type="text" name="hero_button_link" value="<?php echo htmlspecialchars(getSetting('hero_button_link', 'admission.php')); ?>" placeholder="e.g., admission.php or courses.php">
                </div>
            </div>

            <!-- Statistics Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-chart-bar"></i> Statistics Section</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Students</label>
                        <input type="number" name="stats_students" value="<?php echo htmlspecialchars(getSetting('stats_students', '')); ?>" placeholder="Leave blank to hide">
                    </div>

                    <div class="form-group">
                        <label>Faculty Members</label>
                        <input type="number" name="stats_teachers" value="<?php echo htmlspecialchars(getSetting('stats_teachers', '')); ?>" placeholder="Blank = count from Faculty list">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Programs</label>
                        <input type="number" name="stats_courses" value="<?php echo htmlspecialchars(getSetting('stats_courses', '')); ?>" placeholder="Blank = count from Courses list">
                    </div>

                    <div class="form-group">
                        <label>Years of Excellence</label>
                        <input type="number" name="stats_years" value="<?php echo htmlspecialchars(getSetting('stats_years', '')); ?>" placeholder="Leave blank to hide">
                    </div>
                </div>
            </div>

            <!-- About Page Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-info"></i> About Page Content</h3>

                <div class="form-group">
                    <label>About Us Description</label>
                    <textarea name="about_description" rows="4" placeholder="Main description on About page"><?php echo htmlspecialchars(getSetting('about_description', 'Tameer-e-Nau Public School is committed to academic excellence and character building.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Mission Statement</label>
                    <textarea name="mission_statement" rows="3" placeholder="Organization mission"><?php echo htmlspecialchars(getSetting('mission_statement', 'To provide quality education and empower students to achieve their full potential.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Vision Statement</label>
                    <textarea name="vision_statement" rows="3" placeholder="Organization vision"><?php echo htmlspecialchars(getSetting('vision_statement', 'To be a center of educational excellence that nurtures future leaders and innovators.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>"Our Story" - Second Paragraph</label>
                    <textarea name="about_paragraph_2" rows="3"><?php echo htmlspecialchars(getSetting('about_paragraph_2')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>"Our Story" - Third Paragraph</label>
                    <textarea name="about_paragraph_3" rows="3"><?php echo htmlspecialchars(getSetting('about_paragraph_3')); ?></textarea>
                </div>
            </div>

            <div class="settings-section">
                <h3><i class="fas fa-fire"></i> "The Candle" Symbol Section (About Page)</h3>

                <div class="form-group">
                    <label>Paragraph 1</label>
                    <textarea name="candle_paragraph_1" rows="2"><?php echo htmlspecialchars(getSetting('candle_paragraph_1')); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Paragraph 2</label>
                    <textarea name="candle_paragraph_2" rows="2"><?php echo htmlspecialchars(getSetting('candle_paragraph_2')); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Paragraph 3</label>
                    <textarea name="candle_paragraph_3" rows="2"><?php echo htmlspecialchars(getSetting('candle_paragraph_3')); ?></textarea>
                </div>
            </div>

            <!-- Principal's Message (About Page) -->
            <div class="settings-section">
                <h3><i class="fas fa-user-tie"></i> Principal's Message (About Page)</h3>

                <div class="form-group">
                    <label>Principal's Photo</label>
                    <?php $principal_photo = getSetting('principal_photo'); ?>
                    <input type="file" name="principal_photo_upload" accept="image/*">
                    <small style="color: var(--primary-color); font-size: 12px; display: block; margin-top: 6px;"><i class="fas fa-compress-alt"></i> Photo is automatically compressed on upload.</small>
                    <?php if (!empty($principal_photo)): ?>
                        <div style="margin-top: 10px;"><img src="../<?php echo htmlspecialchars($principal_photo); ?>" style="height: 80px; border-radius: 8px;"></div>
                    <?php else: ?>
                        <small style="color: var(--text-light); display: block; margin-top: 5px;">No photo uploaded yet - a generic icon is shown instead.</small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Opening Quote (shown in italics)</label>
                    <textarea name="principal_message_quote" rows="2"><?php echo htmlspecialchars(getSetting('principal_message_quote')); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Message - Paragraph 1</label>
                    <textarea name="principal_message_para_1" rows="2"><?php echo htmlspecialchars(getSetting('principal_message_para_1')); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Message - Paragraph 2</label>
                    <textarea name="principal_message_para_2" rows="2"><?php echo htmlspecialchars(getSetting('principal_message_para_2')); ?></textarea>
                </div>
            </div>

            <!-- Assessment Section (Homepage) -->
            <div class="settings-section">
                <h3><i class="fas fa-clipboard-check"></i> "Our Approach to Assessment" (Homepage)</h3>

                <div class="form-group">
                    <label>Paragraph 1</label>
                    <textarea name="assessment_paragraph_1" rows="2"><?php echo htmlspecialchars(getSetting('assessment_paragraph_1')); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Paragraph 2</label>
                    <textarea name="assessment_paragraph_2" rows="2"><?php echo htmlspecialchars(getSetting('assessment_paragraph_2')); ?></textarea>
                </div>
            </div>

            <!-- Footer Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-shoe-prints"></i> Footer Content</h3>

                <div class="form-group">
                    <label>Footer About Text</label>
                    <textarea name="footer_about_text" rows="3" placeholder="Short description in footer"><?php echo htmlspecialchars(getSetting('footer_about_text', 'Tameer-e-Nau Public School is dedicated to providing quality education and nurturing the potential of every student.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Copyright Text</label>
                    <input type="text" name="copyright_text" value="<?php echo htmlspecialchars(getSetting('copyright_text', 'Tameer-e-Nau Public School. All Rights Reserved.')); ?>" placeholder="Copyright text in footer">
                </div>
            </div>

            <!-- Contact Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-map-marker-alt"></i> Contact Information</h3>

                <div class="form-group">
                    <label>Google Maps Embed Code (Optional)</label>
                    <textarea name="google_maps_embed" rows="3" placeholder="Paste Google Maps embed iframe code here"><?php echo htmlspecialchars(getSetting('google_maps_embed')); ?></textarea>
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Get embed code from Google Maps → Share → Embed a map
                    </small>
                </div>

                <div class="form-group">
                    <label>WhatsApp Number (for quick contact)</label>
                    <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars(getSetting('whatsapp_number')); ?>" placeholder="e.g., +923061345242">
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Include country code (e.g., +92 for Pakistan)
                    </small>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" name="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
    </div>
</div>
<script src="assets/admin.js?v=<?php echo assetVersion('AdminCP/assets/admin.js'); ?>"></script>
</body>
</html>
