<?php
/**
 * Saves a message from any public contact-style form (Contact page, homepage contact
 * form, homepage admission inquiry) into the contacts table and sends the two emails.
 *
 * Returns true only when the message was stored; the caller shows a thank-you message
 * only then. Emails are sent after the save and never decide success (mail() on shared
 * hosting can fail silently, but the message is already in Contact Messages).
 *
 * $f: name, email, phone, subject, message (original, unescaped form values).
 * $intro: one line for the school's notification email.
 */
function saveContactMessage(array $f, $intro = 'Someone has sent a message through the website contact form.') {
    global $conn;
    $f = array_map(fn($v) => trim((string) $v), $f + ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => '']);

    // PHP 8 reports database errors as exceptions; either way the visitor gets a friendly
    // "could not be sent" message and the details go to the error log.
    try {
        $stmt = mysqli_prepare($conn, "INSERT INTO contacts (name, email, phone, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'unread', NOW())");
        if (!$stmt) {
            error_log('Contact form: could not prepare insert: ' . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, 'sssss', $f['name'], $f['email'], $f['phone'], $f['subject'], $f['message']);
        $saved = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (mysqli_sql_exception $e) {
        error_log('Contact form: insert failed: ' . $e->getMessage());
        return false;
    }
    if (!$saved) {
        error_log('Contact form: insert failed: ' . mysqli_error($conn));
        return false;
    }

    require_once __DIR__ . '/email_helper.php';

    // Notification to the school
    $reply_bits = [];
    if ($f['email'] !== '') {
        $reply_bits[] = 'reply to <a href="mailto:' . htmlspecialchars($f['email']) . '" style="color:' . EMAIL_PRIMARY . ';">' . htmlspecialchars($f['email']) . '</a>';
    }
    if ($f['phone'] !== '') {
        $reply_bits[] = 'call ' . htmlspecialchars($f['phone']);
    }
    $message_admin = brandEmail('New ' . ($f['subject'] !== '' ? $f['subject'] : 'contact message'), '<p style="margin:0 0 6px;">' . htmlspecialchars($intro) . '</p>'
        . emailInfoTable([
            'Name' => $f['name'],
            'Email' => $f['email'],
            'Phone' => $f['phone'],
            'Subject' => $f['subject'],
            'Message' => $f['message'],
            'Received' => date('d M Y, h:i A'),
        ])
        . emailNote('<strong>Quick action:</strong> ' . ($reply_bits ? implode(' or ', $reply_bits) . '. ' : '') . 'The message is also saved under Contact Messages in the admin panel.', 'action'));
    mail(ADMIN_EMAIL, 'New ' . ($f['subject'] !== '' ? $f['subject'] : 'Contact Message') . ' - ' . getSiteName(), $message_admin, emailHeaders($f['email']));

    // Confirmation to the sender (only when they gave an email address)
    if ($f['email'] !== '') {
        $sooner = hasSitePhone()
            ? '<p style="margin:0 0 12px;">Need help sooner? Call us on <strong>' . htmlspecialchars(getSitePhone()) . '</strong>'
                . (hasOfficeHours() ? ' during office hours (' . htmlspecialchars(getOfficeHours()) . ')' : '') . '.</p>'
            : '';
        $message_sender = brandEmail('Thank you for contacting us', '<p style="margin:0 0 12px;">Dear ' . htmlspecialchars($f['name']) . ',</p>'
            . emailNote('<strong>&#10003; We have received your message.</strong>')
            . '<p style="margin:0 0 12px;">Your message' . ($f['subject'] !== '' ? ' about &ldquo;<strong>' . htmlspecialchars($f['subject']) . '</strong>&rdquo;' : '') . ' has reached our team. We will get back to you as soon as possible.</p>'
            . $sooner
            . '<p style="margin:22px 0 0;">Best regards,<br><strong>' . htmlspecialchars(getSiteName()) . '</strong></p>'
            . '<p style="margin:18px 0 0;font-size:12.5px;color:' . EMAIL_MUTED . ';">This is an automatic confirmation; replies go to the school office.</p>');
        mail($f['email'], 'Thank You for Contacting ' . getSiteName(), $message_sender, emailHeaders(hasSiteEmail() ? getSiteEmail() : ''));
    }

    return true;
}
