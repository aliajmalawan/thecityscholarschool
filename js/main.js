// The City Scholars School - Main JavaScript

// Sticky header shadow on scroll
document.addEventListener('DOMContentLoaded', function() {
    const siteHeader = document.querySelector('header');
    if (siteHeader) {
        const toggleHeaderShadow = () => {
            siteHeader.classList.toggle('is-scrolled', window.scrollY > 10);
        };
        toggleHeaderShadow();
        window.addEventListener('scroll', toggleHeaderShadow, { passive: true });
    }
});

// Header: drawer menu below 1280px, dropdowns everywhere
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('nav-toggle');
    const nav = document.getElementById('site-nav');
    const backdrop = document.getElementById('nav-backdrop');
    if (!toggle || !nav) return;

    const DRAWER_MAX = 1279;
    const isDrawer = () => window.innerWidth <= DRAWER_MAX;
    const groups = Array.from(nav.querySelectorAll('.nav-dropdown'));
    const focusables = () => Array.from(nav.querySelectorAll('a, button')).filter(el => el.offsetParent !== null);

    function setGroup(group, open) {
        group.classList.toggle('is-open', open);
        const trigger = group.querySelector('.nav-dropdown-trigger');
        if (trigger) trigger.setAttribute('aria-expanded', String(open));
    }

    function setOpen(open, returnFocus) {
        nav.classList.toggle('is-open', open);
        toggle.classList.toggle('is-open', open);
        if (backdrop) backdrop.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        document.body.style.overflow = open ? 'hidden' : '';
        if (open) {
            // open the current page's group, then move focus into the drawer
            groups.forEach(g => setGroup(g, g.classList.contains('is-active')));
            setTimeout(() => { const first = focusables()[0]; if (first) first.focus(); }, 60);
        } else {
            groups.forEach(g => setGroup(g, false));
            if (returnFocus) toggle.focus();
        }
    }

    toggle.addEventListener('click', () => setOpen(!nav.classList.contains('is-open')));
    if (backdrop) backdrop.addEventListener('click', () => setOpen(false, true));

    // Dropdown buttons: expand inline in the drawer; on desktop they also open on hover/focus (CSS)
    groups.forEach(group => {
        const trigger = group.querySelector('.nav-dropdown-trigger');
        if (!trigger) return;
        trigger.addEventListener('click', () => {
            const open = !group.classList.contains('is-open');
            if (!isDrawer()) groups.forEach(g => { if (g !== group) setGroup(g, false); });
            setGroup(group, open);
        });
        group.addEventListener('mouseleave', () => { if (!isDrawer()) setGroup(group, false); });
        group.addEventListener('focusout', e => {
            if (!isDrawer() && !group.contains(e.relatedTarget)) setGroup(group, false);
        });
    });

    // Close after choosing a link, on Escape, on a click elsewhere, or when resizing up to desktop
    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { if (isDrawer()) setOpen(false); }));
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        if (nav.classList.contains('is-open')) { setOpen(false, true); return; }
        const openGroup = groups.find(g => g.classList.contains('is-open'));
        if (openGroup) { setGroup(openGroup, false); openGroup.querySelector('.nav-dropdown-trigger').focus(); }
    });
    document.addEventListener('click', e => {
        if (!isDrawer()) groups.forEach(g => { if (!g.contains(e.target)) setGroup(g, false); });
    });
    window.addEventListener('resize', () => { if (!isDrawer() && nav.classList.contains('is-open')) setOpen(false); });

    // Keep keyboard focus inside the open drawer (its links + the close button)
    document.addEventListener('keydown', e => {
        if (e.key !== 'Tab' || !nav.classList.contains('is-open')) return;
        const items = focusables().concat(toggle);
        const i = items.indexOf(document.activeElement);
        if (e.shiftKey && i <= 0) { e.preventDefault(); items[items.length - 1].focus(); }
        else if (!e.shiftKey && (i === items.length - 1 || i === -1)) { e.preventDefault(); items[0].focus(); }
    });
});

// Home hero slider: automatic crossfade with captions, progress line, dots, arrows and swipe.
// It always auto-plays (also for visitors with "reduce motion" on: they just get no fade/zoom, see CSS).
document.addEventListener('DOMContentLoaded', function() {
    const hero = document.getElementById('hero');
    if (!hero || !hero.classList.contains('hero-slider')) return;

    const slides = Array.from(hero.querySelectorAll('.hero-slide'));
    if (slides.length < 2) return;
    const captions = Array.from(hero.querySelectorAll('.hero-caption'));
    const dots = Array.from(hero.querySelectorAll('.hero-dot'));
    const INTERVAL = 5000;
    const canHover = window.matchMedia('(hover: hover)').matches;

    let current = 0;
    let timer = null;
    let hovering = false;     // mouse is over the slider (desktop only)

    hero.style.setProperty('--hero-interval', INTERVAL + 'ms');

    function restartProgress() {
        hero.classList.remove('is-running');
        void hero.offsetWidth;            // reflow so the CSS animation starts from 0 again
        hero.classList.add('is-running');
    }

    function show(index) {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.classList.toggle('is-active', i === current);
            slide.setAttribute('aria-hidden', i === current ? 'false' : 'true');
        });
        captions.forEach((cap, i) => {
            const on = i === current;
            cap.classList.toggle('is-active', on);
            cap.setAttribute('aria-hidden', on ? 'false' : 'true');
            cap.querySelectorAll('a').forEach(a => a.tabIndex = on ? 0 : -1);
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === current);
            dot.setAttribute('aria-selected', i === current ? 'true' : 'false');
        });
        restartProgress();
    }

    function schedule() {
        clearTimeout(timer);
        if (hovering || document.hidden) return;
        timer = setTimeout(function tick() {
            show(current + 1);
            schedule();
        }, INTERVAL);
    }

    // after any manual change: show the slide, then start a fresh countdown
    function go(index) { show(index); schedule(); }

    // keep the progress line and the countdown in step when hover state changes
    function syncState() {
        hero.classList.toggle('is-hovered', hovering);
        if (hovering || document.hidden) {
            clearTimeout(timer);
        } else {
            go(current);        // resume with a full interval so text can be read
        }
    }

    const prev = hero.querySelector('.hero-prev');
    const next = hero.querySelector('.hero-next');
    if (prev) prev.addEventListener('click', () => go(current - 1));
    if (next) next.addEventListener('click', () => go(current + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => go(i)));

    if (canHover) {
        hero.addEventListener('mouseenter', () => { hovering = true; syncState(); });
        hero.addEventListener('mouseleave', () => { hovering = false; syncState(); });
    }
    document.addEventListener('visibilitychange', syncState);

    // keyboard: left/right arrows while focus is inside the slider
    hero.addEventListener('keydown', e => {
        if (e.key === 'ArrowLeft') go(current - 1);
        if (e.key === 'ArrowRight') go(current + 1);
    });

    // swipe on touch screens
    let startX = null;
    hero.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
    hero.addEventListener('touchend', e => {
        if (startX === null) return;
        const dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 50) go(current + (dx < 0 ? 1 : -1));
        startX = null;
    }, { passive: true });

    show(0);
    schedule();
});

// Smooth Scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ---------------------------------------------------------------------------
// Motion: scroll reveal with direction + stagger, count-up numbers,
// scroll-progress line and back-to-top button.
// Without JavaScript nothing is hidden (the CSS only hides inside html.js),
// and visitors who prefer reduced motion see everything immediately.
// ---------------------------------------------------------------------------
(function() {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Count-up for statistics ("8", "12+", "1,200")
    function countUp(element) {
        const text = element.textContent.trim();
        const target = parseInt(text.replace(/[^\d]/g, ''), 10);
        if (isNaN(target)) return;
        const suffix = text.replace(/^[\d,\s]*/, '');
        if (reduceMotion || target === 0) return;
        const duration = 1600;
        const started = performance.now();
        element.textContent = '0' + suffix;
        (function frame(now) {
            const t = Math.min((now - started) / duration, 1);
            const eased = 1 - Math.pow(1 - t, 3);                 // ease-out
            element.textContent = Math.round(target * eased).toLocaleString('en-US') + suffix;
            if (t < 1) requestAnimationFrame(frame);
        })(started);
    }

    document.addEventListener('DOMContentLoaded', function() {
        // 1) elements that slide in from a side or zoom in
        const directional = [
            ['.about-media', 'reveal-left'],
            ['.about-copy', 'reveal-right'],
            ['.home-about-media', 'reveal-left'],
            ['.home-about-copy', 'reveal-right'],
            ['.admissions-copy', 'reveal-left'],
            ['.home-admissions .form-card', 'reveal-right'],
            ['.contact-details', 'reveal-left'],
            ['.home-contact .form-card', 'reveal-right'],
            ['.events-panel', 'reveal-right'],
            ['.admissions-intro', 'reveal-left'],
            ['.admissions-details', 'reveal-right'],
            ['.cta-band-inner', 'reveal-zoom'],
            ['.life-photo', 'reveal-zoom'],
            ['.update-card', 'reveal-zoom']
        ];
        directional.forEach(([selector, cls]) => {
            document.querySelectorAll(selector).forEach(el => el.classList.add('reveal', cls));
        });

        // 2) things that should simply fade up
        ['.section-header', '.testimonial-carousel', '.quick-strip .container'].forEach(selector => {
            document.querySelectorAll(selector).forEach(el => el.classList.add('reveal'));
        });

        // 3) groups whose children appear one after another
        const staggered = [
            '.quick-grid > *', '.level-grid > *', '.why-grid > *', '.life-photo-grid > *', '.update-grid > *',
            '.card-grid > *', '.grid-auto > *', '.campus-life-grid > *', '.about-points > *', '.about-values li',
            '.admissions-steps li', '.admissions-docs li', '.stats-grid > *',
            '.program-grid > *', '.why-grid > *', '.facility-grid > *', '.life-grid > *', '.news-list > .news-card',
            '.mosaic > *', '.contact-list li'
        ];
        staggered.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.classList.add('reveal');
                const siblings = Array.from(el.parentElement.children);
                const index = Math.max(0, siblings.indexOf(el)) % 6;      // cap so long lists don't wait too long
                el.style.setProperty('--delay', (index * 0.09) + 's');
            });
        });

        const targets = Array.from(document.querySelectorAll('.reveal'));
        const counters = Array.from(document.querySelectorAll('.stat-value'));

        function finish(el) {
            // Once shown, hand the element back to its normal CSS so hover effects and transitions are untouched
            const delay = parseFloat(el.style.getPropertyValue('--delay')) || 0;
            setTimeout(() => {
                el.classList.remove('reveal', 'reveal-left', 'reveal-right', 'reveal-zoom', 'is-visible');
                el.style.removeProperty('--delay');
            }, 1100 + delay * 1000);
        }

        if (!('IntersectionObserver' in window) || reduceMotion) {
            targets.forEach(el => el.classList.add('is-visible'));
            return;
        }

        const revealObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                el.classList.add('is-visible');
                revealObserver.unobserve(el);
                if (!el.classList.contains('section-header')) finish(el);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -50px 0px' });
        targets.forEach(el => revealObserver.observe(el));

        const counterObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                countUp(entry.target);
                counterObserver.unobserve(entry.target);
            });
        }, { threshold: 0.6 });
        counters.forEach(el => counterObserver.observe(el));
    });

    // Scroll progress line + back-to-top button
    document.addEventListener('DOMContentLoaded', function() {
        const bar = document.getElementById('scroll-progress');
        const topBtn = document.getElementById('back-to-top');
        let ticking = false;

        function update() {
            const doc = document.documentElement;
            const max = doc.scrollHeight - window.innerHeight;
            const ratio = max > 0 ? Math.min(window.scrollY / max, 1) : 0;
            if (bar) bar.style.transform = 'scaleX(' + ratio + ')';
            if (topBtn) topBtn.classList.toggle('is-shown', window.scrollY > 600);
            ticking = false;
        }

        window.addEventListener('scroll', () => {
            if (!ticking) { ticking = true; requestAnimationFrame(update); }
        }, { passive: true });
        update();

        if (topBtn) {
            topBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));
        }
    });
})();

// Testimonial Carousel (horizontal scroll-snap track + prev/next buttons)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.testimonial-carousel').forEach(carousel => {
        const track = carousel.querySelector('.testimonial-track');
        const prevBtn = carousel.querySelector('.testimonial-nav.prev');
        const nextBtn = carousel.querySelector('.testimonial-nav.next');
        if (!track) return;

        function scrollStep() {
            const card = track.querySelector('.testimonial-card');
            return card ? card.offsetWidth + 24 : 320;
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                track.scrollBy({ left: -scrollStep(), behavior: 'smooth' });
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                track.scrollBy({ left: scrollStep(), behavior: 'smooth' });
            });
        }
    });
});

// FAQ Accordion
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        if (!question) return;
        question.addEventListener('click', function() {
            const isOpen = item.classList.contains('is-open');
            faqItems.forEach(i => {
                i.classList.remove('is-open');
                const q = i.querySelector('.faq-question');
                if (q) q.setAttribute('aria-expanded', 'false');
            });
            if (!isOpen) {
                item.classList.add('is-open');
                question.setAttribute('aria-expanded', 'true');
            }
        });
    });
});

// Form validation: each message is linked to its field (aria-invalid + aria-describedby),
// the first problem gets focus, and the rules match the server-side checks.
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    let firstInvalid = null;

    function clear(input) {
        input.removeAttribute('aria-invalid');
        const err = input.id ? document.getElementById(input.id + '-error') : null;
        if (err) err.remove();
        const rest = (input.getAttribute('aria-describedby') || '').split(' ').filter(id => id && id !== input.id + '-error');
        if (rest.length) input.setAttribute('aria-describedby', rest.join(' '));
        else input.removeAttribute('aria-describedby');
    }

    function markInvalid(input, msg) {
        if (input.getAttribute('aria-invalid') === 'true') return;   // one message per field
        if (!input.id) input.id = formId + '-' + input.name;
        input.setAttribute('aria-invalid', 'true');
        const err = document.createElement('p');
        err.className = 'field-error';
        err.id = input.id + '-error';
        err.innerHTML = '<i class="fas fa-circle-exclamation" aria-hidden="true"></i> ';
        err.appendChild(document.createTextNode(msg));
        input.insertAdjacentElement('afterend', err);
        input.setAttribute('aria-describedby', ((input.getAttribute('aria-describedby') || '') + ' ' + err.id).trim());
        if (!firstInvalid) firstInvalid = input;
    }

    const fields = Array.from(form.querySelectorAll('input:not([type="hidden"]):not([type="file"]), select, textarea'))
        .filter(el => !el.closest('.form-hp'));
    fields.forEach(clear);

    fields.forEach(input => {
        const value = input.value.trim();
        if (input.required && !value) {
            const label = input.id ? form.querySelector('label[for="' + input.id + '"]') : null;
            const name = label ? label.textContent.replace(/\*|\(optional\)/g, '').trim().toLowerCase() : '';
            markInvalid(input, name
                ? (input.tagName === 'SELECT' ? 'Please choose a ' : 'Please enter the ') + name + '.'
                : 'This field is required.');
            return;
        }
        if (!value) return;
        if (input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            markInvalid(input, 'Please enter a valid email address, for example name@example.com.');
        }
        if (input.type === 'tel' && !/^\+?[0-9]{10,13}$/.test(value.replace(/[\s()-]+/g, ''))) {
            markInvalid(input, 'Please enter a valid phone number, for example 03001234567.');
        }
    });

    if (firstInvalid) {
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstInvalid.focus({ preventScroll: true });
        return false;
    }
    return true;
}

// Forms marked data-validate check themselves on submit; an error disappears as soon as the field is edited
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        if (!form.id) return;
        form.addEventListener('submit', e => {
            if (!validateForm(form.id)) { e.preventDefault(); return; }
            const button = form.querySelector('button[type="submit"]');
            if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
        });
        form.addEventListener('input', e => {
            const input = e.target;
            if (input.getAttribute('aria-invalid') !== 'true') return;
            input.removeAttribute('aria-invalid');
            const err = document.getElementById(input.id + '-error');
            if (err) err.remove();
        });
    });
});

// Photo gallery (gallery.php): category filters, an accessible lightbox and click-to-play videos
function initGallery() {
    const grid = document.getElementById('galleryGrid');

    // Category filters
    const filters = document.querySelectorAll('.gallery-filter');
    filters.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;
            filters.forEach(b => {
                const on = b === btn;
                b.classList.toggle('is-active', on);
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            grid.querySelectorAll('.gallery-item').forEach(item => {
                item.hidden = !(filter === 'all' || item.dataset.category === filter);
            });
        });
    });

    // Lightbox: steps only through the photos the current filter shows
    if (grid) {
        const box = document.createElement('div');
        box.className = 'lightbox';
        box.setAttribute('role', 'dialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-label', 'Photo viewer');
        box.hidden = true;
        box.innerHTML = `
            <button type="button" class="lightbox-close" aria-label="Close"><i class="fas fa-xmark"></i></button>
            <button type="button" class="lightbox-arrow lightbox-prev" aria-label="Previous photo"><i class="fas fa-chevron-left"></i></button>
            <figure class="lightbox-figure">
                <img alt="">
                <figcaption><span class="lightbox-caption"></span><span class="lightbox-count"></span></figcaption>
            </figure>
            <button type="button" class="lightbox-arrow lightbox-next" aria-label="Next photo"><i class="fas fa-chevron-right"></i></button>`;
        document.body.appendChild(box);

        const img = box.querySelector('img');
        const caption = box.querySelector('.lightbox-caption');
        const count = box.querySelector('.lightbox-count');
        let items = [];
        let index = 0;
        let opener = null;

        const visibleLinks = () => Array.from(grid.querySelectorAll('.gallery-item:not([hidden]) .gallery-link'));

        function show(i) {
            index = (i + items.length) % items.length;
            const link = items[index];
            img.src = link.getAttribute('href');
            img.alt = link.querySelector('img').alt;
            caption.textContent = link.dataset.caption || '';
            count.textContent = (index + 1) + ' / ' + items.length;
            // Warm the cache for the neighbours so next/previous feel instant
            [index + 1, index - 1].forEach(n => { const l = items[(n + items.length) % items.length]; if (l) { new Image().src = l.getAttribute('href'); } });
        }

        function open(link) {
            items = visibleLinks();
            opener = link;
            box.hidden = false;
            document.body.classList.add('lightbox-open');
            show(items.indexOf(link));
            box.querySelector('.lightbox-close').focus();
        }

        function close() {
            box.hidden = true;
            img.removeAttribute('src');
            document.body.classList.remove('lightbox-open');
            if (opener) { opener.focus(); }
        }

        grid.addEventListener('click', e => {
            const link = e.target.closest('.gallery-link');
            if (!link) return;
            e.preventDefault();
            open(link);
        });

        box.querySelector('.lightbox-close').addEventListener('click', close);
        box.querySelector('.lightbox-prev').addEventListener('click', () => show(index - 1));
        box.querySelector('.lightbox-next').addEventListener('click', () => show(index + 1));
        box.addEventListener('click', e => { if (e.target === box) close(); });

        document.addEventListener('keydown', e => {
            if (box.hidden) return;
            if (e.key === 'Escape') close();
            else if (e.key === 'ArrowLeft') show(index - 1);
            else if (e.key === 'ArrowRight') show(index + 1);
            else if (e.key === 'Tab') {
                // Keep keyboard focus inside the viewer
                const controls = Array.from(box.querySelectorAll('button'));
                const pos = controls.indexOf(document.activeElement);
                e.preventDefault();
                controls[(pos + (e.shiftKey ? -1 : 1) + controls.length) % controls.length].focus();
            }
        });

        // Swipe left/right on phones
        let startX = null;
        box.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
        box.addEventListener('touchend', e => {
            if (startX === null) return;
            const dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 50) show(index + (dx < 0 ? 1 : -1));
            startX = null;
        });
    }

    // Videos: swap the thumbnail for the YouTube player only when pressed (privacy-friendly domain, no load cost before)
    document.querySelectorAll('.video-frame[data-youtube]').forEach(btn => {
        btn.addEventListener('click', () => {
            const iframe = document.createElement('iframe');
            iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(btn.dataset.youtube) + '?autoplay=1&rel=0';
            iframe.title = btn.getAttribute('aria-label').replace(/^Play video: /, '');
            iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
            iframe.allowFullscreen = true;
            const holder = document.createElement('div');
            holder.className = 'video-frame is-playing';
            holder.appendChild(iframe);
            btn.replaceWith(holder);
        });
    });
}

document.addEventListener('DOMContentLoaded', initGallery);
