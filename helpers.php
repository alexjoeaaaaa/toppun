<?php
/**
 * helpers.php
 * 全站共用工具函式庫
 * 由 db.php 自動載入，無需單獨 require
 */

/**
 * 將圖片 URL 轉換為縮圖 URL
 * 例：image.webp → image_thumb.webp
 */
if (!function_exists('get_thumbnail_url')) {
    function get_thumbnail_url(string $url): string {
        if (empty($url)) return '';
        return preg_replace('/\.([a-zA-Z0-9]+)$/', '_thumb.$1', $url);
    }
}

if (!function_exists('resolve_product_category_name')) {
    function resolve_product_category_name(array $product, PDO $pdo): string {
        $category_name = $product['category_name'] ?? '';
        $category_id = isset($product['category_id']) ? (int)$product['category_id'] : 0;

        if ($category_name === '' && $category_id > 0) {
            try {
                $stmt = $pdo->prepare("SELECT name FROM product_categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $category_name = $stmt->fetchColumn() ?: '';
            } catch (Exception $e) {
                $category_name = '';
            }
        }

        return $category_name !== '' ? $category_name : 'product';
    }
}

if (!function_exists('get_product_name_suffix')) {
    function get_product_name_suffix(array $product, PDO $pdo): int {
        $name = $product['name'] ?? '';
        $id = (int)($product['id'] ?? 0);
        $category_id = isset($product['category_id']) ? (int)$product['category_id'] : 0;

        if ($name === '' || $id <= 0 || $category_id <= 0) {
            return 0;
        }

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE name = ? AND category_id = ? AND is_active = 1 AND id < ?");
            $stmt->execute([$name, $category_id, $id]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
}

/**
 * 商品名稱路徑（維持既有 SEO 友善網址）
 * 例：/全部分類/香氛暖燈/北歐金屬感暖燈
 */
if (!function_exists('get_product_url')) {
    function get_product_url(array $product, PDO $pdo): string {
        if (empty($product) || !isset($product['id'])) return '#';

        $name = $product['name'] ?? '';
        $category_name = resolve_product_category_name($product, $pdo);
        $count = get_product_name_suffix($product, $pdo);

        $encoded_cat = rawurlencode($category_name);
        $encoded_name = rawurlencode($name);

        if ($count === 0) {
            return '/%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E/' . $encoded_cat . '/' . $encoded_name;
        }

        return '/%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E/' . $encoded_cat . '/' . $encoded_name . '-' . ($count + 1);
    }
}

/**
 * 商品 ID 路徑（穩定、不受改名影響）
 * 例：/全部分類/香氛暖燈/14
 */
if (!function_exists('get_product_url_by_id')) {
    function get_product_url_by_id(array $product, PDO $pdo): string {
        if (empty($product) || !isset($product['id'])) return '#';

        $category_name = resolve_product_category_name($product, $pdo);
        $encoded_cat = rawurlencode($category_name);
        $id = (int)$product['id'];

        return '/%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E/' . $encoded_cat . '/' . $id;
    }
}

if (!function_exists('normalize_request_path')) {
    function normalize_request_path(string $path): string {
        $path = urldecode($path);
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }
}

/**
 * 判斷目前請求路徑是否為此商品的有效名稱網址或 ID 網址
 */
if (!function_exists('is_valid_product_request_path')) {
    function is_valid_product_request_path(array $product, PDO $pdo, string $path): bool {
        if (empty($product) || !isset($product['id'])) return false;

        $path = normalize_request_path($path);
        $name_url = normalize_request_path(get_product_url($product, $pdo));
        $id_url = normalize_request_path(get_product_url_by_id($product, $pdo));

        if ($path === $name_url || $path === $id_url) {
            return true;
        }

        // 容許 /全部分類/{任意分類}/{id} 或 /全部分類/{任意分類}/{id}-後綴
        $product_id = (int)$product['id'];
        if (preg_match('#^/全部分類/[^/]+/(\d+)(?:-[^/]*)?$#u', $path, $matches)) {
            return (int)$matches[1] === $product_id;
        }

        return false;
    }
}

if (!function_exists('is_direct_product_detail_request')) {
    function is_direct_product_detail_request(string $path): bool {
        $path = normalize_request_path($path);
        return (bool)preg_match('#^/product-detail$#', $path);
    }
}

if (!function_exists('extract_product_intro_text')) {
    function extract_product_intro_text(array $product): string {
        if (empty($product['description'])) {
            return '';
        }

        $raw = json_decode($product['description'], true);
        if (is_array($raw)) {
            if (isset($raw[0])) {
                return '';
            }
            return trim((string)($raw['intro'] ?? ''));
        }

        return trim((string)$product['description']);
    }
}

if (!function_exists('get_product_seo_title')) {
    function get_product_seo_title(array $product): string {
        $name = trim($product['name'] ?? '');
        $category = trim($product['category_name'] ?? '');
        $brand = 'TopPun香．芬';

        if ($category !== '') {
            $title = $name . '｜' . $category . '｜' . $brand;
        } else {
            $title = $name . '｜' . $brand;
        }

        if (mb_strlen($title) > 32) {
            $suffix = $category !== '' ? ('｜' . $category . '｜' . $brand) : ('｜' . $brand);
            $max_name_len = 32 - mb_strlen($suffix);
            if ($max_name_len > 0) {
                $title = mb_substr($name, 0, $max_name_len) . $suffix;
            }
        }

        return $title;
    }
}

if (!function_exists('get_product_meta_description')) {
    function get_product_meta_description(array $product): string {
        $text = extract_product_intro_text($product);
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', trim($text));

        if ($text === '') {
            $text = trim($product['name'] ?? '') . ' — TopPun香．芬官方商城';
        }

        if (mb_strlen($text) > 120) {
            $text = mb_substr($text, 0, 120) . '…';
        }

        return $text;
    }
}

if (!function_exists('get_product_canonical_url')) {
    function get_product_canonical_url(array $product, PDO $pdo): string {
        return 'https://mcandle.fxinspire.com' . get_product_url($product, $pdo);
    }
}