<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 檢查登入與角色（管理員與作者皆可上傳）
$allowed_roles = ['author', 'admin'];
$current_role  = $_SESSION['role'] ?? $_POST['role'] ?? '';
$current_uid   = $_SESSION['user_id'] ?? $_POST['uid'] ?? null;
$token         = $_POST['token'] ?? '';

// 驗證機制：優先檢查 Session，若無則檢查 Token
require_once __DIR__ . '/upload-secret.php';
$time_slice = floor(time() / 7200);
$expected_token = hash_hmac('sha256', $current_uid . '|' . $current_role . '|' . $time_slice, UPLOAD_SECRET);
// 考慮到時鐘偏差，也檢查上一個時間片
$expected_token_prev = hash_hmac('sha256', $current_uid . '|' . $current_role . '|' . ($time_slice - 1), UPLOAD_SECRET);

$is_token_valid = ($token === $expected_token || $token === $expected_token_prev) && !empty($token);

if (!$current_uid || !in_array($current_role, $allowed_roles) || (!isset($_SESSION['user_id']) && !$is_token_valid)) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode([
        'error' => '未授權的存取 (Session 或 Token 驗證失敗)',
        'debug' => [
            'uid' => $current_uid,
            'role' => $current_role,
            'token_received' => $token,
            'token_expected' => $expected_token,
            'session_id' => session_id(),
            'has_session_uid' => isset($_SESSION['user_id'])
        ]
    ]);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => '僅支援 POST 請求']);
    exit();
}

if (empty($_FILES)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => '沒有接收到檔案，請檢查伺服器 post_max_size 設定。']);
    exit();
}

reset($_FILES);
$temp = current($_FILES);

// 檢查 PHP 上傳錯誤
if ($temp['error'] !== UPLOAD_ERR_OK) {
    header('HTTP/1.1 400 Bad Request');
    $error_msg = '上傳錯誤 (代碼: ' . $temp['error'] . ')';
    if ($temp['error'] === UPLOAD_ERR_INI_SIZE) $error_msg = '圖片超過伺服器限制 (upload_max_filesize)';
    if ($temp['error'] === UPLOAD_ERR_FORM_SIZE) $error_msg = '圖片超過表單限制';
    echo json_encode(['error' => $error_msg]);
    exit();
}

if (is_uploaded_file($temp['tmp_name'])) {
    // 檢查檔案大小 (限制 2MB = 2 * 1024 * 1024 bytes)
    $max_size = 2 * 1024 * 1024;
    if ($temp['size'] > $max_size) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => '圖片太大，網站限制為 2MB。']);
        exit();
    }

    // 檢查檔案副檔名
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $file_ext = strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_exts)) {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => '不支援的檔案格式，僅限 JPG, PNG, WEBP, GIF。']);
        exit();
    }

    // 建立上傳目錄
    $upload_dir = '../assets/images/blog/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // 產生不重複檔名
    $new_filename = uniqid('article_') . '_' . time() . '.' . $file_ext;
    $dest_path = $upload_dir . $new_filename;

    if (move_uploaded_file($temp['tmp_name'], $dest_path)) {
        // 回傳 TinyMCE 需要的格式 (扣除 ../ 的路徑)
        echo json_encode(['location' => 'assets/images/blog/' . $new_filename]);
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => '檔案移動失敗，請檢查權限。']);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => '無效的上傳請求。']);
}
?>
