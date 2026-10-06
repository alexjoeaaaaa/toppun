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

$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($article_id <= 0) {
    header('Location: admin#blog');
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.username, u.email as author_email, p.display_name as author_name 
        FROM articles a 
        JOIN users u ON a.user_id = u.id 
        LEFT JOIN user_profiles p ON u.id = p.user_id 
        WHERE a.id = ?
    ");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();

    if (!$article) {
        die("找不到文章資料。");
    }
} catch (PDOException $e) {
    die("讀取資料失敗: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog 審核 - 商家後台</title>
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

        .review-header {
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

        .review-card {
            background: white;
            border-radius: 0;
            box-shadow: none;
            padding: 30px;
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

        .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #e8d5c8;
        }

        .info-label {
            font-weight: 600;
            color: #a0522d;
            width: 120px;
            display: inline-block;
        }

        .info-value {
            color: #333;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 0;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
            border-color: #ffb74d;
        }

        .article-content {
            background-color: #f8f5f2;
            padding: 25px;
            border: 1px solid #e8d5c8;
            border-radius: 0;
            margin: 20px 0;
            line-height: 1.8;
            color: #333;
        }

        .article-content h1,
        .article-content h2,
        .article-content h3 {
            color: #d77244ff;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .article-content p {
            margin-bottom: 15px;
        }

        .review-actions {
            background-color: #fff5f0;
            padding: 25px;
            border: 1px solid #d8b09a;
            border-radius: 0;
            margin-top: 30px;
        }

        .btn-approve {
            background-color: #4caf50;
            color: white;
            border: 1px solid #4caf50;
            border-radius: 0;
            padding: 12px 30px;
            font-weight: 600;
            margin-right: 10px;
        }

        .btn-approve:hover {
            background-color: #45a049;
            border-color: #45a049;
            color: white;
        }

        .btn-reject {
            background-color: #cd9675ff;
            color: white;
            border: 1px solid #cd9675ff;
            border-radius: 0;
            padding: 12px 30px;
            font-weight: 600;
            margin-right: 10px;
        }

        .btn-reject:hover {
            background-color: #c17d4a;
            border-color: #c17d4a;
            color: white;
        }

        .btn-back {
            background-color: #e89b6f;
            color: white;
            border: 1px solid #e89b6f;
            border-radius: 0;
            padding: 12px 30px;
            font-weight: 600;
        }

        .btn-back:hover {
            background-color: #d88a5e;
            border-color: #d88a5e;
            color: white;
        }

        .review-note {
            width: 100%;
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 12px;
            margin-top: 15px;
            resize: vertical;
        }

        .review-note:focus {
            outline: none;
            border-color: #fcb891ff;
            box-shadow: 0 0 0 2px #e8d5c8;
        }

        .history-item {
            padding: 15px;
            background-color: #f8f5f2;
            border-left: 3px solid #fcb891ff;
            margin-bottom: 15px;
        }

        .history-date {
            font-size: 0.9rem;
            color: #a0522d;
            font-weight: 600;
        }

        .history-action {
            color: #333;
            margin-top: 5px;
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
    <div class="review-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">Blog 文章審核</h1>
            <p class="lead">審核並管理投稿文章</p>
        </div>
    </div>

    <!-- 麵包屑 -->
    <div class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-dark">首頁</a></li>
                    <li class="breadcrumb-item"><a href="admin.php" class="text-decoration-none text-dark">商家後台</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Blog 審核</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container mb-5">
        <!-- 文章基本資訊 -->
        <div class="review-card">
            <h2 class="section-title">
                <i class="bi bi-file-text"></i> 文章資訊
            </h2>

            <div class="info-row">
                <span class="info-label">文章編號:</span>
                <span class="info-value">#BLOG<?php echo str_pad($article['id'], 3, '0', STR_PAD_LEFT); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">文章標題:</span>
                <span class="info-value fw-bold"><?php echo htmlspecialchars($article['title']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">作者:</span>
                <span class="info-value"><?php echo htmlspecialchars($article['author_name'] ?: $article['username']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">作者郵箱:</span>
                <span class="info-value"><?php echo htmlspecialchars($article['author_email']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">提交日期:</span>
                <span class="info-value"><?php echo $article['created_at']; ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">文章分類:</span>
                <span class="info-value"><?php echo htmlspecialchars($article['category']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">審核狀態:</span>
                <?php 
                    $status_class = '';
                    $status_text = '';
                    switch($article['status']) {
                        case 'pending': $status_class = 'status-pending'; $status_text = '待審核'; break;
                        case 'published': $status_class = 'status-completed'; $status_text = '已通過'; break;
                        case 'rejected': $status_class = 'status-inactive'; $status_text = '已拒絕'; break;
                        case 'draft': $status_class = 'status-draft'; $status_text = '草稿'; break;
                    }
                ?>
                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">字數統計:</span>
                <span class="info-value"><?php echo mb_strlen($article['content']); ?> 字</span>
            </div>
        </div>

        <!-- 文章內容 -->
        <div class="review-card">
            <h2 class="section-title">
                <i class="bi bi-journal-text"></i> 文章內容
            </h2>

            <div class="article-content">
                <?php echo nl2br(htmlspecialchars($article['content'])); ?>
            </div>
        </div>

        <!-- 審核歷史 -->
        <div class="review-card">
            <h2 class="section-title">
                <i class="bi bi-clock-history"></i> 審核歷史
            </h2>

            <div class="history-item">
                <div class="history-date">
                    <i class="bi bi-calendar"></i> <?php echo $article['created_at']; ?>
                </div>
                <div class="history-action">
                    <i class="bi bi-upload"></i> 作者 <strong><?php echo htmlspecialchars($article['author_name'] ?: $article['username']); ?></strong> 提交文章
                </div>
            </div>
        </div>

        <!-- 審核操作 -->
        <div class="review-actions">
            <h2 class="section-title">
                <i class="bi bi-check-circle"></i> 審核操作
            </h2>

            <div class="mb-3">
                <label for="reviewNote" class="form-label fw-bold" style="color: #a0522d;">審核備註:</label>
                <textarea class="review-note" id="reviewNote" rows="4" 
                    placeholder="請輸入審核意見或建議(選填)..."></textarea>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-approve" onclick="approveArticle()">
                    <i class="bi bi-check-circle"></i> 通過審核
                </button>
                <button class="btn btn-reject" onclick="rejectArticle()">
                    <i class="bi bi-x-circle"></i> 拒絕文章
                </button>
                <button class="btn btn-back" onclick="window.location.href='admin'">
                    <i class="bi bi-arrow-left"></i> 返回列表
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        function approveArticle() {
            if (confirm('確定要通過這篇文章嗎?')) {
                window.location.href = 'approve_blog?id=<?php echo $article['id']; ?>';
            }
        }

        function rejectArticle() {
            const note = document.getElementById('reviewNote').value;
            if (!note.trim()) {
                alert('拒絕文章時請填寫審核備註,說明拒絕原因。');
                return;
            }
            if (confirm('確定要拒絕這篇文章嗎?')) {
                // 這裡簡化處理, 直接將備註帶在 URL 中 (實際應用建議用 POST)
                window.location.href = 'reject_blog?id=<?php echo $article['id']; ?>&note=' + encodeURIComponent(note);
            }
        }
    </script>
</body>

</html>
