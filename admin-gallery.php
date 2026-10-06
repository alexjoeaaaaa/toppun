<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 檢查登入狀態與角色
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin-login');
    exit();
}

// 獲取管理員資訊
$user_id = $_SESSION['user_id'] ?? null;
$stmt = $pdo->prepare("SELECT u.username, p.display_name, p.avatar FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$admin_data = $stmt->fetch();
$admin_display_name = $admin_data['display_name'] ?: ($admin_data['username'] ?? "管理員");

// 處理刪除圖片 (AJAX) - 同時刪除原圖與縮圖
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'delete_image') {
    $file_path = $_POST['file_path'] ?? '';
    
    // 基本資安檢查，確保只能刪除特定目錄的檔案
    $allowed_dirs = ['assets/images/products/', 'assets/images/blog/'];
    $is_valid_path = false;
    foreach ($allowed_dirs as $dir) {
        if (strpos($file_path, $dir) === 0 && strpos($file_path, '..') === false) {
            $is_valid_path = true;
            break;
        }
    }
    
    if ($is_valid_path) {
        $deleted = [];
        $failed  = [];
        
        // 計算縮圖路徑：在副檔名前加上 _thumb
        $ext       = pathinfo($file_path, PATHINFO_EXTENSION);
        $base      = substr($file_path, 0, -(strlen($ext) + 1)); // 去掉 .ext
        $thumb_path = $base . '_thumb.' . $ext;
        
        foreach ([$file_path, $thumb_path] as $f) {
            if (file_exists($f)) {
                if (unlink($f)) {
                    $deleted[] = basename($f);
                } else {
                    $failed[] = basename($f);
                }
            }
        }
        
        header('Content-Type: application/json');
        if (empty($failed)) {
            echo json_encode(['success' => true, 'message' => '已刪除：' . implode('、', $deleted)]);
        } else {
            echo json_encode(['success' => false, 'message' => '部分刪除失敗：' . implode('、', $failed)]);
        }
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '無效的檔案路徑']);
        exit();
    }
}

// 收集所有產品和文章資料來比對
$products = $pdo->query("SELECT id, name, image_main, description FROM products")->fetchAll(PDO::FETCH_ASSOC);
$articles = $pdo->query("SELECT id, title, image_url, content FROM articles")->fetchAll(PDO::FETCH_ASSOC);

/**
 * 檢查特定圖片路徑是否被包含在 HTML 內容的 <img> 標籤中
 */
function isImageInHtml($html, $filepath) {
    if (empty($html)) return false;
    
    $filename = strtolower(basename($filepath));
    $filepath_lower = strtolower(str_replace('\\', '/', $filepath));
    
    // 匹配 <img> 標籤並提取 src 屬性
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches);
    if (empty($matches[1])) {
        return false;
    }
    
    foreach ($matches[1] as $src) {
        // 解碼 HTML 實體字元（例如網址中的 &amp; 或 URL 編碼）
        $src_decoded = html_entity_decode($src);
        $src_lower = strtolower(str_replace('\\', '/', $src_decoded));
        
        // 比對路徑或檔名（不區分大小寫）
        if (strpos($src_lower, $filepath_lower) !== false || 
            strtolower(basename($src_decoded)) === $filename ||
            strpos($filepath_lower, $src_lower) !== false) {
            return true;
        }
    }
    
    return false;
}

function checkImageUsage($filepath, $products, $articles) {
    $usage = [];
    
    // 取得檔名用來在 HTML 內容中搜尋
    $filename = basename($filepath);
    
    // 若在 windows 上面檔名可能是區分大小寫的，統一小寫做精確比對
    $filepath_lower = strtolower(str_replace('\\', '/', $filepath));
    
    foreach ($products as $p) {
        if (!empty($p['image_main'])) {
            $p_main_lower = strtolower(str_replace('\\', '/', $p['image_main']));
            if ($p_main_lower === $filepath_lower || basename($p['image_main']) === $filename) {
                $usage[] = ['type' => 'product_cover', 'id' => $p['id'], 'name' => "產品封面: " . $p['name']];
            }
        }
        if (!empty($p['description']) && isImageInHtml($p['description'], $filepath)) {
            $usage[] = ['type' => 'product_desc', 'id' => $p['id'], 'name' => "產品內容: " . $p['name']];
        }
    }
    
    foreach ($articles as $a) {
        if (!empty($a['image_url'])) {
            $a_img_lower = strtolower(str_replace('\\', '/', $a['image_url']));
            if ($a_img_lower === $filepath_lower || basename($a['image_url']) === $filename) {
                $usage[] = ['type' => 'article_cover', 'id' => $a['id'], 'name' => "文章封面: " . $a['title']];
            }
        }
        if (!empty($a['content']) && isImageInHtml($a['content'], $filepath)) {
            $usage[] = ['type' => 'article_content', 'id' => $a['id'], 'name' => "文章內容: " . $a['title']];
        }
    }
    
    return $usage;
}

/**
 * 掃描圖片目錄，只掃原圖（忽略 _thumb），並自動偵測縮圖是否存在
 * 每個原圖與其縮圖合併為一個條目。
 */
function scanImages($dir, &$results, $products, $articles) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . $file;
        if (is_dir($path)) {
            scanImages($path . '/', $results, $products, $articles);
        } else {
            $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $basename = pathinfo($file, PATHINFO_FILENAME); // 不含副檔名
            
            // 只處理圖片，且跳過縮圖（_thumb 結尾）
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
            if (substr($basename, -6) === '_thumb') continue;
            
            $clean_path = str_replace('\\', '/', $path);
            $size       = filesize($path);
            $mtime      = filemtime($path);
            $usage      = checkImageUsage($path, $products, $articles);
            
            // 縮圖路徑
            $thumb_file = $basename . '_thumb.' . $ext;
            $thumb_path_raw = $dir . $thumb_file;
            $thumb_path = str_replace('\\', '/', $thumb_path_raw);
            $thumb_exists = file_exists($thumb_path_raw);
            $thumb_size   = $thumb_exists ? filesize($thumb_path_raw) : 0;
            
            $results[] = [
                'path'          => $clean_path,
                'filename'      => $file,
                'size'          => $size,
                'mtime'         => $mtime,
                'usage'         => $usage,
                'is_used'       => count($usage) > 0,
                'category'      => (strpos($clean_path, 'products') !== false) ? '產品圖片' : '網誌圖片',
                'thumb_path'    => $thumb_exists ? $thumb_path : null,
                'thumb_exists'  => $thumb_exists,
                'thumb_size'    => $thumb_size,
                'total_size'    => $size + $thumb_size,
            ];
        }
    }
}

$images = [];
if (is_dir('assets/images/products/')) {
    scanImages('assets/images/products/', $images, $products, $articles);
}
if (is_dir('assets/images/blog/')) {
    scanImages('assets/images/blog/', $images, $products, $articles);
}

// 根據時間排序，最新的在前面
usort($images, function($a, $b) {
    return $b['mtime'] - $a['mtime'];
});

// 計算總數據（以圖片組計，不重複計算縮圖）
$total_size   = 0;
$used_count   = 0;
$unused_count = 0;

foreach ($images as $img) {
    $total_size += $img['total_size']; // 原圖 + 縮圖合計
    if ($img['is_used']) {
        $used_count++;
    } else {
        $unused_count++;
    }
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow); 
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>圖庫管理 - TopPun香．芬</title>
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

        .admin-header {
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

        .stats-card {
            background: white;
            border-radius: 0;
            box-shadow: none;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #d8b09a;
            transition: all 0.3s;
        }

        .stats-card:hover {
            border-color: #fcb891ff;
            box-shadow: 0 0 0 2px #e8d5c8;
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 15px;
        }
        
        .stats-icon.count { background-color: #fcb891ff; }
        .stats-icon.used { background-color: #8db88d; }
        .stats-icon.unused { background-color: #e65100; }
        .stats-icon.size { background-color: #c17d4a; }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #d77244ff;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #a0522d;
            font-size: 0.95rem;
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

        .sidebar {
            background: white;
            border-right: 1px solid #d8b09a;
            min-height: calc(100vh - 200px);
            padding: 0;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            border-bottom: 1px solid #e8d5c8;
        }

        .sidebar-menu a {
            display: block;
            padding: 15px 20px;
            color: #a0522d;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: #f8f5f2;
            color: #d77244ff;
            border-left: 3px solid #fcb891ff;
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }
        
        /* Gallery Styles */
        .gallery-item {
            border: 1px solid #e8d5c8;
            padding: 10px;
            margin-bottom: 20px;
            background: #fff;
            transition: all 0.3s;
            position: relative;
        }
        
        .gallery-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #fcb891ff;
        }
        
        .gallery-img-container {
            height: 150px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f5f2;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .gallery-img-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .gallery-img-container img:hover {
            transform: scale(1.05);
        }
        
        .gallery-info {
            font-size: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .gallery-info .filename {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 600;
        }
        
        .badge-status {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 0.75rem;
        }
        
        .usage-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
            font-size: 0.8rem;
        }
        
        .usage-list li {
            color: #666;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .btn-delete-img {
            background-color: #cd9675ff;
            color: white;
            border-color: #cd9675ff;
            font-size: 0.8rem;
            border-radius: 0;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-delete-img:hover {
            background-color: #c17d4a;
            border-color: #c17d4a;
            color: white;
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
    </style>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&family=Noto+Serif+TC:wght@400;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <!-- 導航欄 -->
    <?php include 'header.php'; ?>

    <!-- 標題 -->
    <div class="admin-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">商家後台管理</h1>
            <p class="lead">圖庫資產管理與稽核</p>
        </div>
    </div>

    <!-- 麵包屑 -->
    <div class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">首頁</a></li>
                    <li class="breadcrumb-item"><a href="admin.php" class="text-decoration-none text-dark">商家後台</a></li>
                    <li class="breadcrumb-item active" aria-current="page">圖庫管理</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container-fluid mb-5">
        <div class="row">
            <!-- 側邊欄 -->
            <aside class="col-lg-2 sidebar">
                <div class="p-4 text-center border-bottom border-secondary-subtle">
                    <img src="<?php echo htmlspecialchars($admin_data['avatar'] ?: 'https://via.placeholder.com/80'); ?>" class="rounded-0 mb-3" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #fcb891ff;">
                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($admin_display_name); ?></h6>
                    <small class="text-muted">系統管理員</small>
                </div>
                <ul class="sidebar-menu">
                    <li>
                        <a href="admin.php#dashboard">
                            <i class="bi bi-speedometer2"></i>儀表板
                        </a>
                    </li>
                    <li>
                        <a href="admin.php#orders">
                            <i class="bi bi-box-seam"></i>訂單管理
                        </a>
                    </li>
                    <li>
                        <a href="admin.php#products">
                            <i class="bi bi-bag"></i>商品管理
                        </a>
                    </li>
                    <li>
                        <a href="admin.php#members">
                            <i class="bi bi-people"></i>會員管理
                        </a>
                    </li>
                    <li>
                        <a href="admin.php#promotions">
                            <i class="bi bi-gift"></i>促銷優惠管理
                        </a>
                    </li>
                    <li>
                        <a href="admin-gallery.php" class="active">
                            <i class="bi bi-images"></i>圖庫管理
                        </a>
                    </li>
                    <li>
                        <a href="admin.php#settings">
                            <i class="bi bi-gear"></i>系統設定
                        </a>
                    </li>
                    <li>
                        <a href="index.php">
                            <i class="bi bi-box-arrow-left"></i>返回前台
                        </a>
                    </li>
                </ul>
            </aside>

            <!-- 主要內容 -->
            <main class="col-lg-10">
                
                <!-- 統計卡片 -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon count">
                                <i class="bi bi-images"></i>
                            </div>
                            <div class="stats-number"><?php echo count($images); ?></div>
                            <div class="stats-label">圖片總數量</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon used">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stats-number"><?php echo $used_count; ?></div>
                            <div class="stats-label">使用中圖片</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon unused">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="stats-number"><?php echo $unused_count; ?></div>
                            <div class="stats-label">未引用圖片 (幽靈檔案)</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon size">
                                <i class="bi bi-hdd"></i>
                            </div>
                            <div class="stats-number"><?php echo formatBytes($total_size); ?></div>
                            <div class="stats-label">圖庫總容量</div>
                        </div>
                    </div>
                </div>

                <!-- 圖庫管理區塊 -->
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="section-title mb-0">
                            <i class="bi bi-images"></i> 圖庫清單
                            <small class="text-muted fs-6 fw-normal ms-2">（原圖與縮圖已合併顯示）</small>
                        </h2>
                        
                        <div class="filter-btn-group" id="gallery-filters">
                            <button class="btn active" data-filter="all">全部圖片</button>
                            <button class="btn" data-filter="used">使用中</button>
                            <button class="btn" data-filter="unused">未引用可刪除</button>
                        </div>
                    </div>

                    <div class="row" id="gallery-container">
                        <?php if (empty($images)): ?>
                            <div class="col-12 text-center py-5 text-muted">目前系統中沒有上傳圖片</div>
                        <?php else: ?>
                            <?php foreach ($images as $img): ?>
                                <?php
                                    // 使用縮圖做預覽（若有），否則使用原圖
                                    $preview_src = $img['thumb_exists'] ? $img['thumb_path'] : $img['path'];
                                    $full_src    = $img['path'];
                                ?>
                                <div class="col-md-3 col-sm-6 image-item" data-status="<?php echo $img['is_used'] ? 'used' : 'unused'; ?>">
                                    <div class="gallery-item">
                                        <?php if ($img['is_used']): ?>
                                            <span class="badge bg-success badge-status"><i class="bi bi-check-circle"></i> 使用中</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger badge-status"><i class="bi bi-exclamation-triangle"></i> 未引用</span>
                                        <?php endif; ?>
                                        
                                        <!-- 圖片預覽（點擊開啟原圖） -->
                                        <div class="gallery-img-container">
                                            <img src="<?php echo htmlspecialchars($preview_src); ?>?v=<?php echo $img['mtime']; ?>"
                                                 alt="<?php echo htmlspecialchars($img['filename']); ?>"
                                                 title="點擊查看原圖"
                                                 onclick="window.open('<?php echo htmlspecialchars($full_src); ?>', '_blank')">
                                        </div>
                                        
                                        <div class="gallery-info">
                                            <!-- 檔名 -->
                                            <div class="filename" title="<?php echo htmlspecialchars($img['filename']); ?>">
                                                <?php echo htmlspecialchars($img['filename']); ?>
                                            </div>
                                            
                                            <!-- 檔案大小與時間 -->
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                <i class="bi bi-folder"></i> <?php echo $img['category']; ?><br>
                                                <i class="bi bi-image"></i> 原圖：<?php echo formatBytes($img['size']); ?>
                                                <?php if ($img['thumb_exists']): ?>
                                                    &nbsp;<i class="bi bi-grid-1x2"></i> 縮圖：<?php echo formatBytes($img['thumb_size']); ?>
                                                <?php else: ?>
                                                    &nbsp;<span class="text-warning"><i class="bi bi-exclamation-circle"></i> 無縮圖</span>
                                                <?php endif; ?><br>
                                                <i class="bi bi-hdd"></i> 合計：<?php echo formatBytes($img['total_size']); ?> &nbsp;
                                                <i class="bi bi-clock"></i> <?php echo date('Y/m/d H:i', $img['mtime']); ?>
                                            </div>
                                            
                                            <!-- 引用來源 -->
                                            <div class="mt-2 border-top pt-2">
                                                <?php if ($img['is_used']): ?>
                                                    <div class="text-success fw-bold" style="font-size: 0.75rem;"><i class="bi bi-link-45deg"></i> 引用來源：</div>
                                                    <ul class="usage-list">
                                                        <?php 
                                                            $display_limit = 2;
                                                            $count = 0;
                                                            foreach ($img['usage'] as $u) {
                                                                if ($count < $display_limit) {
                                                                    echo "<li title='" . htmlspecialchars($u['name']) . "'>• " . htmlspecialchars($u['name']) . "</li>";
                                                                }
                                                                $count++;
                                                            }
                                                            if ($count > $display_limit) {
                                                                echo "<li class='text-muted'>... 及其他 " . ($count - $display_limit) . " 處</li>";
                                                            }
                                                        ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <div class="text-danger" style="font-size: 0.75rem;"><i class="bi bi-info-circle"></i> 系統未檢測到引用</div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- 刪除按鈕（同時刪除原圖+縮圖） -->
                                            <button type="button"
                                                    class="btn btn-delete-img delete-btn"
                                                    data-used="<?php echo $img['is_used'] ? '1' : '0'; ?>"
                                                    data-path="<?php echo htmlspecialchars($img['path']); ?>"
                                                    data-has-thumb="<?php echo $img['thumb_exists'] ? '1' : '0'; ?>">
                                                <i class="bi bi-trash"></i>
                                                刪除<?php echo $img['thumb_exists'] ? '（原圖＋縮圖）' : '（原圖）'; ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 圖片篩選功能
            const filterBtns = document.querySelectorAll('#gallery-filters .btn');
            const imageItems = document.querySelectorAll('.image-item');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filterValue = this.getAttribute('data-filter');
                    
                    imageItems.forEach(item => {
                        if (filterValue === 'all') {
                            item.style.display = 'block';
                        } else if (item.getAttribute('data-status') === filterValue) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
            
            // 刪除圖片功能（同時刪除原圖與縮圖）
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const isUsed    = this.getAttribute('data-used') === '1';
                    const hasThumb  = this.getAttribute('data-has-thumb') === '1';
                    const path      = this.getAttribute('data-path');
                    const itemContainer = this.closest('.image-item');
                    
                    const thumbNote = hasThumb ? '\n（原圖與縮圖將一併刪除）' : '';
                    
                    if (isUsed) {
                        if (!confirm('【警告】這張圖片目前正在被商品或文章引用，刪除後將導致前台顯示破圖！' + thumbNote + '\n\n您確定要強制刪除嗎？')) {
                            return;
                        }
                    } else {
                        if (!confirm('您確定要刪除這張未引用的圖片嗎？此操作無法還原。' + thumbNote)) {
                            return;
                        }
                    }
                    
                    // 發送刪除請求（後端自動處理原圖+縮圖）
                    const formData = new FormData();
                    formData.append('action', 'delete_image');
                    formData.append('file_path', path);
                    
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 刪除中...';
                    
                    fetch('admin-gallery', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // 刪除成功，直接移除卡片
                            itemContainer.remove();
                            // 重新整理頁面以更新統計
                            window.location.reload();
                        } else {
                            alert('刪除失敗：' + data.message);
                            this.disabled = false;
                            const hasThumbNow = this.getAttribute('data-has-thumb') === '1';
                            this.innerHTML = '<i class="bi bi-trash"></i> 刪除' + (hasThumbNow ? '（原圖＋縮圖）' : '（原圖）');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('發生錯誤，請稍後再試');
                        this.disabled = false;
                        const hasThumbNow = this.getAttribute('data-has-thumb') === '1';
                        this.innerHTML = '<i class="bi bi-trash"></i> 刪除' + (hasThumbNow ? '（原圖＋縮圖）' : '（原圖）');
                    });
                });
            });
        });
    </script>
</body>

</html>
