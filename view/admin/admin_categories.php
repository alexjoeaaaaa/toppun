<?php
$page_title = '分類管理';
require_once 'view/admin_layout/header.php';
require_once 'view/admin_layout/sidebar.php';
?>

<main class="col-lg-10">
    <div class="section-card" id="categories">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="section-title mb-0">
                <i class="bi bi-grid"></i> 商品分類管理
            </h2>
            <button class="btn btn-add" onclick="showAddModal()">
                <i class="bi bi-plus-circle"></i> 新增商品分類
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">編號</th>
                        <th style="width: 200px;">分類名稱</th>
                        <th>分類概述 (Meta Description)</th>
                        <th style="width: 250px;">關鍵字 (Keywords)</th>
                        <th style="width: 180px;">操作</th>
                    </tr>
                </thead>
                <tbody id="category-table-body">
                    <tr><td colspan="5" class="text-center">載入中...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ===== 分類 Modal ===== -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="categoryModalLabel">分類資料</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="modal_item_id" value="0">
                    
                    <div class="mb-3">
                        <label class="form-label">分類名稱 <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="modal_name" class="form-control rounded-0 w-100" required placeholder="例如：香氛蠟燭系列">
                        <div id="uncat-name-warning" class="form-text text-danger" style="display: none;">
                            <i class="bi bi-exclamation-triangle"></i> 預設之「尚未分類」不可重新命名。
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">分類概述 <small class="text-muted fw-normal">(用於 Meta Description)</small></label>
                        <textarea name="description" id="modal_description" class="form-control rounded-0 w-100" rows="4" placeholder="請輸入適合 SEO 的商品分類介紹..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">關鍵字 <small class="text-muted fw-normal">(用於 SEO，以半角逗號分隔)</small></label>
                        <input type="text" name="keywords" id="modal_keywords" class="form-control rounded-0 w-100" placeholder="例如：蠟燭,香氛,舒壓">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-0" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary rounded-0" style="background:#fcb891;border-color:#fcb891;color:#fff;">
                        <i class="bi bi-save"></i> 儲存分類
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let categoryModal;
let categoryForm;
let categoriesList = [];

document.addEventListener('DOMContentLoaded', () => {
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    categoryForm  = document.getElementById('categoryForm');

    fetchCategories();

    categoryForm.addEventListener('submit', handleFormSubmit);
});

async function fetchCategories() {
    try {
        const response = await fetch('api/admin_category_api?action=get_list');
        const result = await response.json();
        if (result.success) {
            categoriesList = result.data || [];
            renderCategoryTable(categoriesList);
        } else {
            document.getElementById('category-table-body').innerHTML = 
                `<tr><td colspan="5" class="text-center text-danger">載入失敗: ${result.message}</td></tr>`;
        }
    } catch (e) {
        document.getElementById('category-table-body').innerHTML = 
            `<tr><td colspan="5" class="text-center text-danger">載入失敗，請檢查網路。</td></tr>`;
    }
}

function renderCategoryTable(categories) {
    const tbody = document.getElementById('category-table-body');
    tbody.innerHTML = '';

    if (!categories || categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">尚無分類資料</td></tr>';
        return;
    }

    categories.forEach(c => {
        const tr = document.createElement('tr');
        const isDefault = (c.name === '尚未分類');

        // Render Action buttons
        const editBtn = `<button class="btn btn-action btn-edit me-1" onclick="showEditModal(${c.id})"><i class="bi bi-pencil"></i> 編輯</button>`;
        const deleteBtn = isDefault 
            ? `<button class="btn btn-action btn-delete" disabled style="opacity: 0.5; cursor: not-allowed;" title="預設分類不可刪除"><i class="bi bi-trash"></i> 刪除</button>`
            : `<button class="btn btn-action btn-delete" onclick="deleteCategory(${c.id}, '${c.name.replace(/'/g, "\\'")}')"><i class="bi bi-trash"></i> 刪除</button>`;

        // Render Name column with badge for Default Category
        const nameCol = isDefault 
            ? `${c.name} <span class="status-badge" style="background-color: #f8f5f2; color: #a0522d; border-color: #e8d5c8; font-size: 0.75rem; padding: 2px 6px;">系統預設</span>`
            : c.name;

        tr.innerHTML = `
            <td>#${c.id}</td>
            <td class="fw-bold">${nameCol}</td>
            <td><div style="max-height: 80px; overflow-y: auto; white-space: pre-wrap; font-size: 0.85rem;" class="text-muted">${c.description || '-'}</div></td>
            <td><small class="text-secondary">${c.keywords || '-'}</small></td>
            <td>
                ${editBtn}
                ${deleteBtn}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function showAddModal() {
    categoryForm.reset();
    document.getElementById('modal_item_id').value = 0;
    document.getElementById('categoryModalLabel').textContent = '新增商品分類';
    
    // Reset name input state
    const nameInput = document.getElementById('modal_name');
    nameInput.disabled = false;
    document.getElementById('uncat-name-warning').style.display = 'none';

    categoryModal.show();
}

async function showEditModal(id) {
    categoryForm.reset();
    try {
        const response = await fetch(`api/admin_category_api?action=get_category&item_id=${id}`);
        const result = await response.json();
        if (!result.success) {
            alert('讀取失敗: ' + result.message);
            return;
        }

        const d = result.data;
        document.getElementById('modal_item_id').value = d.id;
        document.getElementById('modal_name').value = d.name;
        document.getElementById('modal_description').value = d.description || '';
        document.getElementById('modal_keywords').value = d.keywords || '';
        document.getElementById('categoryModalLabel').textContent = '編輯商品分類';

        // Check if editing "尚未分類"
        const nameInput = document.getElementById('modal_name');
        const warning = document.getElementById('uncat-name-warning');
        if (d.name === '尚未分類') {
            nameInput.disabled = true;
            warning.style.display = 'block';
        } else {
            nameInput.disabled = false;
            warning.style.display = 'none';
        }

        categoryModal.show();
    } catch (e) {
        alert('讀取失敗，請檢查網路。');
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();

    // Re-enable disabled input temporarily to include it in FormData
    const nameInput = document.getElementById('modal_name');
    const wasDisabled = nameInput.disabled;
    if (wasDisabled) {
        nameInput.disabled = false;
    }

    const formData = new FormData(categoryForm);

    if (wasDisabled) {
        nameInput.disabled = true; // Restore state
    }

    try {
        const response = await fetch('api/admin_category_api?action=save_category', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            categoryModal.hide();
            fetchCategories();
        } else {
            alert('儲存失敗: ' + result.message);
        }
    } catch (err) {
        alert('儲存失敗，請檢查網路。');
    }
}

async function deleteCategory(id, name) {
    if (!confirm(`確定要刪除「${name}」分類嗎？\n該分類底下的所有商品將會自動重新歸類至「尚未分類」中。`)) {
        return;
    }

    try {
        const response = await fetch(`api/admin_category_api?action=delete_category&item_id=${id}`, {
            method: 'POST'
        });
        const result = await response.json();
        if (result.success) {
            alert(result.message || '刪除成功');
            fetchCategories();
        } else {
            alert('刪除失敗: ' + result.message);
        }
    } catch (e) {
        alert('刪除失敗，請檢查網路。');
    }
}
</script>
