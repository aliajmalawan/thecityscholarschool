<?php
require_once 'includes/config.php';
require_once 'includes/image_helper.php';
$page_title = content('seo_admission_title');
$meta_description = content('seo_admission_description');

$success_message = formFlash();   // set after a successful submission, see formDone()
$error_message = '';

// Program list: active courses (AdminCP -> Courses), or the school levels while none are published
$count_result = mysqli_query($conn, "SELECT COUNT(*) AS c FROM courses WHERE status = 'active'");
$has_active_courses = $count_result && (int) mysqli_fetch_assoc($count_result)['c'] > 0;
$level_programs = array_values(array_filter(array_map(fn($n) => content("academics_{$n}_title"), [1, 2, 3, 4])));

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
        // The chosen program must be one of the options in the list: an active course,
        // or (while no courses are published) one of the school levels
        if ($has_active_courses) {
            $course_check = mysqli_prepare($conn, "SELECT COUNT(*) FROM courses WHERE id = ? AND status = 'active'");
            $posted_course = intval($_POST['course_id']);
            mysqli_stmt_bind_param($course_check, 'i', $posted_course);
            mysqli_stmt_execute($course_check);
            mysqli_stmt_bind_result($course_check, $course_exists);
            mysqli_stmt_fetch($course_check);
            mysqli_stmt_close($course_check);
        } else {
            $course_exists = in_array(trim((string) $_POST['course_id']), $level_programs, true);
        }
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

    // PHP 8 reports database errors as exceptions: treat them like any failed save
    try {
        $saved = $doc_error === '' && mysqli_query($conn, $insert_query);
    } catch (mysqli_sql_exception $e) {
        error_log('Admission form save failed: ' . $e->getMessage());
        $saved = false;
    }

    if ($doc_error !== '') {
        $error_message = $doc_error;
    } elseif ($saved) {
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
                . '<p style="margin:16px 0 6px;font-weight:700;color:' . EMAIL_PRIMARY . ';">What happens next</p>'
                . '<ol style="margin:0 0 14px;padding-left:20px;">'
                . '<li>Our admission team reviews your application.</li>'
                . '<li>We verify your documents and details.</li>'
                . '<li>We call or email you within 2&ndash;3 working days &mdash; please keep your phone nearby.</li>'
                . '</ol>'
                . (hasSitePhone() ? '<p style="margin:0 0 12px;">Questions? Call us on <strong>' . htmlspecialchars(getSitePhone()) . '</strong>' . (hasOfficeHours() ? ' (' . htmlspecialchars(getOfficeHours()) . ')' : '') . '.</p>' : '')
                . '<p style="margin:22px 0 0;">Best regards,<br>Admission Office<br><strong>' . htmlspecialchars(getSiteName()) . '</strong></p>'
                . '<p style="margin:18px 0 0;font-size:12.5px;color:' . EMAIL_MUTED . ';">This is an automatic confirmation; replies go to the school office.</p>');
            mail($f('email'), 'Application Received - ' . getSiteName(), $message_student, emailHeaders(hasSiteEmail() ? getSiteEmail() : ''));
        }

        $form_sent = true;

        formDone('Thank you! Your admission application has been received. Our admissions office will contact you soon'
            . ($f('email') !== '' ? ', and a confirmation has been sent to your email address.' : '.'), 'form-status');
    } else {
        $error_message = 'Sorry, your application could not be submitted right now. Please try again in a few minutes.';
    }
}
?>
<?php include 'includes/header.php'; ?>

<?php
$adm_steps = [];
foreach (getFeatureCards('admission_process') as $step) {
    $adm_steps[$step['title']] = $step['description'];   // the DB can hold duplicate rows, so key by title
}
$adm_docs = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', content('home_adm_docs')))));
$fee_rows = [];
$fee_result = mysqli_query($conn, "SELECT DISTINCT * FROM fee_items WHERE status = 'active' ORDER BY display_order ASC, id ASC");
while ($fee_result && ($row = mysqli_fetch_assoc($fee_result))) {
    $fee_rows[] = $row;
}
$program_options = [];
$courses_result = mysqli_query($conn, "SELECT DISTINCT id, name FROM courses WHERE status = 'active' ORDER BY display_order ASC, id ASC");
while ($courses_result && ($course = mysqli_fetch_assoc($courses_result))) {
    $program_options[$course['id']] = $course['name'];
}
$chosen_course = old('course_id') !== '' ? old('course_id') : (string) ($_GET['course'] ?? '');
?>

<?php renderPageBanner('admission', false, 'fa-pen-to-square'); ?>

<!-- Application form + how to apply -->
<section class="page-intro bg-light">
    <div class="container">
        <div class="apply-layout">
            <div class="form-card" id="form-status">
                <span class="eyebrow"><?php echo htmlspecialchars(content('adm_form_eyebrow')); ?></span>
                <h2 class="form-card-title"><?php echo htmlspecialchars(content('adm_form_heading')); ?></h2>
                <p class="form-card-text">Please fill in the details below. Fields marked <span class="req" aria-hidden="true">*</span><span class="sr-only">with an asterisk</span> are required.</p>

                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i> <span><?php echo htmlspecialchars($success_message); ?></span></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <span><?php echo htmlspecialchars($error_message); ?></span></div>
                <?php endif; ?>

                <form method="POST" action="admission#form-status" enctype="multipart/form-data" id="admissionForm" data-validate novalidate>
                    <?php echo formGuardFields(); ?>
                    <fieldset class="form-fieldset">
                        <legend>Student details</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="student_name">Student name <span class="req" aria-hidden="true">*</span></label>
                                <input type="text" id="student_name" name="student_name" required maxlength="100" value="<?php echo old('student_name'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cnic_bform">CNIC / B-Form number <span class="req" aria-hidden="true">*</span></label>
                                <input type="text" id="cnic_bform" name="cnic_bform" required maxlength="15" inputmode="numeric" placeholder="12345-1234567-1" value="<?php echo old('cnic_bform'); ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="course_id">Program / class applying for <span class="req" aria-hidden="true">*</span></label>
                                <select id="course_id" name="course_id" required>
                                    <option value="">Choose a program</option>
                                    <?php if (!empty($program_options)): ?>
                                        <?php foreach ($program_options as $id => $name): ?>
                                        <option value="<?php echo intval($id); ?>"<?php echo $chosen_course === (string) $id ? ' selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($level_programs as $program): ?>
                                        <option value="<?php echo htmlspecialchars($program); ?>"<?php echo $chosen_course === $program ? ' selected' : ''; ?>><?php echo htmlspecialchars($program); ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="previous_education">Previous school / class <span class="req" aria-hidden="true">*</span></label>
                                <input type="text" id="previous_education" name="previous_education" required maxlength="255" placeholder="e.g. Class 4, ABC School" value="<?php echo old('previous_education'); ?>">
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-fieldset">
                        <legend>Parent / guardian details</legend>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="father_name">Father / guardian name <span class="req" aria-hidden="true">*</span></label>
                                <input type="text" id="father_name" name="father_name" required maxlength="100" autocomplete="name" value="<?php echo old('father_name'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone number <span class="req" aria-hidden="true">*</span></label>
                                <input type="tel" id="phone" name="phone" required maxlength="30" autocomplete="tel" inputmode="tel" placeholder="03001234567" value="<?php echo old('phone'); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="optional">(optional)</span></label>
                            <input type="email" id="email" name="email" maxlength="150" autocomplete="email" value="<?php echo old('email'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="address">Home address <span class="req" aria-hidden="true">*</span></label>
                            <textarea id="address" name="address" rows="2" required autocomplete="street-address"><?php echo old('address'); ?></textarea>
                        </div>
                    </fieldset>

                    <fieldset class="form-fieldset">
                        <legend>Documents and notes</legend>
                        <div class="form-group">
                            <label for="documents">Upload documents <span class="optional">(optional)</span></label>
                            <input type="file" id="documents" name="documents" accept=".pdf,.jpg,.jpeg,.png" aria-describedby="documents-hint">
                            <p class="field-hint" id="documents-hint">PDF, JPG or PNG, up to 5 MB.</p>
                        </div>
                        <div class="form-group">
                            <label for="message">Anything else we should know? <span class="optional">(optional)</span></label>
                            <textarea id="message" name="message" rows="3"><?php echo old('message'); ?></textarea>
                        </div>
                    </fieldset>

                    <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-paper-plane" aria-hidden="true"></i> Submit Application</button>
                </form>
            </div>

            <aside class="apply-aside">
                <?php if (!empty($adm_steps)): ?>
                <div class="aside-card">
                    <h2 class="aside-title"><?php echo htmlspecialchars(content('adm_process_heading')); ?></h2>
                    <ol class="admission-steps is-light">
                        <?php foreach ($adm_steps as $step_title => $step_desc): ?>
                        <li><strong><?php echo htmlspecialchars($step_title); ?></strong><span><?php echo htmlspecialchars($step_desc); ?></span></li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>

                <?php if (!empty($adm_docs)): ?>
                <div class="aside-card">
                    <h2 class="aside-title">Documents to Bring</h2>
                    <ul class="check-list">
                        <?php foreach ($adm_docs as $doc): ?>
                        <li><i class="fas fa-circle-check" aria-hidden="true"></i> <?php echo htmlspecialchars($doc); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="aside-note"><?php echo htmlspecialchars(content('home_adm_docs_note')); ?></p>
                </div>
                <?php endif; ?>

                <div class="aside-card is-dark">
                    <h2 class="aside-title">Have a Question First?</h2>
                    <p>Send a short inquiry and our admissions office will get back to you.</p>
                    <a href="./#inquiry" class="btn btn-secondary btn-sm">Send an Inquiry <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
            </aside>
        </div>
    </div>
</section>

<!-- Fees (AdminCP -> Fee Items) -->
<section>
    <div class="container">
        <?php sectionHeader(content('adm_fee_eyebrow'), content('adm_fee_heading'), content('adm_fee_subtitle')); ?>
        <?php if (!empty($fee_rows)): ?>
        <div class="fee-panel">
            <ul class="fee-list">
                <?php foreach ($fee_rows as $row): ?>
                <li>
                    <span class="fee-label"><i class="fas <?php echo htmlspecialchars($row['icon'] ?: 'fa-receipt'); ?>" aria-hidden="true"></i> <?php echo htmlspecialchars($row['label']); ?></span>
                    <span class="fee-amount"><?php echo htmlspecialchars($row['amount']); ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <p class="fee-note"><i class="fas fa-circle-info" aria-hidden="true"></i> <?php echo htmlspecialchars(content('adm_fee_note')); ?></p>
        </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="empty-state-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span>
                <h3>Fee schedule on request</h3>
                <p><?php echo htmlspecialchars(content('adm_fee_empty')); ?></p>
                <a href="contact" class="btn btn-primary btn-sm">Contact Admissions</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php ctaPanel(content('adm_cta_heading'), content('adm_cta_text'), array_merge(
    [['Contact Admissions', 'contact', 'gold', 'fa-envelope'], ['Send an Inquiry', './#inquiry', 'outline']],
    hasSitePhone() ? [['Call ' . primaryPhone(), telHref(primaryPhone()), 'outline', 'fa-phone']] : []
)); ?>

<?php include 'includes/footer.php'; ?>
