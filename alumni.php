<?php
require_once 'includes/config.php';
require_once 'includes/settings_helper.php';
require_once 'includes/image_helper.php';

$message = formFlash();   // set after a successful submission, see formDone()
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['submit_alumni']) || isset($_POST['submit_review']))) {
    $form_problem = formGuardError();
    if ($form_problem === '' && isset($_POST['submit_alumni'])) {
        $form_problem = formValidate([
            'student_name' => ['Student name', 'required'],
            'father_name' => ['Father name', 'required'],
            'course' => ['Course passed', 'required'],
            'passing_year' => ['passing year', 'required', 'year'],
            'mobile_number' => ['mobile number', 'required', 'phone'],
            'whatsapp_number' => ['WhatsApp number', 'phone'],
            'review' => ['Your review / feedback', 'required'],
        ]);
    } elseif ($form_problem === '') {
        $form_problem = formValidate([
            'name' => ['Your name', 'required'],
            'passing_year' => ['passing year', 'year'],
            'review' => ['Your review', 'required'],
        ]);
        if ($form_problem === '' && (intval($_POST['rating'] ?? 0) < 1 || intval($_POST['rating'] ?? 0) > 5)) {
            $form_problem = 'Please choose a rating from 1 to 5 stars.';
        }
    }
    if ($form_problem !== '') {
        $error = $form_problem;
        unset($_POST['submit_alumni'], $_POST['submit_review']);   // nothing below runs
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_alumni'])) {
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $father_name = mysqli_real_escape_string($conn, $_POST['father_name']);
    $current_job = mysqli_real_escape_string($conn, $_POST['current_job']);
    $job_department = mysqli_real_escape_string($conn, $_POST['job_department']);
    $job_city = mysqli_real_escape_string($conn, $_POST['job_city']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $passing_year = intval($_POST['passing_year']);
    $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $whatsapp_number = mysqli_real_escape_string($conn, $_POST['whatsapp_number']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);

    // Photo upload: checked, resized and compressed (never stored under the visitor's own file name)
    $photo_path = null;
    $upload_error = '';
    $saved_photo = savePublicPhoto($_FILES['photo'] ?? null, 'uploads/alumni/', 'alumni_' . time() . '_' . substr(uniqid(), -5), 800, 85);
    if (!empty($saved_photo['error'])) {
        $upload_error = $saved_photo['error'];
    } elseif (!empty($saved_photo['name'])) {
        $photo_path = 'uploads/alumni/' . $saved_photo['name'];
    }

    $new_alumni_id = nextRowId('alumni');
    $sql = "INSERT INTO alumni (id, student_name, father_name, current_job, job_department, job_city, course, passing_year, photo, mobile_number, whatsapp_number, review)
            VALUES ($new_alumni_id, '$student_name', '$father_name', '$current_job', '$job_department', '$job_city', '$course', $passing_year, '$photo_path', '$mobile_number', '$whatsapp_number', '$review')";

    if ($upload_error !== '') {
        $error = $upload_error;
    } elseif (mysqli_query($conn, $sql)) {
        $form_sent = true;
        formDone('Thank you! Your alumni registration has been submitted and is pending approval.');
    } else {
        error_log('Alumni form save failed: ' . mysqli_error($conn));
        $error = 'Sorry, your submission could not be saved. Please try again later.';
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $passing_year = intval($_POST['passing_year']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);
    $rating = intval($_POST['rating']);

    $sql = "INSERT INTO alumni_reviews (name, passing_year, review, rating) VALUES ('$name', $passing_year, '$review', $rating)";

    if (mysqli_query($conn, $sql)) {
        $form_sent = true;
        formDone('Thank you for your review! It will be displayed after approval.');
    } else {
        error_log('Alumni form save failed: ' . mysqli_error($conn));
        $error = 'Sorry, your submission could not be saved. Please try again later.';
    }
}

// Fetch approved alumni
$alumni = mysqli_query($conn, "SELECT DISTINCT * FROM alumni WHERE status = 'approved' ORDER BY passing_year DESC LIMIT 12");

// Fetch approved reviews
$reviews = mysqli_query($conn, "SELECT DISTINCT * FROM alumni_reviews WHERE status = 'approved' ORDER BY created_at DESC LIMIT 6");

$page_title = content('seo_alumni_title');
$meta_description = content('seo_alumni_description');
?>
<?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <?php renderPageBanner('alumni', true, 'fa-user-graduate'); ?>

    <?php if ($message): ?>
        <div class="container" style="margin-top: 30px;">
            <div class="alert alert-success" role="status">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="container" style="margin-top: 30px;">
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Alumni Grid -->
    <section>
        <div class="container">
            <div class="section-header reveal">
                <span class="eyebrow"><?php echo htmlspecialchars(content('alumni_list_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('alumni_list_heading')); ?></h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap: 30px;">
                <?php
                if ($alumni && mysqli_num_rows($alumni) > 0) {
                    while ($alum = mysqli_fetch_assoc($alumni)) {
                        echo '<div class="card" style="text-align: center;">';
                        // the alumnus's own photo, or the default picture set in AdminCP -> Page Content -> Alumni page
                        $alum_photo = !empty($alum['photo']) ? $alum['photo'] : content('alumni_default_photo');
                        echo '<img src="' . htmlspecialchars($alum_photo) . '" alt="' . htmlspecialchars($alum['student_name']) . '" loading="lazy" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0 auto 15px; display: block;">';
                        echo '<h3 style="color: var(--primary-color); margin-bottom: 5px;">' . htmlspecialchars($alum['student_name']) . '</h3>';
                        echo '<p style="color: var(--text-light); margin-bottom: 10px;">' . htmlspecialchars($alum['course']) . ' - ' . $alum['passing_year'] . '</p>';
                        if ($alum['current_job']) {
                            echo '<p style="margin-bottom: 10px;"><strong>' . htmlspecialchars($alum['current_job']) . '</strong></p>';
                            if ($alum['job_city']) {
                                echo '<p style="color: var(--text-light); font-size: 0.9rem;"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($alum['job_city']) . '</p>';
                            }
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<p style="text-align: center; color: var(--text-light); grid-column: 1 / -1;">No alumni to display yet</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Alumni Reviews -->
    <section class="bg-light">
        <div class="container">
            <div class="section-header reveal">
                <span class="eyebrow"><?php echo htmlspecialchars(content('alumni_reviews_eyebrow')); ?></span>
                <h2><?php echo htmlspecialchars(content('alumni_reviews_heading')); ?></h2>
            </div>
            <?php if ($reviews && mysqli_num_rows($reviews) > 0): ?>
            <div class="testimonial-carousel reveal">
                <div class="testimonial-track">
                    <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                        <div class="testimonial-card">
                            <div class="testimonial-stars">
                                <?php
                                for ($i = 0; $i < $review['rating']; $i++) {
                                    echo '<i class="fas fa-star"></i>';
                                }
                                ?>
                            </div>
                            <p class="testimonial-quote">"<?php echo htmlspecialchars($review['review']); ?>"</p>
                            <div class="testimonial-author">
                                <div class="testimonial-avatar"><i class="fas fa-user-graduate"></i></div>
                                <div>
                                    <strong><?php echo htmlspecialchars($review['name']); ?></strong>
                                    <?php if ($review['passing_year']): ?>
                                        <span>Batch <?php echo htmlspecialchars($review['passing_year']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <button type="button" class="testimonial-nav prev" aria-label="Previous review"><i class="fas fa-chevron-left"></i></button>
                <button type="button" class="testimonial-nav next" aria-label="Next review"><i class="fas fa-chevron-right"></i></button>
            </div>
            <?php else: ?>
            <p style="text-align: center; color: var(--text-light); padding: 20px;">No reviews yet - be the first to share your experience!</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Registration Form -->
    <section id="registration">
        <div class="container">
            <div class="card" style="max-width: 800px; margin: 0 auto;">
                <div class="text-center mb-30">
                    <span class="eyebrow"><?php echo htmlspecialchars(content('alumni_form_eyebrow')); ?></span>
                    <h2 style="color: var(--ink);"><?php echo htmlspecialchars(content('alumni_form_heading')); ?></h2>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <?php echo formGuardFields(); ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Student Name *</label>
                            <input type="text" name="student_name" required style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('student_name'); ?>">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Father Name *</label>
                            <input type="text" name="father_name" required style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('father_name'); ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Course Passed *</label>
                            <input type="text" name="course" required placeholder="e.g., Matric, Intermediate" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('course'); ?>">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Passing Year *</label>
                            <input type="number" name="passing_year" required min="1990" max="2030" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('passing_year'); ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Current Job</label>
                            <input type="text" name="current_job" placeholder="Job title" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('current_job'); ?>">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Department</label>
                            <input type="text" name="job_department" placeholder="Department" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('job_department'); ?>">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">City</label>
                            <input type="text" name="job_city" placeholder="City" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('job_city'); ?>">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Mobile Number *</label>
                            <input type="text" name="mobile_number" required placeholder="03001234567" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('mobile_number'); ?>">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" placeholder="03001234567" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('whatsapp_number'); ?>">
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Photo (JPG, PNG)</label>
                        <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Your Review/Feedback * (Compulsory)</label>
                        <textarea name="review" required rows="5" placeholder="Share your experience with Tameer-e-Nau Public School..." style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);"><?php echo old('review'); ?></textarea>
                    </div>

                    <button type="submit" name="submit_alumni" class="btn btn-primary" style="width: 100%; padding: 15px; font-size: 1.1rem;">
                        <i class="fas fa-paper-plane"></i> Submit Registration
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Submit Review Separately -->
    <section class="bg-light" id="review">
        <div class="container">
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="text-center mb-30">
                    <span class="eyebrow"><?php echo htmlspecialchars(content('alumni_review_form_eyebrow')); ?></span>
                    <h2 style="color: var(--ink);"><?php echo htmlspecialchars(content('alumni_review_form_heading')); ?></h2>
                </div>
                <form method="POST">
                    <?php echo formGuardFields(); ?>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Your Name *</label>
                        <input type="text" name="name" required style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('name'); ?>">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Passing Year</label>
                        <input type="number" name="passing_year" min="1990" max="2030" style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);" value="<?php echo old('passing_year'); ?>">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Rating *</label>
                        <select name="rating" required style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);">
                            <option value="5"<?php echo old('rating') === '5' ? ' selected' : ''; ?>>★★★★★ (5 Stars)</option>
                            <option value="4"<?php echo old('rating') === '4' ? ' selected' : ''; ?>>★★★★ (4 Stars)</option>
                            <option value="3"<?php echo old('rating') === '3' ? ' selected' : ''; ?>>★★★ (3 Stars)</option>
                            <option value="2"<?php echo old('rating') === '2' ? ' selected' : ''; ?>>★★ (2 Stars)</option>
                            <option value="1"<?php echo old('rating') === '1' ? ' selected' : ''; ?>>★ (1 Star)</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: var(--text-dark);">Your Review *</label>
                        <textarea name="review" required rows="4" placeholder="Share your thoughts..." style="width: 100%; padding: 13px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-sm);"><?php echo old('review'); ?></textarea>
                    </div>

                    <button type="submit" name="submit_review" class="btn btn-primary" style="width: 100%; padding: 15px;">
                        <i class="fas fa-star"></i> Submit Review
                    </button>
                </form>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
