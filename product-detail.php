<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
require_once __DIR__ . '/helpers.php';


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
$url_category_name = isset($_GET['category_name']) ? trim($_GET['category_name']) : '';

if ($id <= 0 && $name === '') {
    header("Location: /%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E");
    exit;
}

try {
    $product = null;

    if ($id > 0) {
        // 依 ID 獲取商品詳情
        $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
    } elseif ($name !== '') {
        // 支援以數字 ID 作為路徑片段（與名稱網址並存）
        if (preg_match('/^(\d+)(?:-.*)?$/', $name, $id_match)) {
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.id = ? AND p.is_active = 1");
            $stmt->execute([(int)$id_match[1]]);
            $product = $stmt->fetch();
        }

        if (!$product) {
        // 依名稱與分類獲取商品詳情
        // 1. 先嘗試找出該分類名稱的 ID
        $category_id = 0;
        if ($url_category_name !== '') {
            $stmt = $pdo->prepare("SELECT id FROM product_categories WHERE name = ?");
            $stmt->execute([$url_category_name]);
            $category_id = (int)$stmt->fetchColumn();
        }

        if ($category_id > 0) {
            // A. 先試圖在該分類下完全比對商品名稱
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.name = ? AND p.is_active = 1 ORDER BY p.id ASC");
            $stmt->execute([$category_id, $name]);
            $matches = $stmt->fetchAll();

            if (count($matches) > 0) {
                $product = $matches[0];
            } else {
                // B. 若找不到，檢查是否帶有 -N 尾綴
                if (preg_match('/(.+)-([2-9]|[1-9][0-9]+)$/', $name, $matches_suffix)) {
                    $base_name = $matches_suffix[1];
                    $index = (int)$matches_suffix[2];

                    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.name = ? AND p.is_active = 1 ORDER BY p.id ASC");
                    $stmt->execute([$category_id, $base_name]);
                    $all_matches = $stmt->fetchAll();

                    if (count($all_matches) >= $index) {
                        $product = $all_matches[$index - 1];
                    }
                }
            }
        }

        // C. 如果在指定分類下還是找不到，就進行全站名稱搜尋 (容錯 / 換分類自癒)
        if (!$product) {
            // 先試完全比對
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.name = ? AND p.is_active = 1 ORDER BY p.id ASC");
            $stmt->execute([$name]);
            $matches = $stmt->fetchAll();

            if (count($matches) > 0) {
                $product = $matches[0];
            } else {
                // 試尾綴比對
                if (preg_match('/(.+)-([2-9]|[1-9][0-9]+)$/', $name, $matches_suffix)) {
                    $base_name = $matches_suffix[1];
                    $index = (int)$matches_suffix[2];

                    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.name = ? AND p.is_active = 1 ORDER BY p.id ASC");
                    $stmt->execute([$base_name]);
                    $all_matches = $stmt->fetchAll();

                    if (count($all_matches) >= $index) {
                        $product = $all_matches[$index - 1];
                    }
                }
            }
        }
        }
    }

    if (!$product) {
        header("Location: /%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E");
        exit;
    }

    // ── 僅在無效 pretty URL 時 301 至名稱網址；名稱網址、ID 網址、?id= 查詢皆保留有效 ──
    $current_uri = $_SERVER['REQUEST_URI'] ?? '';
    $parsed_url = parse_url($current_uri);
    $path = $parsed_url['path'] ?? '';

    $is_direct_access = is_direct_product_detail_request($path);
    $is_valid_pretty = is_valid_product_request_path($product, $pdo, $path);

    if (!$is_direct_access && !$is_valid_pretty) {
        $query_params = $_GET;
        unset($query_params['id'], $query_params['name'], $query_params['category_name']);
        $query_string = http_build_query($query_params);
        $redirect_url = get_product_url($product, $pdo) . ($query_string ? '?' . $query_string : '');
        header('Location: ' . $redirect_url, true, 301);
        exit;
    }

    $id = (int)$product['id'];

    // 獲取附加圖集
    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$id]);
    $gallery_images = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 獲取相關商品 (同分類，包含分類名稱)
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN product_categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1 LIMIT 4");
    $stmt->execute([$product['category_id'], $id]);
    $related_products = $stmt->fetchAll();

} catch (PDOException $e) {
    die("讀取商品資料失敗: " . $e->getMessage());
}

// ── 解析 description JSON（向後相容）──
$desc_intro    = '';
$desc_features = [];
$desc_detail   = [];
$desc_specs    = [];
$desc_qa       = [];

if (!empty($product['description'])) {
    $raw = json_decode($product['description'], true);
    if (is_array($raw)) {
        if (isset($raw[0])) {
            // 舊 flat array 格式 → 放入 detail
            $desc_detail = $raw;
        } else {
            // 新物件格式 { intro, features, detail, specs, qa }
            $desc_intro    = $raw['intro']    ?? '';
            $desc_features = is_array($raw['features'] ?? null) ? $raw['features'] : [];
            $desc_detail   = $raw['detail']   ?? [];
            $desc_specs    = $raw['specs']    ?? [];
            $desc_qa       = $raw['qa']       ?? [];
        }
    } else {
        // 純文字向後相容 → 顯示於簡介位置
        $desc_intro = $product['description'];
    }
}

/**
 * 輸出一組 block 陣列的 HTML
 */
function renderBlocks(array $blocks): string {
    $html = '';
    foreach ($blocks as $blk) {
        $type = $blk['type'] ?? '';
        if ($type === 'text') {
            if (!empty($blk['html'])) {
                // 富文字 HTML（新格式）— 直接輸出（後台管理員輸入，信任內容）
                $html .= '<div class="product-desc-text rte-content">' . $blk['html'] . '</div>';
            } elseif (!empty($blk['content'])) {
                // 舊純文字格式 — nl2br 向後相容
                $html .= '<p class="product-desc-text">' . nl2br(htmlspecialchars($blk['content'])) . '</p>';
            }
        } elseif ($type === 'image' && !empty($blk['src'])) {
            $alt   = htmlspecialchars($blk['alt'] ?? '');
            $src   = $blk['src'];
            if (strpos($src, '/') !== 0 && strpos($src, 'http') !== 0) {
                $src = '/' . $src;
            }
            $src   = htmlspecialchars($src);
            $html .= '<figure class="product-desc-figure"><img src="' . $src . '" alt="' . $alt . '" loading="lazy"></figure>';
        }
    }
    return $html;
}

// 組合主圖 + 圖集 成輪播陣列
$all_images = [];
if (!empty($product['image_main'])) {
    $img = $product['image_main'];
    if (strpos($img, '/') !== 0 && strpos($img, 'http') !== 0) {
        $img = '/' . $img;
    }
    $all_images[] = $img;
}
foreach ($gallery_images as $gi) {
    if (strpos($gi, '/') !== 0 && strpos($gi, 'http') !== 0) {
        $gi = '/' . $gi;
    }
    $all_images[] = $gi;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(get_product_seo_title($product)); ?></title>
    <link rel="canonical" href="<?php echo htmlspecialchars(get_product_canonical_url($product, $pdo), ENT_QUOTES, 'UTF-8'); ?>" />
    <meta name="description" content="<?php echo htmlspecialchars(get_product_meta_description($product)); ?>">
    <?php if (!empty($product['meta_keywords'])): ?>
    <meta name="keywords" content="<?php echo htmlspecialchars($product['meta_keywords']); ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/common.css">
    <style>
        /* ── 商品主區 ── */
        .product-title { font-size: 2.2rem; font-weight: 700; margin-bottom: 16px; }
        .price-large { font-size: 2rem; color: #d63384; font-weight: 700; }
        .discount-badge { background-color: #fff0f5; color: #d63384; padding: 15px; border-left: 4px solid #d63384; margin-bottom: 25px; }
        .qty-input { width: 250px; }
        .related-products { background-color: #f8f9fa; padding: 80px 0; }
        .breadcrumb-section { background-color: #f8f9fa; padding: 15px 0; }
        .card-img-top { aspect-ratio: 1/1; object-fit: cover; }
        /* 商品特色列表 */
        .feature-list { list-style: none; padding: 0; margin: 0 0 24px; }
        .feature-list li {
            margin-bottom: 10px;
            position: relative;
            padding-left: 28px;
            font-size: 1.05rem;
            color: #444;
        }
        .feature-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #d63384;
            font-weight: bold;
            font-size: 1.1rem;
        }

        /* ── 圖片區 ── */
        #main-image-wrap {
            position: relative;
            overflow: hidden;
            border-radius: 6px;
            background: #f5f5f5;
            aspect-ratio: 1/1;
        }
        #main-image-wrap img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
            transition: opacity .25s;
        }
        .thumb-strip {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .thumb-strip::-webkit-scrollbar { height: 4px; }
        .thumb-strip::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
        .thumb-item {
            flex: 0 0 68px;
            height: 68px;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color .2s;
        }
        .thumb-item img { width: 100%; height: 100%; object-fit: cover; }
        .thumb-item.active { border-color: #d63384; }

        /* ── 描述區塊 ── */
        .desc-section {
            padding: 64px 0 56px;
            background: #fdf9f7;
            border-top: 1px solid #edddd5;
            border-bottom: 1px solid #edddd5;
        }
        /* 描述區內容最大寬度 + 左右留白 */
        .desc-inner {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .desc-section-title {
            font-size: 1rem;
            font-weight: 700;
            color: #a0522d;
            letter-spacing: .12em;
            text-transform: uppercase;
            border-bottom: 1px solid #e8cfc0;
            padding-bottom: 10px;
            margin-bottom: 24px;
        }
        .product-desc-text {
            line-height: 2;
            color: #4a4a4a;
            font-size: 1rem;
            margin-bottom: 1.4rem;
        }
        .product-desc-figure {
            margin: 2rem 0;
            text-align: center;
        }
        .product-desc-figure img {
            max-width: 100%;
            border-radius: 6px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }
        /* specs 表格風格 */
        .specs-block .product-desc-text {
            background: #fff;
            border-left: 3px solid #d63384;
            padding: 12px 18px;
            border-radius: 0 4px 4px 0;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
        }
        /* qa 問答風格 */
        .qa-block .product-desc-text {
            background: #fff;
            border: 1px solid #f0c0d8;
            border-radius: 6px;
            padding: 14px 18px;
        }
        /* 富文字內容（RTE 輸出） */
        .rte-content { line-height: 2; color: #4a4a4a; margin-bottom: 1.4rem; }
        .rte-content ul { padding-left: 1.5em; list-style: disc; margin-bottom: 0.8rem; }
        .rte-content ol { padding-left: 1.5em; list-style: decimal; margin-bottom: 0.8rem; }
        .rte-content li { margin-bottom: 5px; }
        .rte-content p { margin-bottom: 0.8rem; }
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
                    <li class="breadcrumb-item"><a href="/全部分類" class="text-decoration-none text-dark">全部分類</a></li>
                    <li class="breadcrumb-item"><a href="/全部分類/<?php echo htmlspecialchars($product['category_name']); ?>" class="text-decoration-none text-dark"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container my-5 py-5">
        <div class="row g-5">
            <!-- 圖片欄 -->
            <div class="col-lg-6">
                <div id="main-image-wrap" class="shadow-sm">
                    <img id="main-image" src="<?php echo htmlspecialchars($all_images[0] ?? '/assets/images/placeholder.webp'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <?php if (count($all_images) > 1): ?>
                <div class="thumb-strip">
                    <?php foreach ($all_images as $i => $imgUrl): ?>
                    <div class="thumb-item <?php echo $i === 0 ? 'active' : ''; ?>" onclick="switchImage(this, '<?php echo htmlspecialchars($imgUrl); ?>')">
                        <img src="<?php echo htmlspecialchars(preg_replace('/\.([a-zA-Z0-9]+)$/', '_thumb.$1', $imgUrl)); ?>" alt="縮圖">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- 資訊欄 -->
            <div class="col-lg-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-secondary mb-0"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="/admin?page=products&edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-secondary rounded-0">
                            <i class="bi bi-pencil-square me-1"></i>編輯商品
                        </a>
                    <?php endif; ?>
                </div>
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <?php if (!empty($desc_intro)): ?>
                <p class="text-muted fs-5 mb-3"><?php echo nl2br(htmlspecialchars($desc_intro)); ?></p>
                <?php endif; ?>

                <?php if (!empty($desc_features)): ?>
                <ul class="feature-list mb-4">
                    <?php foreach ($desc_features as $feat): ?>
                    <?php if (!empty(trim($feat))): ?>
                    <li><?php echo htmlspecialchars($feat); ?></li>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <div class="price-large mb-4">NT$ <?php echo number_format($product['price']); ?></div>

                <div class="discount-badge">
                    <div class="fw-bold mb-1">【會員專屬優惠】</div>
                    <p class="mb-0 small">全館滿 $1,500 免運費</p>
                    <p class="mb-0 small">首次購物輸入折扣碼「GINANA100」現折 $100</p>
                </div>

                <div class="d-flex align-items-center mb-4">
                    <div class="input-group qty-input me-3">
                        <button class="btn btn-outline-secondary rounded-0" type="button" onclick="changeQty(-1)">-</button>
                        <input type="number" id="qty" name="quantity" class="form-control text-center border-secondary" value="1" min="1">
                        <button class="btn btn-outline-secondary rounded-0" type="button" onclick="changeQty(1)">+</button>
                    </div>
                    <button type="button" onclick="addToCartLocal()" class="btn btn-dark btn-lg flex-grow-1 rounded-0">加入購物車</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── 商品描述區塊 ── -->
    <?php if (!empty($desc_detail) || !empty($desc_specs) || !empty($desc_qa)): ?>
    <section class="desc-section">

        <?php if (!empty($desc_detail)): ?>
        <div class="desc-inner mb-5">
            <h2 class="desc-section-title"><i class="bi bi-layout-text-window me-2"></i>商品描述</h2>
            <div class="detail-block">
                <?php echo renderBlocks($desc_detail); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($desc_specs)): ?>
        <div class="desc-inner mb-5">
            <h2 class="desc-section-title"><i class="bi bi-list-check me-2"></i>商品規格</h2>
            <div class="specs-block">
                <?php echo renderBlocks($desc_specs); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($desc_qa)): ?>
        <div class="desc-inner mb-5">
            <h2 class="desc-section-title"><i class="bi bi-question-circle me-2"></i>常見問題</h2>
            <div class="qa-block">
                <?php echo renderBlocks($desc_qa); ?>
            </div>
        </div>
        <?php endif; ?>

    </section>
    <?php endif; ?>



    <!-- Related Products -->
    <?php if ($related_products): ?>
    <section class="related-products">
        <div class="container">
            <h3 class="text-center mb-5 fw-bold">您可能也會喜歡</h3>
            <div class="row g-4 justify-content-center">
                <?php foreach ($related_products as $rp): 
                    $rp_img = $rp['image_main'];
                    if (strpos($rp_img, '/') !== 0 && strpos($rp_img, 'http') !== 0) {
                        $rp_img = '/' . $rp_img;
                    }
                ?>
                <div class="col-md-3">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <a href="<?php echo get_product_url($rp, $pdo); ?>">
                            <img src="<?php echo htmlspecialchars($rp_img); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($rp['name']); ?>">
                        </a>
                        <div class="card-body">
                            <h5 class="card-title fs-6">
                                <a href="<?php echo get_product_url($rp, $pdo); ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($rp['name']); ?>
                                </a>
                            </h5>
                            <p class="text-danger fw-bold mb-0">NT$ <?php echo number_format($rp['price']); ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'footer.php'; ?>

    <script>
        function changeQty(delta) {
            const input = document.getElementById('qty');
            let val = parseInt(input.value) + delta;
            if (val < 1) val = 1;
            input.value = val;
        }

        async function addToCartLocal() {
            const quantity = document.getElementById('qty').value;
            await addToCart(<?php echo $product['id']; ?>, quantity);
        }

        function switchImage(thumbEl, src) {
            document.getElementById('main-image').src = src;
            document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
            thumbEl.classList.add('active');
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
