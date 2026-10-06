<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 檢查登入狀態與角色
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'author') {
    header('Location: author-login');
    exit();
}

$user_id = $_SESSION['user_id'];

// 獲取作者資料
try {
    $stmt = $pdo->prepare("SELECT u.username, p.display_name, p.avatar FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $author = $stmt->fetch();
    $display_name = $author['display_name'] ?: $author['username'];

    // 獲取統計數據
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published,
            SUM(views) as total_views
        FROM articles 
        WHERE user_id = ?
    ");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch();

    // 獲取文章列表
    $filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $sql = "SELECT * FROM articles WHERE user_id = ?";
    $params = [$user_id];
    if ($filter !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $filter;
    }
    $sql .= " ORDER BY created_at DESC";
    
    $list_stmt = $pdo->prepare($sql);
    $list_stmt->execute($params);
    $articles = $list_stmt->fetchAll();

} catch (PDOException $e) {
    die("讀取資料失敗: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>作者後台 - 文章管理</title>
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

        .dashboard-header {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .sidebar {
            background-color: white;
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 0;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .sidebar-header {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            padding: 20px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            border-bottom: 1px solid #e8d5c8;
        }

        .sidebar li:last-child {
            border-bottom: none;
        }

        .sidebar a {
            display: block;
            padding: 15px 20px;
            color: #a0522d;
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #fcb891ff;
            color: white;
        }

        .sidebar i {
            margin-right: 10px;
            width: 20px;
        }

        .stats-card {
            background: white;
            border-radius: 0;
            box-shadow: none;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #d8b09a;
            text-align: center;
            transition: all 0.3s;
        }

        .stats-card:hover {
            border-color: #fcb891ff;
            box-shadow: 0 4px 15px rgba(232, 155, 111, 0.2);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .stats-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #d77244ff;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #a0522d;
            font-size: 0.9rem;
        }

        .section-card {
            background: white;
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 30px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #d77244ff;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #fcb891ff;
        }

        .btn-new-post {
            background-color: #4caf50;
            color: white;
            border: 1px solid #4caf50;
            border-radius: 0;
            padding: 12px 25px;
            font-weight: 600;
        }

        .btn-new-post:hover {
            background-color: #45a049;
            border-color: #45a049;
            color: white;
        }

        .filter-btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn-group .btn {
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 8px 20px;
            background-color: white;
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
            border-color: #fcb891ff;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background-color: #f8f5f2;
        }

        .data-table th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: #a0522d;
            border: 1px solid #e8d5c8;
        }

        .data-table td {
            padding: 15px;
            border: 1px solid #e8d5c8;
            color: #333;
        }

        .data-table tbody tr:hover {
            background-color: #f8f5f2;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 0;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid;
        }

        .status-draft {
            background-color: #e8d5c8;
            color: #a0522d;
            border-color: #d8b09a;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #e65100;
            border-color: #ffb74d;
        }

        .status-published {
            background-color: #d4e8d4;
            color: #2d5f2d;
            border-color: #8db88d;
        }

        .status-rejected {
            background-color: #ffd4d4;
            color: #8b0000;
            border-color: #ff9999;
        }

        .btn-action {
            border-radius: 0;
            padding: 6px 12px;
            font-size: 0.85rem;
            margin: 2px;
        }

        .btn-view {
            background-color: #e89b6f;
            color: white;
            border: 1px solid #e89b6f;
        }

        .btn-view:hover {
            background-color: #d88a5e;
            border-color: #d88a5e;
            color: white;
        }

        .btn-edit {
            background-color: #fcb891ff;
            color: white;
            border: 1px solid #fcb891ff;
        }

        .btn-edit:hover {
            background-color: #d88a5e;
            border-color: #d88a5e;
            color: white;
        }

        .btn-delete {
            background-color: #cd9675ff;
            color: white;
            border: 1px solid #cd9675ff;
        }

        .btn-delete:hover {
            background-color: #c17d4a;
            border-color: #c17d4a;
            color: white;
        }

        .pagination {
            margin-top: 30px;
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

    <!-- 標題 -->
    <div class="dashboard-header">
        <div class="container text-center">
            <h1 class="display-4 fw-bold">作者後台</h1>
            <p class="lead">管理您的文章與個人資料</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row">
            <!-- 側邊欄 -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <div class="sidebar-header d-flex align-items-center">
                        <?php if (!empty($author['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($author['avatar']); ?>" class="rounded-0 me-2" style="width: 30px; height: 30px; object-fit: cover; border: 1px solid rgba(255,255,255,0.5);">
                        <?php else: ?>
                            <i class="bi bi-person-circle me-2"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($display_name); ?>
                    </div>
                    <ul>
                        <li>
                            <a href="author-dashboard.php" class="<?php echo !isset($_GET['status']) ? 'active' : ''; ?>">
                                <i class="bi bi-speedometer2"></i>儀表板
                            </a>
                        </li>
                        <li>
                            <a href="?status=all" class="<?php echo isset($_GET['status']) && $_GET['status'] === 'all' ? 'active' : ''; ?>">
                                <i class="bi bi-file-text"></i>我的文章
                            </a>
                        </li>
                        <li>
                            <a href="post-editor.php">
                                <i class="bi bi-plus-circle"></i>新增文章
                            </a>
                        </li>
                        <li>
                            <a href="user_profile.php">
                                <i class="bi bi-person"></i>個人資料
                            </a>
                        </li>
                        <li>
                            <a href="logout.php" style="color: #cd9675ff;">
                                <i class="bi bi-box-arrow-right"></i>登出
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- 主要內容 -->
            <div class="col-lg-9">
                <!-- 統計卡片 -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="bi bi-file-text"></i>
                            </div>
                            <div class="stats-number"><?php echo $stats['total']; ?></div>
                            <div class="stats-label">總文章數</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="stats-number"><?php echo $stats['pending'] ?: 0; ?></div>
                            <div class="stats-label">審核中</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stats-number"><?php echo $stats['published'] ?: 0; ?></div>
                            <div class="stats-label">已發布</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-icon">
                                <i class="bi bi-eye"></i>
                            </div>
                            <div class="stats-number"><?php echo number_format($stats['total_views'] ?: 0); ?></div>
                            <div class="stats-label">總瀏覽數</div>
                        </div>
                    </div>
                </div>

                <!-- 文章管理 -->
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="section-title mb-0">
                            <i class="bi bi-file-text"></i> 我的文章
                        </h2>
                        <button class="btn btn-new-post" onclick="window.location.href='post-editor'">
                            <i class="bi bi-plus-circle"></i> 新增文章
                        </button>
                    </div>

                    <!-- 篩選器 -->
                    <div class="filter-btn-group mb-4">
                        <a href="?status=all" class="btn <?php echo $filter === 'all' ? 'active' : ''; ?>">全部</a>
                        <a href="?status=draft" class="btn <?php echo $filter === 'draft' ? 'active' : ''; ?>">草稿</a>
                        <a href="?status=pending" class="btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">待審核</a>
                        <a href="?status=published" class="btn <?php echo $filter === 'published' ? 'active' : ''; ?>">已發布</a>
                        <a href="?status=rejected" class="btn <?php echo $filter === 'rejected' ? 'active' : ''; ?>">已拒絕</a>
                    </div>

                    <!-- 文章列表 -->
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>文章標題</th>
                                    <th>分類</th>
                                    <th>狀態</th>
                                    <th>提交日期</th>
                                    <th>瀏覽數</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($articles)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">尚無文章資料</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($articles as $article): ?>
                                        <?php 
                                            $status_class = '';
                                            $status_text = '';
                                            switch($article['status']) {
                                                case 'draft': $status_class = 'status-draft'; $status_text = '草稿'; break;
                                                case 'pending': $status_class = 'status-pending'; $status_text = '待審核'; break;
                                                case 'published': $status_class = 'status-published'; $status_text = '已發布'; break;
                                                case 'rejected': $status_class = 'status-rejected'; $status_text = '已拒絕'; break;
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($article['title']); ?></td>
                                            <td><?php echo htmlspecialchars($article['category']); ?></td>
                                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                            <td><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></td>
                                            <td><?php echo $article['status'] === 'published' ? number_format($article['views']) : '-'; ?></td>
                                            <td>
                                                <?php if ($article['status'] === 'published'): ?>
                                                    <a href="post-content.php?id=<?php echo $article['id']; ?>" class="btn btn-action btn-view">
                                                        <i class="bi bi-eye"></i> 查看
                                                    </a>
                                                <?php endif; ?>
                                                <a href="post-editor.php?id=<?php echo $article['id']; ?>" class="btn btn-action btn-edit">
                                                    <i class="bi bi-pencil"></i> 編輯
                                                </a>
                                                <button class="btn btn-action btn-delete" data-id="<?php echo $article['id']; ?>">
                                                    <i class="bi bi-trash"></i> 刪除
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 分頁器 -->
                    <nav aria-label="Post pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">上一頁</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">下一頁</a>
                            </li>
                        </ul>
                    </nav>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.btn-delete').forEach(button => {
                button.addEventListener('click', function() {
                    const articleId = this.getAttribute('data-id');
                    if (!articleId) return;

                    if (confirm('您確定要刪除這篇文章嗎？此動作無法復原。')) {
                        const formData = new FormData();
                        formData.append('id', articleId);

                        this.disabled = true;
                        const originalText = this.innerHTML;
                        this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> 刪除中...';

                        fetch('api/delete-article', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert(data.message);
                                window.location.reload();
                            } else {
                                alert('刪除失敗：' + data.message);
                                this.disabled = false;
                                this.innerHTML = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('發生網路錯誤，請稍後再試。');
                            this.disabled = false;
                            this.innerHTML = originalText;
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>
