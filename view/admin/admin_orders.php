<?php
$page_title = '訂單管理';
require_once 'view/admin_layout/header.php';
require_once 'view/admin_layout/sidebar.php';
?>

<main class="col-lg-10">
    <div class="section-card" id="orders">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="section-title mb-0">
                <i class="bi bi-cart-check"></i> 訂單管理
            </h2>
        </div>

        <div class="row align-items-center mb-4">
            <div class="col-md-7">
                <div class="filter-btn-group" id="order-filter-group">
                    <button class="btn active" data-status="all">全部</button>
                    <button class="btn" data-status="unpaid">未付款</button>
                    <button class="btn" data-status="paid">已付款</button>
                    <button class="btn" data-status="shipped">已發貨</button>
                    <button class="btn" data-status="completed">已完成</button>
                </div>
            </div>
            <div class="col-md-5">
                <div class="d-flex" id="order-search-form">
                    <input type="text" id="order-search-input" class="form-control rounded-0 me-2" placeholder="搜尋訂單編號或姓名...">
                    <button type="button" id="order-search-btn" class="btn btn-view rounded-0 fw-bold">搜尋</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>訂單編號</th>
                        <th>客戶名稱</th>
                        <th>訂單金額</th>
                        <th>付款方式</th>
                        <th>下單日期</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="order-table-body">
                    <tr><td colspan="7" class="text-center">載入中...</td></tr>
                </tbody>
            </table>
        </div>
        <nav aria-label="Order pagination" class="mt-4">
            <ul class="pagination justify-content-center" id="order-pagination-container"></ul>
        </nav>
    </div>
</main>

<script>
    let currentOrderStatus = 'all';
    let currentOrderSearch = '';
    let currentOrderPage = 1;

    document.addEventListener('DOMContentLoaded', () => {
        fetchOrders('all', '', 1);

        document.querySelectorAll('#order-filter-group .btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                fetchOrders(e.target.dataset.status, currentOrderSearch, 1);
            });
        });

        const searchBtn = document.getElementById('order-search-btn');
        const searchInput = document.getElementById('order-search-input');
        if (searchBtn && searchInput) {
            searchBtn.addEventListener('click', () => fetchOrders(currentOrderStatus, searchInput.value, 1));
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') fetchOrders(currentOrderStatus, searchInput.value, 1);
            });
        }
    });

    async function fetchOrders(status = currentOrderStatus, search = currentOrderSearch, page = 1) {
        currentOrderStatus = status;
        currentOrderSearch = search;
        currentOrderPage = page;

        document.querySelectorAll('#order-filter-group .btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.status === status);
        });

        const url = `api/admin_order_api?action=get_list&status=${status}&search=${encodeURIComponent(search)}&page=${page}`;
        try {
            const response = await fetch(url);
            const result = await response.json();
            if (result.success) {
                renderOrderTable(result.data.orders);
                renderOrderPagination(result.data.pagination);
            } else {
                document.getElementById('order-table-body').innerHTML = `<tr><td colspan="7" class="text-center text-danger">載入失敗: ${result.message}</td></tr>`;
            }
        } catch (error) {
            console.error('Error fetching orders:', error);
            document.getElementById('order-table-body').innerHTML = '<tr><td colspan="7" class="text-center text-danger">網路錯誤，請重試。</td></tr>';
        }
    }

    function getStatusClass(status) {
        const map = { unpaid: 'status-pending', paid: 'status-processing', shipped: 'status-active', completed: 'status-completed', cancelled: 'status-inactive' };
        return map[status] || '';
    }

    function renderOrderTable(orders) {
        const tbody = document.getElementById('order-table-body');
        tbody.innerHTML = '';

        if (!orders || orders.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">尚無訂單資料</td></tr>';
            return;
        }

        orders.forEach(order => {
            const statusClass = getStatusClass(order.order_status);
            const customerName = order.customer_name || order.username;
            const date = order.created_at ? order.created_at.substring(0, 10) : '';
            const amount = 'NT$ ' + parseInt(order.total_amount).toLocaleString();

            const statusOptions = ['unpaid', 'paid', 'shipped', 'completed', 'cancelled'];
            const statusLabels = { unpaid: '未付款', paid: '已付款', shipped: '已發貨', completed: '已完成', cancelled: '已取消' };
            const optionsHtml = statusOptions.map(s =>
                `<option value="${s}" ${order.order_status === s ? 'selected' : ''}>${statusLabels[s]}</option>`
            ).join('');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${order.id}</td>
                <td>${customerName}</td>
                <td>${amount}</td>
                <td>${order.payment_method || '-'}</td>
                <td>${date}</td>
                <td>
                    <select onchange="updateOrderStatus(${order.id}, this.value)" class="form-select form-select-sm ${statusClass}" style="width: auto;">
                        ${optionsHtml}
                    </select>
                </td>
                <td>
                    <button class="btn btn-action btn-view" type="button" onclick="toggleOrderDetails(${order.id}, this)">
                        <i class="bi bi-eye"></i> 查看
                    </button>
                </td>
            `;
            tbody.appendChild(tr);

            const detailTr = document.createElement('tr');
            detailTr.className = 'order-details-row';
            detailTr.id = `orderDetailsRow${order.id}`;
            detailTr.style.display = 'none';
            detailTr.innerHTML = `<td colspan="7"><div class="order-details-inner" id="orderDetails${order.id}"><div class="text-center py-3"><div class="spinner-border spinner-border-sm" role="status"></div> 載入中...</div></div></td>`;
            tbody.appendChild(detailTr);
        });
    }

    async function toggleOrderDetails(orderId, btn) {
        const detailRow = document.getElementById(`orderDetailsRow${orderId}`);
        const isVisible = detailRow.style.display !== 'none';

        if (isVisible) {
            detailRow.style.display = 'none';
            return;
        }

        detailRow.style.display = '';
        const container = document.getElementById(`orderDetails${orderId}`);

        try {
            const response = await fetch(`api/admin_order_api?action=get_details&order_id=${orderId}`);
            const result = await response.json();

            if (result.success) {
                const items = result.data;
                let rowsHtml = items.map(d => {
                    const img = d.image_main ? d.image_main.replace(/\.([a-zA-Z0-9]+)$/, '_thumb.$1') : 'assets/images/placeholder.webp';
                    const price = parseInt(d.price_at_purchase || 0).toLocaleString();
                    const subtotal = (parseInt(d.price_at_purchase || 0) * d.quantity).toLocaleString();
                    return `<tr>
                        <td style="width:60px"><img src="${img}" style="width:40px;height:40px;object-fit:cover"></td>
                        <td>${d.name}</td>
                        <td>NT$ ${price}</td>
                        <td>${d.quantity}</td>
                        <td>NT$ ${subtotal}</td>
                    </tr>`;
                }).join('');

                container.innerHTML = `
                    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle"></i> 訂單明細資訊</h6>
                    <table class="table table-sm details-table">
                        <thead><tr><th>商品圖</th><th>商品名稱</th><th>單價</th><th>數量</th><th>小計</th></tr></thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                `;
            } else {
                container.innerHTML = `<p class="text-danger">載入明細失敗: ${result.message}</p>`;
            }
        } catch (err) {
            container.innerHTML = '<p class="text-danger">網路錯誤，請重試。</p>';
        }
    }

    function renderOrderPagination(pagination) {
        const container = document.getElementById('order-pagination-container');
        container.innerHTML = '';
        if (pagination.total_pages <= 1) return;

        const prevDisabled = pagination.current_page <= 1 ? 'disabled' : '';
        container.innerHTML += `<li class="page-item ${prevDisabled}"><a class="page-link" href="javascript:void(0)" onclick="fetchOrders('${currentOrderStatus}', '${currentOrderSearch}', ${pagination.current_page - 1})">上一頁</a></li>`;

        for (let i = 1; i <= pagination.total_pages; i++) {
            const active = i === pagination.current_page ? 'active' : '';
            container.innerHTML += `<li class="page-item ${active}"><a class="page-link" href="javascript:void(0)" onclick="fetchOrders('${currentOrderStatus}', '${currentOrderSearch}', ${i})">${i}</a></li>`;
        }

        const nextDisabled = pagination.current_page >= pagination.total_pages ? 'disabled' : '';
        container.innerHTML += `<li class="page-item ${nextDisabled}"><a class="page-link" href="javascript:void(0)" onclick="fetchOrders('${currentOrderStatus}', '${currentOrderSearch}', ${pagination.current_page + 1})">下一頁</a></li>`;
    }

    async function updateOrderStatus(orderId, newStatus) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('status', newStatus);

        try {
            const response = await fetch('api/admin_order_api?action=update_status', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (!result.success) {
                alert('更新失敗: ' + result.message);
                fetchOrders(currentOrderStatus, currentOrderSearch, currentOrderPage);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('更新失敗，請檢查網路。');
        }
    }
</script>

<?php require_once 'view/admin_layout/footer.php'; ?>
