<?php

namespace Api\Services\Admin;

class ImageService {

    /**
     * 處理上傳的圖片並轉存為 WebP，同時生成縮圖
     *
     * @param string $sourceFile 來源檔案的暫存路徑 (如 $_FILES['image']['tmp_name'])
     * @param string $uploadDir 上傳儲存的目標目錄 (相對於網站根目錄，需確保有權限)
     * @param string $baseName 基礎檔名（不含副檔名，如 md5(time())）
     * @param int $maxWidth 主圖最大寬度預設 1200
     * @param int $thumbMaxWidth 縮圖最大寬度預設 300
     * @return string|false 成功回傳主圖的相對路徑 (包含目錄與檔名)，失敗回傳 false
     */
    public function processAndSave($sourceFile, $uploadDir, $baseName, $maxWidth = 1200, $thumbMaxWidth = 300) {
        if (!file_exists($sourceFile)) {
            return false;
        }

        // 確保目錄存在
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // 取得圖片資訊
        $imageInfo = getimagesize($sourceFile);
        if (!$imageInfo) {
            return false;
        }

        list($width, $height, $type) = $imageInfo;
        
        // 載入圖片資源
        $sourceImage = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImage = @imagecreatefromjpeg($sourceFile);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = @imagecreatefrompng($sourceFile);
                break;
            case IMAGETYPE_WEBP:
                $sourceImage = @imagecreatefromwebp($sourceFile);
                break;
            case IMAGETYPE_GIF:
                $sourceImage = @imagecreatefromgif($sourceFile);
                break;
            default:
                return false; // 不支援的格式
        }

        if (!$sourceImage) {
            return false;
        }

        // --- 1. 處理主圖 ---
        $mainFileName = $baseName . '.webp';
        $mainFilePath = rtrim($uploadDir, '/') . '/' . $mainFileName;
        $this->resizeAndSaveAsWebp($sourceImage, $width, $height, $maxWidth, $mainFilePath, 85);

        // --- 2. 處理縮圖 ---
        $thumbFileName = $baseName . '_thumb.webp';
        $thumbFilePath = rtrim($uploadDir, '/') . '/' . $thumbFileName;
        $this->resizeAndSaveAsWebp($sourceImage, $width, $height, $thumbMaxWidth, $thumbFilePath, 80);

        // 釋放記憶體
        imagedestroy($sourceImage);

        return rtrim($uploadDir, '/') . '/' . $mainFileName;
    }

    /**
     * 縮放圖片並儲存為 WebP
     */
    private function resizeAndSaveAsWebp($sourceImage, $originalWidth, $originalHeight, $maxWidth, $destinationPath, $quality = 85) {
        if ($originalWidth > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = (int)($originalHeight * ($maxWidth / $originalWidth));
        } else {
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // 處理透明度背景
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);

        imagecopyresampled(
            $newImage, $sourceImage,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $originalWidth, $originalHeight
        );

        // 儲存為 webp
        imagewebp($newImage, $destinationPath, $quality);
        imagedestroy($newImage);
    }
}
