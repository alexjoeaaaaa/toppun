<?php
namespace Api\Services\Admin;

use PDO;
use Exception;

class ProductService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getCategories() {
        return $this->pdo->query("SELECT * FROM product_categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProducts($page = 1, $items_per_page = 10, $category_id = 0, $keyword = '') {
        $offset = ($page - 1) * $items_per_page;

        $whereParams = [];
        $whereSql = "WHERE 1=1";
        
        if ($category_id > 0) {
            $whereSql .= " AND p.category_id = :category_id";
            $whereParams[':category_id'] = $category_id;
        }
        if ($keyword !== '') {
            $whereSql .= " AND (p.name LIKE :keyword OR p.description LIKE :keyword OR p.meta_keywords LIKE :keyword)";
            $whereParams[':keyword'] = "%{$keyword}%";
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM products p " . $whereSql);
        foreach ($whereParams as $param => $value) {
            $countStmt->bindValue($param, $value);
        }
        $countStmt->execute();
        $total_products = $countStmt->fetchColumn() ?: 0;
        $total_pages = max(1, ceil($total_products / $items_per_page));

        $stmt = $this->pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id " . $whereSql . " ORDER BY p.id DESC LIMIT :limit OFFSET :offset");
        foreach ($whereParams as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_items' => $total_products,
                'items_per_page' => $items_per_page
            ]
        ];
    }

    /**
     * 取得單一商品，並同步撈取附加圖集 (product_images)。
     * description 欄位直接回傳 (呼叫端可自行 json_decode)。
     */
    public function getProductById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new Exception("找不到該商品");
        }

        // 撈取附加圖集，依 sort_order 排序
        $imgStmt = $this->pdo->prepare(
            "SELECT id, image_url, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC"
        );
        $imgStmt->execute([$id]);
        $product['gallery_images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);

        return $product;
    }

    /**
     * 新增或更新商品基本資料（不含附加圖集）。
     * description 儲存為 JSON 字串或純文字，由上層傳入。
     * 回傳商品 ID（新增時為 lastInsertId，更新時為原 $id）。
     */
    public function saveProduct($id, $data) {
        if ($id > 0) {
            $stmt = $this->pdo->prepare(
                "UPDATE products SET name=?, category_id=?, price=?, stock=?, description=?, meta_keywords=?, image_main=?, is_active=? WHERE id=?"
            );
            $success = $stmt->execute([
                $data['name'], $data['category_id'], $data['price'], $data['stock'],
                $data['description'], $data['meta_keywords'], $data['image_main'], $data['is_active'], $id
            ]);
            if (!$success) throw new Exception("儲存商品失敗");
            return $id;
        } else {
            $stmt = $this->pdo->prepare(
                "INSERT INTO products (name, category_id, price, stock, description, meta_keywords, image_main, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $success = $stmt->execute([
                $data['name'], $data['category_id'], $data['price'], $data['stock'],
                $data['description'], $data['meta_keywords'], $data['image_main'], $data['is_active']
            ]);
            if (!$success) throw new Exception("新增商品失敗");
            return (int)$this->pdo->lastInsertId();
        }
    }

    // ─── 附加圖集 (product_images) 相關方法 ────────────────────────────────

    /**
     * 新增一筆附加圖片紀錄。
     */
    public function addGalleryImage($product_id, $image_url, $sort_order = 0) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO product_images (product_id, image_url, sort_order) VALUES (?, ?, ?)"
        );
        $success = $stmt->execute([$product_id, $image_url, $sort_order]);
        if (!$success) throw new Exception("新增附加圖片失敗");
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * 刪除單筆附加圖片紀錄（由 Controller 負責刪除實體檔案）。
     * 回傳被刪除的 image_url，供 Controller 刪除實體檔案。
     */
    public function deleteGalleryImage($image_id, $product_id) {
        $stmt = $this->pdo->prepare(
            "SELECT image_url FROM product_images WHERE id = ? AND product_id = ?"
        );
        $stmt->execute([$image_id, $product_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new Exception("找不到該圖片紀錄");

        $del = $this->pdo->prepare("DELETE FROM product_images WHERE id = ? AND product_id = ?");
        $del->execute([$image_id, $product_id]);
        return $row['image_url'];
    }

    /**
     * 取得某商品的所有附加圖片，供 Controller 批次刪除實體檔案用。
     */
    public function getGalleryImages($product_id) {
        $stmt = $this->pdo->prepare(
            "SELECT id, image_url FROM product_images WHERE product_id = ?"
        );
        $stmt->execute([$product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 刪除某商品所有附加圖片紀錄（刪除商品時一起清除）。
     */
    public function deleteAllGalleryImages($product_id) {
        $stmt = $this->pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
    }

    // ─── 其他操作 ──────────────────────────────────────────────────────────

    public function deleteProduct($id) {
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        $success = $stmt->execute([$id]);
        if (!$success) throw new Exception("刪除商品失敗");
        return true;
    }

    public function toggleStatus($id) {
        $stmt = $this->pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $success = $stmt->execute([$id]);
        if (!$success) throw new Exception("更新商品狀態失敗");
        return true;
    }
}
