<?php
$page_title = '商品管理';
require_once 'view/admin_layout/header.php';
require_once 'view/admin_layout/sidebar.php';

try {
    $all_categories = $pdo->query("SELECT * FROM product_categories ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    $all_categories = [];
}
?>

<main class="col-lg-10">
    <div class="section-card" id="products">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="section-title mb-0"><i class="bi bi-bag"></i> 商品管理</h2>
            <button class="btn btn-add" onclick="showAddModal()">
                <i class="bi bi-plus-circle"></i> 新增商品
            </button>
        </div>

        <div class="row align-items-center mb-4">
            <div class="col-md-5">
                <select id="product-category-filter" class="form-select rounded-0" onchange="fetchProducts(1)">
                    <option value="0">所有分類</option>
                    <?php foreach ($all_categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-7">
                <div class="d-flex">
                    <input type="text" id="product-search-input" class="form-control rounded-0 me-2" placeholder="搜尋商品名稱、描述或關鍵字...">
                    <button type="button" class="btn btn-view rounded-0 fw-bold" onclick="fetchProducts(1)">搜尋</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover data-table">
                <thead>
                    <tr>
                        <th>編號</th>
                        <th style="width:60px;text-align:center">縮圖</th>
                        <th>商品名稱</th>
                        <th>分類</th>
                        <th>價格</th>
                        <th>庫存</th>
                        <th>狀態</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="product-table-body">
                    <tr><td colspan="8" class="text-center">載入中...</td></tr>
                </tbody>
            </table>
        </div>
        <nav class="mt-4">
            <ul class="pagination justify-content-center" id="product-pagination-container"></ul>
        </nav>
    </div>
</main>

<!-- ===== 商品 Modal ===== -->
<div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content rounded-0">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="productModalLabel">商品資料</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_product">
                    <input type="hidden" name="item_id" id="modal_item_id" value="0">

                    <!-- Bootstrap Tabs -->
                    <ul class="nav nav-tabs mb-3" id="productTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-basic">
                                <i class="bi bi-info-circle"></i> 基本資訊
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-images">
                                <i class="bi bi-images"></i> 圖片管理
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-desc">
                                <i class="bi bi-layout-text-window"></i> 商品描述
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-qa">
                                <i class="bi bi-question-circle"></i> Q&A
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-specs">
                                <i class="bi bi-list-check"></i> 規格
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content" id="productTabContent">

                        <!-- Tab 1: 基本資訊 -->
                        <div class="tab-pane fade show active" id="tab-basic">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label">商品名稱 <span class="text-danger">*</span></label>
                                    <input type="text" name="name" id="modal_name" class="form-control rounded-0" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">分類</label>
                                    <select name="category_id" id="modal_category_id" class="form-select rounded-0">
                                        <?php foreach ($all_categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">價格</label>
                                    <div class="input-group">
                                        <span class="input-group-text rounded-0">NT$</span>
                                        <input type="number" name="price" id="modal_price" class="form-control rounded-0" required min="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">庫存</label>
                                    <input type="number" name="stock" id="modal_stock" class="form-control rounded-0" required min="0">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Meta Keywords (SEO)</label>
                                    <input type="text" name="meta_keywords" id="modal_meta_keywords" class="form-control rounded-0" placeholder="逗號分隔，不可有空白">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">商品簡介 <small class="text-muted fw-normal">(顯示於商品頁右側簡述區)</small></label>
                                    <textarea id="modal_intro" class="form-control rounded-0" rows="4" placeholder="簡短描述商品特色..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">商品特色 <small class="text-muted fw-normal">(顯示於右側，帶打勾符號)</small></label>
                                    <div id="feature-list-container" class="mb-2" style="display:flex;flex-direction:column;gap:6px;"></div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-0" onclick="addFeatureItem('')">
                                        <i class="bi bi-plus-circle"></i> 新增特色項目
                                    </button>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="modal_is_active" value="1">
                                        <label class="form-check-label" for="modal_is_active">立即上架</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab 2: 圖片管理 -->
                        <div class="tab-pane fade" id="tab-images">
                            <!-- 主圖 -->
                            <h6 class="fw-bold mb-2"><i class="bi bi-star"></i> 主圖</h6>
                            <input type="hidden" name="existing_image" id="modal_existing_image" value="">
                            <div class="d-flex align-items-start gap-3 mb-4">
                                <img id="image_preview" src="" alt="主圖預覽" style="width:120px;height:120px;object-fit:cover;border:1px solid #ddd;display:none;border-radius:4px;">
                                <div>
                                    <input type="file" name="image_file" id="modal_image_file" class="form-control rounded-0 mb-2" accept="image/*" style="max-width:340px;">
                                    <small class="text-muted">建議尺寸 800×800，支援 JPG/PNG/WEBP，自動轉為 WebP。</small>
                                </div>
                            </div>

                            <hr>

                            <!-- 附加圖集 -->
                            <h6 class="fw-bold mb-2"><i class="bi bi-images"></i> 附加圖集 <small class="text-muted fw-normal">(可多張，用於前台輪播)</small></h6>
                            <div id="gallery-edit-notice" class="alert alert-info py-2 small" style="display:none;">
                                <i class="bi bi-info-circle"></i> 新增商品請先儲存基本資訊後，再回來上傳附加圖集。
                            </div>
                            <div id="gallery-upload-section" style="display:none;">
                                <div id="gallery-thumbnails" class="d-flex flex-wrap gap-2 mb-3"></div>
                                <label class="btn btn-outline-secondary rounded-0 btn-sm">
                                    <i class="bi bi-plus-lg"></i> 選擇圖片上傳
                                    <input type="file" id="gallery_upload_input" accept="image/*" style="display:none;" multiple>
                                </label>
                                <small class="text-muted ms-2">支援多選，每張自動處理為 WebP。</small>
                                <div id="gallery-upload-progress" class="mt-2"></div>
                            </div>
                        </div>

                        <!-- Tab 3: 商品描述 Detail (JSON Block Builder) -->
                        <div class="tab-pane fade" id="tab-desc">
                            <textarea name="description" id="modal_description" style="display:none;"></textarea>
                            <?php echo renderSectionBlockBuilder('detail', '商品詳細描述', 'bi-layout-text-window'); ?>
                        </div>

                        <!-- Tab 4: Q&A (JSON Block Builder) -->
                        <div class="tab-pane fade" id="tab-qa">
                            <?php echo renderSectionBlockBuilder('qa', '常見問題 Q&A', 'bi-question-circle'); ?>
                        </div>

                        <!-- Tab 5: 規格 (JSON Block Builder) -->
                        <div class="tab-pane fade" id="tab-specs">
                            <?php echo renderSectionBlockBuilder('specs', '商品規格', 'bi-list-check'); ?>
                        </div>

                    </div><!-- /tab-content -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-0" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary rounded-0" style="background:#fcb891;border-color:#fcb891;">
                        <i class="bi bi-save"></i> 儲存商品
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ── Block Builder ── */
.block-card {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 10px;
    background: #fff;
}
.block-card .block-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 4px 4px 0 0;
    font-size: 0.82rem;
    font-weight: 600;
    gap: 8px;
}
.block-card.type-text .block-header { border-left: 4px solid #0d6efd; }
.block-card.type-image .block-header { border-left: 4px solid #198754; }
.block-card .block-body { padding: 10px; }
.block-header .block-ctrl-btns .btn { font-size: 0.75rem; padding: 2px 7px; }

/* ── Rich Text Toolbar ── */
.rte-toolbar {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;          /* 不折行，橫向捲動 */
    overflow-x: auto;
    gap: 3px;
    flex: 1;
    min-width: 0;               /* flex 子項可縮小 */
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 3px 6px;
    scrollbar-width: thin;
    scrollbar-color: #dee2e6 transparent;
}
.rte-toolbar::-webkit-scrollbar { height: 3px; }
.rte-toolbar::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 2px; }
.rte-toolbar .tb-btn {
    background: none;
    border: 1px solid transparent;
    border-radius: 3px;
    color: #555;
    font-size: 0.8rem;
    padding: 2px 6px;
    cursor: pointer;
    line-height: 1.4;
    white-space: nowrap;
}
.rte-toolbar .tb-btn:hover { background: #e9ecef; border-color: #ced4da; color: #222; }
.rte-toolbar .tb-btn.active { background: #d77244; color: #fff; border-color: #d77244; }
.rte-toolbar .tb-sep {
    width: 1px;
    height: 18px;
    background: #dee2e6;
    margin: 0 2px;
}
.rte-toolbar select.tb-select {
    border: 1px solid #dee2e6;
    border-radius: 3px;
    font-size: 0.78rem;
    padding: 1px 4px;
    background: #fff;
    color: #555;
    cursor: pointer;
    height: 26px;
}
.rte-toolbar input[type="color"].tb-color {
    width: 26px;
    height: 26px;
    border: 1px solid #dee2e6;
    border-radius: 3px;
    padding: 1px;
    cursor: pointer;
    background: none;
}

/* ── contenteditable area ── */
.rte-editor {
    min-height: 100px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 10px 12px;
    outline: none;
    font-size: 0.95rem;
    line-height: 1.7;
    background: #fff;
    color: #333;
    word-break: break-word;
}
.rte-editor:focus { border-color: #fcb891; box-shadow: 0 0 0 0.2rem rgba(252,184,145,.25); }
.rte-editor[data-placeholder]:empty::before {
    content: attr(data-placeholder);
    color: #aaa;
    pointer-events: none;
}
.rte-editor ul { padding-left: 1.4em; list-style: disc; }
.rte-editor ol { padding-left: 1.4em; list-style: decimal; }

/* Section block builder header */
.section-block-header {
    background: #f1f3f5;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 8px 12px;
    margin-bottom: 12px;
    font-size: 0.85rem;
    color: #495057;
}

/* Gallery thumbs */
.gallery-thumb-item {
    position: relative;
    width: 90px;
    height: 90px;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}
.gallery-thumb-item img { width: 100%; height: 100%; object-fit: cover; }
.gallery-thumb-item .del-btn {
    position: absolute;
    top: 2px; right: 2px;
    background: rgba(220,53,69,0.85);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 22px; height: 22px;
    font-size: 12px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
}
.gallery-thumb-item .del-btn:hover { background: #dc3545; }
</style>

<?php
/**
 * 產生指定 section 的 block builder HTML（PHP helper）
 */
function renderSectionBlockBuilder(string $section, string $label, string $icon): string {
    return <<<HTML
    <div class="section-block-header">
        <i class="bi {$icon}"></i> <strong>{$label}</strong>
    </div>
    <div class="d-flex gap-2 mb-3">
        <button type="button" class="btn btn-outline-primary btn-sm rounded-0" onclick="addBlock('{$section}','text')">
            <i class="bi bi-plus"></i> 新增文字區塊
        </button>
        <button type="button" class="btn btn-outline-success btn-sm rounded-0" onclick="addBlock('{$section}','image')">
            <i class="bi bi-plus"></i> 新增圖片區塊
        </button>
    </div>
    <div id="block-builder-{$section}" style="min-height:100px;"></div>
    <p id="block-empty-hint-{$section}" class="text-muted text-center mt-3">
        <i class="bi {$icon}" style="font-size:2rem;opacity:0.3;"></i><br>
        點擊上方按鈕新增文字或圖片區塊
    </p>
    HTML;
}
?>

<script>
// ═══════════════════════════════════════════════════════
//  State
// ═══════════════════════════════════════════════════════
let currentProductPage = 1;
let productModal;
let productForm;
let hoverPreviewDiv;

// Gallery state: array of { id, image_url } for current product
let galleryImages = [];

/**
 * blockSections — 三個 section 各自維護一個 blocks 陣列
 * JSON 結構: { detail: [...], qa: [...], specs: [...] }
 */
const SECTIONS = ['detail', 'qa', 'specs'];
let blockSections = { detail: [], qa: [], specs: [] };

// features — 商品特色陣列 (純文字)
let productFeatures = [];

// Current editing product id (0 = new)
let currentProductId = 0;

// ═══════════════════════════════════════════════════════
//  Init
// ═══════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    hoverPreviewDiv = document.createElement('div');
    hoverPreviewDiv.id = 'hover-preview-container';
    hoverPreviewDiv.style.cssText = 'display:none;position:fixed;z-index:9999;background:#fff;padding:5px;border:1px solid #ddd;box-shadow:0 8px 16px rgba(0,0,0,.15);border-radius:6px;pointer-events:none;';
    document.body.appendChild(hoverPreviewDiv);

    fetchProducts(1);
    productModal = new bootstrap.Modal(document.getElementById('productModal'));
    productForm  = document.getElementById('productForm');

    // 主圖預覽
    document.getElementById('modal_image_file').onchange = function() {
        const [file] = this.files;
        if (file) {
            const preview = document.getElementById('image_preview');
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        }
    };

    // Meta keywords 去空白
    document.getElementById('modal_meta_keywords').addEventListener('input', function() {
        this.value = this.value.replace(/\s+/g, '');
    });

    // 搜尋 Enter
    document.getElementById('product-search-input').addEventListener('keypress', e => {
        if (e.key === 'Enter') fetchProducts(1);
    });

    // 圖集上傳 input
    document.getElementById('gallery_upload_input').addEventListener('change', handleGalleryUpload);

    // Form submit
    productForm.onsubmit = handleFormSubmit;

    // 自動開啟編輯視窗 (Auto-open edit modal if query param is set)
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    if (editId) {
        showEditModal(parseInt(editId));
    }
});

// ═══════════════════════════════════════════════════════
//  Product List
// ═══════════════════════════════════════════════════════
async function fetchProducts(page = 1) {
    currentProductPage = page;
    const categoryId = document.getElementById('product-category-filter').value;
    const keyword    = document.getElementById('product-search-input').value;
    try {
        const res    = await fetch(`api/admin_product_api?action=get_list&page=${page}&category_id=${categoryId}&search=${encodeURIComponent(keyword)}`);
        const result = await res.json();
        if (result.success) {
            renderProductTable(result.data.products);
            renderProductPagination(result.data.pagination);
        }
    } catch (e) {
        document.getElementById('product-table-body').innerHTML = '<tr><td colspan="8" class="text-center text-danger">載入失敗</td></tr>';
    }
}

function renderProductTable(products) {
    const tbody = document.getElementById('product-table-body');
    tbody.innerHTML = '';
    if (!products || products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">尚無商品資料</td></tr>';
        return;
    }
    products.forEach(p => {
        const tr = document.createElement('tr');
        const price = 'NT$ ' + parseInt(p.price).toLocaleString();
        const catName = p.category_name || '未分類';
        const isActiveChecked = parseInt(p.is_active) ? 'checked' : '';
        const isActiveLabel   = parseInt(p.is_active) ? '上架中' : '已下架';
        const imgUrl      = p.image_main ? p.image_main.replace(/\.([a-zA-Z0-9]+)$/, '_thumb.$1') : 'assets/images/placeholder.webp';
        const largeImgUrl = p.image_main || 'assets/images/placeholder.webp';

        tr.innerHTML = `
            <td>#${p.id}</td>
            <td class="text-center">
                <div class="thumbnail-wrapper" data-large-image="${largeImgUrl}" style="cursor:pointer;display:inline-block;">
                    <img src="${imgUrl}" alt="${p.name}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                </div>
            </td>
            <td>${p.name}</td>
            <td>${catName}</td>
            <td>${price}</td>
            <td>${p.stock ?? '-'}</td>
            <td>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="productStatus${p.id}" ${isActiveChecked} onchange="toggleProductStatus(${p.id})">
                    <label class="form-check-label small" for="productStatus${p.id}">${isActiveLabel}</label>
                </div>
            </td>
            <td>
                <a href="product-detail?id=${p.id}" class="btn btn-action btn-view"><i class="bi bi-eye"></i> 查看</a>
                <button class="btn btn-action btn-edit" onclick="showEditModal(${p.id})"><i class="bi bi-pencil"></i> 編輯</button>
                <button class="btn btn-action btn-delete" onclick="deleteProduct(${p.id})"><i class="bi bi-trash"></i> 刪除</button>
            </td>`;
        tbody.appendChild(tr);
    });
    setupThumbnailHover();
}

function setupThumbnailHover() {
    document.querySelectorAll('.thumbnail-wrapper').forEach(el => {
        el.addEventListener('mouseenter', e => {
            hoverPreviewDiv.innerHTML = `<img src="${e.currentTarget.dataset.largeImage}" style="max-width:300px;max-height:300px;object-fit:contain;border-radius:4px;">`;
            hoverPreviewDiv.style.display = 'block';
        });
        el.addEventListener('mousemove', e => {
            let l = e.clientX + 15, t = e.clientY + 15;
            const r = hoverPreviewDiv.getBoundingClientRect();
            if (l + r.width  > window.innerWidth)  l = e.clientX - r.width  - 15;
            if (t + r.height > window.innerHeight)  t = e.clientY - r.height - 15;
            hoverPreviewDiv.style.left = l + 'px';
            hoverPreviewDiv.style.top  = t + 'px';
        });
        el.addEventListener('mouseleave', () => { hoverPreviewDiv.style.display = 'none'; });
    });
}

function renderProductPagination(pagination) {
    const c = document.getElementById('product-pagination-container');
    c.innerHTML = '';
    if (pagination.total_pages <= 1) return;
    c.innerHTML += `<li class="page-item ${pagination.current_page<=1?'disabled':''}"><a class="page-link" href="javascript:void(0)" onclick="fetchProducts(${pagination.current_page-1})">上一頁</a></li>`;
    for (let i = 1; i <= pagination.total_pages; i++) {
        c.innerHTML += `<li class="page-item ${i===pagination.current_page?'active':''}"><a class="page-link" href="javascript:void(0)" onclick="fetchProducts(${i})">${i}</a></li>`;
    }
    c.innerHTML += `<li class="page-item ${pagination.current_page>=pagination.total_pages?'disabled':''}"><a class="page-link" href="javascript:void(0)" onclick="fetchProducts(${pagination.current_page+1})">下一頁</a></li>`;
}

// ═══════════════════════════════════════════════════════
//  Modal Open
// ═══════════════════════════════════════════════════════
function showAddModal() {
    currentProductId = 0;
    document.getElementById('productModalLabel').textContent = '新增商品';
    productForm.reset();
    document.getElementById('modal_item_id').value = 0;
    document.getElementById('modal_existing_image').value = '';
    document.getElementById('image_preview').style.display = 'none';
    document.getElementById('modal_is_active').checked = true;
    // Gallery: new product notice
    document.getElementById('gallery-edit-notice').style.display = '';
    document.getElementById('gallery-upload-section').style.display = 'none';
    // Blocks
    galleryImages = [];
    blockSections = { detail: [], qa: [], specs: [] };
    productFeatures = [];
    document.getElementById('modal_intro').value = '';
    renderFeatureList();
    SECTIONS.forEach(s => renderBlocks(s));
    // Reset to first tab
    document.querySelector('#productTabs .nav-link.active')?.classList.remove('active');
    document.querySelector('#productTabs .nav-link')?.classList.add('active');
    document.querySelector('#productTabContent .tab-pane.show')?.classList.remove('show','active');
    document.getElementById('tab-basic')?.classList.add('show','active');
    productModal.show();
}

async function showEditModal(productId) {
    currentProductId = productId;
    try {
        const res    = await fetch(`api/admin_product_api?action=get_product&item_id=${productId}`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
        const result = await res.json();
        if (!result.success) { alert('讀取失敗: ' + result.message); return; }
        const d = result.data;

        document.getElementById('productModalLabel').textContent = '編輯商品';
        document.getElementById('modal_item_id').value   = d.id;
        document.getElementById('modal_name').value      = d.name;
        document.getElementById('modal_category_id').value = d.category_id;
        document.getElementById('modal_price').value     = d.price;
        document.getElementById('modal_stock').value     = d.stock;
        document.getElementById('modal_meta_keywords').value = d.meta_keywords || '';
        document.getElementById('modal_is_active').checked   = !!parseInt(d.is_active);
        document.getElementById('modal_existing_image').value = d.image_main || '';

        const preview = document.getElementById('image_preview');
        if (d.image_main) {
            preview.src = d.image_main.replace(/\.([a-zA-Z0-9]+)$/, '_thumb.$1');
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }

        // Gallery
        galleryImages = d.gallery_images || [];
        document.getElementById('gallery-edit-notice').style.display  = 'none';
        document.getElementById('gallery-upload-section').style.display = '';
        renderGalleryThumbs();

        // 解析 description JSON → intro + features + blockSections（向後相容）
        try {
            const raw = d.description ? JSON.parse(d.description) : {};
            if (Array.isArray(raw)) {
                // 舊格式：flat array → detail，intro/features 清空
                document.getElementById('modal_intro').value = '';
                productFeatures = [];
                blockSections = { detail: raw, qa: [], specs: [] };
            } else if (raw && typeof raw === 'object') {
                // 新格式：{ intro, features, detail, qa, specs }
                document.getElementById('modal_intro').value = raw.intro || '';
                productFeatures = Array.isArray(raw.features) ? raw.features : [];
                blockSections = {
                    detail: Array.isArray(raw.detail) ? raw.detail : [],
                    qa:     Array.isArray(raw.qa)     ? raw.qa     : [],
                    specs:  Array.isArray(raw.specs)  ? raw.specs  : []
                };
            } else {
                document.getElementById('modal_intro').value = '';
                productFeatures = [];
                blockSections = { detail: [], qa: [], specs: [] };
            }
        } catch(e) {
            // 純文字：移入 intro
            document.getElementById('modal_intro').value = d.description || '';
            productFeatures = [];
            blockSections = { detail: [], qa: [], specs: [] };
        }
        renderFeatureList();
        SECTIONS.forEach(s => renderBlocks(s));

        productModal.show();
    } catch(e) {
        alert('讀取失敗，請檢查網路。');
    }
}

// ═══════════════════════════════════════════════════════
//  Gallery Management
// ═══════════════════════════════════════════════════════
function renderGalleryThumbs() {
    const container = document.getElementById('gallery-thumbnails');
    container.innerHTML = '';
    galleryImages.forEach(img => {
        const thumbUrl = img.image_url.replace(/\.([a-zA-Z0-9]+)$/, '_thumb.$1');
        const div = document.createElement('div');
        div.className = 'gallery-thumb-item';
        div.dataset.imageId = img.id;
        div.innerHTML = `
            <img src="${thumbUrl}" alt="圖片" title="${img.image_url}">
            <button type="button" class="del-btn" onclick="deleteGalleryImage(${img.id})" title="刪除">✕</button>`;
        container.appendChild(div);
    });
    refreshImagePickerOptions();
}

async function handleGalleryUpload(e) {
    const files = Array.from(e.target.files);
    if (!files.length || currentProductId <= 0) return;
    const progress = document.getElementById('gallery-upload-progress');
    progress.innerHTML = `<div class="text-muted small"><i class="bi bi-hourglass-split"></i> 上傳中 (0/${files.length})...</div>`;
    let done = 0;
    for (const file of files) {
        const fd = new FormData();
        fd.append('action', 'upload_gallery_image');
        fd.append('product_id', currentProductId);
        fd.append('image_file', file);
        try {
            const res    = await fetch('api/admin_product_api', { method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'} });
            const result = await res.json();
            if (result.success) {
                galleryImages.push(result.data);
                done++;
                progress.innerHTML = `<div class="text-muted small"><i class="bi bi-hourglass-split"></i> 上傳中 (${done}/${files.length})...</div>`;
            } else {
                alert('上傳失敗: ' + result.message);
            }
        } catch(err) {
            alert('上傳失敗，請檢查網路。');
        }
    }
    renderGalleryThumbs();
    e.target.value = '';
    progress.innerHTML = `<div class="text-success small"><i class="bi bi-check-circle"></i> 上傳完成 (${done}/${files.length})</div>`;
    setTimeout(() => { progress.innerHTML = ''; }, 3000);
}

async function deleteGalleryImage(imageId) {
    if (!confirm('確定刪除這張附加圖片？')) return;
    try {
        const res    = await fetch(`api/admin_product_api?action=delete_gallery_image&image_id=${imageId}&product_id=${currentProductId}`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
        const result = await res.json();
        if (result.success) {
            galleryImages = galleryImages.filter(i => i.id !== imageId);
            renderGalleryThumbs();
        } else {
            alert('刪除失敗: ' + result.message);
        }
    } catch(e) {
        alert('刪除失敗，請檢查網路。');
    }
}

// ═══════════════════════════════════════════════════════
//  JSON Block Builder  (multi-section)
// ═══════════════════════════════════════════════════════
function addBlock(section, type) {
    if (!blockSections[section]) blockSections[section] = [];
    if (type === 'text')  blockSections[section].push({ type:'text',  content:'' });
    if (type === 'image') blockSections[section].push({ type:'image', src:'', alt:'' });
    renderBlocks(section);
}

function moveBlock(section, index, dir) {
    const arr = blockSections[section];
    const target = index + dir;
    if (target < 0 || target >= arr.length) return;
    [arr[index], arr[target]] = [arr[target], arr[index]];
    renderBlocks(section);
}

function removeBlock(section, index) {
    blockSections[section].splice(index, 1);
    renderBlocks(section);
}

function refreshImagePickerOptions() {
    document.querySelectorAll('.img-src-picker').forEach(sel => {
        const currentVal = sel.value;
        // keep first two options (blank + custom), rebuild gallery options
        while (sel.options.length > 2) sel.remove(2);
        if (galleryImages.length > 0) {
            const grp = document.createElement('optgroup');
            grp.label = '— 從圖集選取 —';
            galleryImages.forEach(img => {
                const opt = document.createElement('option');
                opt.value = img.image_url;
                opt.textContent = img.image_url.split('/').pop();
                grp.appendChild(opt);
            });
            sel.appendChild(grp);
        }
        sel.value = currentVal;
    });
}

function renderBlocks(section) {
    const blocks  = blockSections[section] || [];
    const builder = document.getElementById(`block-builder-${section}`);
    const hint    = document.getElementById(`block-empty-hint-${section}`);
    if (!builder) return;
    builder.innerHTML = '';
    hint.style.display = blocks.length === 0 ? '' : 'none';

    blocks.forEach((blk, idx) => {
        const card = document.createElement('div');
        card.className = `block-card type-${blk.type}`;
        const isFirst = idx === 0, isLast = idx === blocks.length - 1;

        // ── 控制按鈕（右側） ──
        const ctrlBtns = `
            <div class="block-ctrl-btns d-flex gap-1">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveBlock('${section}',${idx},-1)" ${isFirst?'disabled':''} title="上移"><i class="fa fa-arrow-up"></i></button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveBlock('${section}',${idx},1)"  ${isLast?'disabled':''} title="下移"><i class="fa fa-arrow-down"></i></button>
                <button type="button" class="btn btn-outline-danger btn-sm"    onclick="removeBlock('${section}',${idx})" title="刪除"><i class="fa fa-xmark"></i></button>
            </div>`;

        let headerInner = '';
        let body = '';

        if (blk.type === 'text') {
            // ── Rich-text Toolbar ──
            const editorId = `rte-${section}-${idx}`;
            const toolbar = `
                <div class="rte-toolbar" data-editor="${editorId}">
                    <!-- 對齊 -->
                    <button type="button" class="tb-btn" onclick="execFmt('justifyLeft','${editorId}')"    title="置左"><i class="fa fa-align-left"></i></button>
                    <button type="button" class="tb-btn" onclick="execFmt('justifyCenter','${editorId}')"  title="置中"><i class="fa fa-align-center"></i></button>
                    <button type="button" class="tb-btn" onclick="execFmt('justifyRight','${editorId}')"   title="置右"><i class="fa fa-align-right"></i></button>
                    <div class="tb-sep"></div>
                    <!-- 粗體 -->
                    <button type="button" class="tb-btn" onclick="execFmt('bold','${editorId}')"           title="粗體"><i class="fa fa-bold"></i></button>
                    <div class="tb-sep"></div>
                    <!-- 文字大小 -->
                    <select class="tb-select" title="文字大小" onchange="setFontSize(this,'${editorId}')">
                        <option value="">字級</option>
                        <option value="0.8rem">小</option>
                        <option value="1rem">正常</option>
                        <option value="1.2rem">大</option>
                        <option value="1.5rem">特大</option>
                        <option value="2rem">巨大</option>
                    </select>
                    <div class="tb-sep"></div>
                    <!-- 文字顏色 -->
                    <label title="文字顏色" style="display:flex;align-items:center;gap:2px;margin:0;cursor:pointer;">
                        <i class="fa fa-font" style="font-size:0.75rem;"></i>
                        <input type="color" class="tb-color" value="#333333" oninput="setColor(this,'${editorId}')" title="文字顏色">
                    </label>
                    <div class="tb-sep"></div>
                    <!-- 清單 -->
                    <button type="button" class="tb-btn" onclick="execFmt('insertUnorderedList','${editorId}')" title="無序清單"><i class="fa fa-list-ul"></i></button>
                    <button type="button" class="tb-btn" onclick="execFmt('insertOrderedList','${editorId}')"   title="有序清單"><i class="fa fa-list-ol"></i></button>
                    <div class="tb-sep"></div>
                    <!-- 清除格式 -->
                    <button type="button" class="tb-btn" onclick="execFmt('removeFormat','${editorId}')" title="清除格式"><i class="fa fa-eraser"></i></button>
                </div>`;

            headerInner = `
                <span><i class="bi bi-type"></i> 文字區塊 #${idx+1}</span>
                ${toolbar}
                ${ctrlBtns}`;

            body = `<div class="block-body">
                <div id="${editorId}"
                    class="rte-editor"
                    contenteditable="true"
                    data-section="${section}"
                    data-idx="${idx}"
                    data-placeholder="輸入文字內容..."
                    oninput="syncBlockHtml('${section}',${idx},this)"
                >${blk.html || blk.content || ''}</div>
            </div>`;

        } else {
            // ── 圖片區塊 ──
            const escapedSrc = (blk.src||'').replace(/"/g,'&quot;');
            const escapedAlt = (blk.alt||'').replace(/"/g,'&quot;');

            headerInner = `
                <span><i class="bi bi-image"></i> 圖片區塊 #${idx+1}</span>
                <div style="flex:1"></div>
                ${ctrlBtns}`;

            body = `<div class="block-body">
                <div class="mb-2">
                    <label class="form-label small mb-1">圖片來源</label>
                    <select class="form-select form-select-sm rounded-0 img-src-picker mb-1" onchange="onImgPickerChange(this,'${section}',${idx})">
                        <option value="">-- 請選擇或輸入網址 --</option>
                        <option value="__custom__">自訂網址...</option>
                    </select>
                    <input type="text" class="form-control form-control-sm rounded-0 img-src-input" placeholder="圖片網址"
                        value="${escapedSrc}"
                        onchange="blockSections['${section}'][${idx}].src=this.value"
                        oninput="blockSections['${section}'][${idx}].src=this.value">
                </div>
                <div>
                    <label class="form-label small mb-1">替代文字 (alt)</label>
                    <input type="text" class="form-control form-control-sm rounded-0" placeholder="圖片說明"
                        value="${escapedAlt}"
                        onchange="blockSections['${section}'][${idx}].alt=this.value"
                        oninput="blockSections['${section}'][${idx}].alt=this.value">
                </div>
                ${blk.src ? `<img src="${blk.src}" class="mt-2" style="max-height:120px;border:1px solid #ddd;border-radius:4px;">` : ''}
            </div>`;
        }

        card.innerHTML = `<div class="block-header">${headerInner}</div>` + body;
        builder.appendChild(card);
    });

    refreshImagePickerOptions();
}

function onImgPickerChange(sel, section, idx) {
    if (sel.value === '__custom__' || sel.value === '') return;
    blockSections[section][idx].src = sel.value;
    renderBlocks(section);
}

// ═══════════════════════════════════════════════════════
//  Rich Text Editor Helpers
// ═══════════════════════════════════════════════════════

/** 先把焦點還給 contenteditable 再執行指令 */
function execFmt(cmd, editorId) {
    const el = document.getElementById(editorId);
    if (!el) return;
    el.focus();
    document.execCommand(cmd, false, null);
}

/** 設定文字大小（用 fontSize + span 包覆） */
function setFontSize(sel, editorId) {
    const size = sel.value;
    if (!size) return;
    const el = document.getElementById(editorId);
    if (!el) return;
    el.focus();
    // 用 span 包住選取範圍
    const selection = window.getSelection();
    if (selection && selection.rangeCount > 0 && !selection.isCollapsed) {
        const range = selection.getRangeAt(0);
        const span = document.createElement('span');
        span.style.fontSize = size;
        range.surroundContents(span);
    } else {
        // 沒選取 → 套用到整個 editor
        el.style.fontSize = size;
    }
    sel.value = ''; // 重置 dropdown
    syncBlockHtml(el.dataset.section, parseInt(el.dataset.idx), el);
}

/** 設定文字顏色 */
function setColor(input, editorId) {
    const el = document.getElementById(editorId);
    if (!el) return;
    el.focus();
    document.execCommand('foreColor', false, input.value);
    syncBlockHtml(el.dataset.section, parseInt(el.dataset.idx), el);
}

/** 即時同步 innerHTML → blockSections */
function syncBlockHtml(section, idx, el) {
    if (!blockSections[section]) return;
    if (!blockSections[section][idx]) return;
    blockSections[section][idx].html = el.innerHTML;
}

/** 儲存前：掃描所有 rte-editor，確保 innerHTML 最新 */
function syncAllBlocks() {
    document.querySelectorAll('.rte-editor').forEach(el => {
        const section = el.dataset.section;
        const idx     = parseInt(el.dataset.idx);
        if (blockSections[section] && blockSections[section][idx] !== undefined) {
            blockSections[section][idx].html = el.innerHTML;
        }
    });
}

// ═══════════════════════════════════════════════════════
//  Form Submit
// ═══════════════════════════════════════════════════════
async function handleFormSubmit(e) {
    e.preventDefault();
    // 儲存前先同步所有 RTE editor 的 innerHTML
    syncAllBlocks();
    // Serialize intro + features + blockSections → description JSON
    // 結構: { intro: "", features:[...], detail:[...], qa:[...], specs:[...] }
    const intro = document.getElementById('modal_intro').value;
    // 收集 features：從 DOM 讀取最新值
    const featureInputs = document.querySelectorAll('#feature-list-container .feature-input');
    const features = Array.from(featureInputs).map(i => i.value.trim()).filter(v => v !== '');
    document.getElementById('modal_description').value = JSON.stringify({
        intro,
        features,
        ...blockSections
    });

    const formData = new FormData(productForm);
    const id = parseInt(document.getElementById('modal_item_id').value);
    if (id > 0) formData.set('item_id', id);

    try {
        const res    = await fetch('api/admin_product_api', { method:'POST', body:formData, headers:{'X-Requested-With':'XMLHttpRequest'} });
        const result = await res.json();
        if (result.success) {
            alert(result.message);
            productModal.hide();
            fetchProducts(currentProductPage);
        } else {
            alert('儲存失敗: ' + result.message);
        }
    } catch(err) {
        alert('儲存失敗，請檢查網路。');
    }
}

// ═══════════════════════════════════════════════════════
//  Feature List
// ═══════════════════════════════════════════════════════
function renderFeatureList() {
    const container = document.getElementById('feature-list-container');
    container.innerHTML = '';
    productFeatures.forEach((feat, idx) => {
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2';
        row.innerHTML = `
            <span class="text-success fw-bold" style="font-size:1.1rem;">✓</span>
            <input type="text" class="form-control form-control-sm rounded-0 feature-input"
                placeholder="輸入特色描述..."
                value="${feat.replace(/"/g, '&quot;')}"
                oninput="productFeatures[${idx}]=this.value">
            <button type="button" class="btn btn-outline-danger btn-sm rounded-0" onclick="removeFeatureItem(${idx})" title="移除">
                <i class="bi bi-x"></i>
            </button>`;
        container.appendChild(row);
    });
}

function addFeatureItem(value = '') {
    productFeatures.push(value);
    renderFeatureList();
    // 聚焦到新加的 input
    const inputs = document.querySelectorAll('#feature-list-container .feature-input');
    if (inputs.length > 0) inputs[inputs.length - 1].focus();
}

function removeFeatureItem(idx) {
    productFeatures.splice(idx, 1);
    renderFeatureList();
}

// ═══════════════════════════════════════════════════════
//  Delete & Toggle
// ═══════════════════════════════════════════════════════
async function deleteProduct(productId) {
    if (!confirm('確定要刪除此商品？此動作無法復原。')) return;
    try {
        const res    = await fetch(`api/admin_product_api?action=delete_product&item_id=${productId}`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
        const result = await res.json();
        if (result.success) { alert(result.message); fetchProducts(currentProductPage); }
        else alert('刪除失敗: ' + result.message);
    } catch(e) { alert('刪除失敗，請檢查網路。'); }
}

async function toggleProductStatus(productId) {
    try {
        const res    = await fetch(`api/admin_product_api?action=toggle_status&item_id=${productId}`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
        const result = await res.json();
        if (result.success) {
            const checkbox = document.getElementById(`productStatus${productId}`);
            const label    = document.querySelector(`label[for="productStatus${productId}"]`);
            if (label) label.textContent = checkbox.checked ? '上架中' : '已下架';
        } else {
            alert('更新失敗: ' + result.message);
            location.reload();
        }
    } catch(e) { alert('更新失敗，請檢查網路。'); location.reload(); }
}
</script>

<?php require_once 'view/admin_layout/footer.php'; ?>
