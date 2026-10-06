<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 檢查登入狀態與角色
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin-login');
    exit();
}

// 獲取管理員資訊
$user_id = $_SESSION['user_id'] ?? null;
$admin_display_name = "管理員";

try {
    $stmt = $pdo->prepare("SELECT u.username, p.display_name, p.avatar FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $admin_data = $stmt->fetch();
    if ($admin_data) {
        $admin_display_name = $admin_data['display_name'] ?: $admin_data['username'];
    }
} catch (PDOException $e) {
    // 忽略
}

$page_title = $page_title ?? '商家後台';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - 香氛蠟燭企業</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome Free -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- 自訂 CSS -->
    <link rel="stylesheet" href="common.css">
    <style>
        body { background-color: #fafafa; font-size: 0.9rem; }
        .admin-header { background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
        .breadcrumb-section { background-color: #f8f5f2; padding: 15px 0; margin-bottom: 30px; }
        .stats-card { background: white; border-radius: 0; box-shadow: none; padding: 25px; margin-bottom: 30px; border: 1px solid #d8b09a; transition: all 0.3s; }
        .stats-card:hover { border-color: #fcb891ff; box-shadow: 0 0 0 2px #e8d5c8; }
        .stats-icon { width: 60px; height: 60px; border-radius: 0; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; color: white; margin-bottom: 15px; }
        .stats-icon.revenue { background-color: #fcb891ff; }
        .stats-icon.orders { background-color: #e89b6f; }
        .stats-icon.products { background-color: #d88a5e; }
        .stats-icon.members { background-color: #c17d4a; }
        .stats-number { font-size: 2rem; font-weight: 700; color: #d77244ff; margin-bottom: 5px; }
        .stats-label { color: #a0522d; font-size: 0.95rem; }
        .section-card { background: white; border-radius: 0; box-shadow: none; padding: 20px; margin-bottom: 30px; border: 1px solid #d8b09a; }
        .section-title { font-size: 1.25rem; font-weight: 700; color: #d77244ff; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #fcb891ff; }
        .data-table { font-size: 0.9rem; }
        .data-table th { background-color: #f8f5f2; font-weight: 600; color: #a0522d; border: 1px solid #e8d5c8; font-size: 0.95rem; }
        .data-table td { border: 1px solid #e8d5c8; vertical-align: middle; }
        .status-badge { padding: 5px 12px; border-radius: 0; font-size: 0.85rem; font-weight: 600; border: 1px solid; }
        .status-pending { background-color: #fff3e0; color: #e65100; border-color: #ffb74d; }
        .status-processing { background-color: #e3f2fd; color: #1565c0; border-color: #64b5f6; }
        .status-completed { background-color: #d4e8d4; color: #2d5f2d; border-color: #8db88d; }
        .status-active { background-color: #d4e8d4; color: #2d5f2d; border-color: #8db88d; }
        .status-inactive { background-color: #ffebee; color: #c62828; border-color: #ef9a9a; }
        .btn-action { padding: 4px 10px; font-size: 0.85rem; border-radius: 0; }
        .btn-edit { background-color: #f8f5f2; color: #a0522d; border: 1px solid #d8b09a; }
        .btn-edit:hover { background-color: #e8d5c8; color: #8b4513; }
        .btn-delete { background-color: white; color: #dc3545; border: 1px solid #dc3545; }
        .btn-delete:hover { background-color: #dc3545; color: white; }
        .btn-add { background-color: #d77244ff; color: white; border-radius: 0; padding: 8px 20px; }
        .btn-add:hover { background-color: #c1663d; color: white; }
        .sidebar { background: white; padding: 20px; border-right: 1px solid #e8d5c8; min-height: calc(100vh - 300px); }
        .nav-pills .nav-link { color: #a0522d; border-radius: 0; padding: 12px 20px; margin-bottom: 5px; transition: all 0.3s; }
        .nav-pills .nav-link:hover { background-color: #f8f5f2; color: #d77244ff; }
        .nav-pills .nav-link.active { background-color: #fcb891ff; color: white; font-weight: 600; }
        .nav-pills .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
        .filter-btn-group .btn { border-radius: 0; border: 1px solid #d8b09a; color: #a0522d; background: white; }
        .filter-btn-group .btn.active { background: #fcb891ff; color: white; border-color: #fcb891ff; }
        .filter-btn-group .btn:hover:not(.active) { background: #f8f5f2; }
        .modal-content { border: 1px solid #d8b09a; }
        .modal-header { background-color: #f8f5f2; border-bottom: 1px solid #e8d5c8; }
        .modal-footer { border-top: 1px solid #e8d5c8; }
        .form-control:focus, .form-select:focus { border-color: #fcb891ff; box-shadow: 0 0 0 0.25rem rgba(252, 184, 145, 0.25); }
        .btn-view { background-color: #f8f5f2; color: #a0522d; border: 1px solid #d8b09a; padding-left: 24px; padding-right: 24px; }
        .btn-view:hover { background-color: #e8d5c8; color: #8b4513; }
        
        .tab-pane { padding-top: 20px; }
        .nav-tabs .nav-link { color: #a0522d; font-weight: 500; }
        .nav-tabs .nav-link.active { font-weight: 700; color: #d77244ff; border-bottom: 3px solid #d77244ff; }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" style="color: #d77244ff;" href="index">TopPun香．芬</a>
            <button class="navbar-toggler rounded-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index">首頁</a></li>
                    <li class="nav-item"><a class="nav-link" href="product-list">商品總覽</a></li>
                    <li class="nav-item"><a class="nav-link" href="about">品牌故事</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-secondary dropdown-toggle rounded-0 border-0" type="button" id="userMenu" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 嗨，<?php echo htmlspecialchars($admin_display_name); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end rounded-0">
                            <li><a class="dropdown-item" href="admin?page=dashboard"><i class="bi bi-speedometer2"></i> 管理後台</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout"><i class="bi bi-box-arrow-right"></i> 登出</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <header class="admin-header text-center">
        <div class="container">
            <h1 class="fw-bold mb-2" style="font-size: 2rem;">商家管理後台</h1>
            <p class="mb-0" style="font-size: 1rem;">管理訂單、商品、會員與所有業務</p>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container pb-5">
        <div class="row">
