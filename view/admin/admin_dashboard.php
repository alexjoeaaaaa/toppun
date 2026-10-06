<?php
$page_title = '儀表板';
require_once 'view/admin_layout/header.php';
require_once 'view/admin_layout/sidebar.php';
?>

<!-- 主要內容 -->
<main class="col-lg-10">
    <!-- 訊息顯示 -->
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4 rounded-0" role="alert">
            操作成功！
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- 統計卡片 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon revenue"><i class="bi bi-currency-dollar"></i></div>
                <div class="stats-number" id="dashboard-revenue">NT$ --</div>
                <div class="stats-label">總累積營收</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon orders"><i class="bi bi-cart-check"></i></div>
                <div class="stats-number" id="dashboard-orders">--</div>
                <div class="stats-label">總訂單數量</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon products"><i class="bi bi-box-seam"></i></div>
                <div class="stats-number" id="dashboard-products">--</div>
                <div class="stats-label">上架商品數</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-icon members"><i class="bi bi-people"></i></div>
                <div class="stats-number" id="dashboard-members">--</div>
                <div class="stats-label">註冊會員數</div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        fetchDashboardStats();
    });

    async function fetchDashboardStats() {
        try {
            const response = await fetch('api/admin_dashboard_api?action=get_stats');
            const result = await response.json();
            if (result.success) {
                const data = result.data;
                document.getElementById('dashboard-revenue').textContent = 'NT$ ' + parseInt(data.total_revenue).toLocaleString();
                document.getElementById('dashboard-orders').textContent = data.total_orders;
                document.getElementById('dashboard-products').textContent = data.total_products;
                document.getElementById('dashboard-members').textContent = data.total_members;
            }
        } catch (error) {
            console.error('Error fetching dashboard stats:', error);
        }
    }
</script>

<?php require_once 'view/admin_layout/footer.php'; ?>
