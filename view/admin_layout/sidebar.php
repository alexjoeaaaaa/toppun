<?php
$current_page = $_GET['page'] ?? 'dashboard';
?>
<!-- 側邊導覽列 -->
<aside class="col-lg-2 sidebar">
    <div class="d-flex flex-column h-100">
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="admin?page=dashboard" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>儀表板
                </a>
            </li>
            <li class="nav-item">
                <a href="admin?page=orders" class="nav-link <?php echo $current_page == 'orders' ? 'active' : ''; ?>">
                    <i class="bi bi-cart-check"></i>訂單管理
                </a>
            </li>
            <li class="nav-item">
                <a href="admin?page=products" class="nav-link <?php echo $current_page == 'products' ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam"></i>商品管理
                </a>
            </li>
            <li class="nav-item">
                <a href="admin?page=categories" class="nav-link <?php echo $current_page == 'categories' ? 'active' : ''; ?>">
                    <i class="bi bi-grid"></i>分類管理
                </a>
            </li>
            <li class="nav-item">
                <a href="admin?page=members" class="nav-link <?php echo $current_page == 'members' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>會員管理
                </a>
            </li>
            <li class="nav-item">
                <a href="admin?page=promotions" class="nav-link <?php echo $current_page == 'promotions' ? 'active' : ''; ?>">
                    <i class="bi bi-tag"></i>促銷優惠管理
                </a>
            </li>
            <li class="nav-item">
                <a href="admin?page=blog" class="nav-link <?php echo $current_page == 'blog' ? 'active' : ''; ?>">
                    <i class="bi bi-journal-text"></i>Blog 審核管理
                </a>
            </li>
            <li class="nav-item">
                <a href="admin?page=gallery" class="nav-link <?php echo $current_page == 'gallery' ? 'active' : ''; ?>">
                    <i class="bi bi-images"></i>圖庫管理
                </a>
            </li>
        </ul>
        <hr>
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="index" class="nav-link">
                    <i class="bi bi-box-arrow-left"></i>返回前台
                </a>
            </li>
        </ul>
    </div>
</aside>
