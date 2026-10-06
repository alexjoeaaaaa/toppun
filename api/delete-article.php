<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';

// 檢查登入狀態與角色（管理員與作者皆可刪除）
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_author = isset($_SESSION['role']) && $_SESSION['role'] === 'author';

if (!isset($_SESSION['user_id']) || (!$is_admin && !$is_author)) {
    echo json_encode(['success' => false, 'message' => '未經授權。']);
    exit();
}

$user_id = $_SESSION['user_id'];
$article_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($article_id <= 0) {
    echo json_encode(['success' => false, 'message' => '無效的文章 ID。']);
    exit();
}

try {
    // 檢查文章是否存在以及權限
    if ($is_admin) {
        $stmt = $pdo->prepare("SELECT image_url FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);
    } else {
        $stmt = $pdo->prepare("SELECT image_url FROM articles WHERE id = ? AND user_id = ?");
        $stmt->execute([$article_id, $user_id]);
    }
    
    $article = $stmt->fetch();
    if (!$article) {
        echo json_encode(['success' => false, 'message' => '文章不存在或您無權刪除。']);
        exit();
    }

    // 刪除文章
    if ($is_admin) {
        $delete_stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $delete_stmt->execute([$article_id]);
    } else {
        $delete_stmt = $pdo->prepare("DELETE FROM articles WHERE id = ? AND user_id = ?");
        $delete_stmt->execute([$article_id, $user_id]);
    }

    // 嘗試清理文章封面圖片檔案 (若不是預設圖片)
    $image_url = $article['image_url'];
    if ($image_url && strpos($image_url, 'assets/images/blog/') === 0 && basename($image_url) !== 'default.jpg') {
        $file_path = '../' . $image_url;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    echo json_encode(['success' => true, 'message' => '文章已成功刪除。']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤：' . $e->getMessage()]);
}
?>
