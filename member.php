<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 檢查登入狀態
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

$user_id = $_SESSION['user_id'];

// 處理地址操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_address':
                $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, recipient_name, recipient_phone, address, is_default) VALUES (?, ?, ?, ?, ?)");
                $is_default = isset($_POST['is_default']) ? 1 : 0;
                // 如果設為預設，先把其他的拿掉
                if ($is_default) {
                    $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
                } else {
                    // 如果目前沒有任何地址，則自動設為預設
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
                    $check_stmt->execute([$user_id]);
                    if ($check_stmt->fetchColumn() == 0) {
                        $is_default = 1;
                    }
                }
                $stmt->execute([$user_id, $_POST['recipient_name'], $_POST['recipient_phone'], $_POST['address'], $is_default]);
                header("Location: member#address-section");
                exit();
            case 'delete_address':
                $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['address_id'], $user_id]);
                header("Location: member#address-section");
                exit();
            case 'set_default':
                $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
                $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$_POST['address_id'], $user_id]);
                header("Location: member#address-section");
                exit();
        }
    }
}

// 獲取使用者資料
try {
    $stmt = $pdo->prepare("SELECT u.username, u.email, u.role, p.* FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // 如果沒有 profile，建立一個空的預設
    if (!$user['display_name']) {
        $user['display_name'] = $user['username'];
    }

    // 獲取地址列表
    $addr_stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $addr_stmt->execute([$user_id]);
    $addresses = $addr_stmt->fetchAll();

    // 訂單分頁邏輯
   // 訂單篩選條件
$time_filter = isset($_GET['time_filter']) ? $_GET['time_filter'] : 'all';
$time_sql = "";
if ($time_filter === '1month') {
    $time_sql = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($time_filter === '3months') {
    $time_sql = " AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
}

// 訂單分頁邏輯
$items_per_page = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

// 獲取該會員的訂單數量（用於分頁）
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id" . $time_sql);
$count_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$count_stmt->execute();
$total_user_orders = $count_stmt->fetchColumn() ?: 0;
$total_user_pages = ceil($total_user_orders / $items_per_page);

// 獲取該會員的訂單
$orders_stmt = $pdo->prepare("
    SELECT * FROM orders WHERE user_id = :user_id " . $time_sql . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset
");
$orders_stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$orders_stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$orders_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$orders_stmt->execute();
$user_orders = $orders_stmt->fetchAll();

    // --- E. 促銷管理資料 ---
    $coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();
    $promotions = $pdo->query("SELECT * FROM global_promotions ORDER BY created_at DESC")->fetchAll();

} catch (PDOException $e) {
    die("讀取資料失敗: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>會員中心 - TopPun香．芬</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- 自訂 CSS -->
    <link rel="stylesheet" href="common.css">
    <style>
        body {
            background-color: #fafafa;
        }

        .member-header {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .breadcrumb-section {
            background-color: #f8f5f2;
            padding: 15px 0;
            margin-bottom: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 0;
            box-shadow: none;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #d8b09a;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 0;
            object-fit: cover;
            border: 2px solid #fcb891ff;
            box-shadow: none;
        }

        .member-badge {
            display: inline-block;
            padding: 5px 15px;
            background: #fcb891ff;
            color: white;
            border-radius: 0;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .points-display {
            background: #fcb891ff;
            color: white;
            padding: 15px 20px;
            border-radius: 0;
            text-align: center;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: none;
            border: 1px solid #d8b09a;
        }

        .section-card {
            background: white;
            border-radius: 0;
            box-shadow: none;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #d8b09a;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #d77244ff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #fcb891ff;
        }

        .order-table th {
            background-color: #f8f5f2;
            font-weight: 600;
            color: #a0522d;
            border: 1px solid #e8d5c8;
        }

        .order-table td {
            border: 1px solid #e8d5c8;
        }

        .order-status {
            padding: 5px 12px;
            border-radius: 0;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid;
        }

        .status-paid {
            background-color: #d4e8d4;
            color: #2d5f2d;
            border-color: #8db88d;
        }

        .status-shipping {
            background-color: #f5e4d0;
            color: #c17d4a;
            border-color: #d8b09a;
        }

        .status-completed {
            background-color: #e8d8e8;
            color: #6a4a6a;
            border-color: #b89ab8;
        }

        .status-cancelled {
            background-color: #f5f5f5;
            color: #757575;
            border-color: #bdbdbd;
        }

        .status-unpaid {
            background-color: #fff3e0;
            color: #e65100;
            border-color: #ffb74d;
        }

        /* 訂單明細摺疊樣式 */
        .order-details-row {
            background-color: #fdfaf7;
        }
        .order-details-inner {
            padding: 15px 30px;
            border-bottom: 1px solid #e8d5c8;
        }
        .details-table {
            width: 100%;
            margin-bottom: 0;
            background-color: white;
            border: 1px solid #e8d5c8;
        }
        .details-table th {
            background-color: #fdf5f0;
            font-size: 0.85rem;
            padding: 8px;
        }
        .details-table td {
            font-size: 0.9rem;
            padding: 8px;
        }

        .address-card {
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 20px;
            margin-bottom: 15px;
            position: relative;
            transition: all 0.3s;
            background-color: white;
        }

        .address-card:hover {
            border-color: #fcb891ff;
            box-shadow: 0 0 0 2px #e8d5c8;
        }

        .address-card.default {
            border-color: #fcb891ff;
            border-width: 2px;
            background: #f8f5f2;
        }

        .default-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #fcb891ff;
            color: white;
            padding: 3px 10px;
            border-radius: 0;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid #fcb891ff;
        }

        .btn-edit,
        .btn-delete {
            padding: 5px 15px;
            font-size: 0.85rem;
            border-radius: 0;
            border: 1px solid;
        }

        .btn-edit {
            background-color: #e89b6f;
            color: white;
            border-color: #e89b6f;
        }

        .btn-edit:hover {
            background-color: #fcb891ff;
            color: white;
            border-color: #fcb891ff;
        }

        .btn-delete {
            background-color: #cd9675ff;
            color: white;
            border-color: #fcb891ff;
        }

        .btn-delete:hover {
            background-color: #c17d4a;
            border-color: #c17d4a;
        }

        .login-record {
            padding: 12px;
            background-color: #f8f5f2;
            border-left: 2px solid #fcb891ff;
            margin-bottom: 10px;
            border-radius: 0;
        }

        .btn-logout {
            background: #fcb891ff;
            color: white;
            border: 1px solid #fcb891ff;
            padding: 12px 40px;
            border-radius: 0;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(216, 138, 94, 0.3);
            color: white;
            background-color: #c17d4a;
        }

        .filter-btn-group .btn {
            border-radius: 0;
            padding: 8px 20px;
            margin-right: 10px;
            border: 1px solid #d8b09a;
            color: #a0522d;
        }

        .filter-btn-group .btn.active {
            background-color: #fcb891ff;
            color: white;
            border-color: #fcb891ff;
        }

        .filter-btn-group .btn:hover {
            background-color: #fcb891ff;
            color: white;
            border-color: #d8b09a;
        }

        .btn-primary {
            background-color: #e89b6f;
            border-color: #e89b6f;
            border-radius: 0;
        }

        .btn-primary:hover {
            background-color: #fcb891ff;
            border-color: #fcb891ff;
        }

        .btn-outline-primary {
            color: #fcb891ff;
            border-color: #fcb891ff;
            border-radius: 0;
            border-width: 1px;
        }

        .btn-outline-primary:hover {
            background-color: #fcb891ff;
            border-color: #fcb891ff;
            color: white;
        }

        .btn-outline-secondary {
            color: #a0522d;
            border-color: #d8b09a;
            border-radius: 0;
            border-width: 1px;
        }

        .btn-outline-secondary:hover {
            background-color: #e89b6f;
            border-color: #e89b6f;
            color: white;
        }

        .page-link {
            color: #a0522d;
            border-radius: 0;
            border: 1px solid #e8d5c8;
        }

        .page-item.active .page-link {
            background-color: #fcb891ff;
            border-color: #fcb891ff;
        }

        .page-link:hover {
            color: #fcb891ff;
            background-color: #f8f5f2;
            border-color: #d8b09a;
        }
    </style>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&family=Noto+Serif+TC:wght@400;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <!-- 導航欄 -->
    <?php include 'header.php'; ?>

    <!-- 大圖輪播 (Carousel) -->
    <div id="memberCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#memberCarousel" data-bs-slide-to="0" class="active"
                aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#memberCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#memberCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="headimages/pexels-ekaterina-bolovtsova-6311975.jpg" class="d-block w-100" alt="會員專屬優惠">
            </div>
            <div class="carousel-item">
                <img src="headimages/pexels-yaroslav-shuraev-6811708.jpg" class="d-block w-100" alt="會員福利">
            </div>
            <div class="carousel-item">
                <img src="headimages/pexels-serenakoi-5801528.jpg" class="d-block w-100" alt="會員活動">
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#memberCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#memberCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

    <!-- 標題 -->
    <div class="member-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">會員中心</h1>
            <p class="lead">管理您的個人資訊與訂單</p>
        </div>
    </div>

    <!-- 麵包屑 -->
    <div class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">首頁</a></li>
                    <li class="breadcrumb-item active" aria-current="page">會員中心</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container mb-5">
        <!-- 個人資訊區塊 -->
        <div class="profile-card">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <img src="<?php echo htmlspecialchars($user['avatar'] ?: 'https://via.placeholder.com/120'); ?>" alt="會員頭像" class="profile-avatar">
                    <a href="user_profile.php" class="btn btn-sm btn-outline-secondary mt-2 rounded-pill">
                        <i class="bi bi-camera"></i> 更換頭像
                    </a>
                </div>
                <div class="col-md-6">
                    <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($user['display_name']); ?></h3>
                    <span class="member-badge">
                        <i class="bi bi-star-fill"></i> <?php echo htmlspecialchars($user['member_level'] ?? '一般會員'); ?>
                    </span>
                    <p class="text-muted mt-3 mb-0">
                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                    <p class="text-muted">
                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone'] ?? '未提供電話'); ?>
                    </p>
                </div>
                <div class="col-md-4">
                    <div class="points-display">
                        <i class="bi bi-gift"></i> 會員積分
                        <div class="fs-2"><?php echo number_format($user['points'] ?? 0); ?> 點</div>
                    </div>
                    <a href="user_profile.php" class="btn btn-primary w-100 mt-3 rounded-pill">
                        <i class="bi bi-pencil"></i> 編輯個人資訊
                    </a>
                </div>
            </div>
        </div>

        <!-- 訂單管理區塊 -->
        <div class="section-card" id="orders-section">
            <h2 class="section-title">
                <i class="bi bi-box-seam"></i> 訂單管理
            </h2>

            <!-- 篩選器 -->
            <div class="filter-btn-group mb-4">
                <a href="?time_filter=all#orders-section" class="btn btn-outline-secondary <?php echo $time_filter === 'all' ? 'active' : ''; ?>">全部</a>
                <a href="?time_filter=1month#orders-section" class="btn btn-outline-secondary <?php echo $time_filter === '1month' ? 'active' : ''; ?>">近一個月</a>
                <a href="?time_filter=3months#orders-section" class="btn btn-outline-secondary <?php echo $time_filter === '3months' ? 'active' : ''; ?>">近三個月</a>
            </div>

            <!-- 訂單列表 -->
            <div class="table-responsive">
                <table class="table table-hover order-table">
                    <thead>
                        <tr>
                            <th>訂單編號</th>
                            <th>訂單日期</th>
                            <th>訂單狀態</th>
                            <th>訂單總額</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($user_orders)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">目前尚無訂單記錄</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($user_orders as $order): ?>
                                <?php
                                    $status_map = [
                                        'unpaid' => ['text' => '未付款', 'class' => 'status-unpaid'],
                                        'paid' => ['text' => '已付款', 'class' => 'status-paid'],
                                        'shipped' => ['text' => '運送中', 'class' => 'status-shipping'],
                                        'completed' => ['text' => '已完成', 'class' => 'status-completed'],
                                        'cancelled' => ['text' => '已取消', 'class' => 'status-cancelled']
                                    ];
                                    $current_status = $status_map[$order['order_status']] ?? ['text' => $order['order_status'], 'class' => ''];
                                ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                    <td><span class="order-status <?php echo $current_status['class']; ?>"><?php echo $current_status['text']; ?></span></td>
                                    <td>NT$ <?php echo number_format($order['total_amount']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill" type="button" data-bs-toggle="collapse" data-bs-target="#orderDetails<?php echo $order['id']; ?>" aria-expanded="false" aria-controls="orderDetails<?php echo $order['id']; ?>">
                                            <i class="bi bi-eye"></i> 查看詳情
                                        </button>
                                    </td>
                                </tr>
                                <!-- 訂單明細摺疊列 -->
                                <tr class="collapse order-details-row" id="orderDetails<?php echo $order['id']; ?>">
                                    <td colspan="5">
                                        <div class="order-details-inner text-start">
                                            <?php
                                                // 查詢訂單明細
                                                $items_stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image_main FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                                $items_stmt->execute([$order['id']]);
                                                $details = $items_stmt->fetchAll();
                                            ?>
                                            <h6 class="fw-bold mb-3">訂單內容明細</h6>
                                            <table class="table table-sm details-table">
                                                <thead>
                                                    <tr>
                                                        <th>商品</th>
                                                        <th>單價</th>
                                                        <th>數量</th>
                                                        <th>小計</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($details as $d): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <img src="<?php echo $d['image_main'] ? get_thumbnail_url($d['image_main']) : 'assets/images/placeholder.webp'; ?>" alt="" style="width: 30px; height: 30px; object-fit: cover; margin-right: 10px;">
                                                                <span><?php echo htmlspecialchars($d['product_name']); ?></span>
                                                            </div>
                                                        </td>
                                                        <td>NT$ <?php echo number_format($d['price_at_purchase'] ?? 0); ?></td>
                                                        <td><?php echo $d['quantity']; ?></td>
                                                        <td>NT$ <?php echo number_format(($d['price_at_purchase'] ?? 0) * $d['quantity']); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="3" class="text-end fw-bold">總計金額：</td>
                                                        <td class="fw-bold text-danger">NT$ <?php echo number_format($order['total_amount']); ?></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                            <div class="mt-3 small text-muted">
                                                <p class="mb-0"><strong>收件與配送資訊：</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'] ?? '無資料')); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 分頁器 -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Order pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill me-2" href="?page=<?php echo $page - 1; ?>#orders-section" tabindex="-1">上一頁</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link rounded-circle mx-1" href="?page=<?php echo $i; ?>#orders-section"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link rounded-pill ms-2" href="?page=<?php echo $page + 1; ?>#orders-section">下一頁</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>

        <!-- 地址管理區塊 -->
        <div class="section-card" id="address-section">
            <h2 class="section-title">
                <i class="bi bi-geo-alt"></i> 地址管理
            </h2>

            <!-- 地址列表 -->
            <div class="row">
                <?php if (empty($addresses)): ?>
                    <div class="col-12 text-center py-4 text-muted">目前尚無地址記錄</div>
                <?php else: ?>
                    <?php foreach ($addresses as $addr): ?>
                        <div class="col-md-6 mb-3">
                            <div class="address-card <?php echo $addr['is_default'] ? 'default' : ''; ?>">
                                <?php if ($addr['is_default']): ?>
                                    <span class="default-badge">預設地址</span>
                                <?php endif; ?>
                                <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($addr['recipient_name']); ?></h5>
                                <p class="mb-1"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($addr['recipient_phone']); ?></p>
                                <p class="mb-3"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($addr['address']); ?></p>
                                
                                <div class="d-flex gap-2">
                                    <?php if (!$addr['is_default']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="set_default">
                                            <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill">
                                                設為預設
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('確定要刪除此地址嗎？');">
                                        <input type="hidden" name="action" value="delete_address">
                                        <input type="hidden" name="address_id" value="<?php echo $addr['id']; ?>">
                                        <button type="submit" class="btn btn-delete btn-sm">
                                            <i class="bi bi-trash"></i> 刪除
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button class="btn btn-outline-primary rounded-pill mt-3" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                <i class="bi bi-plus-circle"></i> 新增地址
            </button>
        </div>

        <!-- 新增地址 Modal -->
        <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addAddressModalLabel">新增地址</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="add_address">
                            <div class="mb-3">
                                <label class="form-label">收件人姓名</label>
                                <input type="text" name="recipient_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">聯絡電話</label>
                                <input type="tel" name="recipient_phone" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">配送地址</label>
                                <textarea name="address" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" id="flexCheckDefault">
                                <label class="form-check-label" for="flexCheckDefault">
                                    設為預設地址
                                </label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary">儲存地址</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 密碼與安全區塊 -->
        <div class="section-card">
            <h2 class="section-title">
                <i class="bi bi-shield-lock"></i> 密碼與安全
            </h2>

            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">密碼設定</h5>
                    <button class="btn btn-outline-secondary rounded-pill">
                        <i class="bi bi-key"></i> 更改密碼
                    </button>

                    <h5 class="fw-bold mt-4 mb-3">兩步驟驗證</h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                        <label class="form-check-label" for="twoFactorAuth">
                            啟用兩步驟驗證 (提升帳號安全性)
                        </label>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">最近登入記錄</h5>
                    <div class="login-record">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-clock"></i> 2026-02-07 14:30</span>
                            <span class="text-muted">IP: 123.456.789.012</span>
                        </div>
                    </div>
                    <div class="login-record">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-clock"></i> 2026-02-06 09:15</span>
                            <span class="text-muted">IP: 123.456.789.012</span>
                        </div>
                    </div>
                    <div class="login-record">
                        <div class="d-flex justify-content-between">
                            <span><i class="bi bi-clock"></i> 2026-02-05 18:45</span>
                            <span class="text-muted">IP: 123.456.789.012</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 登出按鈕 -->
        <div class="text-center mt-5">
            <button class="btn btn-logout">
                <i class="bi bi-box-arrow-right"></i> 登出帳號
            </button>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        // 篩選器按鈕切換
        document.querySelectorAll('.filter-btn-group .btn').forEach(button => {
            button.addEventListener('click', function () {
                document.querySelectorAll('.filter-btn-group .btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>

</html>
