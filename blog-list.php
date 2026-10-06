<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 篩選參數
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 6;
$offset = ($page - 1) * $per_page;

try {
    // 建立查詢
    $sql = "
        SELECT a.*, u.username, p.display_name as author_name 
        FROM articles a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE a.status = 'published'
    ";
    $params = [];

    if ($category !== 'all') {
        $sql .= " AND a.category = ?";
        $params[] = $category;
    }

    if ($keyword !== '') {
        $sql .= " AND (a.title LIKE ? OR a.summary LIKE ?)";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }

    // 計算總數用於分頁
    $count_sql = "SELECT COUNT(*) FROM (" . $sql . ") as t";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_articles = $count_stmt->fetchColumn();
    $total_pages = ceil($total_articles / $per_page);

    // 取得分頁資料
    $sql .= " ORDER BY a.created_at DESC LIMIT $offset, $per_page";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();

} catch (PDOException $e) {
    die("讀取資料失敗: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog 文章列表 - TopPun香．芬</title>
    <link rel="canonical" href="https://mcandle.fxinspire.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" />
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

        .blog-header {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }

        .search-box {
            max-width: 600px;
            margin: 30px auto 0;
        }

        .search-input {
            border: none;
            border-radius: 0;
            padding: 15px 20px;
            font-size: 1rem;
        }

        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
        }

        .btn-search {
            background-color: white;
            color: #d77244ff;
            border: none;
            border-radius: 0;
            padding: 15px 30px;
            font-weight: 600;
        }

        .btn-search:hover {
            background-color: #f8f5f2;
            color: #d77244ff;
        }

        .category-filter {
            background-color: white;
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 20px;
            margin-bottom: 30px;
        }

        .filter-title {
            color: #d77244ff;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }

        .filter-btn {
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 8px 20px;
            margin: 5px;
            background-color: white;
            color: #a0522d;
            transition: all 0.3s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background-color: #fcb891ff;
            color: white;
            border-color: #fcb891ff;
        }

        .blog-card {
            background: white;
            border: 1px solid #d8b09a;
            border-radius: 0;
            overflow: hidden;
            margin-bottom: 30px;
            transition: all 0.3s;
            height: 100%;
        }

        .blog-card:hover {
            border-color: #fcb891ff;
            box-shadow: 0 4px 15px rgba(232, 155, 111, 0.2);
            transform: translateY(-5px);
        }

        .blog-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }

        .blog-content {
            padding: 25px;
        }

        .blog-category {
            display: inline-block;
            background-color: #fcb891ff;
            color: white;
            padding: 5px 15px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .blog-title {
            color: #d77244ff;
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-decoration: none;
            display: block;
        }

        .blog-title:hover {
            color: #fcb891ff;
        }

        .blog-excerpt {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .blog-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 1px solid #e8d5c8;
            font-size: 0.9rem;
            color: #a0522d;
        }

        .blog-author {
            display: flex;
            align-items: center;
        }

        .blog-author i {
            margin-right: 5px;
        }

        .blog-date {
            display: flex;
            align-items: center;
        }

        .blog-date i {
            margin-right: 5px;
        }

        .btn-read-more {
            background-color: #fcb891ff;
            color: white;
            border: 1px solid #fcb891ff;
            border-radius: 0;
            padding: 10px 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }

        .btn-read-more:hover {
            background-color: #d88a5e;
            border-color: #d88a5e;
            color: white;
        }

        .pagination {
            margin-top: 50px;
        }

        .page-link {
            color: #a0522d;
            border-radius: 0;
            border: 1px solid #e8d5c8;
            margin: 0 3px;
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

    <!-- 標題與搜尋 -->
    <div class="blog-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">香氛生活誌</h1>
            <p class="lead">探索香氛世界,分享生活美學</p>
            
            <div class="search-box">
                <form action="blog-list" method="GET">
                    <div class="input-group">
                        <input type="text" name="keyword" class="form-control search-input" placeholder="搜尋文章..." value="<?php echo htmlspecialchars($keyword); ?>">
                        <?php if ($category !== 'all'): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                        <?php endif; ?>
                        <button class="btn btn-search" type="submit">
                            <i class="bi bi-search"></i> 搜尋
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <!-- 分類篩選 -->
        <div class="category-filter">
            <div class="filter-title">
                <i class="bi bi-funnel"></i> 文章分類
            </div>
            <div class="d-flex flex-wrap">
                <a href="?category=all" class="filter-btn <?php echo $category === 'all' ? 'active' : ''; ?>">全部文章</a>
                <a href="?category=選購指南" class="filter-btn <?php echo $category === '選購指南' ? 'active' : ''; ?>">選購指南</a>
                <a href="?category=使用技巧" class="filter-btn <?php echo $category === '使用技巧' ? 'active' : ''; ?>">使用技巧</a>
                <a href="?category=居家佈置" class="filter-btn <?php echo $category === '居家佈置' ? 'active' : ''; ?>">居家佈置</a>
                <a href="?category=產品知識" class="filter-btn <?php echo $category === '產品知識' ? 'active' : ''; ?>">產品知識</a>
                <a href="?category=其他" class="filter-btn <?php echo $category === '其他' ? 'active' : ''; ?>">其他</a>
            </div>
        </div>

        <!-- 文章列表 -->
        <div class="row">
            <?php if (empty($articles)): ?>
                <div class="col-12 text-center py-5">
                    <h3>找不到符合條件的文章。</h3>
                    <p>請嘗試其他關鍵字或分類。</p>
                </div>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="blog-card">
                            <img src="<?php echo $article['image_url'] ?: 'https://via.placeholder.com/400x250/fcb891/ffffff?text=GINANA+CANDLES'; ?>" alt="文章圖片" class="blog-image">
                            <div class="blog-content">
                                <span class="blog-category"><?php echo htmlspecialchars($article['category']); ?></span>
                                <a href="post-content.php?id=<?php echo $article['id']; ?>" class="blog-title"><?php echo htmlspecialchars($article['title']); ?></a>
                                <p class="blog-excerpt">
                                    <?php echo htmlspecialchars($article['summary']); ?>
                                </p>
                                <div class="blog-meta">
                                    <span class="blog-author">
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($article['author_name'] ?: $article['username']); ?>
                                    </span>
                                    <span class="blog-date">
                                        <i class="bi bi-calendar"></i> <?php echo date('Y-m-d', strtotime($article['created_at'])); ?>
                                    </span>
                                </div>
                                <a href="post-content.php?id=<?php echo $article['id']; ?>" class="btn-read-more">
                                    閱讀更多 <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 分頁 -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Blog pagination">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&category=<?php echo urlencode($category); ?>&keyword=<?php echo urlencode($keyword); ?>" tabindex="-1">上一頁</a>
                </li>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>&keyword=<?php echo urlencode($keyword); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&category=<?php echo urlencode($category); ?>&keyword=<?php echo urlencode($keyword); ?>">下一頁</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        function filterCategory(category) {
            // 移除所有按鈕的 active 類別
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // 為點擊的按鈕添加 active 類別
            event.target.classList.add('active');
            
            // 這裡可以加入實際的篩選邏輯
            console.log('篩選分類:', category);
        }
    </script>
</body>

</html>
