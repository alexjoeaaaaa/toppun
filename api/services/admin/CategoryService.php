<?php
namespace Api\Services\Admin;

use PDO;
use Exception;

class CategoryService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getCategories() {
        return $this->pdo->query("SELECT * FROM product_categories ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$category) {
            throw new Exception("找不到該商品分類");
        }
        return $category;
    }

    public function saveCategory($id, $data) {
        $name = $data['name'];
        $description = $data['description'];
        $keywords = $data['keywords'];

        if (empty($name)) {
            throw new Exception("分類名稱不可為空");
        }

        // Check if name already exists (excluding the current category)
        $stmt = $this->pdo->prepare("SELECT id FROM product_categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        if ($stmt->fetch()) {
            throw new Exception("此分類名稱已存在");
        }

        if ($id > 0) {
            // Check if we are trying to rename "尚未分類"
            $checkStmt = $this->pdo->prepare("SELECT name FROM product_categories WHERE id = ?");
            $checkStmt->execute([$id]);
            $currentName = $checkStmt->fetchColumn();
            if ($currentName === '尚未分類' && $name !== '尚未分類') {
                throw new Exception("預設之「尚未分類」不可重新命名");
            }

            $stmt = $this->pdo->prepare("UPDATE product_categories SET name = ?, description = ?, keywords = ? WHERE id = ?");
            $stmt->execute([$name, $description, $keywords, $id]);
            return $id;
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO product_categories (name, description, keywords) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $keywords]);
            return (int)$this->pdo->lastInsertId();
        }
    }

    public function deleteCategory($id) {
        // Find if the category being deleted is "尚未分類"
        $stmt = $this->pdo->prepare("SELECT name FROM product_categories WHERE id = ?");
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        if (!$cat) {
            throw new Exception("找不到該商品分類");
        }

        if ($cat['name'] === '尚未分類') {
            throw new Exception("此分類為預設之「尚未分類」，不可刪除。");
        }

        // Get or create "尚未分類" id
        $uncatId = $this->getOrCreateUncategorizedId();

        $this->pdo->beginTransaction();
        try {
            // Update products in this category to uncategorized
            $stmtUpdate = $this->pdo->prepare("UPDATE products SET category_id = ? WHERE category_id = ?");
            $stmtUpdate->execute([$uncatId, $id]);

            // Delete the category
            $stmtDelete = $this->pdo->prepare("DELETE FROM product_categories WHERE id = ?");
            $stmtDelete->execute([$id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function getOrCreateUncategorizedId() {
        $stmt = $this->pdo->prepare("SELECT id FROM product_categories WHERE name = ?");
        $stmt->execute(['尚未分類']);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }

        $stmtInsert = $this->pdo->prepare("INSERT INTO product_categories (name, description, keywords) VALUES (?, ?, ?)");
        $stmtInsert->execute(['尚未分類', '預設分類，當其他分類被刪除時，商品會被移至此分類。', '預設,尚未分類']);
        return (int)$this->pdo->lastInsertId();
    }
}
