<?php
session_start();
require_once 'db.php';

// 檢查登入狀態
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// 處理表單提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $display_name = $_POST['display_name'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE user_profiles SET display_name = ?, bio = ?, phone = ? WHERE user_id = ?");
        $stmt->execute([$display_name, $bio, $phone, $user_id]);
        $success_msg = '個人資料更新成功！';
    } catch (PDOException $e) {
        $error_msg = '更新失敗：' . $e->getMessage();
    }
}

// 獲取使用者資料
try {
    $stmt = $pdo->prepare("SELECT u.username, u.email, u.role, p.* FROM users u LEFT JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("讀取資料失敗: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>編輯個人資料 - 香氛蠟燭企業</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- 自訂 CSS -->
    <link rel="stylesheet" href="common.css">
    <!-- Cropper.js CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <style>
        body {
            background-color: #fafafa;
        }

        .profile-header {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }

        .section-card {
            background: white;
            border-radius: 0;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #d8b09a;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #d77244ff;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px solid #fcb891ff;
        }

        .form-label {
            font-weight: 600;
            color: #a0522d;
        }

        .form-control {
            border-radius: 0;
            border-color: #d8b09a;
        }

        .form-control:focus {
            border-color: #fcb891ff;
            box-shadow: 0 0 0 0.25rem rgba(252, 184, 145, 0.25);
        }

        .btn-save {
            background-color: #fcb891ff;
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 0;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-save:hover {
            background-color: #c17d4a;
            color: white;
            transform: translateY(-2px);
        }

        .readonly-value {
            background-color: #f8f5f2;
            padding: 10px;
            border: 1px solid #e8d5c8;
            color: #666;
        }

        /* Cropper 樣式 */
        .avatar-upload-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .avatar-preview-wrapper {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border: 2px solid #fcb891ff;
            overflow: hidden;
            background-color: #f8f5f2;
        }

        .avatar-preview-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-container {
            max-height: 400px;
            margin-bottom: 20px;
        }

        .img-container img {
            max-width: 100%;
        }
    </style>
</head>

<body>
    <!-- 導航欄 -->
    <?php include 'header.php'; ?>

    <!-- 標題 -->
    <div class="profile-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">編輯個人資料</h1>
            <p class="lead">更新您的基本資訊與聯繫方式</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <?php if ($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_msg): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form id="profileForm" method="POST">
                    <!-- 頭像上傳區塊 -->
                    <div class="section-card">
                        <h2 class="section-title"><i class="bi bi-camera me-2"></i>個人頭像</h2>
                        <div class="avatar-upload-container">
                            <div class="avatar-preview-wrapper">
                                <img id="currentAvatar" src="<?php echo htmlspecialchars($user['avatar'] ?: 'https://via.placeholder.com/150'); ?>" alt="目前頭像">
                            </div>
                            <div class="mb-3">
                                <label for="avatarInput" class="btn btn-outline-primary rounded-0">
                                    <i class="bi bi-upload me-2"></i>選擇圖片
                                </label>
                                <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                                <p class="text-muted small mt-2">支援 JPG、PNG、WebP，大小限制 2MB，將自動剪裁為 1:1 比例</p>
                            </div>
                        </div>
                    </div>

                    <!-- 基本帳號資訊 (唯讀) -->
                    <div class="section-card">
                        <h2 class="section-title"><i class="bi bi-person-lock me-2"></i>帳號資訊</h2>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">使用者名稱</label>
                                <div class="readonly-value"><?php echo htmlspecialchars($user['username']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">電子郵件</label>
                                <div class="readonly-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">目前等級</label>
                                <div class="readonly-value">
                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                    <?php echo htmlspecialchars($user['member_level'] ?? '一般會員'); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">帳號消費總額</label>
                                <div class="readonly-value">NT$ <?php echo number_format($user['total_spending'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- 可編輯資料 -->
                    <div class="section-card">
                        <h2 class="section-title"><i class="bi bi-pencil-square me-2"></i>詳細資料</h2>
                        
                        <div class="mb-3">
                            <label for="display_name" class="form-label">顯示名稱</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" 
                                   value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>" placeholder="例：王小明">
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">聯絡電話</label>
                            <input type="text" class="form-control" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="例：0912-345-678">
                        </div>


                        <div class="mb-3">
                            <label for="bio" class="form-label">個人簡介</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4" 
                                      placeholder="簡單介紹一下您自己..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">剩餘積分</label>
                            <div class="readonly-value"><?php echo number_format($user['points'] ?? 0); ?> 點</div>
                        </div>
                    </div>

                    <div class="text-center mb-5">
                        <?php
                            $back_url = 'member.php';
                            if ($user['role'] === 'author') $back_url = 'author-dashboard.php';
                            elseif ($user['role'] === 'admin') $back_url = 'admin.php';
                        ?>
                        <a href="<?php echo $back_url; ?>" class="btn btn-outline-secondary px-5 py-2 me-3 rounded-0">返回</a>
                        <button type="submit" class="btn btn-save">儲存變更</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Cropper.js JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

    <!-- 剪裁 Modal -->
    <div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-0">
                <div class="modal-header">
                    <h5 class="modal-title">剪裁頭像 (1:1)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="img-container">
                        <img id="imageToCrop" src="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-0" data-bs-dismiss="modal">取消</button>
                    <button type="button" class="btn btn-save" id="cropButton">剪裁並上傳</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cropper;
        const avatarInput = document.getElementById('avatarInput');
        const imageToCrop = document.getElementById('imageToCrop');
        const cropModal = new bootstrap.Modal(document.getElementById('cropModal'));
        const cropButton = document.getElementById('cropButton');
        const currentAvatar = document.getElementById('currentAvatar');

        avatarInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                
                // 不再硬性攔截 2MB，改為後續自動壓縮
                const reader = new FileReader();
                reader.onload = function (e) {
                    imageToCrop.src = e.target.result;
                    cropModal.show();
                };
                reader.readAsDataURL(file);
            }
        });

        document.getElementById('cropModal').addEventListener('shown.bs.modal', function () {
            cropper = new Cropper(imageToCrop, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
            });
        });

        document.getElementById('cropModal').addEventListener('hidden.bs.modal', function () {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            avatarInput.value = '';
        });

        cropButton.addEventListener('click', function () {
            if (!cropper) return;

            // 獲取剪裁後的圖片資料，並嘗試壓縮至 2MB 以下
            let quality = 0.85;
            let base64Data;
            const MAX_SIZE = 2 * 1024 * 1024; // 2MB

            const canvas = cropper.getCroppedCanvas({
                width: 800, // 提升一點解析度
                height: 800,
                imageSmoothingQuality: 'high'
            });

            // 循環降低品質直到符合大小限制 (雖然 800x800 的 JPEG 通常不超過 200KB)
            do {
                base64Data = canvas.toDataURL('image/jpeg', quality);
                // Base64 長度換算成位元組約為長度 * 0.75
                const estimatedSize = base64Data.length * 0.75;
                if (estimatedSize <= MAX_SIZE || quality <= 0.1) break;
                quality -= 0.1;
            } while (true);

            // 停用按鈕顯示上傳中
            cropButton.disabled = true;
            cropButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>上傳中...';

            fetch('api/upload-avatar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ image: base64Data })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentAvatar.src = data.avatar_url + '?t=' + new Date().getTime();
                    cropModal.hide();
                    alert('頭像更新成功！');
                } else {
                    alert('上傳失敗：' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('上傳發生錯誤，請稍後再試。');
            })
            .finally(() => {
                cropButton.disabled = false;
                cropButton.innerHTML = '剪裁並上傳';
            });
        });
    </script>
</body>

</html>
