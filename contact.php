<?php
require_once 'includes/config.php';
$page_title = content('seo_contact_title');
$meta_description = content('seo_contact_description');

$success_message = formFlash();   // set after a successful submission, see formDone()
$error_message = '';

// Handle contact form submission
// The same checks the browser makes, repeated here because bots post without a browser
$contact_post_ok = function () {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    return $name !== '' && $message !== '' && filter_var($email, FILTER_VALIDATE_EMAIL);
};

if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($guard_problem = formGuardError()) !== '') {
    $error_message = $guard_problem;
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && !$contact_post_ok()) {
    $error_message = "Please fill in your name, a valid email address and your message.";
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once __DIR__ . '/includes/contact_handler.php';
    $saved = saveContactMessage([
        'name' => formText('name', 100),
        'email' => formText('email', 150),
        'phone' => formText('phone', 30),
        'subject' => formText('subject', 200),
        'message' => formText('message', 5000),
    ]);
    if ($saved) {
        $form_sent = true;
        formDone("Thank you for contacting us! We have received your message and will get back to you soon.", 'form-status');
    } else {
        $error_message = "Sorry, your message could not be sent. Please try again in a few minutes.";
    }
}
?>
<?php include 'includes/header.php'; ?>

<?php
$subjects = ['Admission Inquiry', 'Course Information', 'Fee Inquiry', 'General Question', 'Feedback', 'Complaint', 'Other'];
$hours_lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', content('contact_hours')))));
$map_src = mapEmbedSrc();
$faqs = [];
$faqs_result = mysqli_query($conn, "SELECT DISTINCT * FROM faqs WHERE status = 'active' ORDER BY display_order ASC, id ASC");
while ($faqs_result && ($faq = mysqli_fetch_assoc($faqs_result))) { $faqs[] = $faq; }
?>

<?php renderPageBanner('contact', false, 'fa-envelope'); ?>

<!-- Details + form -->
<section class="page-intro">
    <div class="container">
        <div class="contact-shell">
            <div class="contact-details">
                <span class="eyebrow">Contact Details</span>
                <h2 class="section-title">Get in Touch</h2>
                <p class="lead">Whether you are a parent, a student or a visitor, we are happy to help. Send us a message and our office will reply as soon as possible.</p>
                <?php contactDetailsList(); ?>
                <?php socialIcons(); ?>

                <?php if (!empty($hours_lines)): ?>
                <div class="aside-card hours-card">
                    <h3 class="aside-title"><i class="fas fa-clock" aria-hidden="true"></i> Working Hours</h3>
                    <ul class="hours-list">
                        <?php foreach ($hours_lines as $hours_line):
                            [$hours_day, $hours_time] = array_pad(array_map('trim', explode('|', $hours_line, 2)), 2, '');
                            $hours_closed = strcasecmp($hours_time, 'Closed') === 0;
                        ?>
                        <li><span class="hours-day"><?php echo htmlspecialchars($hours_day); ?></span><span class="<?php echo $hours_closed ? 'hours-closed' : ''; ?>"><?php echo htmlspecialchars($hours_time); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <div class="form-card" id="form-status">
                <span class="eyebrow"><?php echo htmlspecialchars(content('contact_form_eyebrow')); ?></span>
                <h2 class="form-card-title"><?php echo htmlspecialchars(content('contact_form_heading')); ?></h2>
                <p class="form-card-text">Fields marked <span class="req" aria-hidden="true">*</span><span class="sr-only">with an asterisk</span> are required.</p>

                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="status"><i class="fas fa-circle-check" aria-hidden="true"></i> <span><?php echo htmlspecialchars($success_message); ?></span></div>
                <?php endif; ?>
                <?php if ($error_message): ?>
                    <div class="alert alert-error" role="alert"><i class="fas fa-circle-exclamation" aria-hidden="true"></i> <span><?php echo htmlspecialchars($error_message); ?></span></div>
                <?php endif; ?>

                <form method="POST" action="contact#form-status" id="contactForm" data-validate novalidate>
                    <?php echo formGuardFields(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Your name <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="name" name="name" required maxlength="100" autocomplete="name" value="<?php echo old('name'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="req" aria-hidden="true">*</span></label>
                            <input type="email" id="email" name="email" required maxlength="150" autocomplete="email" value="<?php echo old('email'); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone number <span class="optional">(optional)</span></label>
                            <input type="tel" id="phone" name="phone" maxlength="30" autocomplete="tel" inputmode="tel" placeholder="03001234567" value="<?php echo old('phone'); ?>">
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject <span class="req" aria-hidden="true">*</span></label>
                            <select id="subject" name="subject" required>
                                <option value="">Choose a subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?php echo htmlspecialchars($subject); ?>"<?php echo old('subject') === htmlspecialchars($subject, ENT_QUOTES) ? ' selected' : ''; ?>><?php echo htmlspecialchars($subject); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="message">Message <span class="req" aria-hidden="true">*</span></label>
                        <textarea id="message" name="message" rows="5" required maxlength="5000"><?php echo old('message'); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane" aria-hidden="true"></i> Send Message</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php if ($map_src !== ''): ?>
<!-- Map: only once an embed code has been saved in Settings -->
<section class="bg-light">
    <div class="container">
        <?php sectionHeader(content('contact_map_eyebrow'), content('contact_map_heading')); ?>
        <div class="contact-map is-wide">
            <iframe src="<?php echo htmlspecialchars($map_src); ?>" title="Map showing the location of <?php echo htmlspecialchars(getSiteName()); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($faqs)): ?>
<section class="<?php echo $map_src !== '' ? '' : 'bg-light'; ?>">
    <div class="container">
        <?php sectionHeader(content('contact_faq_eyebrow'), content('contact_faq_heading'), content('contact_faq_subtitle')); ?>
        <div class="faq-list">
            <?php foreach ($faqs as $i => $faq): ?>
            <div class="faq-item">
                <button type="button" class="faq-question" aria-expanded="false" aria-controls="faq-answer-<?php echo $i; ?>" id="faq-q-<?php echo $i; ?>"><span><?php echo htmlspecialchars($faq['question']); ?></span><i class="fas fa-chevron-down" aria-hidden="true"></i></button>
                <div class="faq-answer" id="faq-answer-<?php echo $i; ?>" role="region" aria-labelledby="faq-q-<?php echo $i; ?>"><p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
