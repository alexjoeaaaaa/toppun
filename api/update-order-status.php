<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';

// 檢查管理員權限
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => '未經授權。']);
    exit();
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

$allowed_status = ['unpaid', 'paid', 'shipped', 'completed', 'cancelled'];
if (!in_array($status, $allowed_status)) {
    echo json_encode(['success' => false, 'message' => '無效的狀態。']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    echo json_encode(['success' => true, 'message' => '訂單狀態更新成功！']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '資料庫錯誤：' . $e->getMessage()]);
}
?>
