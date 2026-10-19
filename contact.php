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
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject'] ?? ''));
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));

    $insert_query = "INSERT INTO contacts (name, email, phone, subject, message, status, created_at)
                     VALUES ('$name', '$email', '$phone', '$subject', '$message', 'unread', NOW())";

    if (mysqli_query($conn, $insert_query)) {
        // Branded emails: the original form values (not the SQL-escaped copies) are shown
        require_once __DIR__ . '/includes/email_helper.php';
        $f_name = trim($_POST['name'] ?? '');
        $f_email = trim($_POST['email'] ?? '');
        $f_phone = trim($_POST['phone'] ?? '');
        $f_subject = trim($_POST['subject'] ?? '');
        $f_message = trim($_POST['message'] ?? '');

        // Notification to the school
        $message_admin = brandEmail('New contact message', '<p style="margin:0 0 6px;">Someone has sent a message through the website contact form.</p>'
            . emailInfoTable([
                'Name' => $f_name,
                'Email' => $f_email,
                'Phone' => $f_phone,
                'Subject' => $f_subject,
                'Message' => $f_message,
                'Received' => date('d M Y, h:i A'),
            ])
            . emailNote('<strong>Quick action:</strong> reply to <a href="mailto:' . htmlspecialchars($f_email) . '" style="color:' . EMAIL_OLIVE . ';">' . htmlspecialchars($f_email) . '</a> or call ' . htmlspecialchars($f_phone) . '. The message is also saved under Contact Messages in the admin panel.', 'action'));
        mail(ADMIN_EMAIL, 'New Contact Message - ' . getSiteName(), $message_admin, emailHeaders($f_email));

        // Confirmation to the sender
        $message_sender = brandEmail('Thank you for contacting us', '<p style="margin:0 0 12px;">Dear ' . htmlspecialchars($f_name) . ',</p>'
            . emailNote('<strong>&#10003; We have received your message.</strong>')
            . '<p style="margin:0 0 12px;">Your message about &ldquo;<strong>' . htmlspecialchars($f_subject) . '</strong>&rdquo; has reached our team. We will get back to you within <strong>24&ndash;48 hours</strong>.</p>'
            . '<p style="margin:0 0 12px;">Need help sooner? Call us on <strong>' . htmlspecialchars(getSitePhone()) . '</strong> during office hours (' . htmlspecialchars(getOfficeHours()) . ').</p>'
            . '<p style="margin:22px 0 0;">Best regards,<br><strong>' . htmlspecialchars(getSiteName()) . '</strong></p>'
            . '<p style="margin:18px 0 0;font-size:12.5px;color:' . EMAIL_MUTED . ';">This is an automatic confirmation; replies go to the school office.</p>');
        mail($f_email, 'Thank You for Contacting ' . getSiteName(), $message_sender, emailHeaders(hasSiteEmail() ? getSiteEmail() : ''));

        $form_sent = true;

        formDone("Thank you for contacting us! A confirmation email has been sent to your email address. We will get back to you soon.", 'form-status');
    } else {
        $error_message = "Error sending message. Please try again.";
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
            <div class="card" style="text-align: center;">
                <div class="card-icon" style="margin: 0 auto 20px;">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h3>Visit Us</h3>
                <p><?php echo htmlspecialchars(getSiteName()); ?><br><?php echo nl2br(htmlspecialchars(getSiteAddress())); ?></p>
            </div>

            <div class="card" style="text-align: center;">
                <div class="card-icon" style="margin: 0 auto 20px;">
                    <i class="fas fa-phone"></i>
                </div>
                <h3>Call Us</h3>
                <p><a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', getSitePhone())); ?>"><?php echo htmlspecialchars(getSitePhone()); ?></a><br>
                Mon - Sat: 8:00 AM - 5:00 PM</p>
            </div>

            <div class="card" style="text-align: center;">
                <div class="card-icon" style="margin: 0 auto 20px;">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3>Email Us</h3>
                <p><a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><?php echo htmlspecialchars(getSiteEmail()); ?></a><br>
                We reply within 24 hours</p>
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
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo $error_message; ?></span>
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
