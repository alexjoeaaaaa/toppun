<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>忘記密碼 - 作者系統</title>
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

        .forgot-container {
            max-width: 500px;
            width: 100%;
            padding: 20px;
        }

        .forgot-card {
            background: white;
            border-radius: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 40px;
            border: 1px solid #d8b09a;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .forgot-header h1 {
            color: #d77244ff;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .forgot-header p {
            color: #a0522d;
            font-size: 0.95rem;
        }

        .forgot-icon {
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

        .forgot-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 0;
            background-color: #e8d5c8;
            color: #a0522d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 10px;
            border: 2px solid #d8b09a;
        }

        .step.active {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            border-color: #fcb891ff;
        }

        .step.completed {
            background-color: #4caf50;
            color: white;
            border-color: #4caf50;
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
        }

        .form-control:focus {
            border-color: #fcb891ff;
            box-shadow: 0 0 0 2px #e8d5c8;
        }

        .btn-submit {
            background: linear-gradient(135deg, #fcb891ff 0%, #efa77eff 100%);
            color: white;
            border: none;
            border-radius: 0;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #efa77eff 0%, #d88a5e 100%);
            color: white;
        }

        .btn-back {
            background-color: #e89b6f;
            color: white;
            border: 1px solid #e89b6f;
            border-radius: 0;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }

        .btn-back:hover {
            background-color: #d88a5e;
            border-color: #d88a5e;
            color: white;
        }

        .success-message {
            background-color: #d4e8d4;
            border: 1px solid #8db88d;
            border-radius: 0;
            padding: 20px;
            text-align: center;
            color: #2d5f2d;
        }

        .success-message i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .resend-link {
            text-align: center;
            margin-top: 15px;
            color: #a0522d;
            font-size: 0.9rem;
        }

        .resend-link a {
            color: #d77244ff;
            text-decoration: none;
            font-weight: 600;
        }

        .resend-link a:hover {
            color: #fcb891ff;
            text-decoration: underline;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
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
            <div class="forgot-header">
                <div class="forgot-icon">
                    <i class="bi bi-key"></i>
                </div>
                <h1>忘記密碼</h1>
                <p>請輸入您的電子郵件,我們將發送重設密碼連結</p>
            </div>

            <!-- 步驟指示器 -->
            <div class="step-indicator">
                <div class="step active" id="step1Indicator">1</div>
                <div class="step" id="step2Indicator">2</div>
            </div>

            <!-- 步驟 1: 輸入郵件 -->
            <div class="step-content active" id="step1">
                <form id="forgotForm" onsubmit="handleSubmit(event)">
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="bi bi-envelope"></i> 電子郵件
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                            placeholder="請輸入註冊時使用的電子郵件" required>
                    </div>

                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-send"></i> 發送重設連結
                    </button>

                    <button type="button" class="btn btn-back" onclick="window.location.href='author-login'">
                        <i class="bi bi-arrow-left"></i> 返回登入
                    </button>
                </form>
            </div>

            <!-- 步驟 2: 確認訊息 -->
            <div class="step-content" id="step2">
                <div class="success-message">
                    <i class="bi bi-check-circle-fill"></i>
                    <h4>郵件已發送!</h4>
                    <p class="mb-0">我們已將密碼重設連結發送至您的電子郵件。<br>請檢查您的收件匣並點擊連結以重設密碼。</p>
                </div>

                <div class="resend-link">
                    沒有收到郵件? <a href="#" onclick="resendEmail(event)">重新發送</a>
                </div>

                <button type="button" class="btn btn-back mt-3" onclick="window.location.href='author-login'">
                    <i class="bi bi-arrow-left"></i> 返回登入
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        function handleSubmit(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value;

            // 這裡應該要有實際的郵件發送邏輯
            // 目前只是示範,直接顯示成功訊息
            
            if (email) {
                // 切換到步驟 2
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
                
                // 更新步驟指示器
                document.getElementById('step1Indicator').classList.remove('active');
                document.getElementById('step1Indicator').classList.add('completed');
                document.getElementById('step2Indicator').classList.add('active');
            }
        }

        function resendEmail(event) {
            event.preventDefault();
            alert('重設連結已重新發送至您的電子郵件!');
        }
    </script>
</body>

</html>
