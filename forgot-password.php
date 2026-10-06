<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>忘記密碼 - TopPun香．芬</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- 自訂 CSS -->
    <link rel="stylesheet" href="common.css">
    <style>
        .forgot-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 40px 20px;
        }

        .forgot-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }

        .forgot-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .forgot-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .forgot-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .forgot-header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .forgot-body {
            padding: 50px 40px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .step {
            display: flex;
            align-items: center;
            color: #ccc;
        }

        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 10px;
        }

        .step.active .step-number {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .step.active .step-text {
            color: #333;
            font-weight: 600;
        }

        .step-divider {
            width: 60px;
            height: 2px;
            background-color: #e9ecef;
            margin: 0 15px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #f093fb;
            box-shadow: 0 0 0 0.2rem rgba(240, 147, 251, 0.25);
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

        .btn-submit {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
            color: white;
        }

        .info-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .info-box i {
            color: #ffc107;
            margin-right: 10px;
        }

        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .success-box i {
            color: #28a745;
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: #f093fb;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link a:hover {
            color: #f5576c;
            text-decoration: underline;
        }

        .hidden {
            display: none;
        }
    </style>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&family=Noto+Serif+TC:wght@400;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <!-- 頁面標題 -->
            <div class="forgot-header">
                <div class="forgot-icon">
                    <i class="bi bi-key"></i>
                </div>
                <h1>忘記密碼</h1>
                <p>別擔心,我們會協助您重設密碼</p>
            </div>

            <div class="forgot-body">
                <!-- 步驟指示器 -->
                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">
                        <div class="step-number">1</div>
                        <div class="step-text">輸入信箱</div>
                    </div>
                    <div class="step-divider"></div>
                    <div class="step" id="step2-indicator">
                        <div class="step-number">2</div>
                        <div class="step-text">確認信件</div>
                    </div>
                </div>

                <!-- 步驟 1: 輸入電子郵件 -->
                <div id="step1-form">
                    <div class="info-box">
                        <i class="bi bi-info-circle-fill"></i>
                        <strong>提示:</strong> 請輸入您註冊時使用的電子郵件地址,我們將發送密碼重設連結至您的信箱。
                    </div>

                    <form id="forgotPasswordForm">
                        <div class="mb-4">
                            <label for="email" class="form-label fw-bold">電子郵件</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="請輸入您的電子郵件" required>
                            </div>
                            <div class="form-text">請確認您輸入的電子郵件地址正確無誤</div>
                        </div>

                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-send me-2"></i>發送重設連結
                        </button>
                    </form>
                </div>

                <!-- 步驟 2: 成功訊息 (預設隱藏) -->
                <div id="step2-success" class="hidden">
                    <div class="success-box">
                        <div>
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <h4 class="fw-bold mb-3">郵件已發送!</h4>
                        <p class="mb-0">
                            我們已將密碼重設連結發送至您的電子郵件信箱。<br>
                            請檢查您的收件匣(包含垃圾郵件資料夾),並點擊連結完成密碼重設。
                        </p>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-clock me-2"></i>
                        <strong>注意:</strong> 重設連結將在 24 小時後失效,請盡快完成密碼重設。
                    </div>

                    <div class="text-center">
                        <p class="text-muted mb-3">沒有收到郵件?</p>
                        <button class="btn btn-outline-secondary rounded-pill" onclick="resendEmail()">
                            <i class="bi bi-arrow-clockwise me-2"></i>重新發送郵件
                        </button>
                    </div>
                </div>

                <!-- 返回登入連結 -->
                <div class="back-link">
                    <i class="bi bi-arrow-left me-2"></i>
                    <a href="login.php">返回登入頁面</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        // 處理表單提交
        document.getElementById('forgotPasswordForm').addEventListener('submit', function (e) {
            e.preventDefault();

            // 模擬發送郵件 (實際應用中應該發送 AJAX 請求到後端)
            const email = document.getElementById('email').value;

            // 顯示載入狀態
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>發送中...';
            submitBtn.disabled = true;

            // 模擬 API 請求延遲
            setTimeout(function () {
                // 隱藏步驟 1
                document.getElementById('step1-form').classList.add('hidden');

                // 顯示步驟 2
                document.getElementById('step2-success').classList.remove('hidden');

                // 更新步驟指示器
                document.getElementById('step1-indicator').classList.remove('active');
                document.getElementById('step2-indicator').classList.add('active');

                // 重置按鈕
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });

        // 重新發送郵件
        function resendEmail() {
            alert('重設連結已重新發送至您的電子郵件信箱!');
        }
    </script>
</body>

</html>
