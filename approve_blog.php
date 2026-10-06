<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 檢查管理員權限
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("未授權的操作。");
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE articles SET status = 'published' WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die("更新失敗: " . $e->getMessage());
    }
}

header('Location: admin#blog');
exit();
?>
