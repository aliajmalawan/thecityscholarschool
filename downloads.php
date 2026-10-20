<?php
require_once 'includes/config.php';
$page_title = content('seo_downloads_title');
$meta_description = content('seo_downloads_description');

// Files from AdminCP -> Downloads
$downloads = [];
$res = mysqli_query($conn, "SELECT DISTINCT * FROM downloads WHERE status = 'active' ORDER BY display_order ASC, date DESC");
while ($res && ($row = mysqli_fetch_assoc($res))) {
    $downloads[] = $row;
}
$file_icons = ['pdf' => 'fa-file-pdf', 'doc' => 'fa-file-word', 'docx' => 'fa-file-word', 'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel',
               'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image', 'png' => 'fa-file-image', 'zip' => 'fa-file-zipper'];
?>
<?php include 'includes/header.php'; ?>

<?php renderPageBanner('downloads', true, 'fa-download', ['Academics', 'courses']); ?>

<section class="page-intro bg-light">
    <div class="container">
        <h2 class="sr-only">Documents to download</h2>
        <?php if (!empty($downloads)): ?>
        <ul class="download-list">
            <?php foreach ($downloads as $download):
                $ext = strtolower(pathinfo($download['file_name'] ?: $download['file_path'], PATHINFO_EXTENSION));
                $size = is_file(__DIR__ . '/' . $download['file_path']) ? filesize(__DIR__ . '/' . $download['file_path']) : 0;
            ?>
            <li class="download-item">
                <span class="download-icon"><i class="fas <?php echo $file_icons[$ext] ?? 'fa-file'; ?>" aria-hidden="true"></i></span>
                <div class="download-body">
                    <strong><?php echo htmlspecialchars($download['description']); ?></strong>
                    <span class="download-meta">
                        <?php if (!empty($download['date'])): ?><span><i class="far fa-calendar" aria-hidden="true"></i> <?php echo date('d M Y', strtotime($download['date'])); ?></span><?php endif; ?>
                        <span><?php echo strtoupper($ext ?: 'file'); ?><?php echo $size ? ' · ' . ($size >= 1048576 ? round($size / 1048576, 1) . ' MB' : max(1, round($size / 1024)) . ' KB') : ''; ?></span>
                    </span>
                </div>
                <a href="<?php echo htmlspecialchars($download['file_path']); ?>" download class="btn btn-primary btn-sm"><i class="fas fa-download" aria-hidden="true"></i> Download<span class="sr-only">: <?php echo htmlspecialchars($download['description']); ?></span></a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
            <?php renderEmptyState('fa-folder-open', 'No downloads available yet', 'Forms, notices and other documents will be listed here when they are published.'); ?>
        <?php endif; ?>
    </div>
</section>

<?php admissionsCta('Need a Document That Is Not Listed?', 'Contact our office and we will help you find it.'); ?>

<?php include 'includes/footer.php'; ?>
