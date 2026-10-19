<?php
/**
 * Branded HTML emails (admission and contact notifications).
 *
 * Every email the website sends uses the same layout as the site: the school logo
 * and name on the olive header, a maroon accent line, the content on a cream
 * background and the school's contact details in a charcoal footer.
 * Styles are inline and the layout is table-based, because most email apps
 * ignore <style> blocks and modern CSS layout.
 */

// Brand palette (same values as the tokens in css/style.css)
const EMAIL_OLIVE = '#123F73';      // navy: header band, headings
const EMAIL_OLIVE_LIGHT = '#123F73';
const EMAIL_MAROON = '#F8B923';     // gold accent line / action notes
const EMAIL_CREAM = '#F2F5F9';
const EMAIL_SAGE = '#E6EDF6';
const EMAIL_BORDER = '#D8E0EA';
const EMAIL_TEXT = '#253449';
const EMAIL_MUTED = '#4E5D72';
const EMAIL_FONT = "'Segoe UI', Helvetica, Arial, sans-serif";

/** Absolute URL of the site root, e.g. https://example.com (emails can't use relative links). */
function emailSiteUrl() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
}

/**
 * Wrap content in the branded email layout.
 * $heading is plain text; $content_html is already-escaped HTML.
 */
function brandEmail($heading, $content_html) {
    $site = htmlspecialchars(getSiteName());
    $base = emailSiteUrl();
    $logo = htmlspecialchars($base . '/' . getLogoSmallPath());
    $address = nl2br(htmlspecialchars(getSiteAddress()));
    $phone = htmlspecialchars(getSitePhone());
    $email = htmlspecialchars(getSiteEmail());
    $hours = htmlspecialchars(getOfficeHours());
    $heading = htmlspecialchars($heading);
    $year = date('Y');

    $contact_bits = [];
    if ($phone !== '') { $contact_bits[] = $phone; }
    if (hasSiteEmail()) { $contact_bits[] = '<a href="mailto:' . $email . '" style="color:#F8B923;text-decoration:none;">' . $email . '</a>'; }
    $contact_line = implode(' &nbsp;&middot;&nbsp; ', $contact_bits);

    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $heading . '</title></head>'
        . '<body style="margin:0;padding:0;background:' . EMAIL_CREAM . ';font-family:' . EMAIL_FONT . ';color:' . EMAIL_TEXT . ';">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . EMAIL_CREAM . ';padding:24px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#FFFFFF;border:1px solid ' . EMAIL_BORDER . ';border-radius:12px;overflow:hidden;">'
        // Header: logo + school name
        . '<tr><td style="background:' . EMAIL_OLIVE . ';padding:22px 28px;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0"><tr>'
        . '<td style="padding-right:14px;vertical-align:middle;"><img src="' . $logo . '" width="52" height="52" alt="' . $site . ' logo" style="display:block;object-fit:contain;"></td>'
        . '<td style="vertical-align:middle;"><div style="font-size:18px;font-weight:700;color:#FFFFFF;line-height:1.3;">' . $site . '</div>'
        . '<div style="font-size:13px;color:#C9D6E8;">' . htmlspecialchars(hasSiteAddress() ? trim((string) strtok(getSiteAddress(), "\n")) : content('site_tagline')) .'</div></td>'
        . '</tr></table></td></tr>'
        // Gold accent line
        . '<tr><td style="height:4px;background:' . EMAIL_MAROON . ';font-size:0;line-height:0;">&nbsp;</td></tr>'
        // Body
        . '<tr><td style="padding:30px 28px 26px;font-size:15px;line-height:1.65;color:' . EMAIL_TEXT . ';">'
        . '<h1 style="margin:0 0 16px;font-size:22px;line-height:1.3;color:' . EMAIL_OLIVE . ';">' . $heading . '</h1>'
        . $content_html
        . '</td></tr>'
        // Footer
        . '<tr><td style="background:#082B50;padding:20px 28px;font-size:12.5px;line-height:1.7;color:#B7C3D3;text-align:center;">'
        . '<div style="font-weight:700;color:#FFFFFF;font-size:14px;">' . $site . '</div>'
        . ($address !== '' ? '<div>' . $address . '</div>' : '')
        . ($contact_line !== '' ? '<div>' . $contact_line . '</div>' : '')
        . ($hours !== '' ? '<div>Office hours: ' . $hours . '</div>' : '')
        . '<div style="margin-top:8px;"><a href="' . htmlspecialchars($base) . '/" style="color:#F8B923;text-decoration:none;">' . htmlspecialchars(preg_replace('#^https?://#', '', $base)) . '</a> &nbsp;&middot;&nbsp; &copy; ' . $year . '</div>'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** Two-column details table; values are plain text (escaped here), labels too. */
function emailInfoTable(array $rows) {
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:18px 0;border:1px solid ' . EMAIL_BORDER . ';border-radius:8px;">';
    foreach ($rows as $label => $value) {
        $html .= '<tr>'
            . '<td style="padding:10px 14px;width:38%;background:' . EMAIL_SAGE . ';border-bottom:1px solid ' . EMAIL_BORDER . ';font-weight:600;font-size:14px;color:' . EMAIL_OLIVE . ';vertical-align:top;">' . htmlspecialchars($label) . '</td>'
            . '<td style="padding:10px 14px;border-bottom:1px solid ' . EMAIL_BORDER . ';font-size:14px;vertical-align:top;">' . nl2br(htmlspecialchars((string) $value)) . '</td>'
            . '</tr>';
    }
    return $html . '</table>';
}

/** Highlighted note. $tone: 'success' (navy) or 'action' (gold). $html is already-escaped HTML. */
function emailNote($html, $tone = 'success') {
    $bar = $tone === 'action' ? EMAIL_MAROON : EMAIL_OLIVE_LIGHT;
    $bg = $tone === 'action' ? '#FEF6E1' : EMAIL_SAGE;
    return '<div style="margin:18px 0;padding:14px 16px;background:' . $bg . ';border-left:4px solid ' . $bar . ';border-radius:6px;font-size:14.5px;">' . $html . '</div>';
}

/** Mail headers shared by every site email. */
function emailHeaders($reply_to = '') {
    $host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $from_name = str_replace(["\r", "\n", '"'], '', getSiteName());
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= 'From: ' . $from_name . ' <noreply@' . $host . ">\r\n";
    $reply_to = str_replace(["\r", "\n"], '', (string) $reply_to);
    if ($reply_to !== '' && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
        $headers .= 'Reply-To: ' . $reply_to . "\r\n";
    }
    return $headers;
}
