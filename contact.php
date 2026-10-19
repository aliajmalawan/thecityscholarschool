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

<!-- Page Header -->
<?php renderPageBanner('contact'); ?>

<!-- Contact Info Cards -->
<section class="bg-light">
    <div class="container">
        <h2 class="sr-only">Contact details</h2>
        <div class="card-grid">
            <div class="card contact-info-card">
                <div class="card-icon"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Visit Us</h3>
                <?php if (hasSiteAddress()): ?>
                    <p><?php echo htmlspecialchars(getSiteName()); ?><br><?php echo nl2br(htmlspecialchars(getSiteAddress())); ?></p>
                <?php else: ?>
                    <p class="is-placeholder">Campus address will be announced soon.</p>
                <?php endif; ?>
            </div>

            <div class="card contact-info-card">
                <div class="card-icon"><i class="fas fa-phone"></i></div>
                <h3>Call Us</h3>
                <?php if (hasSitePhone()): ?>
                    <p><a href="<?php echo htmlspecialchars(telHref(primaryPhone())); ?>"><?php echo htmlspecialchars(getSitePhone()); ?></a><?php if (hasOfficeHours()): ?><br><?php echo htmlspecialchars(getOfficeHours()); ?><?php endif; ?></p>
                <?php else: ?>
                    <p class="is-placeholder">Phone number coming soon. Please use the form below.</p>
                <?php endif; ?>
            </div>

            <div class="card contact-info-card">
                <div class="card-icon"><i class="fas fa-envelope"></i></div>
                <h3>Email Us</h3>
                <?php if (hasSiteEmail()): ?>
                    <p><a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><?php echo htmlspecialchars(getSiteEmail()); ?></a></p>
                <?php else: ?>
                    <p class="is-placeholder">Email address coming soon. Please use the form below.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form & Map -->
<section>
    <div class="container">
        <div class="grid-2 reveal" style="align-items: start;">
            <!-- Contact Form -->
            <div>
                <span class="eyebrow"><?php echo htmlspecialchars(content('contact_form_eyebrow')); ?></span>
                <h2 style="color: var(--ink); margin-bottom: 20px;"><?php echo htmlspecialchars(content('contact_form_heading')); ?></h2>

                <?php if ($success_message): ?>
                    <div class="alert alert-success" id="form-status" role="status">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error" role="alert">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="contactForm" onsubmit="return validateForm('contactForm')">
                    <?php echo formGuardFields(); ?>
                    <div class="grid-2" style="gap: 20px; align-items: start;">
                        <div class="form-group">
                            <label for="name">Your Name *</label>
                            <input type="text" id="name" name="name" required placeholder="Enter your name" value="<?php echo old('name'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required placeholder="your.email@example.com" value="<?php echo old('email'); ?>">
                        </div>
                    </div>

                    <div class="grid-2" style="gap: 20px; align-items: start;">
                        <div class="form-group">
                            <label for="phone">Phone Number *</label>
                            <input type="tel" id="phone" name="phone" required placeholder="03001234567" value="<?php echo old('phone'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <select id="subject" name="subject" required>
                                <option value="">-- Select Subject --</option>
                                <option value="Admission Inquiry"<?php echo old('subject') === 'Admission Inquiry' ? ' selected' : ''; ?>>Admission Inquiry</option>
                                <option value="Course Information"<?php echo old('subject') === 'Course Information' ? ' selected' : ''; ?>>Course Information</option>
                                <option value="Fee Inquiry"<?php echo old('subject') === 'Fee Inquiry' ? ' selected' : ''; ?>>Fee Inquiry</option>
                                <option value="General Question"<?php echo old('subject') === 'General Question' ? ' selected' : ''; ?>>General Question</option>
                                <option value="Feedback"<?php echo old('subject') === 'Feedback' ? ' selected' : ''; ?>>Feedback</option>
                                <option value="Complaint"<?php echo old('subject') === 'Complaint' ? ' selected' : ''; ?>>Complaint</option>
                                <option value="Other"<?php echo old('subject') === 'Other' ? ' selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="message">Your Message *</label>
                        <textarea id="message" name="message" required placeholder="Write your message here..."><?php echo old('message'); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

            <!-- Google Map & Additional Info -->
            <div>
                <span class="eyebrow"><?php echo htmlspecialchars(content('contact_map_eyebrow')); ?></span>
                <h2 style="color: var(--ink); margin-bottom: 20px;"><?php echo htmlspecialchars(content('contact_map_heading')); ?></h2>

                <!-- Google Map -->
                <div style="border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 30px; height: 400px; border: 2px solid var(--border-color); box-shadow: var(--shadow-sm);">
                    <iframe src="https://maps.google.com/maps?width=600&height=400&hl=en&q=<?php echo rawurlencode(content('contact_map_query')); ?>&t=&z=14&ie=UTF8&iwloc=B&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>

                <!-- Working Hours -->
                <div class="card">
                    <h3 style="color: var(--primary-color); margin-bottom: 20px;"><i class="fas fa-clock"></i> Working Hours</h3>
                    <ul class="hours-list">
                        <?php
                        // One "Day | Time" per line, edited under Page Content > Contact page
                        foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', content('contact_hours')))) as $hours_line):
                            [$hours_day, $hours_time] = array_pad(array_map('trim', explode('|', $hours_line, 2)), 2, '');
                            $hours_closed = strcasecmp($hours_time, 'Closed') === 0;
                        ?>
                        <li>
                            <span class="hours-day"><i class="fas <?php echo $hours_closed ? 'fa-calendar-times' : 'fa-calendar-day'; ?>"></i> <?php echo htmlspecialchars($hours_day); ?></span>
                            <span class="<?php echo $hours_closed ? 'hours-closed' : ''; ?>"><?php echo htmlspecialchars($hours_time); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="bg-light">
    <div class="container">
        <div class="section-header reveal">
            <span class="eyebrow"><?php echo htmlspecialchars(content('contact_faq_eyebrow')); ?></span>
            <h2><?php echo htmlspecialchars(content('contact_faq_heading')); ?></h2>
            <p><?php echo htmlspecialchars(content('contact_faq_subtitle')); ?></p>
        </div>

        <div class="faq-list">
            <?php
            $faqs_result = mysqli_query($conn, "SELECT DISTINCT * FROM faqs WHERE status = 'active' ORDER BY display_order ASC, id ASC");
            if ($faqs_result) {
                while ($faq = mysqli_fetch_assoc($faqs_result)) {
                    echo '<div class="faq-item">';
                    echo '<button type="button" class="faq-question"><span>' . htmlspecialchars($faq['question']) . '</span><i class="fas fa-chevron-down"></i></button>';
                    echo '<div class="faq-answer"><p>' . nl2br(htmlspecialchars($faq['answer'])) . '</p></div>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
