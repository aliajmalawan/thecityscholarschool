    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section footer-brand">
                    <a href="./" class="footer-logo">
                        <img src="<?php echo getLogoSmallPath(); ?>" alt="<?php echo htmlspecialchars(getSiteName()); ?> Logo" width="64" height="64" onerror="this.style.display='none'">
                        <span>
                            <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                            <small><?php echo htmlspecialchars(content('site_tagline')); ?></small>
                        </span>
                    </a>
                    <p><?php echo htmlspecialchars(getFooterContent()['about_text']); ?></p>
                    <?php
                    $social = array_filter(getSocialMedia());
                    if (!empty($social)): ?>
                    <div class="social-links">
                        <?php if (!empty($social['facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($social['facebook']); ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <?php endif;
                        if (!empty($social['instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($social['instagram']); ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <?php endif;
                        if (!empty($social['youtube'])): ?>
                            <a href="<?php echo htmlspecialchars($social['youtube']); ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <?php endif;
                        if (!empty($social['twitter'])): ?>
                            <a href="<?php echo htmlspecialchars($social['twitter']); ?>" target="_blank" rel="noopener" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="footer-section footer-links">
                    <h2 class="footer-heading">Quick Links</h2>
                    <ul>
                        <li><a href="./">Home</a></li>
                        <li><a href="about">About Us</a></li>
                        <li><a href="courses">Academics</a></li>
                        <li><a href="admission">Admissions</a></li>
                        <li><a href="gallery">Campus Life</a></li>
                        <li><a href="contact">Contact Us</a></li>
                    </ul>
                </div>

                <div class="footer-section footer-links">
                    <h2 class="footer-heading">Explore</h2>
                    <ul>
                        <li><a href="faculty">Our Faculty</a></li>
                        <li><a href="events">News &amp; Events</a></li>
                        <li><a href="examination">Examination</a></li>
                        <li><a href="downloads">Downloads</a></li>
                        <li><a href="alumni">Alumni</a></li>
                    </ul>
                </div>

                <div class="footer-section footer-contact">
                    <h2 class="footer-heading">Contact Info</h2>
                    <?php // The address is saved on several lines in Settings; the footer shows it on one: "Canal View, Hasilpur, Pakistan" ?>
                    <p><i class="fas fa-map-marker-alt"></i> <span><?php echo htmlspecialchars(implode(', ', array_filter(array_map(fn($line) => trim($line, " \t,"), preg_split('/\r\n|\r|\n/', getSiteAddress()))))); ?></span></p>
                    <?php foreach (array_filter(array_map('trim', explode(',', getSitePhone()))) as $number): ?>
                        <p><i class="fas fa-phone"></i> <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $number)); ?>"><?php echo htmlspecialchars($number); ?></a></p>
                    <?php endforeach; ?>
                    <?php if (hasSiteEmail()): ?>
                        <p><i class="fas fa-envelope"></i> <a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><?php echo htmlspecialchars(getSiteEmail()); ?></a></p>
                    <?php endif; ?>
                    <p><i class="fas fa-clock"></i> <span><?php echo htmlspecialchars(getOfficeHours()); ?></span></p>
                    <?php $footer_phone = trim(explode(',', getSitePhone())[0]); ?>
                    <div class="footer-actions">
                        <?php if ($footer_phone !== ''): ?>
                        <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $footer_phone)); ?>" class="footer-action"><i class="fas fa-phone"></i> Call Now</a>
                        <?php endif; ?>
                        <a href="admission" class="footer-action is-primary"><i class="fas fa-pen-to-square"></i> Apply Online</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSiteName()); ?>. All Rights Reserved.</p>
                <p class="footer-legal"><a href="privacy-policy.html">Privacy Policy</a><span aria-hidden="true">&middot;</span><a href="contact">Contact</a></p>
            </div>
        </div>
    </footer>

    <!-- Website Analytics Tracker -->
    <?php
    // Track page views for analytics (only on public pages, not admin)
    if (file_exists(__DIR__ . '/analytics_tracker.php')) {
        include __DIR__ . '/analytics_tracker.php';
    }
    ?>

    <div class="scroll-progress" id="scroll-progress" aria-hidden="true"></div>
    <button type="button" class="back-to-top" id="back-to-top" aria-label="Back to top"><i class="fas fa-arrow-up"></i></button>

    <!-- JavaScript -->
    <script src="js/main.js?v=<?php echo assetVersion('js/main.js'); ?>"></script>
</body>
</html>
