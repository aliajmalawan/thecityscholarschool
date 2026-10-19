    </main>

    <!-- Footer: brand + intro, quick links, academics, contact. Contact lines show what is filled in
         under Settings, or a short "coming soon" note; social icons only for real profile links. -->
    <?php
    $footer_social = array_filter(getSocialMedia());
    $footer_social_icons = ['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'twitter' => 'fa-x-twitter'];
    ?>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col footer-brand">
                    <a href="./" class="footer-logo">
                        <img src="<?php echo getLogoSmallPath(); ?>" alt="" width="78" height="72" loading="lazy" onerror="this.style.display='none'">
                        <span>
                            <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                            <small><?php echo htmlspecialchars(content('site_tagline')); ?></small>
                        </span>
                    </a>
                    <p><?php echo htmlspecialchars(getFooterContent()['about_text']); ?></p>
                    <?php if (!empty($footer_social)): ?>
                    <div class="footer-social" aria-label="Social media">
                        <?php foreach ($footer_social_icons as $key => $icon):
                            if (empty($footer_social[$key])) continue; ?>
                            <a href="<?php echo htmlspecialchars($footer_social[$key]); ?>" target="_blank" rel="noopener" aria-label="<?php echo ucfirst($key); ?> (opens in a new tab)"><i class="fab <?php echo $icon; ?>" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <nav class="footer-col" aria-labelledby="footer-quick">
                    <h2 class="footer-heading" id="footer-quick">Quick Links</h2>
                    <ul class="footer-links">
                        <li><a href="./">Home</a></li>
                        <li><a href="about">About Us</a></li>
                        <li><a href="admission">Admissions</a></li>
                        <li><a href="gallery">Gallery</a></li>
                        <li><a href="events">News &amp; Events</a></li>
                        <li><a href="contact">Contact Us</a></li>
                    </ul>
                </nav>

                <nav class="footer-col" aria-labelledby="footer-academics">
                    <h2 class="footer-heading" id="footer-academics">Academics</h2>
                    <ul class="footer-links">
                        <li><a href="courses">Courses &amp; Programs</a></li>
                        <li><a href="faculty">Our Faculty</a></li>
                        <li><a href="examination">Examination</a></li>
                        <li><a href="downloads">Downloads</a></li>
                        <li><a href="campuses">Our Campuses</a></li>
                        <li><a href="alumni">Alumni</a></li>
                    </ul>
                </nav>

                <div class="footer-col footer-contact">
                    <h2 class="footer-heading">Contact</h2>
                    <ul class="footer-contact-list">
                        <li>
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            <?php if (hasSiteAddress()): ?>
                                <span><?php echo htmlspecialchars(siteAddressOneLine()); ?></span>
                            <?php else: ?>
                                <span class="is-placeholder">Address to be announced</span>
                            <?php endif; ?>
                        </li>
                        <li>
                            <i class="fas fa-phone" aria-hidden="true"></i>
                            <?php if (hasSitePhone()): ?>
                                <span><?php foreach (array_filter(array_map('trim', explode(',', getSitePhone()))) as $i => $number): ?><?php echo $i ? '<br>' : ''; ?><a href="<?php echo htmlspecialchars(telHref($number)); ?>"><?php echo htmlspecialchars($number); ?></a><?php endforeach; ?></span>
                            <?php else: ?>
                                <span class="is-placeholder">Phone number coming soon</span>
                            <?php endif; ?>
                        </li>
                        <li>
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                            <?php if (hasSiteEmail()): ?>
                                <a href="mailto:<?php echo htmlspecialchars(getSiteEmail()); ?>"><?php echo htmlspecialchars(getSiteEmail()); ?></a>
                            <?php else: ?>
                                <span class="is-placeholder">Email coming soon</span>
                            <?php endif; ?>
                        </li>
                        <?php if (hasOfficeHours()): ?>
                        <li><i class="fas fa-clock" aria-hidden="true"></i> <span><?php echo htmlspecialchars(getOfficeHours()); ?></span></li>
                        <?php endif; ?>
                    </ul>
                    <a href="admission" class="btn btn-secondary btn-sm footer-apply"><i class="fas fa-pen-to-square" aria-hidden="true"></i> <?php echo htmlspecialchars(content('apply_button_label')); ?></a>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSiteName()); ?>. All rights reserved.</p>
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
    <button type="button" class="back-to-top" id="back-to-top" aria-label="Back to top"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>

    <!-- JavaScript -->
    <script src="js/main.js?v=<?php echo assetVersion('js/main.js'); ?>"></script>
</body>
</html>
