<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../db.php';
require_once 'core/Response.php';
require_once 'services/admin/DashboardService.php';
require_once 'controllers/admin/DashboardController.php';

use Api\Core\Response;
use Api\Services\Admin\DashboardService;
use Api\Controllers\Admin\DashboardController;

// 檢查登入狀態與角色
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    Response::error('未授權的操作。', 401);
}

try {
    $dashboardService = new DashboardService($pdo);
    $dashboardController = new DashboardController($dashboardService);
    $dashboardController->handleRequest();
} catch (Exception $e) {
    Response::error('系統錯誤: ' . $e->getMessage(), 500);
}
