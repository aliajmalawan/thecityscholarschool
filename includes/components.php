<?php
/**
 * Small reusable building blocks for the public pages, so every page uses the
 * same markup (and therefore the same styles) for its headings, cards, calls to
 * action and contact details. All text passed in is plain text; it is escaped here.
 */

/** Centered section heading: small label, title, optional subtitle. */
function sectionHeader($eyebrow, $title, $subtitle = '') {
    echo '<div class="section-header">';
    if ($eyebrow !== '') {
        echo '<span class="eyebrow">' . htmlspecialchars($eyebrow) . '</span>';
    }
    echo '<h2>' . htmlspecialchars($title) . '</h2>';
    if ($subtitle !== '') {
        echo '<p>' . htmlspecialchars($subtitle) . '</p>';
    }
    echo '</div>';
}

/**
 * Grid of icon cards (Why Choose Us, Core Values, Faculty highlights, ...).
 * $cards: rows with icon (full Font Awesome class, e.g. "fas fa-star"), title, description.
 */
function iconCardGrid(array $cards) {
    if (empty($cards)) {
        return;
    }
    echo '<div class="why-grid">';
    foreach ($cards as $card) {
        echo '<article class="why-card">';
        echo '<span class="why-icon"><i class="' . htmlspecialchars($card['icon'] ?: 'fas fa-star') . '" aria-hidden="true"></i></span>';
        echo '<h3>' . htmlspecialchars($card['title']) . '</h3>';
        if (!empty($card['description'])) {
            echo '<p>' . htmlspecialchars($card['description']) . '</p>';
        }
        echo '</article>';
    }
    echo '</div>';
}

/**
 * Cards that link to related pages ("Explore Academics", "Campus Life" hub).
 * $links: [icon (fa-*), title, text, href]
 */
function linkCards(array $links) {
    echo '<div class="link-grid">';
    foreach ($links as [$icon, $title, $text, $href]) {
        echo '<a href="' . htmlspecialchars($href) . '" class="link-card">';
        echo '<span class="link-card-icon"><i class="fas ' . htmlspecialchars($icon) . '" aria-hidden="true"></i></span>';
        echo '<span class="link-card-body"><strong>' . htmlspecialchars($title) . '</strong><span>' . htmlspecialchars($text) . '</span></span>';
        echo '<i class="fas fa-arrow-right link-card-arrow" aria-hidden="true"></i>';
        echo '</a>';
    }
    echo '</div>';
}

/**
 * Navy call-to-action panel at the bottom of a page.
 * $buttons: [label, href, variant ('gold' | 'outline'), icon (fa-*, optional)]
 */
function ctaPanel($heading, $text, array $buttons) {
    echo '<section class="cta-section"><div class="container"><div class="cta-panel">';
    echo '<div class="cta-panel-copy"><h2>' . htmlspecialchars($heading) . '</h2>';
    if ($text !== '') {
        echo '<p>' . htmlspecialchars($text) . '</p>';
    }
    echo '</div>';
    if (!empty($buttons)) {
        echo '<div class="btn-group">';
        foreach ($buttons as $b) {
            [$label, $href, $variant] = $b;
            $icon = $b[3] ?? '';
            echo '<a href="' . htmlspecialchars($href) . '" class="btn ' . ($variant === 'gold' ? 'btn-secondary' : 'btn-outline') . '">'
                . ($icon !== '' ? '<i class="fas ' . htmlspecialchars($icon) . '" aria-hidden="true"></i> ' : '')
                . htmlspecialchars($label) . '</a>';
        }
        echo '</div>';
    }
    echo '</div></div></section>';
}

/** Standard bottom panel: apply + contact (+ call when a phone number is set). */
function admissionsCta($heading, $text) {
    $buttons = [['Apply for Admission', 'admission', 'gold', 'fa-pen-to-square'], ['Contact Us', 'contact', 'outline']];
    if (hasSitePhone()) {
        $buttons[] = ['Call ' . primaryPhone(), telHref(primaryPhone()), 'outline', 'fa-phone'];
    }
    ctaPanel($heading, $text, $buttons);
}

/**
 * The school's contact details as a list (address, phone, email, office hours).
 * Anything not filled in under Settings shows a short "coming soon" note instead.
 */
function contactDetailsList() {
    $rows = [
        ['fa-location-dot', 'Address', hasSiteAddress() ? nl2br(htmlspecialchars(getSiteAddress())) : '', 'To be announced'],
        ['fa-phone', 'Phone', hasSitePhone() ? '<a href="' . htmlspecialchars(telHref(primaryPhone())) . '">' . htmlspecialchars(getSitePhone()) . '</a>' : '', 'Coming soon'],
        ['fa-envelope', 'Email', hasSiteEmail() ? '<a href="mailto:' . htmlspecialchars(getSiteEmail()) . '">' . htmlspecialchars(getSiteEmail()) . '</a>' : '', 'Coming soon'],
        ['fa-clock', 'Office hours', hasOfficeHours() ? htmlspecialchars(getOfficeHours()) : '', 'To be announced'],
    ];
    echo '<ul class="contact-list">';
    foreach ($rows as [$icon, $label, $html, $placeholder]) {
        echo '<li><span class="contact-icon"><i class="fas ' . $icon . '" aria-hidden="true"></i></span><div><strong>' . $label . '</strong>';
        echo $html !== '' ? '<span>' . $html . '</span>' : '<span class="is-placeholder">' . $placeholder . '</span>';
        echo '</div></li>';
    }
    echo '</ul>';
}

/** Social profile icons, only for links that are actually set in Settings. */
function socialIcons($class = 'contact-social') {
    $links = array_filter(getSocialMedia());
    if (empty($links)) {
        return false;
    }
    $icons = ['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'twitter' => 'fa-x-twitter'];
    echo '<div class="' . htmlspecialchars($class) . '">';
    foreach ($icons as $key => $icon) {
        if (empty($links[$key])) continue;
        echo '<a href="' . htmlspecialchars($links[$key]) . '" target="_blank" rel="noopener" aria-label="' . ucfirst($key) . ' (opens in a new tab)"><i class="fab ' . $icon . '" aria-hidden="true"></i></a>';
    }
    echo '</div>';
    return true;
}

/**
 * One event (AdminCP -> Events): date badge, optional photo, title, description, time and place.
 * $full shows the whole description (events page); otherwise a short excerpt.
 */
function renderEventCard(array $event, $full = false, $past = false) {
    $ts = strtotime($event['event_date']);
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $event['description'])));
    if (!$full && mb_strlen($text) > 140) {
        $text = rtrim(mb_substr($text, 0, 140)) . '…';
    }
    echo '<article class="event-card' . ($past ? ' is-past' : '') . '" id="event-' . intval($event['id']) . '">';
    if (!empty($event['image'])) {
        echo '<div class="event-card-media"><img src="' . htmlspecialchars($event['image']) . '" alt="" loading="lazy"></div>';
    }
    echo '<div class="event-card-body">';
    echo '<span class="event-date" aria-hidden="true"><strong>' . date('d', $ts) . '</strong>' . date('M', $ts) . '</span>';
    echo '<div>';
    echo '<h3>' . htmlspecialchars($event['title']) . '</h3>';
    echo '<p class="event-meta"><span><i class="far fa-calendar" aria-hidden="true"></i> ' . date('l, d F Y', $ts) . '</span>';
    if (!empty($event['time'])) {
        echo '<span><i class="far fa-clock" aria-hidden="true"></i> ' . htmlspecialchars($event['time']) . '</span>';
    }
    if (!empty($event['location'])) {
        echo '<span><i class="fas fa-location-dot" aria-hidden="true"></i> ' . htmlspecialchars($event['location']) . '</span>';
    }
    echo '</p>';
    if ($text !== '') {
        echo '<p class="event-text">' . ($full ? nl2br(htmlspecialchars((string) $event['description'])) : htmlspecialchars($text)) . '</p>';
    }
    if (!$full) {
        echo '<a href="events#event-' . intval($event['id']) . '" class="text-link">Details<span class="sr-only">: ' . htmlspecialchars($event['title']) . '</span> <i class="fas fa-arrow-right" aria-hidden="true"></i></a>';
    }
    echo '</div></div></article>';
}

/** One news post, shown in full (AdminCP -> News). */
function renderNewsArticle(array $post) {
    echo '<article class="news-article" id="news-' . intval($post['id']) . '">';
    if (!empty($post['image'])) {
        echo '<div class="news-article-media"><img src="' . htmlspecialchars($post['image']) . '" alt="" loading="lazy"></div>';
    }
    echo '<div class="news-article-body">';
    echo '<p class="news-date"><i class="far fa-calendar" aria-hidden="true"></i> <time datetime="' . date('Y-m-d', strtotime($post['created_at'])) . '">' . date('d F Y', strtotime($post['created_at'])) . '</time>';
    if (!empty($post['author'])) {
        echo ' <span aria-hidden="true">·</span> ' . htmlspecialchars($post['author']);
    }
    echo '</p>';
    echo '<h3>' . htmlspecialchars($post['title']) . '</h3>';
    echo '<div class="prose">' . nl2br(htmlspecialchars((string) $post['content'])) . '</div>';
    echo '</div></article>';
}

/**
 * <picture> with a WebP version for browsers that support it and the original as the
 * fallback. The WebP twin is created once next to the original (only kept when smaller).
 * $attrs: extra attributes for the <img>, e.g. ['loading' => 'lazy', 'width' => 640].
 */
function pictureImg($path, $alt, array $attrs = []) {
    $root = dirname(__DIR__) . '/';
    $path = ltrim((string) $path, '/');
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    if ($webp !== $path && is_file($root . $path) && (!is_file($root . $webp) || filemtime($root . $webp) < filemtime($root . $path))) {
        createWebpTwin($root . $path);
    }
    if (empty($attrs['width']) && is_file($root . $path) && ($size = @getimagesize($root . $path))) {
        $attrs += ['width' => $size[0], 'height' => $size[1]];
    }
    $html = '<picture>';
    if ($webp !== $path && is_file($root . $webp)) {
        $html .= '<source type="image/webp" srcset="' . htmlspecialchars($webp) . '">';
    }
    $html .= '<img src="' . htmlspecialchars($path) . '" alt="' . htmlspecialchars($alt) . '"';
    foreach ($attrs + ['decoding' => 'async'] as $name => $value) {
        $html .= ' ' . $name . '="' . htmlspecialchars((string) $value) . '"';
    }
    return $html . '></picture>';
}

/** Google Maps embed URL taken from the iframe code saved in Settings, or '' when none is set. */
function mapEmbedSrc() {
    if (preg_match('#src=["\'](https://(?:www\.)?google\.com/maps/embed\?[^"\']+)["\']#i', getSetting('google_maps_embed', ''), $m)) {
        return html_entity_decode($m[1]);
    }
    return '';
}
