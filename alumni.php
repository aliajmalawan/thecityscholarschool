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

    try {
        $saved = $upload_error === '' && mysqli_query($conn, $sql);
    } catch (mysqli_sql_exception $e) {
        error_log('Alumni form save failed: ' . $e->getMessage());
        $saved = false;
    }

    if ($upload_error !== '') {
        $error = $upload_error;
    } elseif ($saved) {
        $form_sent = true;
        formDone('Thank you! Your alumni registration has been submitted and is pending approval.', 'form-status');
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

    try {
        $saved = mysqli_query($conn, $sql);
    } catch (mysqli_sql_exception $e) {
        error_log('Alumni review save failed: ' . $e->getMessage());
        $saved = false;
    }
    if ($saved) {
        $form_sent = true;
        formDone('Thank you for your review! It will be displayed after approval.', 'form-status');
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

<?php renderPageBanner('alumni', true, 'fa-user-graduate', ['Campus Life', 'campus-life']); ?>

<?php if ($message || $error): ?>
<div class="container page-alert" id="form-status">
    <?php if ($message): ?>
        <div class="alert alert-success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i> <span><?php echo htmlspecialchars($message); ?></span></div>
    <?php else: ?>
        <div class="alert alert-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <span><?php echo htmlspecialchars($error); ?></span></div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Alumni (approved in AdminCP -> Alumni) -->
<section class="page-intro">
    <div class="container">
        <?php sectionHeader(content('alumni_list_eyebrow'), content('alumni_list_heading')); ?>
        <?php if ($alumni && mysqli_num_rows($alumni) > 0): ?>
        <div class="alumni-grid">
            <?php while ($alum = mysqli_fetch_assoc($alumni)):
                // the alumnus's own photo, or the default picture set in AdminCP -> Page Content -> Alumni page
                $alum_photo = !empty($alum['photo']) ? $alum['photo'] : content('alumni_default_photo');
            ?>
            <article class="alumni-card">
                <img src="<?php echo htmlspecialchars($alum_photo); ?>" alt="" loading="lazy" width="96" height="96">
                <h3><?php echo htmlspecialchars($alum['student_name']); ?></h3>
                <p class="alumni-batch"><?php echo htmlspecialchars(trim($alum['course'] . ' · ' . $alum['passing_year'], ' ·')); ?></p>
                <?php if (!empty($alum['current_job'])): ?>
                    <p class="alumni-job"><i class="fas fa-briefcase" aria-hidden="true"></i> <?php echo htmlspecialchars($alum['current_job']); ?><?php echo !empty($alum['job_city']) ? ', ' . htmlspecialchars($alum['job_city']) : ''; ?></p>
                <?php endif; ?>
            </article>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
            <?php renderEmptyState('fa-user-graduate', 'Our alumni network is just getting started', 'Former students can register below to be featured here.'); ?>
        <?php endif; ?>
    </div>
</section>

<!-- Reviews (approved in AdminCP -> Alumni Reviews) -->
<section class="bg-light">
    <div class="container">
        <?php sectionHeader(content('alumni_reviews_eyebrow'), content('alumni_reviews_heading')); ?>
        <?php if ($reviews && mysqli_num_rows($reviews) > 0): ?>
        <div class="testimonial-carousel">
            <div class="testimonial-track">
                <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                <figure class="testimonial-card">
                    <div class="testimonial-stars" role="img" aria-label="<?php echo intval($review['rating']); ?> out of 5 stars">
                        <?php for ($i = 0; $i < intval($review['rating']); $i++) echo '<i class="fas fa-star" aria-hidden="true"></i>'; ?>
                    </div>
                    <blockquote class="testimonial-quote">&ldquo;<?php echo htmlspecialchars($review['review']); ?>&rdquo;</blockquote>
                    <figcaption class="testimonial-author">
                        <div class="testimonial-avatar"><i class="fas fa-user-graduate" aria-hidden="true"></i></div>
                        <div>
                            <strong><?php echo htmlspecialchars($review['name']); ?></strong>
                            <?php if (!empty($review['passing_year'])): ?><span>Batch <?php echo htmlspecialchars($review['passing_year']); ?></span><?php endif; ?>
                        </div>
                    </figcaption>
                </figure>
                <?php endwhile; ?>
            </div>
            <button type="button" class="testimonial-nav prev" aria-label="Previous review"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
            <button type="button" class="testimonial-nav next" aria-label="Next review"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
        </div>
        <?php else: ?>
            <?php renderEmptyState('fa-comment-dots', 'No reviews yet', 'Be the first to share your experience using the form below.'); ?>
        <?php endif; ?>
    </div>
</section>

<!-- The two forms side by side -->
<section>
    <div class="container">
        <div class="alumni-forms">
            <div class="form-card" id="registration">
                <span class="eyebrow"><?php echo htmlspecialchars(content('alumni_form_eyebrow')); ?></span>
                <h2 class="form-card-title"><?php echo htmlspecialchars(content('alumni_form_heading')); ?></h2>
                <p class="form-card-text">Registrations appear on this page after approval by the school.</p>
                <form method="POST" enctype="multipart/form-data" id="alumniForm" data-validate novalidate>
                    <?php echo formGuardFields(); ?>
                    <input type="hidden" name="submit_alumni" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="al_student_name">Your name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="al_student_name" name="student_name" required maxlength="100" value="<?php echo old('student_name'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="al_father_name">Father's name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="al_father_name" name="father_name" required maxlength="100" value="<?php echo old('father_name'); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="al_course">Class / program completed <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="al_course" name="course" required maxlength="100" placeholder="e.g. Matric" value="<?php echo old('course'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="al_passing_year">Passing year <span class="req" aria-hidden="true">*</span></label>
                            <input type="number" id="al_passing_year" name="passing_year" required min="1950" max="<?php echo date('Y') + 1; ?>" value="<?php echo old('passing_year'); ?>">
                        </div>
                    </div>
                    <div class="form-row is-3">
                        <div class="form-group">
                            <label for="al_current_job">Current job <span class="optional">(optional)</span></label>
                            <input type="text" id="al_current_job" name="current_job" maxlength="100" value="<?php echo old('current_job'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="al_job_department">Department <span class="optional">(optional)</span></label>
                            <input type="text" id="al_job_department" name="job_department" maxlength="100" value="<?php echo old('job_department'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="al_job_city">City <span class="optional">(optional)</span></label>
                            <input type="text" id="al_job_city" name="job_city" maxlength="100" value="<?php echo old('job_city'); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="al_mobile">Mobile number <span class="req" aria-hidden="true">*</span></label>
                            <input type="tel" id="al_mobile" name="mobile_number" required maxlength="30" inputmode="tel" placeholder="03001234567" value="<?php echo old('mobile_number'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="al_whatsapp">WhatsApp number <span class="optional">(optional)</span></label>
                            <input type="tel" id="al_whatsapp" name="whatsapp_number" maxlength="30" inputmode="tel" value="<?php echo old('whatsapp_number'); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="al_photo">Photo <span class="optional">(optional)</span></label>
                        <input type="file" id="al_photo" name="photo" accept="image/*" aria-describedby="al_photo_hint">
                        <p class="field-hint" id="al_photo_hint">JPG or PNG; it is resized automatically.</p>
                    </div>
                    <div class="form-group">
                        <label for="al_review">Your experience at the school <span class="req" aria-hidden="true">*</span></label>
                        <textarea id="al_review" name="review" required rows="4" maxlength="2000"><?php echo old('review'); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane" aria-hidden="true"></i> Submit Registration</button>
                </form>
            </div>

            <div class="form-card" id="review">
                <span class="eyebrow"><?php echo htmlspecialchars(content('alumni_review_form_eyebrow')); ?></span>
                <h2 class="form-card-title"><?php echo htmlspecialchars(content('alumni_review_form_heading')); ?></h2>
                <p class="form-card-text">Reviews are shown after approval by the school.</p>
                <form method="POST" id="reviewForm" data-validate novalidate>
                    <?php echo formGuardFields(); ?>
                    <input type="hidden" name="submit_review" value="1">
                    <div class="form-group">
                        <label for="rv_name">Your name <span class="req" aria-hidden="true">*</span></label>
                        <input type="text" id="rv_name" name="name" required maxlength="100" value="<?php echo old('name'); ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="rv_passing_year">Passing year <span class="optional">(optional)</span></label>
                            <input type="number" id="rv_passing_year" name="passing_year" min="1950" max="<?php echo date('Y') + 1; ?>" value="<?php echo old('passing_year'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="rv_rating">Rating <span class="req" aria-hidden="true">*</span></label>
                            <select id="rv_rating" name="rating" required>
                                <?php for ($r = 5; $r >= 1; $r--): ?>
                                <option value="<?php echo $r; ?>"<?php echo old('rating') === (string) $r ? ' selected' : ''; ?>><?php echo $r; ?> star<?php echo $r > 1 ? 's' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rv_review">Your review <span class="req" aria-hidden="true">*</span></label>
                        <textarea id="rv_review" name="review" required rows="5" maxlength="2000"><?php echo old('review'); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-star" aria-hidden="true"></i> Submit Review</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
