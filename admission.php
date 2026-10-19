<?php
require_once 'includes/config.php';
require_once 'includes/image_helper.php';
$page_title = content('seo_admission_title');
$meta_description = content('seo_admission_description');

$success_message = formFlash();   // set after a successful submission, see formDone()
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $error_message = formGuardError() ?: formValidate([
        'student_name' => ['Student name', 'required'],
        'father_name' => ['Father / guardian name', 'required'],
        'cnic_bform' => ['CNIC / B-Form number', 'required', 'cnic'],
        'phone' => ['phone number', 'required', 'phone'],
        'email' => ['Email', 'email'],
        'address' => ['Residential address', 'required'],
        'course_id' => ['Program / course', 'required'],
        'previous_education' => ['Previous education', 'required'],
    ]);
    if ($error_message === '') {
        // The chosen program must be one of the active courses offered in the list
        $course_check = mysqli_prepare($conn, "SELECT COUNT(*) FROM courses WHERE id = ? AND status = 'active'");
        $posted_course = intval($_POST['course_id']);
        mysqli_stmt_bind_param($course_check, 'i', $posted_course);
        mysqli_stmt_execute($course_check);
        mysqli_stmt_bind_result($course_check, $course_exists);
        mysqli_stmt_fetch($course_check);
        mysqli_stmt_close($course_check);
        if (!$course_exists) {
            $error_message = 'Please choose a program from the list.';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $error_message === '') {
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $father_name = mysqli_real_escape_string($conn, $_POST['father_name']);
    $cnic_bform = mysqli_real_escape_string($conn, $_POST['cnic_bform']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $course_id = mysqli_real_escape_string($conn, $_POST['course_id']);
    $previous_education = mysqli_real_escape_string($conn, $_POST['previous_education']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    // Look up the human-readable course name for the emails below - the
    // form only submits the numeric course_id, and showing that raw number
    // to the applicant ("your application for 5") looks broken.
    $course_name = $course_id;
    $course_name_result = mysqli_query($conn, "SELECT name FROM courses WHERE id = '$course_id'");
    if ($course_name_result && $course_name_row = mysqli_fetch_assoc($course_name_result)) {
        $course_name = $course_name_row['name'];
    }

    // Supporting document: PDF or photo/scan, checked by content, images compressed, random file name
    $document_path = '';
    $doc_error = '';
    $saved_doc = savePublicDocument($_FILES['documents'] ?? null, 'uploads/documents/', 'doc_' . time() . '_' . substr(uniqid(), -6));
    if (!empty($saved_doc['error'])) {
        $doc_error = $saved_doc['error'];
    } elseif (!empty($saved_doc['name'])) {
        $document_path = 'uploads/documents/' . $saved_doc['name'];
    }

    $insert_query = "INSERT INTO admissions (student_name, father_name, cnic_bform, phone, email, address, course_id, previous_education, documents, message, status, created_at)
                     VALUES ('$student_name', '$father_name', '$cnic_bform', '$phone', '$email', '$address', '$course_id', '$previous_education', '$document_path', '$message', 'pending', NOW())";

    if ($doc_error !== '') {
        $error_message = $doc_error;
    } elseif (mysqli_query($conn, $insert_query)) {
        // Branded emails: the original form values (not the SQL-escaped copies) are shown
        require_once __DIR__ . '/includes/email_helper.php';
        $f = function ($key) { return trim($_POST[$key] ?? ''); };

        // Notification to the school
        $message_admin = brandEmail('New admission application', '<p style="margin:0 0 6px;">A new admission application has been submitted on the website.</p>'
            . emailInfoTable([
                'Student name' => $f('student_name'),
                'Father / guardian' => $f('father_name'),
                'CNIC / B-Form' => $f('cnic_bform'),
                'Phone' => $f('phone'),
                'Email' => $f('email'),
                'Address' => $f('address'),
                'Program / class' => $course_name,
                'Previous education' => $f('previous_education'),
                'Additional information' => $f('message'),
                'Document attached' => $document_path !== '' ? 'Yes (open it in the admin panel)' : 'No',
                'Received' => date('d M Y, h:i A'),
            ])
            . emailNote('<strong>Action required:</strong> review this application under Admissions in the admin panel and contact the family.', 'action'));
        mail(ADMIN_EMAIL, 'New Admission Application - ' . getSiteName(), $message_admin, emailHeaders($f('email')));

        // Confirmation to the applicant (email is optional on the form)
        if ($f('email') !== '') {
            $message_student = brandEmail('Application received', '<p style="margin:0 0 12px;">Dear ' . htmlspecialchars($f('student_name')) . ',</p>'
                . '<p style="margin:0 0 12px;">Thank you for applying to <strong>' . htmlspecialchars(getSiteName()) . '</strong>.</p>'
                . emailNote('<strong>&#10003; Your application for ' . htmlspecialchars($course_name) . ' has been received.</strong>')
                . '<p style="margin:16px 0 6px;font-weight:700;color:' . EMAIL_OLIVE . ';">What happens next</p>'
                . '<ol style="margin:0 0 14px;padding-left:20px;">'
                . '<li>Our admission team reviews your application.</li>'
                . '<li>We verify your documents and details.</li>'
                . '<li>We call or email you within 2&ndash;3 working days &mdash; please keep your phone nearby.</li>'
                . '</ol>'
                . '<p style="margin:0 0 12px;">Questions? Call us on <strong>' . htmlspecialchars(getSitePhone()) . '</strong> (' . htmlspecialchars(getOfficeHours()) . ').</p>'
                . '<p style="margin:22px 0 0;">Best regards,<br>Admission Office<br><strong>' . htmlspecialchars(getSiteName()) . '</strong></p>'
                . '<p style="margin:18px 0 0;font-size:12.5px;color:' . EMAIL_MUTED . ';">This is an automatic confirmation; replies go to the school office.</p>');
            mail($f('email'), 'Application Received - ' . getSiteName(), $message_student, emailHeaders(hasSiteEmail() ? getSiteEmail() : ''));
        }

        $form_sent = true;

        formDone("Your admission application has been submitted successfully! A confirmation email has been sent to your email address. We will contact you soon.", 'form-status');
    } else {
        $error_message = "Error submitting application. Please try again.";
    }
}
?>
<?php include 'includes/header.php'; ?>

<!-- Page Header -->
<?php renderPageBanner('admission'); ?>

<!-- Admission Form -->
<section class="bg-light">
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success" id="form-status" role="status" style="max-width: 800px; margin-left: auto; margin-right: auto;">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error" style="max-width: 800px; margin-left: auto; margin-right: auto;">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <div style="max-width: 800px; margin: 0 auto;">
            <div class="card" style="padding: 40px;">
                <div class="text-center mb-30">
                    <span class="eyebrow"><?php echo htmlspecialchars(content('adm_form_eyebrow')); ?></span>
                    <h2 style="color: var(--ink);"><?php echo htmlspecialchars(content('adm_form_heading')); ?></h2>
                </div>

                <form method="POST" enctype="multipart/form-data" id="admissionForm" data-guard onsubmit="return validateForm('admissionForm')">
                    <?php echo formGuardFields(); ?>
                    <div class="grid-2" style="gap: 20px; align-items: start;">
                        <div class="form-group">
                            <label for="student_name">Student Name *</label>
                            <input type="text" id="student_name" name="student_name" required placeholder="Enter student's full name" value="<?php echo old('student_name'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="father_name">Father/Guardian Name *</label>
                            <input type="text" id="father_name" name="father_name" required placeholder="Enter father or guardian name" value="<?php echo old('father_name'); ?>">
                        </div>
                    </div>

                    <div class="grid-2" style="gap: 20px; align-items: start;">
                        <div class="form-group">
                            <label for="cnic_bform">CNIC / B-Form Number *</label>
                            <input type="text" id="cnic_bform" name="cnic_bform" required placeholder="e.g., 12345-1234567-1" value="<?php echo old('cnic_bform'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="previous_education">Previous Education *</label>
                            <input type="text" id="previous_education" name="previous_education" required placeholder="e.g., Matric from XYZ School, 85%" value="<?php echo old('previous_education'); ?>">
                        </div>
                    </div>

                    <div class="grid-2" style="gap: 20px; align-items: start;">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required placeholder="03001234567" value="<?php echo old('phone'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="your.email@example.com" value="<?php echo old('email'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Residential Address *</label>
                        <textarea id="address" name="address" required placeholder="Enter complete address"><?php echo old('address'); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="course_id">Select Program/Course *</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">-- Choose a Program --</option>
                            <?php
                            $courses_query = "SELECT DISTINCT id, name FROM courses WHERE status = 'active' ORDER BY display_order ASC, id ASC";
                            $courses_result = mysqli_query($conn, $courses_query);

                            if ($courses_result && mysqli_num_rows($courses_result) > 0) {
                                while ($course = mysqli_fetch_assoc($courses_result)) {
                                    $chosen_course = old('course_id') !== '' ? old('course_id') : ($_GET['course'] ?? '');
                                    $selected = ($chosen_course !== '' && $chosen_course == $course['id']) ? 'selected' : '';
                                    echo '<option value="' . $course['id'] . '" ' . $selected . '>' . htmlspecialchars($course['name']) . '</option>';
                                }
                            } else {
                                // Default options
                                $default_programs = [
                                    'Early Years',
                                    'Primary School',
                                    'Middle School',
                                    'Matric - Science Group',
                                    'Matric - Computer Science Group',
                                    'Matric - Arts Group'
                                ];
                                foreach ($default_programs as $program) {
                                    echo '<option value="' . $program . '">' . $program . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="documents">Upload Documents (Optional)</label>
                        <input type="file" id="documents" name="documents" accept=".pdf,.jpg,.jpeg,.png" style="padding: 10px;">
                        <small style="color: var(--text-light); display: block; margin-top: 5px;">Supported formats: PDF, JPG, PNG (Max 5MB)</small>
                    </div>

                    <div class="form-group">
                        <label for="message">Additional Information</label>
                        <textarea id="message" name="message" placeholder="Any additional information you'd like to share..."><?php echo old('message'); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 18px; padding: 15px;">
                        <i class="fas fa-paper-plane"></i> Submit Application
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Admission Process -->
<section>
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('adm_process_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('adm_process_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('adm_process_subtitle')); ?></p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 250px), 1fr)); gap: 30px;">
            <?php foreach (getFeatureCards('admission_process') as $step): ?>
            <div class="card reveal" style="text-align: center;">
                <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 20px; font-weight: 700;"><?php echo intval($step['display_order']); ?></div>
                <h3 style="margin-bottom: 12px;"><?php echo htmlspecialchars($step['title']); ?></h3>
                <p><?php echo htmlspecialchars($step['description']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Fee Information -->
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('adm_fee_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('adm_fee_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('adm_fee_subtitle')); ?></p>
        </div>

        <div class="card fee-card">
            <div style="text-align: center; margin-bottom: 30px;">
                <i class="fas fa-money-bill-wave" style="font-size: 60px; color: var(--accent-color);"></i>
            </div>

            <?php
            $fee_rows = [];
            $fee_result = mysqli_query($conn, "SELECT DISTINCT * FROM fee_items WHERE status = 'active' ORDER BY display_order ASC, id ASC");
            if ($fee_result) {
                while ($row = mysqli_fetch_assoc($fee_result)) {
                    $fee_rows[] = $row;
                }
            }
            ?>
            <ul style="list-style: none; padding: 0;">
                <?php foreach ($fee_rows as $i => $row): ?>
                <li style="display: flex; align-items: center; justify-content: space-between; gap: 15px; padding: 15px 0; <?php echo $i < count($fee_rows) - 1 ? 'border-bottom: 1px solid var(--border-color);' : ''; ?>">
                    <span style="display: flex; align-items: center; gap: 12px; font-weight: 600;">
                        <i class="fas <?php echo htmlspecialchars($row['icon']); ?>" style="color: var(--primary-color); width: 20px; text-align: center;"></i>
                        <?php echo htmlspecialchars($row['label']); ?>
                    </span>
                    <span class="fee-amount"><?php echo htmlspecialchars($row['amount']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>

            <div style="background: var(--bg-light); padding: 20px; border-radius: 10px; margin-top: 30px; text-align: center;">
                <p style="margin: 0;"><strong>Note:</strong> Flexible payment options available. Scholarships for deserving students.</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Info -->
<section>
    <div class="container">
        <div class="cta-banner reveal">
            <h2><?php echo htmlspecialchars(content('adm_cta_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('adm_cta_text')); ?></p>
            <div class="btn-group" style="justify-content: center;">
                <a href="contact" class="btn btn-secondary">Contact Us</a>
                <?php $admission_phone = trim(explode(',', getSitePhone())[0]); ?>
                <a href="tel:<?php echo htmlspecialchars(str_replace([' ', '-'], '', $admission_phone)); ?>" class="btn btn-outline"><i class="fas fa-phone"></i> Call Now</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
