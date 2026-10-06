<?php
$page_title = '會員管理';
require_once 'view/admin_layout/header.php';
require_once 'view/admin_layout/sidebar.php';
?>

<main class="col-lg-10">
    <!-- 會員管理區塊 -->
    <div class="section-card" id="members">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="section-title mb-0">
                <i class="bi bi-people"></i> 會員管理
            </h2>
        </div>

        <div class="row mb-3">
            <div class="col-md-5">
                <div class="d-flex">
                    <input type="text" id="member-search-input" class="form-control rounded-0 me-2" placeholder="搜尋姓名或Email...">
                    <button type="button" id="member-search-btn" class="btn btn-view rounded-0 fw-bold">搜尋</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>會員編號</th>
                        <th>會員姓名</th>
                        <th>電子郵件</th>
                        <th>註冊日期</th>
                        <th>會員等級</th>
                        <th>消費金額</th>
                        <th>訂單數</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="member-table-body">
                    <tr><td colspan="8" class="text-center">載入中...</td></tr>
                </tbody>
            </table>
        </div>

        <nav aria-label="Member pagination" class="mt-4">
            <ul class="pagination justify-content-center" id="member-pagination-container"></ul>
        </nav>
    </div>
</main>

<script>
    let currentMemberPage = 1;
    let currentMemberSearch = '';
    let membersList = []; // 全域變數以儲存載入的會員

    document.addEventListener('DOMContentLoaded', () => {
        fetchMembers(1);

        const searchBtn = document.getElementById('member-search-btn');
        const searchInput = document.getElementById('member-search-input');
        if (searchBtn && searchInput) {
            searchBtn.addEventListener('click', () => fetchMembers(1, searchInput.value));
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') fetchMembers(1, searchInput.value);
            });
        }

        const editForm = document.getElementById('edit-member-form');
        if (editForm) {
            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(editForm);

                try {
                    const response = await fetch('api/admin_member_api?action=update_member', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        const modalEl = document.getElementById('editMemberModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                        fetchMembers(currentMemberPage, currentMemberSearch);
                    } else {
                        alert('更新失敗: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error updating member:', error);
                    alert('更新發生錯誤，請稍後再試。');
                }
            });
        }
    });

    async function fetchMembers(page = 1, search = '') {
        currentMemberPage = page;
        currentMemberSearch = search;

        try {
            const url = `api/admin_member_api?action=get_list&page=${page}&search=${encodeURIComponent(search)}`;
            const response = await fetch(url);
            const result = await response.json();

            if (result.success) {
                renderMemberTable(result.data.members);
                renderMemberPagination(result.data.pagination);
            } else {
                document.getElementById('member-table-body').innerHTML =
                    '<tr><td colspan="8" class="text-center text-danger">載入失敗: ' + result.message + '</td></tr>';
            }
        } catch (error) {
            console.error('Error fetching members:', error);
            document.getElementById('member-table-body').innerHTML =
                '<tr><td colspan="8" class="text-center text-danger">載入失敗，請檢查網路。</td></tr>';
        }
    }

    function renderMemberTable(members) {
        const tbody = document.getElementById('member-table-body');
        tbody.innerHTML = '';
        membersList = members || [];

        if (!members || members.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">尚無會員資料</td></tr>';
            return;
        }

        members.forEach(m => {
            const idStr = String(m.id).padStart(3, '0');
            const name = m.display_name || m.username;
            const date = m.created_at ? m.created_at.substring(0, 10) : '-';
            
            let roleBadge = '';
            if (m.role === 'admin') {
                roleBadge = '<span class="status-badge" style="background-color: #f3e5f5; color: #7b1fa2; border-color: #e1bee7;">系統管理員</span>';
            } else if (m.role === 'author') {
                roleBadge = '<span class="status-badge status-processing">專欄作家</span>';
            } else {
                roleBadge = '<span class="status-badge status-active">一般會員</span>';
            }

            const totalSpent = parseInt(m.total_spent || 0).toLocaleString();

            const deleteBtn = m.role === 'admin'
                ? `<button class="btn btn-action btn-delete" disabled title="無法刪除管理員" style="opacity: 0.5; cursor: not-allowed;">
                        <i class="bi bi-trash"></i> 刪除
                   </button>`
                : `<button class="btn btn-action btn-delete" onclick="deleteMember(${m.id})">
                        <i class="bi bi-trash"></i> 刪除
                   </button>`;

            const editBtn = `<button class="btn btn-action btn-edit me-1" onclick="editMember(${m.id})">
                                <i class="bi bi-pencil"></i> 編輯
                             </button>`;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#MEM${idStr}</td>
                <td>${name}</td>
                <td>${m.email}</td>
                <td>${date}</td>
                <td>${roleBadge}</td>
                <td>NT$ ${totalSpent}</td>
                <td>${m.order_count}</td>
                <td>
                    ${editBtn}
                    ${deleteBtn}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderMemberPagination(pagination) {
        const container = document.getElementById('member-pagination-container');
        container.innerHTML = '';

        if (pagination.total_pages <= 1) return;

        const prevDisabled = pagination.current_page <= 1 ? 'disabled' : '';
        container.innerHTML += `
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="javascript:void(0)" onclick="fetchMembers(${pagination.current_page - 1}, currentMemberSearch)">上一頁</a>
            </li>
        `;

        for (let i = 1; i <= pagination.total_pages; i++) {
            const active = i === pagination.current_page ? 'active' : '';
            container.innerHTML += `
                <li class="page-item ${active}">
                    <a class="page-link" href="javascript:void(0)" onclick="fetchMembers(${i}, currentMemberSearch)">${i}</a>
                </li>
            `;
        }

        const nextDisabled = pagination.current_page >= pagination.total_pages ? 'disabled' : '';
        container.innerHTML += `
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="javascript:void(0)" onclick="fetchMembers(${pagination.current_page + 1}, currentMemberSearch)">下一頁</a>
            </li>
        `;
    }

    async function deleteMember(id) {
        if (!confirm('確定要刪除此會員嗎？此動作無法復原。')) return;

        const formData = new FormData();
        formData.append('user_id', id);

        try {
            const response = await fetch('api/admin_member_api?action=delete_member', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                fetchMembers(currentMemberPage, currentMemberSearch);
            } else {
                alert('刪除失敗: ' + result.message);
            }
        } catch (error) {
            alert('刪除失敗，請檢查網路。');
        }
    }

    function editMember(id) {
        const member = membersList.find(m => m.id === id);
        if (!member) return;

        document.getElementById('edit-user-id').value = member.id;
        document.getElementById('edit-username').value = member.username;
        document.getElementById('edit-email').value = member.email;
        document.getElementById('edit-display-name').value = member.display_name || '';
        document.getElementById('edit-phone').value = member.phone || '';
        document.getElementById('edit-role').value = member.role;

        const modal = new bootstrap.Modal(document.getElementById('editMemberModal'));
        modal.show();
    }
</script>

<!-- 編輯會員 Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1" aria-labelledby="editMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-0">
            <form id="edit-member-form">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editMemberModalLabel">
                        <i class="bi bi-pencil-square"></i> 編輯會員資料
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-user-id" name="user_id">
                    
                    <div class="mb-3">
                        <label for="edit-username" class="form-label fw-bold">帳號 (使用者名稱)</label>
                        <input type="text" class="form-control rounded-0" id="edit-username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-display-name" class="form-label fw-bold">顯示姓名</label>
                        <input type="text" class="form-control rounded-0" id="edit-display-name" name="display_name" placeholder="尚未設定顯示姓名">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-email" class="form-label fw-bold">電子郵件 (Email)</label>
                        <input type="email" class="form-control rounded-0" id="edit-email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-phone" class="form-label fw-bold">聯絡電話</label>
                        <input type="text" class="form-control rounded-0" id="edit-phone" name="phone" placeholder="尚未設定聯絡電話">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-role" class="form-label fw-bold">會員身分</label>
                        <select class="form-select rounded-0" id="edit-role" name="role" required>
                            <option value="member">一般會員</option>
                            <option value="author">專欄作家</option>
                            <option value="admin">系統管理員</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-view rounded-0" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-add rounded-0 fw-bold">儲存變更</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'view/admin_layout/footer.php'; ?>
