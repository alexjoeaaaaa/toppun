<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '請先登入會員。', 'error_code' => 'auth_required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的商品或數量。']);
    exit();
}

try {
    // 檢查商品是否存在且上架中
    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => '商品不存在或已下架。']);
        exit();
    }

    // 檢查購物車是否已有此商品
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing_item = $stmt->fetch();

    if ($existing_item) {
        // 更新數量
        $new_quantity = $existing_item['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$new_quantity, $existing_item['id']]);
    } else {
        // 新增商品
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }

    // 獲取目前購物車總件數
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_count = (int)$stmt->fetchColumn();

    echo json_encode([
        'success' => true, 
        'message' => '已成功加入購物車！', 
        'cart_count' => $cart_count
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤：' . $e->getMessage()]);
}
?>
