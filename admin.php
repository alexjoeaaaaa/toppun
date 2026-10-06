<?php
// 後台單一入口路由器 (Front Controller)
// 隱藏實際的資料夾結構 (view/admin/)，統一由 admin.php 處理

$page = $_GET['page'] ?? 'dashboard';

// 定義允許存取的頁面白名單
$allowed_pages = [
    'dashboard', 
    'orders', 
    'products', 
    'categories', 
    'members', 
    'promotions', 
    'blog',
    'gallery'
];

// 如果請求的頁面不在白名單內，強制導向儀表板
if (!in_array($page, $allowed_pages)) {
    header("Location: admin?page=dashboard");
    exit();
}

// 載入對應的視圖檔案
// 因為是在根目錄的 admin.php 執行，所以所有相對路徑（如 db.php, api/..., assets/...）都會以根目錄為基準
require_once "view/admin/admin_{$page}.php";
