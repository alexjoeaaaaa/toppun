<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once __DIR__ . '/helpers.php';

// 分頁設定
$items_per_page = 9;
$current_page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// 獲取目前篩選條件
$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : null;
$category_name = isset($_GET['category_name']) ? trim($_GET['category_name']) : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (int)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (int)$_GET['max_price'] : null;
$sort = isset($_GET['sort']) ? (int)$_GET['sort'] : 0;

$current_category = null;
if ($category_id) {
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $current_category = $stmt->fetch();
} elseif ($category_name !== '') {
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE name = ?");
    $stmt->execute([$category_name]);
    $current_category = $stmt->fetch();
    if ($current_category) {
        $category_id = (int)$current_category['id'];
    }
}

// ── 301 轉址到 SEO 友善的分類網址 ──
$expected_url = '/%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E'; // 預設 /全部分類
if ($current_category) {
    $expected_url .= '/' . rawurlencode($current_category['name']);
}

$current_uri = $_SERVER['REQUEST_URI'];
$parsed_url = parse_url($current_uri);
$path = $parsed_url['path'] ?? '';

if (urldecode($path) !== urldecode($expected_url)) {
    $query_params = $_GET;
    unset($query_params['category_id']);
    unset($query_params['category_name']);
    $query_string = http_build_query($query_params);

    $redirect_url = $expected_url . ($query_string ? '?' . $query_string : '');
    header("Location: " . $redirect_url, true, 301);
    exit;
}

// 獲取所有分類供側邊欄使用
$categories = $pdo->query("SELECT * FROM product_categories")->fetchAll();

// 基礎查詢條件
$where_clauses = "WHERE p.is_active = 1";
$params = [];

if ($category_id) {
    $where_clauses .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if ($min_price !== null) {
    $where_clauses .= " AND p.price >= ?";
    $params[] = $min_price;
}

if ($max_price !== null) {
    $where_clauses .= " AND p.price <= ?";
    $params[] = $max_price;
}

// 計算總數
$count_query = "SELECT COUNT(*) FROM products p $where_clauses";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

// 獲取產品資料
$query = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id $where_clauses";

// 排序邏輯
switch ($sort) {
    case 1: $query .= " ORDER BY p.id ASC"; break;
    case 2: $query .= " ORDER BY p.price DESC"; break;
    case 3: $query .= " ORDER BY p.price ASC"; break;
    default: $query .= " ORDER BY p.id DESC"; break;
}

$query .= " LIMIT $items_per_page OFFSET $offset";

$products_stmt = $pdo->prepare($query);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll();

$page_title = $current_category ? $current_category['name'] : "全部商品";

// Meta SEO 設定
$meta_keywords = '';
$meta_description = '';

if ($current_category) {
    $meta_keywords = $current_category['keywords'] ?? '';
    $meta_description = $current_category['description'] ?? '';
} else {
    // 預設模式（全部商品）時，使用「尚未分類」的 SEO 設定頂著
    $stmt = $pdo->prepare("SELECT * FROM product_categories WHERE name = ?");
    $stmt->execute(['尚未分類']);
    $default_cat = $stmt->fetch();
    if ($default_cat) {
        $meta_keywords = $default_cat['keywords'] ?? '';
        $meta_description = $default_cat['description'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - TopPun香．芬
</title>
    <link rel="canonical" href="https://mcandle.fxinspire.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" />
    <?php if (!empty($meta_keywords)): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($meta_keywords); ?>">
    <?php endif; ?>
    <?php if (!empty($meta_description)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/common.css">
    <style>
        .sidebar { background-color: #fff; padding: 20px; }
        .filter-section { margin-bottom: 30px; }
        .filter-title { font-weight: 700; margin-bottom: 20px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1.5px; color: #999; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .category-link { font-size: 0.9rem; color: #555; padding: 8px 0; display: block; text-decoration: none; transition: all 0.3s ease; border-bottom: 1px solid transparent; }
        .category-link:hover { color: #000; padding-left: 5px; }
        .category-link.active { color: #000; font-weight: 700; border-bottom: 1px solid #000; }
        .breadcrumb-section { background-color: #f8f9fa; padding: 15px 0; margin-bottom: 30px; }
        .product-grid { padding-bottom: 50px; }
        .sort-select { max-width: 200px; }
        .price-filter input { border-radius: 0; }
        .product-grid .card-img-top {
            aspect-ratio: 4 / 3;
            object-fit: cover;
            width: 100%;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/index" class="text-decoration-none text-dark">首頁</a></li>
                    <?php if ($current_category): ?>
                        <li class="breadcrumb-item"><a href="/全部分類" class="text-decoration-none text-dark">全部分類</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($current_category['name']); ?></li>
                    <?php else: ?>
                        <li class="breadcrumb-item active">全部分類</li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <aside class="col-lg-3">
                <div class="sidebar">
                    <div class="filter-section">
                        <div class="filter-title">產品分類</div>
                        <div class="category-list">
                            <a href="/全部分類" class="category-link <?php echo !$category_id ? 'active' : ''; ?>">全部商品</a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="/全部分類/<?php echo htmlspecialchars($cat['name']); ?>" 
                                   class="category-link <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="filter-section">
                        <div class="filter-title">價格篩選</div>
                        <form action="<?php echo htmlspecialchars($expected_url); ?>" method="GET">
                            <?php if (isset($_GET['sort'])): ?>
                                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
                            <?php endif; ?>
                            <div class="price-filter d-flex align-items-center">
                                <input type="number" name="min_price" class="form-control form-control-sm" placeholder="最小" value="<?php echo $min_price; ?>">
                                <span class="mx-2">~</span>
                                <input type="number" name="max_price" class="form-control form-control-sm" placeholder="最大" value="<?php echo $max_price; ?>">
                            </div>
                            <button type="submit" class="btn btn-dark btn-sm w-100 mt-3 rounded-0">套用</button>
                        </form>
                    </div>
                </div>
            </aside>

            <main class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="text-muted small">共 <?php echo $total_items; ?> 筆</div>
                    <div class="sort-select">
                        <form id="sortForm" action="<?php echo htmlspecialchars($expected_url); ?>" method="GET">
                            <?php if ($min_price !== null): ?>
                                <input type="hidden" name="min_price" value="<?php echo htmlspecialchars($min_price); ?>">
                            <?php endif; ?>
                            <?php if ($max_price !== null): ?>
                                <input type="hidden" name="max_price" value="<?php echo htmlspecialchars($max_price); ?>">
                            <?php endif; ?>
                            <select name="sort" class="form-select form-select-sm rounded-0" onchange="this.form.submit()">
                                <option value="0" <?php echo $sort == 0 ? 'selected' : ''; ?>>新到舊</option>
                                <option value="1" <?php echo $sort == 1 ? 'selected' : ''; ?>>舊到新</option>
                                <option value="2" <?php echo $sort == 2 ? 'selected' : ''; ?>>價格高到低</option>
                                <option value="3" <?php echo $sort == 3 ? 'selected' : ''; ?>>價格低到高</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="row g-4 product-grid">
                    <?php foreach ($products as $p): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 border-0 shadow-sm">
                            <a href="<?php echo get_product_url($p, $pdo); ?>">
                                <?php 
                                $thumb_img = get_thumbnail_url($p['image_main']);
                                if (strpos($thumb_img, '/') !== 0 && strpos($thumb_img, 'http') !== 0) {
                                    $thumb_img = '/' . $thumb_img;
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($thumb_img); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($p['name']); ?>">
                            </a>
                            <div class="card-body text-center d-flex flex-column">
                                <h5 class="card-title fs-6">
                                    <a href="<?php echo get_product_url($p, $pdo); ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($p['name']); ?>
                                    </a>
                                </h5>
                                <p class="text-danger fw-bold mb-3">NT$ <?php echo number_format($p['price']); ?></p>
                                <button onclick="addToCart(<?php echo $p['id']; ?>)" class="btn btn-dark btn-sm rounded-0 mt-auto">加入購物車</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                <?php
                                $page_params = $_GET;
                                unset($page_params['category_name']);
                                unset($page_params['category_id']);
                                $page_params['page'] = $i;
                                $page_query = http_build_query($page_params);
                                $page_url = ($current_category ? '/全部分類/' . htmlspecialchars($current_category['name']) : '/全部分類') . ($page_query ? '?' . $page_query : '');
                                ?>
                                <a class="page-link" href="<?php echo $page_url; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
