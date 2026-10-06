<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    header('Location: login?redirect=' . urlencode('cart'));
    exit();
}

$user_id = $_SESSION['user_id'];

// 處理移除商品
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['id'])) {
    $item_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user_id]);
    header('Location: cart');
    exit();
}

// 處理數量更新 (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $item_id => $qty) {
        $qty = max(1, (int)$qty);
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$qty, (int)$item_id, $user_id]);
    }
    header('Location: cart');
    exit();
}

try {
    // 獲取購物車內容
    $stmt = $pdo->prepare("
        SELECT ci.id as cart_id, ci.quantity, p.* 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    // 計算總計
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // 運費規則
    $shipping_fee = ($subtotal >= 1500 || $subtotal == 0) ? 0 : 100;
    $total = $subtotal + $shipping_fee;

} catch (PDOException $e) {
    die("資料讀取失敗: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的購物車 - TopPun香．芬</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="common.css">
    <style>
        .cart-table th { background-color: #f8f9fa; border-top: none; }
        .cart-item-img { width: 80px; height: 80px; object-fit: cover; }
        .qty-control { max-width: 120px; }
        .summary-box { background-color: #f8f9fa; padding: 30px; border: 1px solid #dee2e6; }
        .btn-checkout { background-color: #333; color: white; border: none; padding: 15px; width: 100%; font-weight: 700; transition: all 0.3s; }
        .btn-checkout:hover { background-color: #000; color: white; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container my-5 py-5">
        <h2 class="mb-5 fw-bold"><i class="bi bi-cart3"></i> 我的購物車</h2>

        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-1 text-muted"></i>
                <h4 class="mt-4 text-muted">您的購物車目前是空的</h4>
                <a href="/%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E" class="btn btn-dark mt-4 rounded-0 px-5">繼續購物</a>
            </div>
        <?php else: ?>
            <form action="cart" method="POST">
                <div class="row g-5">
                    <div class="col-lg-8">
                        <div class="table-responsive">
                            <table class="table cart-table align-middle">
                                <thead>
                                    <tr>
                                        <th>商品詳情</th>
                                        <th class="text-center">單價</th>
                                        <th class="text-center">數量</th>
                                        <th class="text-end">小計</th>
                                        <th class="text-center">移除</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars(get_thumbnail_url($item['image_main'])); ?>" class="cart-item-img me-3" alt="">
                                                    <div>
                                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                        <small class="text-muted">商品 ID: <?php echo $item['id']; ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">NT$ <?php echo number_format($item['price']); ?></td>
                                            <td class="text-center">
                                                <input type="number" name="quantities[<?php echo $item['cart_id']; ?>]" 
                                                       value="<?php echo $item['quantity']; ?>" min="1" 
                                                       class="form-control form-control-sm mx-auto text-center qty-control">
                                            </td>
                                            <td class="text-end fw-bold">NT$ <?php echo number_format($item['price'] * $item['quantity']); ?></td>
                                            <td class="text-center">
                                                <a href="cart.php?action=remove&id=<?php echo $item['cart_id']; ?>" 
                                                   class="text-danger" onclick="return confirm('確定要移除此商品嗎？')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between mt-4">
                            <a href="/%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E" class="btn btn-outline-dark rounded-0 px-4">
                                <i class="bi bi-arrow-left"></i> 繼續購物
                            </a>
                            <button type="submit" name="update_cart" class="btn btn-dark rounded-0 px-4">
                                更新購物車
                            </button>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="summary-box">
                            <h5 class="fw-bold mb-4">訂單摘要</h5>
                            <div class="d-flex justify-content-between mb-3">
                                <span>商品小計</span>
                                <span>NT$ <?php echo number_format($subtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span>運費</span>
                                <span><?php echo $shipping_fee > 0 ? 'NT$ ' . number_format($shipping_fee) : '免運費'; ?></span>
                            </div>
                            <?php if ($shipping_fee > 0): ?>
                                <div class="alert alert-info py-2 small rounded-0">
                                    再買 NT$ <?php echo number_format(1500 - $subtotal); ?> 即可享免運！
                                </div>
                            <?php endif; ?>
                            <hr>
                            <div class="d-flex justify-content-between mb-4 mt-2">
                                <span class="h5 fw-bold">總計</span>
                                <span class="h5 fw-bold text-danger">NT$ <?php echo number_format($total); ?></span>
                            </div>
                            <a href="checkout.php" class="btn-checkout text-center text-decoration-none d-block">
                                前往結帳 <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
