<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>聯絡我們 - TopPun香．芬</title>
    <link rel="canonical" href="https://mcandle.fxinspire.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- 自訂 CSS -->
    <link rel="stylesheet" href="common.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('headimages/pexels-serenakoi-5801528.jpg');
            background-size: cover;
            background-position: center;
            height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .contact-section { padding: 80px 0; }
        
        .contact-info-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            height: 100%;
        }

        .icon-box {
            width: 60px;
            height: 60px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }

        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(0,0,0,0.05);
            border-color: #333;
        }

        .btn-submit {
            background-color: #333;
            color: white;
            padding: 12px 30px;
            border-radius: 8px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background-color: #000;
            color: white;
            transform: translateY(-2px);
        }

        .map-wrapper {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            height: 450px;
        }

        iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'header.php'; ?>

    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold">聯絡我們</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb justify-content-center">
                    <li class="breadcrumb-item"><a href="index.php" class="text-white text-decoration-none">首頁</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">聯絡我們</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Contact Info Cards -->
    <section class="contact-section pb-0">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <div class="col-md-4">
                    <div class="card contact-info-card p-4 text-center">
                        <div class="icon-box mx-auto">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <h4 class="fw-bold">門市地址</h4>
                        <p class="text-muted">710 台南市永康區正南三路75號</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card contact-info-card p-4 text-center">
                        <div class="icon-box mx-auto">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <h4 class="fw-bold">電話資訊</h4>
                        <p class="text-muted mb-0">TEL: 06-254-3888</p>
                        <p class="text-muted">FAX: 06-243-0586</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card contact-info-card p-4 text-center">
                        <div class="icon-box mx-auto">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <h4 class="fw-bold">電子郵件</h4>
                        <p class="text-muted">service@ginanacandles.com</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form & Map -->
    <section class="contact-section">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-6">
                    <h2 class="fw-bold mb-4">給我們留言</h2>
                    <p class="text-muted mb-5">如果您有任何問題或建議，歡迎填寫下方的表單與我們聯繫。我們的工作人員將在 24 小時內回覆您。</p>
                    
                    <form action="#" method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">您的姓名</label>
                            <input type="text" class="form-control" name="name" required placeholder="請輸入姓名">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">電子郵件</label>
                            <input type="email" class="form-control" name="email" required placeholder="請輸入 Email">
                        </div>
                        <div class="col-12">
                            <label class="form-label">主旨</label>
                            <input type="text" class="form-control" name="subject" required placeholder="請輸入主旨">
                        </div>
                        <div class="col-12">
                            <label class="form-label">訊息內容</label>
                            <textarea class="form-control" name="message" rows="5" required placeholder="您的問題或建議..."></textarea>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-submit w-100">發送訊息</button>
                        </div>
                    </form>
                </div>
                <div class="col-lg-6">
                    <div class="map-wrapper">
                        <!-- Google Map Embed Placeholder -->
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3671.916045873911!2d120.2366!3d23.0274!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x346e771092e0df7b%3A0xc66699af36fbc9e7!2zNzEw5Y-w5Y2X5biC5rC45bq35Y2A5q2j5Y2X5LiJ6LevNzXomZ8!5e0!3m2!1szh-TW!2stw!4v1700000000000!5m2!1szh-TW!2stw" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
