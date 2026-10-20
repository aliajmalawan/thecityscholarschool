<?php
/**
 * One-off repair: some photos were saved as WebP or PNG data under a .jpg name
 * (an old upload bug, fixed in includes/image_helper.php). Browsers that sniff the
 * content still show them, but older Safari cannot, the server labels them
 * image/jpeg, and the automatic WebP versions of banners could not be made.
 *
 * This rewrites every such file in images/ and uploads/ as a real JPEG (or PNG for
 * .png names), keeping the same file name, so nothing in the database changes.
 *
 * Run from the command line only, in the website folder:
 *     php database/fix-image-formats.php          (shows what it would change)
 *     php database/fix-image-formats.php --apply  (changes the files)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$apply = in_array('--apply', $argv, true);
$expected = ['jpg' => IMAGETYPE_JPEG, 'jpeg' => IMAGETYPE_JPEG, 'png' => IMAGETYPE_PNG];
$fixed = 0;
$problems = 0;

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/images', FilesystemIterator::SKIP_DOTS));
$uploads = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/uploads', FilesystemIterator::SKIP_DOTS));
foreach ([$files, $uploads] as $iterator) {
    foreach ($iterator as $file) {
        $path = $file->getPathname();
        if (strpos($path, DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR) !== false) {
            continue;   // generated thumbnails are rebuilt automatically
        }
        $ext = strtolower($file->getExtension());
        if (!isset($expected[$ext])) {
            continue;
        }
        $info = @getimagesize($path);
        if (!$info || $info[2] === $expected[$ext]) {
            continue;
        }

        $relative = substr($path, strlen($root) + 1);
        echo ($apply ? 'Fixing ' : 'Would fix ') . $relative . ' (' . image_type_to_extension($info[2], false) . " data)\n";
        if (!$apply) {
            $fixed++;
            continue;
        }

        $image = match ($info[2]) {
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };
        if (!$image) {
            echo "  could not read it, left as it is\n";
            $problems++;
            continue;
        }

        $tmp = $path . '.tmp';
        if ($expected[$ext] === IMAGETYPE_JPEG) {
            // flatten any transparency onto white: JPEG has no alpha channel
            $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefilledrectangle($canvas, 0, 0, imagesx($image), imagesy($image), imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $ok = imagejpeg($canvas, $tmp, 85);
            imagedestroy($canvas);
        } else {
            imagesavealpha($image, true);
            $ok = imagepng($image, $tmp, 6);
        }
        imagedestroy($image);

        if ($ok && rename($tmp, $path)) {
            $fixed++;
        } else {
            @unlink($tmp);
            echo "  could not save it, left as it is\n";
            $problems++;
        }
    }
}

echo "\n" . ($apply ? "Fixed $fixed file(s)" : "$fixed file(s) need fixing; run again with --apply")
    . ($problems ? ", $problems could not be fixed" : '') . ".\n";
