<?php
$page_title = '圖庫管理';
require_once 'view/admin_layout/header.php';
require_once 'view/admin_layout/sidebar.php';

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
 * 掃描圖片目錄，只處理原圖（忽略 _thumb），自動偵測對應縮圖是否存在。
 * 每組原圖＋縮圖合併為一個條目，避免圖庫重複顯示。
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

            // 只處理圖片，且跳過縮圖（檔名以 _thumb 結尾）
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
            if (substr($basename, -6) === '_thumb') continue;

            $clean_path = str_replace('\\', '/', $path);
            $size       = filesize($path);
            $mtime      = filemtime($path);
            $usage      = checkImageUsage($path, $products, $articles);

            // 計算縮圖路徑並確認是否存在
            $thumb_file     = $basename . '_thumb.' . $ext;
            $thumb_path_raw = $dir . $thumb_file;
            $thumb_path     = str_replace('\\', '/', $thumb_path_raw);
            $thumb_exists   = file_exists($thumb_path_raw);
            $thumb_size     = $thumb_exists ? filesize($thumb_path_raw) : 0;

            $results[] = [
                'path'         => $clean_path,
                'filename'     => $file,
                'size'         => $size,
                'mtime'        => $mtime,
                'usage'        => $usage,
                'is_used'      => count($usage) > 0,
                'category'     => (strpos($clean_path, 'products') !== false) ? '產品圖片' : '網誌圖片',
                'thumb_path'   => $thumb_exists ? $thumb_path : null,
                'thumb_exists' => $thumb_exists,
                'thumb_size'   => $thumb_size,
                'total_size'   => $size + $thumb_size,
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

<style>
    .stats-icon.count { background-color: #fcb891ff; }
    .stats-icon.used { background-color: #8db88d; }
    .stats-icon.unused { background-color: #e65100; }
    .stats-icon.size { background-color: #c17d4a; }

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
        position: relative;
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

    /* 縮圖 icon 標記 */
    .thumb-badge {
        display: inline-flex;
        align-items: center;
        color: #a0522d;
        cursor: default;
        font-size: 0.8rem;
    }

    /* 圖片 Popup 浮層 */
    #img-popup-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0, 0, 0, 0.72);
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(4px);
        pointer-events: none; /* 純視覺，不攔截滑鼠事件 */
    }

    #img-popup-overlay.active {
        display: flex;
    }

    #img-popup-overlay .popup-inner {
        position: relative;
        max-width: 80vw;
        max-height: 82vh;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: popupIn 0.18s ease;
    }

    @keyframes popupIn {
        from { opacity: 0; transform: scale(0.92); }
        to   { opacity: 1; transform: scale(1); }
    }

    #img-popup-overlay .popup-inner img {
        max-width: 80vw;
        max-height: 82vh;
        object-fit: contain;
        border: 3px solid rgba(255,255,255,0.15);
        box-shadow: 0 8px 40px rgba(0,0,0,0.6);
        display: block;
    }

    #img-popup-overlay .popup-filename {
        position: absolute;
        bottom: -34px;
        left: 0;
        right: 0;
        text-align: center;
        color: rgba(255,255,255,0.75);
        font-size: 0.8rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #img-popup-overlay .popup-close {
        position: absolute;
        top: -38px;
        right: 0;
        background: none;
        border: none;
        color: #fff;
        font-size: 1.6rem;
        cursor: pointer;
        line-height: 1;
        opacity: 0.8;
    }

    #img-popup-overlay .popup-close:hover { opacity: 1; }

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
</style>

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
                <?php foreach ($images as $img):
                    // 用縮圖做預覽（載入快），點擊後開啟原圖
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

                            <!-- 預覽用縮圖，滑鼠移入顯示放大 popup，點擊開啟原圖 -->
                            <div class="gallery-img-container">
                                <img src="<?php echo htmlspecialchars($preview_src); ?>?v=<?php echo $img['mtime']; ?>"
                                     alt="<?php echo htmlspecialchars($img['filename']); ?>"
                                     data-fullsrc="<?php echo htmlspecialchars($full_src); ?>"
                                     data-filename="<?php echo htmlspecialchars($img['filename']); ?>"
                                     class="gallery-thumb"
                                     onclick="window.open('<?php echo htmlspecialchars($full_src); ?>', '_blank')">
                            </div>

                            <div class="gallery-info">
                                <!-- 檔名 -->
                                <div class="filename" title="<?php echo htmlspecialchars($img['filename']); ?>">
                                    <?php echo htmlspecialchars($img['filename']); ?>
                                </div>

                                <!-- 大小與時間 -->
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-folder"></i> <?php echo $img['category']; ?><br>
                                    <span class="d-flex align-items-center justify-content-between">
                                        <span><i class="bi bi-image"></i> <?php echo formatBytes($img['size']); ?></span>
                                        <?php if ($img['thumb_exists']): ?>
                                            <span class="thumb-badge ms-1"
                                                  title="縮圖大小：<?php echo formatBytes($img['thumb_size']); ?>">
                                                <i class="fa-regular fa-images"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-warning ms-1" title="無縮圖"><i class="fa-solid fa-triangle-exclamation"></i></span>
                                        <?php endif; ?>
                                    </span>
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

                                <!-- 刪除按鈕（同時刪原圖＋縮圖） -->
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

<!-- 圖片放大 Popup Overlay -->
<div id="img-popup-overlay">
    <div class="popup-inner">
        <button class="popup-close" id="popup-close-btn" title="關閉">&times;</button>
        <img src="" alt="" id="popup-img">
        <div class="popup-filename" id="popup-filename"></div>
    </div>
</div>

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
                const isUsed   = this.getAttribute('data-used') === '1';
                const hasThumb = this.getAttribute('data-has-thumb') === '1';
                const path     = this.getAttribute('data-path');
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

                const formData = new FormData();
                formData.append('action', 'delete_image');
                formData.append('file_path', path);

                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 刪除中...';

                // 呼叫 admin-gallery（根目錄）處理刪除（同時刪原圖+縮圖）
                fetch('admin-gallery', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        itemContainer.remove();
                        window.location.reload();
                    } else {
                        alert('刪除失敗：' + data.message);
                        this.disabled = false;
                        const ht = this.getAttribute('data-has-thumb') === '1';
                        this.innerHTML = '<i class="bi bi-trash"></i> 刪除' + (ht ? '（原圖＋縮圖）' : '（原圖）');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('發生錯誤，請稍後再試');
                    this.disabled = false;
                    const ht = this.getAttribute('data-has-thumb') === '1';
                    this.innerHTML = '<i class="bi bi-trash"></i> 刪除' + (ht ? '（原圖＋縮圖）' : '（原圖）');
                });
            });
        });
        // ── Popup 放大圖（純 hover，移出即關）─────────────────
        const overlay   = document.getElementById('img-popup-overlay');
        const popupImg  = document.getElementById('popup-img');
        const popupName = document.getElementById('popup-filename');
        let popupTimer  = null;

        function showPopup(el) {
            const fullsrc  = el.getAttribute('data-fullsrc');
            const filename = el.getAttribute('data-filename');
            // 更新 popup 位置（跟隨卡片位置）
            const rect = el.getBoundingClientRect();
            popupImg.src        = fullsrc;
            popupImg.alt        = filename;
            popupName.textContent = filename;
            overlay.classList.add('active');
        }

        function hidePopup() {
            clearTimeout(popupTimer);
            overlay.classList.remove('active');
            popupImg.src = '';
        }

        document.querySelectorAll('.gallery-thumb').forEach(img => {
            img.addEventListener('mouseenter', function() {
                clearTimeout(popupTimer);
                popupTimer = setTimeout(() => showPopup(this), 200);
            });
            img.addEventListener('mouseleave', function() {
                // 移出立即關閉
                hidePopup();
            });
        });

        // ESC 也可關閉
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') hidePopup();
        });
        // ─────────────────────────────────────────────────────
    });
</script>

<?php require_once 'view/admin_layout/footer.php'; ?>
