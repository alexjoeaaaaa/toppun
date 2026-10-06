<?php
namespace Api\Services;

use Exception;

class ImageService {
    /**
     * Process and save an uploaded image to WebP format with a thumbnail.
     * 
     * @param string $sourceTmpPath Uploaded temporary file path
     * @param string $fileName Original filename
     * @param string $destinationDir Target directory
     * @param int $maxMainWidth Maximum width for the main image
     * @return string Returns the saved main image filename (e.g. xxxx.webp)
     * @throws Exception If image processing fails
     */
    public static function processAndSaveImage($sourceTmpPath, $fileName, $destinationDir, $maxMainWidth = 1200) {
        $maxThumbWidth = 300;
        
        $imageInfo = getimagesize($sourceTmpPath);
        if ($imageInfo === false) {
            throw new Exception("無效的圖片檔案");
        }
        
        $mimeType = $imageInfo['mime'];
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        // Load image based on MIME type
        $sourceImage = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourceTmpPath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourceTmpPath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourceTmpPath);
                break;
            default:
                throw new Exception("不支援的圖片格式 (僅支援 JPG, PNG, WEBP)。實際格式: " . $mimeType);
        }
        
        if (!$sourceImage) {
            throw new Exception("無法讀取圖片");
        }
        
        // Handle transparency for PNG and WebP
        if ($mimeType == 'image/png' || $mimeType == 'image/webp') {
            imagepalettetotruecolor($sourceImage);
            imagealphablending($sourceImage, true);
            imagesavealpha($sourceImage, true);
        }

        // Generate base name for the new WebP file
        $fileBaseName = md5(time() . '_' . $fileName);
        $newFileName = $fileBaseName . '.webp';
        $thumbFileName = $fileBaseName . '_thumb.webp';
        
        // Ensure destination directory exists
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }
        
        $mainDestPath = rtrim($destinationDir, '/') . '/' . $newFileName;
        $thumbDestPath = rtrim($destinationDir, '/') . '/' . $thumbFileName;
        
        // 1. Process Main Image
        $mainWidth = $width;
        $mainHeight = $height;
        if ($width > $maxMainWidth) {
            $mainWidth = $maxMainWidth;
            $mainHeight = floor($height * ($maxMainWidth / $width));
        }
        
        $mainImage = imagecreatetruecolor($mainWidth, $mainHeight);
        // Retain transparency
        imagealphablending($mainImage, false);
        imagesavealpha($mainImage, true);
        $transparent = imagecolorallocatealpha($mainImage, 255, 255, 255, 127);
        imagefilledrectangle($mainImage, 0, 0, $mainWidth, $mainHeight, $transparent);
        
        imagecopyresampled($mainImage, $sourceImage, 0, 0, 0, 0, $mainWidth, $mainHeight, $width, $height);
        
        // Save Main Image as WebP
        if (!imagewebp($mainImage, $mainDestPath, 85)) {
            imagedestroy($sourceImage);
            imagedestroy($mainImage);
            throw new Exception("無法儲存主圖片");
        }
        imagedestroy($mainImage);
        
        // 2. Process Thumbnail Image
        $thumbWidth = $width;
        $thumbHeight = $height;
        if ($width > $maxThumbWidth) {
            $thumbWidth = $maxThumbWidth;
            $thumbHeight = floor($height * ($maxThumbWidth / $width));
        }
        
        $thumbImage = imagecreatetruecolor($thumbWidth, $thumbHeight);
        // Retain transparency
        imagealphablending($thumbImage, false);
        imagesavealpha($thumbImage, true);
        $transparentThumb = imagecolorallocatealpha($thumbImage, 255, 255, 255, 127);
        imagefilledrectangle($thumbImage, 0, 0, $thumbWidth, $thumbHeight, $transparentThumb);
        
        imagecopyresampled($thumbImage, $sourceImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);
        
        // Save Thumbnail Image as WebP
        if (!imagewebp($thumbImage, $thumbDestPath, 80)) {
            imagedestroy($sourceImage);
            imagedestroy($thumbImage);
            throw new Exception("無法儲存縮圖");
        }
        
        imagedestroy($thumbImage);
        imagedestroy($sourceImage);
        
        return $newFileName;
    }

    /**
     * Delete an image and its thumbnail if they exist.
     * 
     * @param string $mainImagePath Absolute or relative path to the main image
     * @return bool True if main image was deleted or didn't exist
     */
    public static function deleteImageWithThumb($mainImagePath) {
        if (empty($mainImagePath)) return true;
        
        $success = true;
        if (file_exists($mainImagePath)) {
            $success = unlink($mainImagePath);
        }
        
        // Generate thumb path
        $pathInfo = pathinfo($mainImagePath);
        $thumbPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
        
        return $success;
    }
}
