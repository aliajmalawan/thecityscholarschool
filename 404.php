<?php
require_once 'includes/config.php';
$page_title = 'Page Not Found';
$noindex = true;
http_response_code(404);
?>
<?php include 'includes/header.php'; ?>

<?php renderPageHero(content('banner_contact_image'), 'Error 404', 'Page Not Found', "The page you're looking for doesn't exist or may have been moved.", 'Page Not Found'); ?>

<section class="page-intro">
    <div class="container">
        <div class="not-found">
            <p class="lead">Double-check the address, or use one of the links below to find what you were looking for.</p>
            <?php linkCards([
                ['fa-house', 'Home', 'Start from the homepage', './'],
                ['fa-graduation-cap', 'Academics', 'Programs and courses', 'courses'],
                ['fa-pen-to-square', 'Admissions', 'How to apply', 'admission'],
                ['fa-envelope', 'Contact Us', 'Ask our office', 'contact'],
            ]); ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
