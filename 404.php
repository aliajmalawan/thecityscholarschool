<?php
require_once 'includes/config.php';
$page_title = 'Page Not Found';
$noindex = true;
http_response_code(404);
?>
<?php include 'includes/header.php'; ?>

<?php renderPageHero('images/site/banner-contact.jpg', 'Error 404', 'Page Not Found', "The page you're looking for doesn't exist or may have been moved.", 'Page Not Found'); ?>

<section>
    <div class="container text-center">
        <div style="max-width: 600px; margin: 0 auto;">
            <p style="font-size: 18px; color: var(--text-light); margin-bottom: 34px; line-height: 1.8;">
                Double-check the address, or use the links below to get back on track.
            </p>
            <div class="btn-group" style="justify-content: center;">
                <a href="./" class="btn btn-primary">Back to Home</a>
                <a href="contact" class="btn btn-outline-dark">Contact Us</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
