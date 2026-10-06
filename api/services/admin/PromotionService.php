<?php
namespace Api\Services\Admin;

use PDO;
use Exception;

class PromotionService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    // ==================== Coupons ====================

    public function getCoupons() {
        $stmt = $this->pdo->query("SELECT * FROM coupons ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function saveCoupon($data, $id = 0) {
        $code = trim($data['code'] ?? '');
        if ($code === '') {
            throw new Exception('折扣代碼不能為空');
        }

        $type       = $data['type'] ?? 'fixed';
        $value      = (float)($data['value'] ?? 0);
        $min_spend  = (float)($data['min_spend'] ?? 0);
        $is_first   = isset($data['is_first_order_only']) ? 1 : 0;
        $is_active  = isset($data['is_active']) ? 1 : 0;
        $expiry     = !empty($data['expiry_date']) ? $data['expiry_date'] : null;

        if ($id > 0) {
            $stmt = $this->pdo->prepare("UPDATE coupons SET code=?, type=?, value=?, min_spend=?, is_first_order_only=?, expiry_date=?, is_active=? WHERE id=?");
            $stmt->execute([$code, $type, $value, $min_spend, $is_first, $expiry, $is_active, $id]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO coupons (code, type, value, min_spend, is_first_order_only, expiry_date, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $value, $min_spend, $is_first, $expiry, $is_active]);
        }
        return true;
    }

    public function deleteCoupon($id) {
        $this->pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
        return true;
    }

    // ==================== Global Promotions ====================

    public function getPromotions() {
        $stmt = $this->pdo->query("SELECT * FROM global_promotions ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function savePromotion($data, $id = 0) {
        $name = trim($data['name'] ?? '');
        if ($name === '') {
            throw new Exception('活動名稱不能為空');
        }

        $type      = $data['type'] ?? 'free_shipping';
        $threshold = (float)($data['threshold_amount'] ?? 0);
        $discount  = (float)($data['discount_value'] ?? 0);
        $is_active = isset($data['is_active']) ? 1 : 0;

        if ($id > 0) {
            $stmt = $this->pdo->prepare("UPDATE global_promotions SET name=?, type=?, threshold_amount=?, discount_value=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $type, $threshold, $discount, $is_active, $id]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO global_promotions (name, type, threshold_amount, discount_value, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $type, $threshold, $discount, $is_active]);
        }
        return true;
    }

    public function deletePromotion($id) {
        $this->pdo->prepare("DELETE FROM global_promotions WHERE id = ?")->execute([$id]);
        return true;
    }
}
