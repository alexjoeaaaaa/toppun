<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db.php';
require_once 'core/Response.php';
require_once 'services/admin/BlogService.php';
require_once 'controllers/admin/BlogController.php';

use Api\Core\Response;
use Api\Services\Admin\BlogService;
use Api\Controllers\Admin\BlogController;

// 檢查登入狀態與角色 (Admin 專屬 API)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    Response::error('未授權的操作。', 401);
}

try {
    // 實例化 Service 與 Controller
    $blogService = new BlogService($pdo);
    $blogController = new BlogController($blogService);

    // 處理請求
    $blogController->handleRequest();

} catch (Exception $e) {
    Response::error('系統錯誤: ' . $e->getMessage(), 500);
}
