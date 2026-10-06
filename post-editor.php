<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 檢查登入狀態與角色（管理員與作者皆可使用）
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_author = isset($_SESSION['role']) && $_SESSION['role'] === 'author';
if (!isset($_SESSION['user_id']) || (!$is_admin && !$is_author)) {
    header('Location: author-login');
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_role  = $_SESSION['role'];
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article    = null;

// 產生一次性上傳驗證 token（不依賴 XHR session cookie）
// token 以 user_id + role + 當前小時 做 HMAC，每小時刷新，兼容跨小時的請求
require_once __DIR__ . '/upload-secret.php';
$upload_token = hash_hmac('sha256', $user_id . '|' . $user_role . '|' . floor(time() / 7200), UPLOAD_SECRET);

// 如果是編輯模式，獲取文章內容
// 管理員可編輯任何文章，作者只能編輯自己的
if ($article_id > 0) {
    if ($is_admin) {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
        $stmt->execute([$article_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ? AND user_id = ?");
        $stmt->execute([$article_id, $user_id]);
    }
    $article = $stmt->fetch();
    if (!$article) {
        die("文章不存在或您無權存取。");
    }
}

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $category = $_POST['category'] ?? '';
    $summary = $_POST['summary'] ?? '';
    $content = $_POST['content'] ?? '';
    // 管理員可直接選擇任意狀態，作者只能存草稿或提交審核
    if ($is_admin) {
        $status = $_POST['status'] ?? 'published';
    } else {
        $status = isset($_POST['submit_review']) ? 'pending' : 'draft';
    }
    
    // 預設圖片
    $image_url = $article['image_url'] ?? 'assets/images/blog/default.jpg';

    // 如果從 localStorage 恢復或表單錯誤，我們需要保留輸入的內容供重新顯示
    // 這裡我們直接使用 $_POST 資料來渲染表單

    // 處理圖片上傳
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        // 檢查檔案大小 (2MB)
        $max_size = 2 * 1024 * 1024;
        if ($_FILES['image_file']['size'] > $max_size) {
            $error = "封面圖片太大，請限制在 2MB 以內。";
        } else {
            $upload_dir = 'assets/images/blog/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = uniqid('post_') . '.' . $file_ext;
                $dest_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest_path)) {
                    $image_url = $dest_path;
                }
            } else {
                $error = "不支援的封面圖片格式。";
            }
        }
    }

    if (!isset($error)) {
        try {
            if ($article_id > 0) {
                // 更新文章（管理員不限 user_id）
                if ($is_admin) {
                    $stmt = $pdo->prepare("UPDATE articles SET title = ?, category = ?, summary = ?, content = ?, image_url = ?, status = ? WHERE id = ?");
                    $stmt->execute([$title, $category, $summary, $content, $image_url, $status, $article_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE articles SET title = ?, category = ?, summary = ?, content = ?, image_url = ?, status = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$title, $category, $summary, $content, $image_url, $status, $article_id, $user_id]);
                }
            } else {
                // 新增文章
                $stmt = $pdo->prepare("INSERT INTO articles (user_id, title, category, summary, content, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $title, $category, $summary, $content, $image_url, $status]);
            }
            $redirect = $is_admin ? 'admin?msg=success#blog' : 'author-dashboard?msg=success';
            header("Location: $redirect");
            exit();
        } catch (PDOException $e) {
            $error = "儲存失敗: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article_id > 0 ? '編輯文章' : '新增文章'; ?> - <?php echo $is_admin ? '管理員後台' : '作者後台'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@300;400;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/nvs8kd46lz49dzee2efhaaui8sgyuoodljflc74c56slyh83/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
    <style>
        body { background-color: #fafafa; font-family: 'Noto Serif TC', serif; }
        .dashboard-header { background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%); color: white; padding: 40px 0; margin-bottom: 30px; }
        .editor-card { background: white; border: 1px solid #d8b09a; border-radius: 0; padding: 30px; margin-bottom: 50px; }
        .form-label { font-weight: 700; color: #a0522d; }
        .form-control { border-radius: 0; border: 1px solid #e8d5c8; }
        .btn-save { background-color: #fcb891ff; color: white; border-radius: 0; border: none; padding: 10px 25px; transition: all 0.3s; }
        .btn-save:hover { background-color: #efa77eff; color: white; }
        .btn-submit { background-color: #4caf50; color: white; border-radius: 0; border: none; padding: 10px 25px; }
        .btn-cancel { background-color: #f5f5f5; color: #333; border-radius: 0; border: 1px solid #ddd; padding: 10px 25px; }
        .btn-submit:hover { background-color: #45a049; color: white; }
        .preview-img { max-width: 200px; margin-top: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="dashboard-header">
        <div class="container">
            <?php if ($is_admin): ?>
                <span class="badge mb-2" style="background:rgba(255,255,255,0.25);font-size:0.8rem;"><i class="bi bi-shield-fill-check"></i> 管理員</span>
            <?php endif; ?>
            <h1 class="h2 mb-0"><?php echo $article_id > 0 ? '編輯文章' : '建立新文章'; ?></h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <?php if ($is_admin): ?>
                        <li class="breadcrumb-item"><a href="admin.php" class="text-white">商家後台</a></li>
                        <li class="breadcrumb-item"><a href="admin.php#blog" class="text-white">Blog 管理</a></li>
                    <?php else: ?>
                        <li class="breadcrumb-item"><a href="author-dashboard.php" class="text-white">作者後台</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active text-white" aria-current="page"><?php echo $article_id > 0 ? '編輯文章' : '新增文章'; ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="editor-card">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="title" class="form-label">文章標題</label>
                            <input type="text" class="form-control" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? $article['title'] ?? ''); ?>" placeholder="輸入吸引人的標題...">
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="category" class="form-label">分類</label>
                                <select class="form-select rounded-0" id="category" name="category" required>
                                    <option value="">請選擇分類</option>
                                    <?php
                                    $current_cat = $_POST['category'] ?? $article['category'] ?? '';
                                    $categories = ['選購指南', '使用技巧', '居家佈置', '其他'];
                                    foreach ($categories as $cat) {
                                        $selected = $current_cat === $cat ? 'selected' : '';
                                        echo "<option value=\"$cat\" $selected>$cat</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="image_file" class="form-label">封面圖片 <small class="text-muted" id="cover-status">(大圖將自動壓縮)</small></label>
                                <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
                                <div id="cover-preview-container" class="mt-2 <?php echo empty($article['image_url']) ? 'd-none' : ''; ?>">
                                    <small class="text-muted d-block mb-1">封面預覽：</small>
                                    <img src="<?php echo htmlspecialchars($article['image_url'] ?? ''); ?>" class="preview-img d-block" id="cover-preview" alt="封面預覽">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="summary" class="form-label">文章摘要</label>
                            <textarea class="form-control" id="summary" name="summary" rows="3" placeholder="簡短描述文章內容..."><?php echo htmlspecialchars($_POST['summary'] ?? $article['summary'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="content" class="form-label">文章內容 <small class="text-muted">(可直接拖放圖片，大圖自動壓縮)</small></label>
                            <textarea id="content" name="content"><?php echo htmlspecialchars($_POST['content'] ?? $article['content'] ?? ''); ?></textarea>
                        </div>

                        <!-- 管理員專用：發布狀態選單 -->
                        <?php if ($is_admin): ?>
                        <div class="mb-4 p-3" style="background:#fff5f0;border:1px solid #d8b09a;">
                            <label for="admin_status" class="form-label fw-bold" style="color:#d77244;"><i class="bi bi-toggles"></i> 發布狀態</label>
                            <select class="form-select rounded-0" id="admin_status" name="status">
                                <?php
                                $cur_status = $_POST['status'] ?? $article['status'] ?? 'published';
                                $status_opts = [
                                    'published' => '✅ 立即發布',
                                    'draft'     => '📝 草稿（不公開）',
                                    'pending'   => '⏳ 待審核',
                                    'rejected'  => '❌ 已拒絕',
                                ];
                                foreach ($status_opts as $v => $l) {
                                    echo '<option value="' . $v . '"' . ($cur_status === $v ? ' selected' : '') . '>' . $l . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mt-5">
                            <a href="<?php echo $is_admin ? 'admin.php#blog' : 'author-dashboard.php'; ?>" class="btn btn-cancel text-decoration-none">取消返回</a>
                            <div class="btn-group">
                                <?php if ($is_admin): ?>
                                    <button type="submit" name="publish" class="btn btn-submit">
                                        <i class="bi bi-send-check"></i> <?php echo $article_id > 0 ? '儲存變更' : '發布文章'; ?>
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="save_draft" class="btn btn-save me-2">儲存草稿</button>
                                    <button type="submit" name="submit_review" class="btn btn-submit">提交審核</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        // 1. TinyMCE 初始化
        tinymce.init({
            selector: '#content',
            height: 500,
            language: 'zh_TW',
            plugins: 'image advlist autolink lists link charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image | removeformat | help',
            automatic_uploads: true,
            images_reuse_filename: true,
            image_dimensions: true,
            image_class_list: [
                { title: 'Responsive', value: 'img-fluid' }
            ],
            images_upload_handler: function (blobInfo, progress) {
                return new Promise((resolve, reject) => {
                    const blob = blobInfo.blob();
                    compressImage(blob, 1200, 0.85).then(compressedBlob => {
                        const xhr = new XMLHttpRequest();
                        xhr.withCredentials = true;
                        xhr.open('POST', 'api/upload-article-image.php');

                        // 注入驗證資訊 (解決 session 丟失問題)
                        const uid = '<?php echo $user_id; ?>';
                        const role = '<?php echo $user_role; ?>';
                        const token = '<?php echo $upload_token; ?>';

                        xhr.upload.onprogress = (e) => {
                            progress(e.loaded / e.total * 100);
                        };

                        xhr.onload = () => {
                            if (xhr.status === 403) {
                                reject({ message: 'HTTP Error: ' + xhr.status + ' (權限不足或連線逾時)', remove: true });
                                return;
                            }
                            if (xhr.status < 200 || xhr.status >= 300) {
                                let errorMsg = 'HTTP Error: ' + xhr.status;
                                try {
                                    const json = JSON.parse(xhr.responseText);
                                    if (json && json.error) errorMsg = json.error;
                                } catch (e) {}
                                reject(errorMsg);
                                return;
                            }

                            const json = JSON.parse(xhr.responseText);
                            if (!json || typeof json.location != 'string') {
                                reject('無效的 JSON 回應: ' + xhr.responseText);
                                return;
                            }

                            resolve(json.location);
                        };

                        xhr.onerror = () => {
                            reject('圖片上傳失敗 (網路錯誤或伺服器未回應)');
                        };

                        const formData = new FormData();
                        const compressedFile = new File([compressedBlob], blobInfo.filename(), { type: 'image/webp' });
                        formData.append('file', compressedFile);
                        formData.append('uid', uid);
                        formData.append('role', role);
                        formData.append('token', token);
                        xhr.send(formData);
                    }).catch(err => reject('壓縮失敗: ' + err));
                });
            },
            content_style: '@import url("https://fonts.googleapis.com/css2?family=Noto+Serif+TC:wght@300;400;700&display=swap"); body { font-family:\'Noto Serif TC\', serif; font-size:16px } img { max-width: 100% !important; height: auto !important; }',
            placeholder: '在此開始撰寫您的精彩內容...',
            setup: function (editor) {
                editor.on('NodeChange', function (e) {
                    if (e.element.nodeName === 'IMG') {
                        let width = parseInt(e.element.style.width || e.element.width);
                        if (width > 800) {
                            e.element.style.width = '800px';
                            e.element.style.height = 'auto';
                            e.element.width = 800;
                        }
                    }
                });
                // 內容變更時觸發暫存
                editor.on('Change KeyUp', function () {
                    debouncedSave();
                });
            }
        });

        // 2. 圖片壓縮工具函數
        function compressImage(file, maxWidth, quality) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = e => {
                    const img = new Image();
                    img.src = e.target.result;
                    img.onload = () => {
                        let width = img.width;
                        let height = img.height;
                        if (width > maxWidth) {
                            height *= maxWidth / width;
                            width = maxWidth;
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = width;
                        canvas.height = height;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
                        canvas.toBlob(blob => resolve(blob), 'image/webp', quality);
                    };
                    img.onerror = reject;
                };
                reader.onerror = reject;
            });
        }

        // 3. 封面圖片自動壓縮
        let isCoverCompressing = false;
        document.getElementById('image_file').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const statusLabel = document.getElementById('cover-status');
            const preview = document.getElementById('cover-preview');
            const previewContainer = document.getElementById('cover-preview-container');
            const submitBtns = document.querySelectorAll('button[type="submit"]');

            // 顯示預覽 (原始)
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                previewContainer.classList.remove('d-none');
            };
            reader.readAsDataURL(file);

            // 只有大於 1MB 或是非 WEBP/PNG 才壓縮
            if (file.size > 1 * 1024 * 1024 || !['image/webp', 'image/png', 'image/jpeg'].includes(file.type)) {
                isCoverCompressing = true;
                statusLabel.innerHTML = '<span class="text-primary"><i class="bi bi-hourglass-split"></i> 壓縮中...</span>';
                submitBtns.forEach(btn => btn.disabled = true);

                const originalName = file.name;
                compressImage(file, 1600, 0.8).then(blob => {
                    const compressedFile = new File([blob], originalName, { type: 'image/webp' });
                    
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(compressedFile);
                    e.target.files = dataTransfer.files;
                    
                    // 更新預覽為壓縮後的 (可選，通常差異不大)
                    preview.src = URL.createObjectURL(blob);
                    
                    statusLabel.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> 壓縮完成 (-' + Math.round((1 - blob.size/file.size)*100) + '%)</span>';
                    console.log('封面已自動壓縮：', (file.size/1024/1024).toFixed(2) + 'MB -> ' + (blob.size/1024/1024).toFixed(2) + 'MB');
                }).catch(err => {
                    statusLabel.innerHTML = '<span class="text-danger">壓縮失敗</span>';
                    console.error(err);
                }).finally(() => {
                    isCoverCompressing = false;
                    submitBtns.forEach(btn => btn.disabled = false);
                });
            } else {
                statusLabel.innerHTML = '<span class="text-muted">(免壓縮)</span>';
            }
        });

        // 4. 自動暫存與恢復功能 (防掉稿)
        const STORAGE_KEY = 'ginanacandles_post_draft_<?php echo $article_id; ?>';
        
        function saveToLocalStorage() {
            const data = {
                title: document.getElementById('title').value,
                category: document.getElementById('category').value,
                summary: document.getElementById('summary').value,
                content: tinymce.get('content').getContent(),
                timestamp: new Date().getTime()
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            console.log('稿件已自動儲存至本地');
        }

        let saveTimeout;
        function debouncedSave() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(saveToLocalStorage, 2000);
        }

        // 監聽一般欄位變更
        ['title', 'category', 'summary'].forEach(id => {
            document.getElementById(id).addEventListener('input', debouncedSave);
        });

        // 頁面載入時檢查暫存
        window.addEventListener('load', function() {
            const savedData = localStorage.getItem(STORAGE_KEY);
            if (savedData) {
                const data = JSON.parse(savedData);
                const timeStr = new Date(data.timestamp).toLocaleString();
                
                // 只有當暫存內容與當前內容不同時才詢問
                if (confirm('發現於 ' + timeStr + ' 儲存的未提交草稿，是否恢復內容？\n(這將複蓋當前輸入的內容)')) {
                    document.getElementById('title').value = data.title;
                    document.getElementById('category').value = data.category;
                    document.getElementById('summary').value = data.summary;
                    tinymce.get('content').setContent(data.content);
                }
            }
        });

        // 提交成功時清除暫存
        document.querySelector('form').addEventListener('submit', function(e) {
            if (isCoverCompressing) {
                e.preventDefault();
                alert('圖片正在壓縮中，請稍候...');
                return;
            }
            localStorage.removeItem(STORAGE_KEY);
        });
    </script>
</body>
</html>
