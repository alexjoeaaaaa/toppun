<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? 'index.php';

    if ($email && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: " . $redirect);
                exit;
            } else {
                $error = '電子郵件或密碼錯誤';
            }
        } catch (PDOException $e) {
            $error = '登入時發生錯誤，請稍後再試';
        }
    } else {
        $error = '請輸入電子郵件和密碼';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>會員登入 - TopPun香．芬</title>
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
            margin-bottom: 40px;
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

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background-color: #e9ecef;
        }

        .divider span {
            background-color: white;
            padding: 0 15px;
            position: relative;
            color: #999;
        }

        .social-login {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }

        .btn-social {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: 2px solid #e9ecef;
            background: white;
            transition: all 0.3s;
            font-weight: 600;
        }

        .btn-social:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-google {
            color: #db4437;
        }

        .btn-facebook {
            color: #4267B2;
        }

        .btn-line {
            color: #00B900;
        }

        .forgot-password {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .forgot-password:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .register-link {
            text-align: center;
            margin-top: 30px;
            color: #666;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .register-link a:hover {
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
                        <i class="bi bi-shop"></i>
                    </div>
                    <h2>歡迎回來!</h2>
                    <p>登入您的帳號,探索更多專屬會員優惠與精選香氛商品</p>
                    <div class="mt-4">
                        <i class="bi bi-star-fill me-2"></i>
                        <i class="bi bi-star-fill me-2"></i>
                        <i class="bi bi-star-fill me-2"></i>
                        <i class="bi bi-star-fill me-2"></i>
                        <i class="bi bi-star-fill"></i>
                    </div>
                </div>

                <!-- 右側登入表單 -->
                <div class="col-md-7 login-right">
                    <div class="text-end mb-3">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                            <i class="bi bi-house"></i> 返回首頁
                        </a>
                    </div>

                    <h1 class="login-title">會員登入</h1>
                    <p class="login-subtitle">請輸入您的帳號密碼</p>

                    <!-- 登入表單 -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <form action="login" method="POST">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">電子郵件</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="請輸入您的電子郵件" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">密碼</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="請輸入您的密碼" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe">
                                <label class="form-check-label" for="rememberMe">
                                    記住我
                                </label>
                            </div>
                            <a href="forgot-password.php" class="forgot-password">忘記密碼?</a>
                        </div>

                        <button type="submit" class="btn btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>登入
                        </button>
                    </form>

                    <div class="divider">
                        <span>或使用以下方式登入</span>
                    </div>

                    <!-- 社群登入 -->
                    <div class="social-login">
                        <button class="btn btn-social btn-google">
                            <i class="bi bi-google me-2"></i>Google
                        </button>
                        <button class="btn btn-social btn-facebook">
                            <i class="bi bi-facebook me-2"></i>Facebook
                        </button>
                        <button class="btn btn-social btn-line">
                            <i class="bi bi-line me-2"></i>LINE
                        </button>
                    </div>

                    <div class="register-link">
                        還不是會員? <a href="register.php">立即註冊</a>
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
