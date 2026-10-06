<?php
$page_title = 'Blog 審核管理';
require_once 'view/admin_layout/header.php';
require_once 'view/admin_layout/sidebar.php';
?>

<main class="col-lg-10">
    <div class="section-card" id="blog">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="section-title mb-0">
                <i class="bi bi-journal-text"></i> Blog 審核管理
            </h2>
            <a href="post-editor.php" class="btn btn-add">
                <i class="bi bi-plus-circle"></i> 新增文章
            </a>
        </div>

        <div class="filter-btn-group mb-4" id="blog-filter-group">
            <button class="btn active" data-filter="all">全部</button>
            <button class="btn" data-filter="pending">待審核</button>
            <button class="btn" data-filter="published">已通過</button>
            <button class="btn" data-filter="rejected">已拒絕</button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>文章編號</th>
                        <th>文章標題</th>
                        <th>作者</th>
                        <th>提交日期</th>
                        <th>分類</th>
                        <th style="width: 100px; text-align: center;">審核狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="blog-table-body">
                    <tr>
                        <td colspan="7" class="text-center">載入中...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav aria-label="Blog pagination" class="mt-4">
            <ul class="pagination justify-content-center" id="blog-pagination-container">
            </ul>
        </nav>
    </div>
</main>

<script>
    let currentBlogFilter = 'all';
    let currentBlogPage = 1;

    document.addEventListener('DOMContentLoaded', () => {
        fetchBlogs('all', 1);

        document.querySelectorAll('#blog-filter-group .btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const filter = e.target.dataset.filter;
                fetchBlogs(filter, 1);
            });
        });
    });

    async function fetchBlogs(filter = 'all', page = 1) {
        currentBlogFilter = filter;
        currentBlogPage = page;
        
        document.querySelectorAll('#blog-filter-group .btn').forEach(btn => {
            if (btn.dataset.filter === filter) btn.classList.add('active');
            else btn.classList.remove('active');
        });

        try {
            const response = await fetch(`api/admin_blog_api?action=get_list&filter=${filter}&page=${page}`);
            const result = await response.json();
            
            if (result.success) {
                renderBlogTable(result.data.articles);
                renderBlogPagination(result.data.pagination);
            } else {
                alert('獲取部落格列表失敗: ' + result.message);
            }
        } catch (error) {
            console.error('Error fetching blogs:', error);
            document.getElementById('blog-table-body').innerHTML = '<tr><td colspan="7" class="text-center text-danger">載入失敗，請檢查網路。</td></tr>';
        }
    }

    function renderBlogTable(articles) {
        const tbody = document.getElementById('blog-table-body');
        tbody.innerHTML = '';

        if (!articles || articles.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">尚無文章資料</td></tr>';
            return;
        }

        articles.forEach(article => {
            let statusClass = '', statusText = '';
            switch(article.status) {
                case 'pending': statusClass = 'status-pending'; statusText = '待審核'; break;
                case 'published': statusClass = 'status-completed'; statusText = '已通過'; break;
                case 'rejected': statusClass = 'status-inactive'; statusText = '已拒絕'; break;
            }

            const blogIdStr = String(article.id).padStart(3, '0');
            const authorName = article.author_name ? article.author_name : article.username;
            const createdDate = article.created_at ? article.created_at.substring(0, 10) : '';

            let actionButtons = `
                <a href="blog-review.php?id=${article.id}" class="btn btn-action btn-view"><i class="bi bi-eye"></i> 查看</a>
                <a href="post-editor.php?id=${article.id}" class="btn btn-action btn-edit"><i class="bi bi-pencil"></i> 編輯</a>
            `;

            if (article.status === 'pending') {
                actionButtons += `
                    <button class="btn btn-action btn-edit" style="background-color: #4caf50; border-color: #4caf50;" onclick="updateBlogStatus(${article.id}, 'published')">
                        <i class="bi bi-check-circle"></i> 通過
                    </button>
                    <button class="btn btn-action btn-delete" onclick="updateBlogStatus(${article.id}, 'rejected')">
                        <i class="bi bi-x-circle"></i> 拒絕
                    </button>
                `;
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#BLOG${blogIdStr}</td>
                <td>${article.title}</td>
                <td>${authorName}</td>
                <td>${createdDate}</td>
                <td>${article.category}</td>
                <td class="text-center"><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td>${actionButtons}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderBlogPagination(pagination) {
        const container = document.getElementById('blog-pagination-container');
        container.innerHTML = '';
        
        if (pagination.total_pages <= 1) return;

        const prevDisabled = pagination.current_page <= 1 ? 'disabled' : '';
        container.innerHTML += `
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="javascript:void(0)" onclick="fetchBlogs('${currentBlogFilter}', ${pagination.current_page - 1})">上一頁</a>
            </li>
        `;

        for (let i = 1; i <= pagination.total_pages; i++) {
            const active = i === pagination.current_page ? 'active' : '';
            container.innerHTML += `
                <li class="page-item ${active}">
                    <a class="page-link" href="javascript:void(0)" onclick="fetchBlogs('${currentBlogFilter}', ${i})">${i}</a>
                </li>
            `;
        }

        const nextDisabled = pagination.current_page >= pagination.total_pages ? 'disabled' : '';
        container.innerHTML += `
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="javascript:void(0)" onclick="fetchBlogs('${currentBlogFilter}', ${pagination.current_page + 1})">下一頁</a>
            </li>
        `;
    }

    async function updateBlogStatus(id, status) {
        const actionText = status === 'published' ? '通過' : '拒絕';
        if (!confirm(`確定要${actionText}此文章嗎？`)) return;

        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);

        try {
            const response = await fetch('api/admin_blog_api?action=update_status', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                alert(result.message);
                fetchBlogs(currentBlogFilter, currentBlogPage);
            } else {
                alert('更新失敗: ' + result.message);
            }
        } catch (error) {
            console.error('Error updating status:', error);
            alert('更新失敗，請檢查網路。');
        }
    }
</script>

<?php require_once 'view/admin_layout/footer.php'; ?>
