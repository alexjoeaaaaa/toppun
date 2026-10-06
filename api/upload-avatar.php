<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';

// 檢查登入狀態
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未授權的存取']);
    exit();
}

$user_id = $_SESSION['user_id'];

// 檢查請求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '不支援的請求方法']);
    exit();
}

// 獲取 Base64 圖片資料
$jsonData = json_decode(file_get_contents('php://input'), true);
$base64Image = $jsonData['image'] ?? null;

if (!$base64Image) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '未提供圖片資料']);
    exit();
}

// 解析 Base64 資料
if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
    $data = substr($base64Image, strpos($base64Image, ',') + 1);
    $type = strtolower($type[1]); // jpg, jpeg, png, gif

    if (!in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '不支援的圖片格式']);
        exit();
    }

    $data = base64_decode($data);

    if ($data === false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '圖片解碼失敗']);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '無效的圖片格式']);
    exit();
}

// 檢查大小 (2MB)
if (strlen($data) > 2 * 1024 * 1024) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '圖片大小超過 2MB 限制']);
    exit();
}

// 定義儲存路徑
$uploadDir = '../assets/images/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 產生新的檔名
$fileName = 'avatar_' . $user_id . '_' . time() . '.' . $type;
$filePath = $uploadDir . $fileName;

// 儲存檔案
if (file_put_contents($filePath, $data)) {
    try {
        // 獲取舊頭像路徑，準備刪除
        $stmt = $pdo->prepare("SELECT avatar FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $oldAvatar = $stmt->fetchColumn();

        // 更新資料庫
        // 資料庫儲存路徑應為相對於根目錄的路徑
        $dbPath = 'assets/images/avatars/' . $fileName;
        $stmt = $pdo->prepare("REPLACE INTO user_profiles (user_id, avatar) VALUES (?, ?) ON DUPLICATE KEY UPDATE avatar = ?");
        
        // 這裡需要注意，如果 user_profiles 還沒有該使用者的記錄，display_name 等欄位會是 NULL
        // 但因為我們在 SQL 匯入時已經有基本資料，通常只需 UPDATE
        // 如果想更安全，可以使用 INSERT ... ON DUPLICATE KEY UPDATE
        $stmt = $pdo->prepare("UPDATE user_profiles SET avatar = ? WHERE user_id = ?");
        $stmt->execute([$dbPath, $user_id]);

        // 刪除舊檔案
        if ($oldAvatar && file_exists('../' . $oldAvatar) && strpos($oldAvatar, 'default') === false) {
            unlink('../' . $oldAvatar);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => '頭像上傳成功', 'avatar_url' => $dbPath]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '資料庫更新失敗: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => '檔案寫入失敗']);
}
?>
