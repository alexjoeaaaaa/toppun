<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: blog-list');
    exit();
}

try {
    // 增加瀏覽次數
    $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$id]);

    // 獲取文章詳情
    $stmt = $pdo->prepare("
        SELECT a.*, u.username, p.display_name as author_name, p.bio as author_bio, p.avatar as author_avatar
        FROM articles a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE a.id = ? AND a.status = 'published'
    ");
    $stmt->execute([$id]);
    $article = $stmt->fetch();

    if (!$article) {
        die("文章不存在或尚未發布。");
    }

    // 獲取相關文章
    $related_stmt = $pdo->prepare("
        SELECT id, title, created_at, image_url 
        FROM articles 
        WHERE category = ? AND id != ? AND status = 'published' 
        ORDER BY created_at DESC 
        LIMIT 2
    ");
    $related_stmt->execute([$article['category'], $id]);
    $related_posts = $related_stmt->fetchAll();

} catch (PDOException $e) {
    die("讀取資料失敗: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - GINANA CANDLES Blog</title>
    <link rel="canonical" href="https://mcandle.fxinspire.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" />
    <?php
        // 優先使用 summary，若無則從內文截取前 160 字
        if (!empty($article['summary'])) {
            $meta_desc = $article['summary'];
        } else {
            $plain_content = strip_tags($article['content']);
            $meta_desc = mb_substr($plain_content, 0, 160);
        }
        $meta_desc = htmlspecialchars(trim($meta_desc));
    ?>
    <meta name="description" content="<?php echo $meta_desc; ?>">
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

        .post-header {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            padding: 60px 0 40px;
            margin-bottom: 50px;
        }

        .post-category {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.3);
            padding: 5px 15px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .post-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 30px;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .post-meta i {
            margin-right: 5px;
        }

        .post-content-wrapper {
            background: white;
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 50px;
            margin-bottom: 40px;
        }

        .post-content {
            font-size: 1.1rem;
            line-height: 1.9;
            color: #333;
        }

        .post-content h2 {
            color: #d77244ff;
            font-size: 2rem;
            font-weight: 700;
            margin-top: 40px;
            margin-bottom: 20px;
        }

        .post-content h3 {
            color: #d77244ff;
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        .post-content p {
            margin-bottom: 20px;
        }
        
        .post-content img {
            max-width: 100%;
            height: auto !important;
            display: block;
            margin: 20px auto;
            border: 1px solid #e8d5c8;
        }

        .author-box {
            background-color: #f8f5f2;
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 30px;
            margin-bottom: 40px;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .author-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid #fcb891ff;
        }

        .author-details h4 {
            color: #d77244ff;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .author-details p {
            color: #666;
            margin-bottom: 0;
        }

        .share-buttons {
            background-color: #fff5f0;
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 25px;
            margin-bottom: 40px;
            text-align: center;
        }

        .share-title {
            color: #d77244ff;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .btn-share {
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 10px 20px;
            margin: 5px;
            background-color: white;
            color: #a0522d;
            font-weight: 600;
        }

        .btn-share:hover {
            background-color: #fcb891ff;
            color: white;
            border-color: #fcb891ff;
        }

        .related-posts {
            margin-top: 60px;
        }

        .section-title {
            color: #d77244ff;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #fcb891ff;
        }

        .related-card {
            background: white;
            border: 1px solid #d8b09a;
            border-radius: 0;
            overflow: hidden;
            transition: all 0.3s;
        }

        .related-card:hover {
            border-color: #fcb891ff;
            box-shadow: 0 4px 15px rgba(232, 155, 111, 0.2);
            transform: translateY(-5px);
        }

        .related-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .related-content {
            padding: 20px;
        }

        .related-title {
            color: #d77244ff;
            font-size: 1.1rem;
            font-weight: 700;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
        }

        .related-title:hover {
            color: #fcb891ff;
        }

        .related-date {
            color: #a0522d;
            font-size: 0.9rem;
        }

        .btn-back {
            background-color: #e89b6f;
            color: white;
            border: 1px solid #e89b6f;
            border-radius: 0;
            padding: 12px 30px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back:hover {
            background-color: #d88a5e;
            border-color: #d88a5e;
            color: white;
        }

        .btn-edit-post {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 0;
            padding: 7px 18px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            margin-top: 18px;
            transition: all 0.25s;
            backdrop-filter: blur(4px);
        }

        .btn-edit-post:hover {
            background-color: rgba(255, 255, 255, 0.35);
            color: white;
            border-color: rgba(255, 255, 255, 0.8);
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

    <!-- 文章標題區 -->
    <div class="post-header">
        <div class="container">
            <span class="post-category"><?php echo htmlspecialchars($article['category']); ?></span>
            <h1 class="post-title"><?php echo htmlspecialchars($article['title']); ?></h1>
            <div class="post-meta">
                <span><i class="bi bi-person"></i> <?php echo htmlspecialchars($article['author_name'] ?: $article['username']); ?></span>
                <span><i class="bi bi-calendar"></i> <?php echo date('Y-m-d', strtotime($article['created_at'])); ?></span>
                <span><i class="bi bi-eye"></i> <?php echo number_format($article['views']); ?> 次瀏覽</span>
            </div>
            <?php
                $is_admin  = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
                $is_author = isset($_SESSION['role']) && $_SESSION['role'] === 'author'
                             && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $article['user_id'];
                if ($is_admin || $is_author):
            ?>
            <a href="post-editor.php?id=<?php echo $article['id']; ?>" class="btn-edit-post">
                <i class="bi bi-pencil-square"></i>
                <?php echo $is_admin ? '編輯文章（管理員）' : '編輯文章'; ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <div class="col-lg-12 mx-auto">
                <!-- 文章內容 -->
                 
                <div class="post-content-wrapper">
                    <div class="post-content">
                        <?php echo $article['content']; ?>
                    </div>
                </div>

                <!-- 作者資訊 -->
                <div class="author-box">
                    <div class="author-info">
                        <img src="<?php echo $article['author_avatar'] ?: 'https://via.placeholder.com/80'; ?>" alt="作者頭像" class="author-avatar">
                        <div class="author-details">
                            <h4><?php echo htmlspecialchars($article['author_name'] ?: $article['username']); ?></h4>
                            <p><?php echo htmlspecialchars($article['author_bio'] ?: '這位作者很懶，還沒有寫下自我介紹。'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- 分享按鈕 -->
                <div class="share-buttons">
                    <div class="share-title">
                        <i class="bi bi-share"></i> 分享這篇文章
                    </div>
                    <button class="btn btn-share">
                        <i class="bi bi-facebook"></i> Facebook
                    </button>
                    <button class="btn btn-share">
                        <i class="bi bi-twitter"></i> Twitter
                    </button>
                    <button class="btn btn-share">
                        <i class="bi bi-line"></i> LINE
                    </button>
                    <button class="btn btn-share">
                        <i class="bi bi-link-45deg"></i> 複製連結
                    </button>
                </div>

                <!-- 返回按鈕 -->
                <div class="text-center mb-5">
                    <a href="blog-list.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> 返回文章列表
                    </a>
                </div>

                <!-- 相關文章 -->
                <div class="related-posts">
                    <h2 class="section-title">相關文章推薦</h2>
                    <div class="row">
                        <?php if (empty($related_posts)): ?>
                            <div class="col-12 text-muted">暫無相關文章推薦。</div>
                        <?php else: ?>
                            <?php foreach ($related_posts as $rp): ?>
                                <div class="col-md-4">
                                    <div class="related-card">
                                        <img src="<?php echo $rp['image_url'] ?: 'https://via.placeholder.com/300x200/efa77e/ffffff?text=GINANA+CANDLES'; ?>" alt="相關文章" class="related-image">
                                        <div class="related-content">
                                            <a href="post-content.php?id=<?php echo $rp['id']; ?>" class="related-title"><?php echo htmlspecialchars($rp['title']); ?></a>
                                            <p class="related-date">
                                                <i class="bi bi-calendar"></i> <?php echo date('Y-m-d', strtotime($rp['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>
