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
    <title>關於品牌 - TopPun香．芬</title>
    <link rel="canonical" href="https://mcandle.fxinspire.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- 自訂 CSS -->
    <link rel="stylesheet" href="common.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('headimages/pexels-serenakoi-5801528.jpg');
            background-size: cover;
            background-position: center;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .about-section { padding: 80px 0; }
        .section-title { font-weight: 700; margin-bottom: 40px; position: relative; padding-bottom: 15px; }
        .section-title::after { content: ''; position: absolute; left: 0; bottom: 0; width: 50px; height: 3px; background-color: #333; }
        .section-title.text-center::after { left: 50%; transform: translateX(-50%); }

        /* Timeline Milestone */
        .timeline { position: relative; padding: 40px 0; }
        .timeline::before { content: ''; position: absolute; left: 50%; top: 0; width: 2px; height: 100%; background: #eee; transform: translateX(-50%); }
        .timeline-item { margin-bottom: 60px; position: relative; width: 100%; }
        .timeline-content { width: 45%; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .timeline-item:nth-child(odd) .timeline-content { margin-left: auto; }
        .timeline-dot { position: absolute; left: 50%; top: 20px; width: 20px; height: 20px; background: #333; border-radius: 50%; transform: translateX(-50%); z-index: 1; }

        /* Certificate Cards */
        .cert-card { border: none; transition: transform 0.3s; }
        .cert-card:hover { transform: translateY(-10px); }
        .cert-img { height: 250px; object-fit: contain; background: #f8f9fa; padding: 20px; }

        @media (max-width: 768px) {
            .timeline::before { left: 20px; }
            .timeline-content { width: calc(100% - 50px); margin-left: 50px !important; }
            .timeline-dot { left: 20px; }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'header.php'; ?>

    <section class="hero-section">
        <div class="container">
            <h1 class="display-3 fw-bold">TopPun香．芬</h1>
            <p class="lead">光影交織出的溫暖時刻，開啟生活的香氛之旅</p>
        </div>
    </section>

    <!-- Company Info -->
    <section class="about-section" id="brand-story">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="section-title">品牌故事</h2>
                    <p class="lead text-muted">我們相信，每一盞亮起的燭光，都是對生活的一種溫柔致敬。</p>
                    <p>TopPun香．芬成立於2010年，源自於對自然與居家美學的熱愛。我們專注於研發最高品質的天然香氛產品，將瑞典的簡約生活哲學融入每一款產品設計中。</p>
                    <p>我們堅持選用 100% 天然環保大豆蠟與純淨植物精油，每一款香氛都經過上百次的調研與測試，確保能為您的空間帶來最舒適、層次豐富的嗅覺體驗。</p>
                </div>
                <div class="col-lg-6">
                    <img src="headimages/pexels-alesiakozik-7795826.jpg" class="img-fluid rounded shadow" alt="品牌故事">
                </div>
            </div>
        </div>
    </section>

    <!-- Milestones -->
    <section class="about-section bg-light" id="milestones">
        <div class="container">
            <h2 class="section-title text-center">精彩里程碑</h2>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4 class="fw-bold">2010</h4>
                        <h5>品牌創立</h5>
                        <p>在台創立 GINANA CANDLES，首批「北歐森林系列」大豆蠟燭正式發表，強調天然環保無煙燒香。</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4 class="fw-bold">2015</h4>
                        <h5>引進香氛暖燈</h5>
                        <p>首度引進專利感應式香氛暖燈，開創「無火香氛」新趨勢，成為台灣暖燈領導品牌。</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4 class="fw-bold">2020</h4>
                        <h5>進駐國際百貨</h5>
                        <p>於台北信義區開設首間品牌概念旗艦店，並獲得國際知名精品百貨邀請進駐，邁向國際化經營。</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4 class="fw-bold">2024</h4>
                        <h5>永續發展計畫</h5>
                        <p>全面啟動「Green GINANA」計畫，包裝全數更換為 FSC 認證回收紙材，實現品牌永續承諾。</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Certificates -->
    <section class="about-section" id="certificates">
        <div class="container">
            <h2 class="section-title text-center">品質鑑定與認證</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card cert-card h-100 text-center">
                        <div class="cert-img d-flex align-items-center justify-content-center">
                            <i class="bi bi-patch-check text-primary display-1"></i>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold">SGS 天然認證</h5>
                            <p class="small text-muted">通過重金屬與塑化劑檢驗，確保燃燒過程中不產生有害物質。</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card cert-card h-100 text-center">
                        <div class="cert-img d-flex align-items-center justify-content-center">
                            <i class="bi bi-recycle text-success display-1"></i>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold">FSC 永續包裝</h5>
                            <p class="small text-muted">所有包裝紙材均來自受控管之永續林場，致力減少地球負擔。</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card cert-card h-100 text-center">
                        <div class="cert-img d-flex align-items-center justify-content-center">
                            <i class="bi bi-award text-warning display-1"></i>
                        </div>
                        <div class="card-body">
                            <h5 class="fw-bold">IFRA 國際標準</h5>
                            <p class="small text-muted">所有香精原料均符合國際香料協會標準，安全且純淨。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Vision -->
    <section class="about-section bg-dark text-white text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="section-title text-center text-white" style="border-bottom:none;">我們的願景</h2>
                    <p class="fs-4 italic">「讓香氛走進每個人的生活，成為日常中最溫暖的陪伴。」</p>
                    <hr class="w-25 mx-auto my-4 border-white opacity-50">
                    <p class="opacity-75">我們致力於推廣高品質的居家香氛，不只是產品，更是一種生活態度的傳遞。</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
