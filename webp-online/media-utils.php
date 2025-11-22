<?php

require_once __DIR__ . '/../config.php';

function getThumbBasename(string $filename): string {
    $base = pathinfo($filename, PATHINFO_FILENAME);
    return $base . '.' . THUMB_EXTENSION;
}

function getUploadThumbPath(string $filename): string {
    return UPLOAD_THUMBS_DIR . getThumbBasename($filename);
}

function getConvertThumbPath(string $filename): string {
    return CONVERT_THUMBS_DIR . getThumbBasename($filename);
}

function getUploadThumbPublicUrl(string $filename): ?string {
    $thumbPath = getUploadThumbPath($filename);
    if (is_file($thumbPath)) {
        return UPLOAD_THUMBS_PUBLIC_URL . basename($thumbPath);
    }
    return null;
}

function getConvertThumbPublicUrl(string $filename): ?string {
    $thumbPath = getConvertThumbPath($filename);
    if (is_file($thumbPath)) {
        return CONVERT_THUMBS_PUBLIC_URL . basename($thumbPath);
    }
    return null;
}

function generateThumbnail(string $sourcePath, string $targetPath, int $maxSize = 360): bool {
    if (!is_file($sourcePath)) {
        return false;
    }

    $targetDir = dirname($targetPath);
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }

    $info = @getimagesize($sourcePath);
    if (!$info) {
        return false;
    }

    [$width, $height] = $info;
    if ($width === 0 || $height === 0) {
        return false;
    }

    $mime = $info['mime'] ?? '';
    switch ($mime) {
        case 'image/jpeg':
            $src = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $src = function_exists('imagecreatefrompng') ? @imagecreatefrompng($sourcePath) : false;
            break;
        case 'image/gif':
            $src = function_exists('imagecreatefromgif') ? @imagecreatefromgif($sourcePath) : false;
            break;
        case 'image/webp':
            $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false;
            break;
        default:
            $src = false;
    }

    if (!$src) {
        return false;
    }

    $ratio = $width / $height;
    if ($width > $height) {
        $newWidth = min($width, $maxSize);
        $newHeight = (int)round($newWidth / $ratio);
    } else {
        $newHeight = min($height, $maxSize);
        $newWidth = (int)round($newHeight * $ratio);
    }

    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($thumb, false);
    imagesavealpha($thumb, true);
    imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    $result = imagejpeg($thumb, $targetPath, 82);

    imagedestroy($src);
    imagedestroy($thumb);

    if ($result) {
        @chmod($targetPath, 0644);
    }

    return $result;
}

