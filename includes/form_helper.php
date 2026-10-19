<?php
/**
 * Protection and server-side checks for the public forms (contact, admission, alumni).
 *
 * Browsers already check the fields, but bots post straight to the server, so every
 * rule that matters is repeated here. Spam is stopped without a CAPTCHA:
 *  - a hidden "website" field that people never see but form-filling bots do fill in,
 *  - a signed timestamp: a form sent less than 3 seconds after it was shown is a bot,
 *  - at most 5 submissions per 10 minutes from one browser session.
 */

const FORM_MIN_SECONDS = 3;
const FORM_MAX_PER_WINDOW = 5;
const FORM_WINDOW_SECONDS = 600;

/** Hidden fields to print inside every public <form>. */
function formGuardFields() {
    if (empty($_SESSION['form_key'])) {
        $_SESSION['form_key'] = bin2hex(random_bytes(16));
    }
    $t = time();
    $sig = hash_hmac('sha256', (string) $t, $_SESSION['form_key']);
    return '<div class="form-hp" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>'
        . '<input type="hidden" name="form_ts" value="' . $t . '.' . $sig . '">';
}

/** '' when the submission looks like a person, otherwise a message to show. */
function formGuardError() {
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        return 'Your message could not be sent. Please try again.';
    }
    [$t, $sig] = array_pad(explode('.', (string) ($_POST['form_ts'] ?? ''), 2), 2, '');
    $expected = hash_hmac('sha256', (string) (int) $t, (string) ($_SESSION['form_key'] ?? ''));
    if ($t === '' || !hash_equals($expected, (string) $sig)) {
        return 'This form has expired. Please reload the page and send it again.';
    }
    if (time() - (int) $t < FORM_MIN_SECONDS) {
        return 'That was very quick. Please check the form and send it again.';
    }
    $recent = array_filter($_SESSION['form_posts'] ?? [], fn($ts) => $ts > time() - FORM_WINDOW_SECONDS);
    if (count($recent) >= FORM_MAX_PER_WINDOW) {
        return 'You have sent several forms in a short time. Please wait a few minutes and try again.';
    }
    $recent[] = time();
    $_SESSION['form_posts'] = array_values($recent);
    return '';
}

/** Trimmed text from the form, cut to $max characters. */
function formText($key, $max = 255) {
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $max);
}

/**
 * Check fields; returns the first problem as a message, or ''.
 * $rules: field => [label, rule, ...] where rules are 'required', 'email', 'phone', 'cnic', 'year'.
 */
function formValidate(array $rules) {
    foreach ($rules as $key => $spec) {
        $label = array_shift($spec);
        $value = trim((string) ($_POST[$key] ?? ''));
        if (in_array('required', $spec, true) && $value === '') {
            return "Please fill in: $label.";
        }
        if ($value === '') {
            continue;
        }
        if (in_array('email', $spec, true) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "Please enter a valid email address.";
        }
        if (in_array('phone', $spec, true) && !preg_match('/^\+?[0-9]{10,13}$/', preg_replace('/[\s()-]+/', '', $value))) {
            return "Please enter a valid $label (for example 03001234567).";
        }
        if (in_array('cnic', $spec, true) && !preg_match('/^[0-9]{5}-?[0-9]{7}-?[0-9]$/', $value)) {
            return "Please enter the $label as 13 digits (for example 12345-1234567-1).";
        }
        if (in_array('year', $spec, true) && (!ctype_digit($value) || (int) $value < 1950 || (int) $value > (int) date('Y') + 1)) {
            return "Please enter a valid $label.";
        }
    }
    return '';
}

/**
 * Value to put back into a field after the form was rejected, so nobody has to type
 * everything again. Empty after a successful submission (the form starts fresh) and
 * on a normal page load. Already escaped for use inside value="" or a textarea.
 */
function old($key) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return '';
    }
    if (!empty($GLOBALS['form_sent'])) {
        return '';   // pages set $form_sent = true after a successful submission
    }
    return htmlspecialchars(trim((string) ($_POST[$key] ?? '')), ENT_QUOTES);
}

/**
 * After a successful submission: remember the thank-you message and reload the page
 * with a normal GET request (Post/Redirect/Get). Refreshing the page then can't send
 * the same application or message a second time. $anchor scrolls back to the form.
 */
function formDone($message, $anchor = '') {
    $_SESSION['form_flash'] = $message;
    $path = strtok($_SERVER['REQUEST_URI'], '?');
    header('Location: ' . $path . '?sent=1' . ($anchor !== '' ? '#' . $anchor : ''), true, 303);
    exit;
}

/** The thank-you message saved by formDone(), shown once. */
function formFlash() {
    $message = $_SESSION['form_flash'] ?? '';
    unset($_SESSION['form_flash']);
    return $message;
}
