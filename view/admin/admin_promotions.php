<?php
$page_title = '促銷優惠管理';
require_once 'view/admin_layout/header.php';
require_once 'view/admin_layout/sidebar.php';
?>

<main class="col-lg-10">
    <div class="section-card" id="promotions">
        <div class="d-flex justify-content-between align-items-center mb-0">
            <h2 class="section-title mb-3">
                <i class="bi bi-gift"></i> 促銷優惠管理
            </h2>
        </div>

        <ul class="nav nav-tabs mb-4 border-bottom-0" id="promoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-0 border-bottom" id="coupons-tab" data-bs-toggle="tab" data-bs-target="#coupons-panel" type="button" role="tab">折扣碼 (Coupons)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-0 border-bottom" id="global-tab" data-bs-toggle="tab" data-bs-target="#global-panel" type="button" role="tab">全館活動 (Global Promotions)</button>
            </li>
        </ul>

        <div class="tab-content" id="promoTabsContent">
            <!-- 折扣碼分頁 -->
            <div class="tab-pane fade show active" id="coupons-panel" role="tabpanel">
                <div class="mb-3 text-end">
                    <button class="btn btn-add" onclick="showCouponModal()">新增折扣碼</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>代碼</th>
                                <th>類型</th>
                                <th>面額</th>
                                <th>低消</th>
                                <th>首購限定</th>
                                <th>狀態</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="coupon-table-body">
                            <tr><td colspan="7" class="text-center">載入中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 全館活動分頁 -->
            <div class="tab-pane fade" id="global-panel" role="tabpanel">
                <div class="mb-3 text-end">
                    <button class="btn btn-add" onclick="showPromotionModal()">新增全館活動</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>名稱</th>
                                <th>類型</th>
                                <th>門檻金額</th>
                                <th>優惠內容</th>
                                <th>狀態</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody id="promotion-table-body">
                            <tr><td colspan="6" class="text-center">載入中...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- 折扣碼 Modal -->
<div class="modal fade" id="couponModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="couponModalLabel">折扣碼設定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="couponForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_coupon">
                    <input type="hidden" name="item_id" id="coupon_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">折扣代碼</label>
                        <input type="text" name="code" id="coupon_code_input" class="form-control rounded-0" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">類型</label>
                            <select name="type" id="coupon_type" class="form-select rounded-0">
                                <option value="fixed">固定金額</option>
                                <option value="percentage">百分比 (%)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">金額/百分比</label>
                            <input type="number" name="value" id="coupon_value" class="form-control rounded-0" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">最低消費門檻 (NT$)</label>
                        <input type="number" name="min_spend" id="coupon_min_spend" class="form-control rounded-0" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">到期日</label>
                        <input type="datetime-local" name="expiry_date" id="coupon_expiry" class="form-control rounded-0">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="is_first_order_only" id="coupon_is_first">
                        <label class="form-check-label" for="coupon_is_first">僅限首次購物使用</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="coupon_is_active" checked>
                        <label class="form-check-label" for="coupon_is_active">啟用此折扣碼</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-0" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary rounded-0" style="background-color: #fcb891ff; border-color: #fcb891ff;">儲存設定</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 全館活動 Modal -->
<div class="modal fade" id="promotionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="promotionModalLabel">全館活動設定</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="promotionForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_promotion">
                    <input type="hidden" name="item_id" id="promo_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">活動名稱</label>
                        <input type="text" name="name" id="promo_name" class="form-control rounded-0" required placeholder="例如：夏日滿額免運">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">活動類型</label>
                        <select name="type" id="promo_type" class="form-select rounded-0" onchange="togglePromoValue(this.value)">
                            <option value="free_shipping">滿額免運</option>
                            <option value="threshold_discount">滿額現金折抵</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">觸發門檻 (NT$)</label>
                        <input type="number" name="threshold_amount" id="promo_threshold" class="form-control rounded-0" required>
                    </div>
                    <div id="promo_value_div" class="mb-3" style="display:none;">
                        <label class="form-label">折抵金額 (NT$)</label>
                        <input type="number" name="discount_value" id="promo_value" class="form-control rounded-0" value="0">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="promo_is_active" checked>
                        <label class="form-check-label" for="promo_is_active">啟用此活動</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-0" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary rounded-0" style="background-color: #fcb891ff; border-color: #fcb891ff;">儲存活動</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let couponModal, promotionModal;

    document.addEventListener('DOMContentLoaded', () => {
        fetchCoupons();
        fetchPromotions();

        const couponModalEl = document.getElementById('couponModal');
        const promotionModalEl = document.getElementById('promotionModal');
        if (couponModalEl) couponModal = new bootstrap.Modal(couponModalEl);
        if (promotionModalEl) promotionModal = new bootstrap.Modal(promotionModalEl);
        
        const couponForm = document.getElementById('couponForm');
        if (couponForm) couponForm.onsubmit = promoFormHandler;
        
        const promotionForm = document.getElementById('promotionForm');
        if (promotionForm) promotionForm.onsubmit = promoFormHandler;
    });

    async function fetchCoupons() {
        try {
            const response = await fetch('api/admin_promotion_api?action=get_coupons');
            const result = await response.json();
            if (result.success) renderCouponTable(result.data);
        } catch (error) {
            document.getElementById('coupon-table-body').innerHTML = '<tr><td colspan="7" class="text-center text-danger">載入失敗</td></tr>';
        }
    }

    function renderCouponTable(coupons) {
        const tbody = document.getElementById('coupon-table-body');
        tbody.innerHTML = '';
        if (!coupons || coupons.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">尚無折扣碼資料</td></tr>';
            return;
        }
        coupons.forEach(c => {
            const typeText = c.type === 'fixed' ? '固定金額' : '百分比 (%)';
            const valueText = c.type === 'fixed' ? 'NT$ ' + parseInt(c.value).toLocaleString() : c.value + '%';
            const minSpend = 'NT$ ' + parseInt(c.min_spend).toLocaleString();
            const isFirst = parseInt(c.is_first_order_only) ? '是' : '否';
            const isActive = parseInt(c.is_active);
            const statusClass = isActive ? 'status-active' : 'status-inactive';
            const statusText = isActive ? '啟用中' : '已停用';
            const dataJson = JSON.stringify(c).replace(/'/g, '&#39;');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${c.code}</strong></td>
                <td>${typeText}</td>
                <td>${valueText}</td>
                <td>${minSpend}</td>
                <td>${isFirst}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>
                    <button class="btn btn-action btn-edit" onclick='editCoupon(${dataJson})'>編輯</button>
                    <button class="btn btn-action btn-delete" onclick="deleteCoupon(${c.id})">刪除</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    async function fetchPromotions() {
        try {
            const response = await fetch('api/admin_promotion_api?action=get_promotions');
            const result = await response.json();
            if (result.success) renderPromotionTable(result.data);
        } catch (error) {
            document.getElementById('promotion-table-body').innerHTML = '<tr><td colspan="6" class="text-center text-danger">載入失敗</td></tr>';
        }
    }

    function renderPromotionTable(promotions) {
        const tbody = document.getElementById('promotion-table-body');
        tbody.innerHTML = '';
        if (!promotions || promotions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">尚無全館活動</td></tr>';
            return;
        }
        promotions.forEach(pr => {
            const typeText = pr.type === 'free_shipping' ? '滿額免運' : '滿額折抵';
            const threshold = 'NT$ ' + parseInt(pr.threshold_amount).toLocaleString();
            const content = pr.type === 'free_shipping' ? '免運費' : '折抵 NT$ ' + parseInt(pr.discount_value).toLocaleString();
            const isActive = parseInt(pr.is_active);
            const statusClass = isActive ? 'status-active' : 'status-inactive';
            const statusText = isActive ? '啟用中' : '已停用';
            const dataJson = JSON.stringify(pr).replace(/'/g, '&#39;');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${pr.name}</td>
                <td>${typeText}</td>
                <td>${threshold}</td>
                <td>${content}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>
                    <button class="btn btn-action btn-edit" onclick='editPromotion(${dataJson})'>編輯</button>
                    <button class="btn btn-action btn-delete" onclick="deletePromotion(${pr.id})">刪除</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function showCouponModal() {
        if (!couponModal) return;
        document.getElementById('couponModalLabel').textContent = '新增折扣碼';
        document.getElementById('coupon_id').value = 0;
        document.getElementById('couponForm').reset();
        couponModal.show();
    }

    function editCoupon(data) {
        if (!couponModal) return;
        document.getElementById('couponModalLabel').textContent = '編輯折扣碼';
        document.getElementById('coupon_id').value = data.id;
        document.getElementById('coupon_code_input').value = data.code;
        document.getElementById('coupon_type').value = data.type;
        document.getElementById('coupon_value').value = data.value;
        document.getElementById('coupon_min_spend').value = data.min_spend;
        document.getElementById('coupon_expiry').value = data.expiry_date ? data.expiry_date.replace(' ', 'T') : '';
        document.getElementById('coupon_is_first').checked = !!parseInt(data.is_first_order_only);
        document.getElementById('coupon_is_active').checked = !!parseInt(data.is_active);
        couponModal.show();
    }

    function showPromotionModal() {
        if (!promotionModal) return;
        document.getElementById('promotionModalLabel').textContent = '新增全館活動';
        document.getElementById('promo_id').value = 0;
        document.getElementById('promotionForm').reset();
        togglePromoValue('free_shipping');
        promotionModal.show();
    }

    function editPromotion(data) {
        if (!promotionModal) return;
        document.getElementById('promotionModalLabel').textContent = '編輯全館活動';
        document.getElementById('promo_id').value = data.id;
        document.getElementById('promo_name').value = data.name;
        document.getElementById('promo_type').value = data.type;
        document.getElementById('promo_threshold').value = data.threshold_amount;
        document.getElementById('promo_value').value = data.discount_value;
        document.getElementById('promo_is_active').checked = !!parseInt(data.is_active);
        togglePromoValue(data.type);
        promotionModal.show();
    }

    function togglePromoValue(type) {
        document.getElementById('promo_value_div').style.display = (type === 'threshold_discount' ? 'block' : 'none');
    }

    async function promoFormHandler(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const action = formData.get('action');
        const id = form.querySelector('[name="item_id"]').value;
        if (id > 0) formData.append('item_id', id);

        try {
            const response = await fetch(`api/admin_promotion_api?action=${action}`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                alert(result.message);
                if (action === 'save_coupon') {
                    if (couponModal) couponModal.hide();
                    fetchCoupons();
                } else {
                    if (promotionModal) promotionModal.hide();
                    fetchPromotions();
                }
            } else {
                alert('失敗: ' + result.message);
            }
        } catch (error) {
            alert('操作失敗，請檢查網路。');
        }
    }

    async function deleteCoupon(id) {
        if (!confirm('確定要刪除此折扣碼嗎？')) return;
        const formData = new FormData();
        formData.append('item_id', id);
        try {
            const response = await fetch('api/admin_promotion_api?action=delete_coupon', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) fetchCoupons();
            else alert('刪除失敗: ' + result.message);
        } catch (error) { alert('刪除失敗，請檢查網路。'); }
    }

    async function deletePromotion(id) {
        if (!confirm('確定要刪除此促銷活動嗎？')) return;
        const formData = new FormData();
        formData.append('item_id', id);
        try {
            const response = await fetch('api/admin_promotion_api?action=delete_promotion', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) fetchPromotions();
            else alert('刪除失敗: ' + result.message);
        } catch (error) { alert('刪除失敗，請檢查網路。'); }
    }
</script>

<?php require_once 'view/admin_layout/footer.php'; ?>
