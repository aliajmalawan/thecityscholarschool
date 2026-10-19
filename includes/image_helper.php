<?php
/**
 * Image Compression Helper
 *
 * Shrinks uploaded images by resizing oversized dimensions and using a
 * high JPEG quality, instead of crushing quality to hit a small file-size
 * target (that approach produces visible blocking/artifacts on detailed
 * photos). A phone photo at 4000x3000px doesn't need to stay that large
 * to look sharp on a web page - resizing down to a sane max dimension
 * cuts file size dramatically while staying visually "HD".
 */

/**
 * Compress an uploaded image in place (or into a chosen destination).
 *
 * @param string $sourcePath      Temp path of the uploaded file ($_FILES[...]['tmp_name'])
 * @param string $destinationPath Where to save the processed image
 * @param int    $maxDimension    Longest side is capped to this many pixels (never upscales)
 * @param int    $jpegQuality     JPEG quality 0-100 (85 is visually near-lossless)
 * @return bool True on success
 */
function compressUploadedImage($sourcePath, $destinationPath, $maxDimension = 1600, $jpegQuality = 85) {
    $info = @getimagesize($sourcePath);
    if (!$info) {
        return false;
    }

    [$width, $height, $type] = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = @imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false;
            break;
        default:
            return false;
    }

    if (!$source) {
        return false;
    }

    if ($type === IMAGETYPE_JPEG) {
        [$source, $width, $height] = orientFromExif($source, $sourcePath, $width, $height);
    }

    // Only scale down, never up - and only if it actually exceeds the cap
    $scale = min(1, $maxDimension / max($width, $height));
    $newWidth = max(1, (int) round($width * $scale));
    $newHeight = max(1, (int) round($height * $scale));

    if ($scale < 1) {
        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG/GIF instead of flattening to black
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resampled (not resized) copy - the quality-preserving interpolation
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);
    } else {
        // Already within the size cap - keep original pixels, just re-encode
        $canvas = $source;
    }

    $saved = false;
    switch ($type) {
        case IMAGETYPE_PNG:
            // PNG compression is always lossless - level 6 is a good size/speed
            // balance and never affects visual quality.
            $saved = imagepng($canvas, $destinationPath, 6);
            break;
        case IMAGETYPE_GIF:
            $saved = imagegif($canvas, $destinationPath);
            break;
        case IMAGETYPE_WEBP:
            $saved = function_exists('imagewebp') ? imagewebp($canvas, $destinationPath, $jpegQuality) : false;
            break;
        case IMAGETYPE_JPEG:
        default:
            $saved = imagejpeg($canvas, $destinationPath, $jpegQuality);
            break;
    }

    imagedestroy($canvas);

    // Safety net: if re-encoding somehow produced a larger file than the
    // original (rare, e.g. a tiny already-optimized image), keep the
    // original instead of the "compressed" version.
    if ($saved && file_exists($destinationPath) && filesize($destinationPath) > filesize($sourcePath)) {
        copy($sourcePath, $destinationPath);
    }

    return $saved && file_exists($destinationPath);
}

/**
 * Convenience wrapper: compress an uploaded image and always save it with
 * the given destination path's own extension preserved from the source
 * type (JPEG in, .jpg out; PNG in, .png out - never forces a JPEG
 * conversion, which would destroy transparency on logos/signatures).
 */
function compressUploadedImageKeepFormat($sourcePath, $destDir, $baseFilename, $maxDimension = 1600, $jpegQuality = 85) {
    $info = @getimagesize($sourcePath);
    if (!$info) {
        return false;
    }

    $extensions = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_WEBP => 'webp',
    ];

    $ext = $extensions[$info[2]] ?? null;
    if (!$ext) {
        return false;
    }

    $filename = $baseFilename . '.' . $ext;
    $destinationPath = rtrim($destDir, '/') . '/' . $filename;

    if (!file_exists($destDir)) {
        mkdir($destDir, 0777, true);
    }

    if (compressUploadedImage($sourcePath, $destinationPath, $maxDimension, $jpegQuality)) {
        return $filename;
    }

    return false;
}

/**
 * Compress an uploaded PHOTO and always save it as JPEG, regardless of
 * the source format. PNG is lossless, so a phone-camera photo saved as
 * PNG (screenshots, some phone camera apps, Photoshop exports) stays
 * multi-megabyte no matter what PNG compression level you pick - JPEG
 * handles photographic detail dramatically better. Only use this for
 * genuine photos (hero banners, gallery, news, events, campuses, staff
 * photos, etc) - never for logos, which may need real transparency.
 *
 * @param string $sourcePath   Temp path of the uploaded file
 * @param string $destDir      Directory to save into (created if missing)
 * @param string $baseFilename Filename without extension - .jpg is appended
 * @return string|false Final filename (e.g. "hero_123.jpg") or false on failure
 */
function compressUploadedPhotoAsJpeg($sourcePath, $destDir, $baseFilename, $maxDimension = 1920, $jpegQuality = 85) {
    $info = @getimagesize($sourcePath);
    if (!$info) {
        return false;
    }
    [$width, $height, $type] = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $source = @imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false;
            break;
        default:
            return false;
    }
    if (!$source) {
        return false;
    }

    if ($type === IMAGETYPE_JPEG) {
        [$source, $width, $height] = orientFromExif($source, $sourcePath, $width, $height);
    }

    $scale = min(1, $maxDimension / max($width, $height));
    $newWidth = max(1, (int) round($width * $scale));
    $newHeight = max(1, (int) round($height * $scale));

    $canvas = imagecreatetruecolor($newWidth, $newHeight);
    // Flatten any transparency onto white - this is photographic content,
    // not a logo, so alpha isn't meaningful and JPEG has no alpha channel.
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($source);

    if (!file_exists($destDir)) {
        mkdir($destDir, 0777, true);
    }
    $filename = $baseFilename . '.jpg';
    $destinationPath = rtrim($destDir, '/') . '/' . $filename;
    $saved = imagejpeg($canvas, $destinationPath, $jpegQuality);
    imagedestroy($canvas);

    if ($saved && file_exists($destinationPath) && filesize($destinationPath) > filesize($sourcePath)) {
        // Safety net: re-encoding shouldn't usually grow the file, but if
        // the source was already a tiny well-optimized JPEG, keep it.
        copy($sourcePath, $destinationPath);
    }

    return ($saved && file_exists($destinationPath)) ? $filename : false;
}

/**
 * Human-readable byte size, e.g. 1258291 -> "1.2 MB"
 */
function formatFileSize($bytes) {
    if ($bytes >= 1024 * 1024) {
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Build a short "(2.3 MB -> 145 KB, 94% smaller)" string comparing an
 * original uploaded file to the compressed result - call this right after
 * compressUploadedImage()/compressUploadedImageKeepFormat() so the admin
 * actually sees the compression happen instead of it being invisible.
 */
function describeCompressionSavings($originalPath, $compressedPath) {
    if (!file_exists($originalPath) || !file_exists($compressedPath)) {
        return '';
    }

    $before = filesize($originalPath);
    $after = filesize($compressedPath);

    if ($before <= 0) {
        return '';
    }

    $percent = round((1 - $after / $before) * 100);
    $percent_text = $percent > 0 ? "{$percent}% smaller" : 'no size change';

    return ' (' . formatFileSize($before) . ' -> ' . formatFileSize($after) . ', ' . $percent_text . ')';
}

/**
 * Rotate a freshly decoded JPEG upright using its EXIF orientation. Phone photos are often stored
 * sideways with an "orientation" flag; re-encoding drops that flag, so without this they would
 * end up rotated on the website. Returns [image, width, height] (unchanged for other formats).
 */
function orientFromExif($image, $sourcePath, $width, $height) {
    if (!function_exists('exif_read_data') || !function_exists('imagerotate')) {
        return [$image, $width, $height];
    }
    $exif = @exif_read_data($sourcePath);
    $orientation = $exif['Orientation'] ?? 1;
    $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
    if ($angle !== 0) {
        $rotated = imagerotate($image, $angle, 0);
        if ($rotated) {
            imagedestroy($image);
            $image = $rotated;
            if ($angle !== 180) {
                [$width, $height] = [$height, $width];
            }
        }
    }
    return [$image, $width, $height];
}

/**
 * Write a WebP copy next to a JPEG/PNG (photo.jpg -> photo.webp). WebP files are about 30-40% smaller,
 * so pages that know about the twin (the home slider) serve it to browsers that support it.
 */
function createWebpTwin($imagePath, $quality = 82) {
    if (!function_exists('imagewebp') || !is_file($imagePath)) {
        return false;
    }
    $info = @getimagesize($imagePath);
    if (!$info) {
        return false;
    }
    $image = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($imagePath) : @imagecreatefromjpeg($imagePath);
    if (!$image) {
        return false;
    }
    $twin = preg_replace('/\.(jpe?g|png)$/i', '.webp', $imagePath);
    $ok = $twin !== $imagePath && imagewebp($image, $twin, $quality);
    imagedestroy($image);
    if ($ok && filesize($twin) >= filesize($imagePath)) {
        unlink($twin);          // not smaller, so it is not worth serving
        return false;
    }
    return $ok;
}

/**
 * Safe handling of a photo uploaded through a PUBLIC form: checks it is really an image of an allowed
 * type and not too large, then resizes + compresses it to a JPEG under a random file name (the visitor's
 * file name is never used). Returns ['name' => 'file.jpg'] or ['error' => 'message'].
 */
function savePublicPhoto($file, $destDir, $baseFilename, $maxDimension = 800, $quality = 85, $maxBytes = 8388608) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['name' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'The photo could not be uploaded. Please try again with a smaller file.'];
    }
    if ($file['size'] > $maxBytes) {
        return ['error' => 'The photo is too large. Please choose one under ' . formatFileSize($maxBytes) . '.'];
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        return ['error' => 'Please upload a JPG, PNG or WebP photo.'];
    }
    $name = compressUploadedPhotoAsJpeg($file['tmp_name'], $destDir, $baseFilename, $maxDimension, $quality);
    return $name ? ['name' => $name] : ['error' => 'The photo could not be processed. Please try another one.'];
}

/**
 * Safe handling of a document uploaded through a PUBLIC form (PDF or a photo/scan). Images are resized
 * and compressed; PDFs are checked by their content and stored as they are. Random file name, size limit.
 * Returns ['name' => 'file.ext'] or ['error' => 'message'].
 */
function savePublicDocument($file, $destDir, $baseFilename, $maxBytes = 8388608) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['name' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'The document could not be uploaded. Please try again with a smaller file.'];
    }
    if ($file['size'] > $maxBytes) {
        return ['error' => 'The document is too large. Please choose a file under ' . formatFileSize($maxBytes) . '.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext === 'pdf') {
        $head = (string) @file_get_contents($file['tmp_name'], false, null, 0, 5);
        if ($head !== '%PDF-') {
            return ['error' => 'That file is not a valid PDF.'];
        }
        if (!is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }
        $name = $baseFilename . '.pdf';
        return move_uploaded_file($file['tmp_name'], rtrim($destDir, '/') . '/' . $name) ? ['name' => $name] : ['error' => 'The document could not be saved.'];
    }

    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return ['error' => 'That file is not a valid image.'];
        }
        $name = compressUploadedPhotoAsJpeg($file['tmp_name'], $destDir, $baseFilename, 1800, 85);
        return $name ? ['name' => $name] : ['error' => 'The document could not be processed.'];
    }

    return ['error' => 'Please upload a PDF, JPG, PNG or WebP file.'];
}

/**
 * Small WebP copy of a site image for grids and cards (made once, then reused).
 * uploads/gallery/photo.jpg -> uploads/gallery/thumbs/photo-w640.webp
 * Returns ['src' => path, 'width' => w, 'height' => h]; falls back to the original
 * file when the thumbnail can't be made (no GD/WebP, unreadable or tiny image).
 * $path is relative to the site root, the same form stored in the database.
 */
function mediaThumb($path, $width = 640, $quality = 78) {
    $root = dirname(__DIR__) . '/';
    $path = ltrim((string) $path, '/');
    $full = $root . $path;
    $fallback = ['src' => $path, 'width' => 0, 'height' => 0];

    if ($path === '' || strpos($path, '..') !== false || !is_file($full)) {
        return $fallback;
    }
    $info = @getimagesize($full);
    if (!$info) {
        return $fallback;
    }
    $fallback['width'] = $info[0];
    $fallback['height'] = $info[1];
    if ($info[0] <= $width || !function_exists('imagewebp')) {
        return $fallback;   // already small enough, or no WebP support on this server
    }

    $thumb_rel = dirname($path) . '/thumbs/' . pathinfo($path, PATHINFO_FILENAME) . '-w' . $width . '.webp';
    $thumb_full = $root . $thumb_rel;
    $height = (int) round($info[1] * $width / $info[0]);

    // (Re)build when missing or older than the original (the photo was replaced)
    if (!is_file($thumb_full) || filemtime($thumb_full) < filemtime($full)) {
        switch ($info[2]) {
            case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($full); break;
            case IMAGETYPE_PNG:  $src = @imagecreatefrompng($full); break;
            case IMAGETYPE_WEBP: $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($full) : false; break;
            default: $src = false;
        }
        if (!$src) {
            return $fallback;
        }
        $dst = imagecreatetruecolor($width, $height);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);
        if (!is_dir(dirname($thumb_full))) {
            @mkdir(dirname($thumb_full), 0755, true);
        }
        $ok = @imagewebp($dst, $thumb_full, $quality);
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok) {
            return $fallback;
        }
    }
    return ['src' => $thumb_rel, 'width' => $width, 'height' => $height];
}

/**
 * CSS background-image value that serves a WebP twin (photo.webp next to photo.jpg)
 * to browsers that support it. Creates the twin on first use when it's missing.
 * Use as: style="background-image: url(...); background-image: <this>"
 */
function webpBackground($path) {
    $root = dirname(__DIR__) . '/';
    $path = ltrim((string) $path, '/');
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
    if ($webp === $path || !is_file($root . $path)) {
        return '';
    }
    if (!is_file($root . $webp) || filemtime($root . $webp) < filemtime($root . $path)) {
        createWebpTwin($root . $path);
    }
    if (!is_file($root . $webp)) {
        return '';
    }
    $type = preg_match('/\.png$/i', $path) ? 'image/png' : 'image/jpeg';
    return 'image-set(url(\'' . htmlspecialchars($webp) . '\') type("image/webp"), url(\'' . htmlspecialchars($path) . '\') type("' . $type . '"))';
}
?>
