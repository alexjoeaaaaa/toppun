<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 檢查是否登入
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // 獲取購物車內容
    $stmt = $pdo->prepare("
        SELECT ci.quantity, p.* 
        FROM cart_items ci 
        JOIN products p ON ci.product_id = p.id 
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();

    // 獲取預設收件資訊
    $default_addr_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1 LIMIT 1");
    $default_addr_stmt->execute([$user_id]);
    $default_address = $default_addr_stmt->fetch();

    if (empty($cart_items)) {
        header('Location: cart');
        exit();
    }

    // 計算總計
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    // 5. 獲取所有啟用的全球促銷活動
$promo_stmt = $pdo->query("SELECT * FROM global_promotions WHERE is_active = 1");
$active_promotions = $promo_stmt->fetchAll();

$promotion_discount = 0;
$free_shipping = false;
$applied_promo_name = "";

foreach ($active_promotions as $promo) {
    if ($subtotal >= $promo['threshold_amount']) {
        if ($promo['type'] === 'free_shipping') {
            $free_shipping = true;
            $applied_promo_name = $promo['name'];
        } elseif ($promo['type'] === 'threshold_discount') {
            // 取最高金額的折抵
            if ($promo['discount_value'] > $promotion_discount) {
                $promotion_discount = $promo['discount_value'];
                $applied_promo_name = $promo['name'];
            }
        }
    }
}

$original_shipping_fee_value = 100; // Assuming default shipping fee is 100
$shipping_fee = $free_shipping ? 0 : $original_shipping_fee_value;
$total = $subtotal - $promotion_discount + $shipping_fee;

// 6. 處理折扣碼（如果有的話）
$applied_coupon = null;
$coupon_discount = 0;
$coupon_error = null;
if (isset($_POST['coupon_code']) && !empty(trim($_POST['coupon_code']))) {
    $code = trim($_POST['coupon_code']);
    $cp_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND (expiry_date IS NULL OR expiry_date > NOW())");
    $cp_stmt->execute([$code]);
    $applied_coupon = $cp_stmt->fetch();

    if ($applied_coupon) {
        if ($subtotal < $applied_coupon['min_spend']) {
            $coupon_error = "未達該折扣碼最低消費門檻 NT$ " . number_format($applied_coupon['min_spend']);
            $applied_coupon = null;
        } elseif ($applied_coupon['is_first_order_only']) {
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND order_status != 'cancelled'");
            $check_stmt->execute([$user_id]);
            if ($check_stmt->fetchColumn() > 0) {
                $coupon_error = "此折扣碼限首購用戶使用";
                $applied_coupon = null;
            }
        }
        
        if ($applied_coupon) {
            if ($applied_coupon['type'] === 'fixed') {
                $coupon_discount = $applied_coupon['value'];
            } else {
                $coupon_discount = round($subtotal * ($applied_coupon['value'] / 100));
            }
        }
    } else {
        $coupon_error = "無效或已過期的折扣碼";
    }
}

$total = max(0, $total - $coupon_discount);

    // 處理結帳提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
        $pdo->beginTransaction();

        try {
            // 生成訂單編號 (GINANA + 日期 + 隨機)
            $order_number = 'GINANA' . date('YmdHis') . rand(10, 99);
            
            // 1. 建立訂單主檔
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, applied_coupon_code, promotion_discount, coupon_discount, original_shipping_fee)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $full_shipping_info = "收件人: " . $_POST['name'] . "\n電話: " . $_POST['phone'] . "\n地址: " . $_POST['address'];
        $stmt->execute([
            $user_id, 
            $total, 
            $full_shipping_info, 
            $_POST['payment_method'],
            $applied_coupon ? $applied_coupon['code'] : null,
            $promotion_discount,
            $coupon_discount,
            $original_shipping_fee_value // 原本應付運費
        ]);
            $order_id = $pdo->lastInsertId();

            // 2. 建立訂單明細
            foreach ($cart_items as $item) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_id, 
                    $item['id'], 
                    $item['quantity'],
                    $item['price']
                ]);
            }

            // 3. 清空購物車
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();
            
            // 顯示成功頁面 (這裡簡化，直接 alert 後導向首頁或會員中心)
            echo "<script>alert('訂單已成功建立！訂單編號：$order_number'); window.location.href='index.php';</script>";
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            die("下單失敗: " . $e->getMessage());
        }
    }

} catch (PDOException $e) {
    die("資料讀取失敗: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>結帳確認 - TopPun香．芬</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="common.css">
    <style>
        .checkout-container { max-width: 900px; margin: 50px auto; }
        .checkout-section { background: white; border: 1px solid #dee2e6; padding: 30px; margin-bottom: 30px; }
        .order-summary-mini { background: #f8f9fa; padding: 20px; }
        .mini-item { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container checkout-container">
        <h2 class="mb-5 fw-bold text-center">結帳下單</h2>

        <form action="checkout" method="POST">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="checkout-section">
                        <h5 class="fw-bold mb-4">收件人資訊</h5>
                        <div class="mb-3">
                            <label class="form-label">收件人姓名</label>
                            <input type="text" name="name" class="form-control rounded-0" value="<?php echo htmlspecialchars($default_address['recipient_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">聯絡電話</label>
                            <input type="tel" name="phone" class="form-control rounded-0" value="<?php echo htmlspecialchars($default_address['recipient_phone'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">配送地址</label>
                            <textarea name="address" class="form-control rounded-0" rows="3" required><?php echo htmlspecialchars($default_address['address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="checkout-section">
                        <h5 class="fw-bold mb-4">付款方式</h5>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" value="credit_card" id="pay1" checked>
                            <label class="form-check-label" for="pay1">信用卡線上付款</label>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="payment_method" value="bank_transfer" id="pay2">
                            <label class="form-check-label" for="pay2">銀行轉帳 / ATM</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" value="cod" id="pay3">
                            <label class="form-check-label" for="pay3">貨到付款 (需加收 $30 手續費)</label>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="checkout-section order-summary-mini">
                        <h5 class="fw-bold mb-4">訂單明細</h5>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="mini-item">
                                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                <span>NT$ <?php echo number_format($item['price'] * $item['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="mini-item">
                            <span>小計</span>
                            <span>NT$ <?php echo number_format($subtotal); ?></span>
                        </div>
                        <?php if ($promotion_discount > 0): ?>
                            <div class="mini-item text-success">
                                <span>活動折抵 (<?php echo htmlspecialchars($applied_promo_name); ?>)</span>
                                <span>- NT$ <?php echo number_format($promotion_discount); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- 折扣碼輸入 -->
                        <div class="mt-3 mb-3">
                            <label class="small text-muted mb-1">使用折扣碼</label>
                            <div class="input-group">
                                <input type="text" name="coupon_code" id="coupon_input" class="form-control form-control-sm rounded-0" placeholder="請輸入折扣碼" value="<?php echo htmlspecialchars($_POST['coupon_code'] ?? ''); ?>">
                                <button type="submit" name="apply_coupon" class="btn btn-outline-dark btn-sm rounded-0">套用</button>
                            </div>
                            <?php if (isset($coupon_error)): ?>
                                <div class="text-danger small mt-1"><?php echo $coupon_error; ?></div>
                            <?php endif; ?>
                            <?php if ($applied_coupon): ?>
                                <div class="text-success small mt-1">已套用折扣碼：<?php echo $applied_coupon['code']; ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if ($coupon_discount > 0): ?>
                            <div class="mini-item text-success">
                                <span>折扣碼折抵</span>
                                <span>- NT$ <?php echo number_format($coupon_discount); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="mini-item">
                            <span>運費 <?php if($free_shipping) echo '<span class="badge bg-success ms-1">滿額免運</span>'; ?></span>
                            <span>NT$ <?php echo number_format($shipping_fee); ?></span>
                        </div>
                        <div class="mini-item h5 fw-bold mt-3 text-danger">
                            <span>應付總額</span>
                            <span>NT$ <?php echo number_format($total); ?></span>
                        </div>
                        <button type="submit" name="place_order" class="btn btn-dark w-100 mt-4 py-3 fw-bold rounded-0">
                            確認並下單
                        </button>
                        <p class="text-center mt-3 mb-0 small text-muted">點擊下單即表示您同意本站的服務條款與配送政策。</p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
