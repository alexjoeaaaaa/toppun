<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

// 如果已經登入且是 admin，直接跳轉
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: admin');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header("Location: admin");
                exit;
            } else {
                $error = '電子郵件或密碼錯誤，或權限不足';
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
    <title>商家登入 - TopPun香．芬</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Noto Serif TC', serif;
        }

        .login-container {
            max-width: 450px;
            width: 100%;
            padding: 20px;
        }

        .login-card {
            background: white;
            border-radius: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            border: 1px solid #d8b09a;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #d77244ff;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #a0522d;
            font-size: 0.95rem;
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            border-radius: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            border: 2px solid #d8b09a;
        }

        .login-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .form-label {
            color: #a0522d;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #d8b09a;
            border-radius: 0;
            padding: 12px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #fcb891ff;
            box-shadow: 0 0 0 2px #e8d5c8;
        }

        .btn-login {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            border: none;
            border-radius: 0;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #efa77eff 0%, #d88a5e 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(232, 155, 111, 0.4);
        }

        .btn-home {
            background-color: #e89b6f;
            color: white;
            border: 1px solid #e89b6f;
            border-radius: 0;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }

        .btn-home:hover {
            background-color: #d88a5e;
            border-color: #d88a5e;
            color: white;
        }

        .forgot-password {
            text-align: center;
            margin-top: 15px;
        }

        .forgot-password a {
            color: #d77244ff;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password a:hover {
            color: #fcb891ff;
            text-decoration: underline;
        }

        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background-color: #e8d5c8;
        }

        .divider span {
            background-color: white;
            padding: 0 15px;
            position: relative;
            color: #a0522d;
            font-size: 0.9rem;
        }

        .form-check-input {
            border: 1px solid #d8b09a;
            border-radius: 0;
        }

        .form-check-input:checked {
            background-color: #fcb891ff;
            border-color: #fcb891ff;
        }

        .form-check-label {
            color: #666;
            font-size: 0.9rem;
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
            <div class="login-header">
                <div class="login-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h1>商家登入</h1>
                <p>歡迎回來!請登入您的商家帳號</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2" role="alert" style="border-radius: 0; font-size: 0.9rem;">
                    <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="admin-login">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="bi bi-envelope"></i> 電子郵件
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                        placeholder="請輸入電子郵件" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock"></i> 密碼
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                        placeholder="請輸入密碼" required>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">
                        記住我
                    </label>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> 登入
                </button>

                <div class="forgot-password">
                    <a href="admin-forgot-password">
                        <i class="bi bi-question-circle"></i> 忘記密碼?
                    </a>
                </div>

                <div class="divider">
                    <span>或</span>
                </div>

                <button type="button" class="btn btn-home" onclick="window.location.href='index'">
                    <i class="bi bi-house"></i> 返回首頁
                </button>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <!-- 移除舊有前端登入邏輯 -->
</body>

</html>
