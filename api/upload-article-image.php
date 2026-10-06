<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. 讀取本機金鑰（必須與 post-editor.php 一致）
require_once dirname(__DIR__) . '/upload-secret.php';

// 2. 獲取驗證參數 (優先從 Session，次選 POST)
$current_uid   = $_SESSION['user_id'] ?? $_POST['uid'] ?? null;
$current_role  = $_SESSION['role']    ?? $_POST['role'] ?? '';
$received_token = $_POST['token']      ?? '';

// 3. 計算預期 Token (允許 2 小時內的時差)
$time_slice = floor(time() / 7200);
$expected_token = hash_hmac('sha256', $current_uid . '|' . $current_role . '|' . $time_slice, UPLOAD_SECRET);
$expected_token_prev = hash_hmac('sha256', $current_uid . '|' . $current_role . '|' . ($time_slice - 1), UPLOAD_SECRET);

$is_token_valid = ($received_token === $expected_token || $received_token === $expected_token_prev) && !empty($received_token);
$is_role_valid  = in_array($current_role, ['author', 'admin']);

// 4. 權限檢查
if (!$current_uid || !$is_role_valid || (!isset($_SESSION['user_id']) && !$is_token_valid)) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => '驗證失敗，請重新登入',
        'debug' => [
            'uid' => $current_uid,
            'role' => $current_role,
            'has_session' => isset($_SESSION['user_id']),
            'token_match' => $is_token_valid
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// 5. 處理上傳邏輯
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES)) {
    echo json_encode(['error' => '無效的請求或檔案未上傳'], JSON_UNESCAPED_UNICODE);
    exit();
}

$file = current($_FILES);
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => '上傳錯誤代碼: ' . $file['error']], JSON_UNESCAPED_UNICODE);
    exit();
}

$upload_dir = '../assets/images/blog/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$new_name = uniqid('img_') . '_' . time() . '.' . $ext;
$dest = $upload_dir . $new_name;

if (move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['location' => 'assets/images/blog/' . $new_name]);
} else {
    echo json_encode(['error' => '無法儲存檔案，請檢查資料夾寫入權限'], JSON_UNESCAPED_UNICODE);
}
?>
