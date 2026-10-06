<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username && $email && $password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error = '兩次輸入的密碼不一致';
        } elseif (strlen($password) < 6) {
            $error = '密碼長度至少需要 6 個字元';
        } else {
            try {
                // 檢查電子郵件或使用者名稱是否已存在
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);
                if ($stmt->fetch()) {
                    $error = '此電子郵件或使用者名稱已被註冊';
                } else {
                    // 雜湊密碼
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // 插入新使用者
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'member')");
                    if ($stmt->execute([$username, $email, $hashed_password])) {
                        $success = '註冊成功！即將跳轉至登入頁面...';
                        header("refresh:3;url=login.php");
                    } else {
                        $error = '註冊失敗，請稍後再試';
                    }
                }
            } catch (PDOException $e) {
                $error = '發生資料庫錯誤，請稍後再試';
            }
        }
    } else {
        $error = '請填寫所有必填欄位';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>會員註冊 - 香氛蠟燭企業</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- 自訂 CSS -->
    <link rel="stylesheet" href="common.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }

        .login-left {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-left h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .login-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .login-icon {
            font-size: 5rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }

        .login-right {
            padding: 60px 50px;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: #666;
            margin-bottom: 30px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .login-link {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-left {
                display: none;
            }

            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&family=Noto+Serif+TC:wght@400;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- 左側裝飾區 -->
                <div class="col-md-5 login-left">
                    <div class="login-icon">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h2>加入我們!</h2>
                    <p>建立您的帳號,享受個人化的購物體驗與會員專屬優惠</p>
                    <div class="mt-4">
                        <i class="bi bi-heart-fill me-2"></i>
                        <i class="bi bi-heart-fill me-2"></i>
                        <i class="bi bi-heart-fill me-2"></i>
                        <i class="bi bi-heart-fill me-2"></i>
                        <i class="bi bi-heart-fill"></i>
                    </div>
                </div>

                <!-- 右側註冊表單 -->
                <div class="col-md-7 login-right">
                    <div class="text-end mb-3">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                            <i class="bi bi-house"></i> 返回首頁
                        </a>
                    </div>

                    <h1 class="login-title">會員註冊</h1>
                    <p class="login-subtitle">請填寫以下資訊完成註冊</p>

                    <!-- 訊息顯示 -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form action="register" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label fw-bold">使用者名稱</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="請輸入您的顯示名稱" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">電子郵件</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="這將作為您的登入帳號" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">密碼</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="至少 6 個字元" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-bold">確認密碼</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                    placeholder="請再次輸入密碼" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-register">
                            <i class="bi bi-person-check me-2"></i>立即註冊
                        </button>
                    </form>

                    <div class="login-link">
                        已經有帳號了? <a href="login.php">登入會員</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</body>

</html>
