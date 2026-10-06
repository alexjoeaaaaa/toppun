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
        <title> Toppun香‧芬 100%天然大豆蠟燭・香氛暖燈| 台南香氛蠟燭推薦 |</title>         
        <meta discription content="Toppun香‧芬 - 台南在地香氛蠟燭品牌，堅持100%天然大豆蠟與精油。瑞典森林系列、香氛暖燈組，居家放鬆首選。台南永康旗艦店實體體驗。"> 
        <link rel="canonical" href="https://mcandle.fxinspire.com<?php echo htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'); ?>"/> 
        <!-- Bootstrap CSS -->         
        <link href="bootstrap_theme/bootstrap.css" rel="stylesheet" type="text/css"> 
        <!-- 自訂 CSS -->         
        <link rel="stylesheet" href="common.css"> 
        <style>/* Mission Section Styles */.mission-section { background-image: url('headimages/pexels-yaroslav-shuraev-6811708.jpg'); background-attachment: fixed; background-size: cover; background-position: center; padding: 120px 0; position: relative; color: white; text-align: center; } .mission-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6);  /* Darker overlay for readability */ z-index: 1; } .mission-content { position: relative; z-index: 2; max-width: 800px; margin: 0 auto; } .mission-title { font-size: 2.5rem; font-weight: 700; margin-bottom: 1.5rem; letter-spacing: 2px; } .mission-text { font-size: 1.2rem; line-height: 1.8; font-weight: 300; } /* Series Spotlight Styles */.series-spotlight { padding: 0; background-color: #fff; } .series-spotlight-intro { background-color: #F3EAE3; padding: 80px 0 50px; } .series-warmer-block { background-color: #ddd0c2; padding: 0; color: #2b241e; } .series-row { display: flex; align-items: center; margin-bottom: 60px; } .series-row:last-child { margin-bottom: 0; } .series-img { width: 100%; height: 400px; object-fit: cover; } .series-content { padding: 40px; } .series-title { font-family: 'Noto Serif TC', serif;  /* Use serif for storytelling feel if available */ font-size: 2rem; font-weight: 700; margin-bottom: 20px; color: #333; } .series-title-flourish { display: inline-flex; align-items: center; gap: 14px; } .series-title-flourish::after { content: ''; width: 72px; height: 1px; background-color: #8f7174; } .series-desc { color: #666; line-height: 1.8; font-size: 1.05rem; } .series-overlay-card { position: relative; height: 480px; overflow: hidden; } .series-overlay-card img { width: 100%; height: 100%; object-fit: cover; display: block; } .series-overlay-card::after { content: ''; position: absolute; inset: 0; background: rgba(92, 66, 60, 0.5); } .series-overlay-content { position: absolute; inset: 0; z-index: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 40px; } .series-overlay-content .series-title { color: #fff; } .series-overlay-content .series-desc { color: rgba(255, 255, 255, 0.92); max-width: 28rem; margin-bottom: 0; } .series-sale-tag { position: absolute; top: 0; left: 0; z-index: 2; background-color: #04724D; color: #fff; font-size: 1.15rem; font-weight: 700; letter-spacing: 0.12em; padding: 0.7rem 1.25rem; line-height: 1.2; } /* Location Section Styles */.location-section { background-color: #f8f9fa; padding: 80px 0; } .info-box h3 { font-weight: 700; margin-bottom: 20px; color: #333; } .info-list { list-style: none; padding: 0; margin-bottom: 30px; } .info-list li { margin-bottom: 15px; font-size: 1.1rem; color: #555; display: flex; align-items: flex-start; } .info-icon { margin-right: 15px; color: #d63384; min-width: 25px; } .map-container { width: 100%; height: 100%; min-height: 400px; overflow: hidden; } .map-container iframe { width: 100%; height: 100%; border: 0; } /* Interactive Distributor Items */.distributor-item { cursor: pointer; transition: background-color 0.2s; padding: 10px; } .distributor-item:hover { background-color: #e9ecef; } .distributor-item.active { background-color: #e2e6ea; border-left: 4px solid #d66433; } .smallh { font-size: 1rem; white-space: normal;  /* 允許自動換行 */ display: inline-block;  /* 或 display: inline; */ margin-top: 1rem; margin-bottom: 2rem; } h2 { margin-top: 2rem; } .home-title-block { background-color: #FFF7F1; background-image: url('headimages/paper-texture.jpg'); background-repeat: repeat; background-size: 900px auto; background-blend-mode: multiply; padding-top: 5.5rem; padding-bottom: 5.5rem; } .home-title-block h1, 
        .home-title-block h2 { font-family: 'Noto Serif TC', serif; font-weight: 700; color: #333; }</style>         
        <!-- Google Fonts -->         
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@300;400;700&family=Noto+Serif+TC:wght@400;700&display=swap" rel="stylesheet"> 
    </head>     
    <body> 
        <!-- 導航欄 -->         
        <?php include 'header.php'; ?> 
        <!-- 大圖輪播 (Carousel) -->         
        <div id="carouselExampleIndicators" class="carousel slide" data-bs-ride="carousel"> 
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2" class="active" aria-current="true"></button>
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3" class=""></button>
            </div>
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="1" aria-label="Slide 2" class="active" aria-current="true"></button>
                <button type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide-to="2" aria-label="Slide 3" class=""></button>
            </div>             
            <div class="carousel-inner"> 
                <div class="carousel-item active">
                    <img src="headimages/table-with-white-candles-plant-with-green-plant-background.jpg" class="d-block w-100" alt="Toppun香‧芬純白大豆蠟燭，在自然光與白花之間安靜燃燒">
                    <div class="carousel-hero-copy" style="position: absolute; top: 48%; left: 28%; transform: translate(-50%, -50%); width: max-content; white-space: nowrap; color: white; text-align: center;">
                        <p><strong>純白大豆蠟燭</strong>為家留一盞安靜的光。</p>
                        <p>天然蠟材無黑煙，<strong>香氣回到日常</strong></p>
                    </div>
                </div>                 
                <div class="carousel-item">
                    <img src="headimages/pexels-yaroslav-shuraev-6811708.jpg" class="d-block w-100" alt="大圖2">
                   <div class="carousel-hero-copy" style="position: absolute; top: 50%; left: 30%; transform: translate(-50%, -50%); width: max-content; white-space: nowrap; color: white; text-align: center;">
                        <p><strong>Toppun香.芬</strong>給你最療育的平靜空間。</p>
                        <p>療育香氛，<strong>喚醒內在平靜</strong></p>
                    </div>
                </div>                 
                <div class="carousel-item">
                    <img src="headimages/candle-near-book-cup-with-toy-snowflake-wood-board-near-heap-snow-through-window.jpg" class="d-block w-100" alt="Toppun香‧芬大豆蠟燭陪著攤開的書與熱飲，適合冬日居家慢讀">
                    <div class="carousel-hero-copy" style="position: absolute; top: 20%; left: 22%; transform: translate(-50%, -50%); width: max-content; white-space: nowrap; color: white; text-align: center;">
                        <p><strong>冬夜慢讀</strong>讓大豆蠟燭陪一本書。</p>
                        <p>熱飲還有餘溫，<strong>香氣已先坐下</strong></p>
                    </div>
                </div>                 
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>             
        </div>
                <!-- 標題+副標題 -->         
        <section class="home-title-block"> 
            <div class="container text-center"> 
                <h1>Toppun香‧芬-天然大豆香氛蠟燭推薦・送禮首選</h1> 
                <h2>點亮您的生活，散發自然芬芳-香氛蠟燭推薦使用</h2> 
            </div>             
        </section>           
        <!-- 香氛產品介紹 -->         
        <section class="product-intro"> 
            <div class="container-fluid p-0"> 
                <div class="row g-0"> 
                    <!-- Card 1 -->                     
                    <div class="col-md-6 col-lg-4"> 
                        <div class="card product-card"> 
                            <div class="card-img-wrapper">
                                <img src="headimages/cozy-home-composition-with-candles-blurred-background.jpg" class="card-img-top" alt="香氛蠟燭系列，點燃的大豆蠟燭" style="border-radius: 0;">
                            </div>                             
                            <div class="card-body text-center"> 
                                <h5 class="card-title">經典大豆蠟燭</h5> 
                                <p class="card-text">  <h2 class="smallh">使用100%天然大豆蠟製作，搭配純棉燭芯，燃燒純淨無黑煙。</h2></p> 
                                <div class="d-grid gap-2 d-sm-block">
                                    <a href="/全部分類/香氛蠟燭系列" class="btn btn-primary">查看詳情</a> 
                                </div>                                 
                            </div>                             
                        </div>                         
                    </div>                     
                    <!-- Card 2 -->                     
                    <div class="col-md-6 col-lg-4"> 
                        <div class="card product-card"> 
                            <div class="card-img-wrapper">
                                <img src="headimages/pexels-alesiakozik-7795826.jpg" class="card-img-top" alt="香氛暖燈組" style="border-radius: 0;">
                            </div>                             
                            <div class="card-body text-center"> 
                                <h5 class="card-title">時尚香氛暖燈組</h5> 
                                <p class="card-text">  <h2 class="smallh">無需點火即可享受香氛，安全又環保。</h2></p> 
                                <div class="d-grid gap-2 d-sm-block">
                                    <a href="/全部分類/香氛暖燈" class="btn btn-primary">查看詳情</a> 
                                </div>                                 
                            </div>                             
                        </div>                         
                    </div>                     
                    <!-- Card 3 -->                     
                    <div class="col-md-6 col-lg-4"> 
                        <div class="card product-card"> 
                            <div class="card-img-wrapper">
                                <img src="headimages/spa-composition-with-incense-sticks-air-humidifier-aroma-oils.jpg" class="card-img-top" alt="居家香氛，擴香機、藤枝擴香與精油" style="border-radius: 0;">
                            </div>                             
                            <div class="card-body text-center"> 
                                <h5 class="card-title">森林系居家擴香</h5> 
                                <p class="card-text">  <h2 class="smallh">萃取天然植物精華，持久散發清新自然的森林氣息。</h2></p> 
                                <div class="d-grid gap-2 d-sm-block">
                                    <a href="/全部分類/居家香氛" class="btn btn-primary">查看詳情</a> 
                                </div>                                 
                            </div>                             
                        </div>                         
                    </div>                     
                </div>                 
            </div>             
        </section>         
       
        <!-- 引言 (公司歷程)+配圖 -->         
        <section class="intro-section half-split"> 
            <div class="half-split-copy"> 
                <div class="half-split-copy-inner"> 
                    <h2 class="series-title series-title-flourish"><span>公司歷程-15年堅持天然香氛工藝</span></h2> 
                    <p>我們成立於2010年，專注於天然香氛蠟燭的研發與生產。從小作坊起步，到如今擁有完整生產線，我們始終堅持使用上等大豆蠟和天然精油，為客戶帶來舒適的居家體驗。
 </p> 
                </div>                     
            </div>                 
            <div class="half-split-media">
                <img src="headimages/store-with-display-products-it.jpg" alt="公司配圖"> 
            </div>                 
        </section>         
        <!-- Company Mission Section -->         
        <section class="mission-section"> 
            <div class="mission-overlay"></div>             
            <div class="container mission-content"> 
                <h2 class="mission-title">我們的宗旨</h2> 
                <p class="mission-text"> 
                堅持使用100%天然原料，以匠心工藝打造每一款香氛。我們相信，香氣不僅是嗅覺的享受，更是生活質感的體現。
 <br>致力於為您創造放鬆、舒適的居家空間，讓每一次的呼吸，都成為一種療癒。
 </p> 
            </div>             
        </section>         
        <!-- Series Spotlight Section -->         
        <section class="series-spotlight"> 
            <div class="series-spotlight-intro"> 
                <div class="container"> 
                    <div class="text-center"> 
                        <h2 class="fw-bold">精選香氛系列 – 大豆蠟燭・香氛暖燈・森林擴香</h2> 
                        <p class="text-muted">探索我們最受歡迎的香氛故事</p> 
                    </div>                     
                </div>                 
            </div>             
            <div class="container-fluid p-0"> 
                <div class="row g-0 series-split"> 
                    <div class="col-lg-6"> 
                        <div class="series-overlay-card"> 
                            <img src="headimages/pexels-ekaterina-bolovtsova-6311975.jpg" alt="瑞典森林系列"> 
                            <span class="series-sale-tag">打折</span> 
                            <div class="series-overlay-content"> 
                                <h3 class="series-title">瑞典森林系列</h3> 
                                <p class="series-desc"> 
                                靈感源自廣袤的瑞典森林，融合冷杉、松木與大地氣息。讓您即使在都市中，也能呼吸到大自然的純淨與寧靜。
 </p> 
                                <a href="/全部分類" class="btn btn-outline-light rounded-0 mt-3">探索更多</a> 
                            </div>                             
                        </div>                         
                    </div>                     
                    <div class="col-lg-6"> 
                        <div class="series-overlay-card"> 
                            <img src="headimages/cozy-home-composition-with-candles-blurred-background.jpg" alt="香氛蠟燭系列"> 
                            <span class="series-sale-tag">打折</span> 
                            <div class="series-overlay-content"> 
                                <h3 class="series-title">經典大豆系列</h3> 
                                <p class="series-desc"> 
                                採用100%天然環保大豆蠟，搭配精選天然精油。無煙無毒的燃燒過程，是妝點居家生活品質的最佳選擇。
 </p> 
                                <a href="/全部分類/香氛蠟燭系列" class="btn btn-outline-light rounded-0 mt-3">探索更多</a> 
                            </div>                             
                        </div>                         
                    </div>                     
                </div>                 
            </div>             
            <div class="series-warmer-block half-split half-split--media-left"> 
                <div class="half-split-media"> 
                    <img src="headimages/pexels-serenakoi-5801528.jpg" alt="香氛暖燈系列"> 
                </div>                     
                <div class="half-split-copy"> 
                    <div class="half-split-copy-inner"> 
                        <h3 class="series-title series-title-flourish"><span>香氛暖燈系列</span></h3> 
                        <p class="series-desc"> 
                            安全、優雅且實用的香氛體驗。無需明火即可透過熱能散發香氣，同時也能作為居家裝飾的點睛之筆。
 </p> 
                        <a href="/全部分類/香氛暖燈" class="btn btn-outline-dark rounded-0 mt-3">探索更多</a> 
                    </div>                         
                </div>                     
            </div>             
        </section>         
        <!-- Product Categories -->         
        <section class="category-section"> 
            <div class="container-fluid p-0"> 
                <div class="row g-0"> 
                    <?php
                try {
                    $categories = $pdo->query("SELECT * FROM product_categories ORDER BY id ASC LIMIT 4")->fetchAll();
                    // 這裡先自訂一些背景圖，實際環境可能會有 category_image 欄位
                    $bg_images = [
                        '香氛蠟燭系列' => 'headimages/cozy-home-composition-with-candles-blurred-background.jpg',
                        '香氛暖燈' => 'headimages/pexels-alesiakozik-7795826.jpg',
                        '個人香氛' => 'headimages/cozy-autumnal-composition-with-burning-candles-pumpkins-blurred-background.jpg',
                        '居家香氛' => 'headimages/spa-composition-with-incense-sticks-air-humidifier-aroma-oils.jpg',
                    ];
                    $fallback_bg = reset($bg_images);
                    foreach ($categories as $cat) {
                        $bg = $bg_images[$cat['name']] ?? $fallback_bg;
                        echo '
                        <div class="col-md-6 col-lg-3">
                            <a href="/%E5%85%A8%E9%83%A8%E5%88%86%E9%A1%9E/' . rawurlencode($cat['name']) . '" class="category-card">
                                <div class="category-bg" style="background-image: url(\'' . $bg . '\');"></div>
                                <div class="category-overlay"></div>
                                <h3 class="category-title">' . htmlspecialchars($cat['name']) . '</h3>
                            </a>
                        </div>';
                    }
                } catch (PDOException $e) {
                    echo '<div class="col-12 text-center py-3">無法載入分類</div>';
                }
                ?> 
                </div>                 
            </div>             
        </section>         
        <!-- Sales Channels & Map Section -->         
        <section class="location-section" id="contact"> 
            <div class="container"> 
                <div class="row align-items-center"> 
                    <div class="col-lg-6 mb-4 mb-lg-0"> 
                        <div class="info-box pe-lg-5"> 
                            <h3 class="mb-4">銷售據點 & 聯絡我們</h3> 
                            <p class="mb-4 text-muted">歡迎親臨我們的實體店面體驗香氛，或透過以下方式聯繫我們。</p> 
                            <ul class="info-list"> 
                                <li> 
                                    <span class="info-icon">📍</span> 
                                    <div> 
                                        <h2><strong>旗艦店地址</strong>-台南永康旗艦店</h2>
                                        <br> 
                                        710 台南市永康區正南三路75號
                                    </div>                                     
                                </li>                                 
                                <li> 
                                    <span class="info-icon">🕒</span> 
                                    <div> 
                                        <strong>營業時間</strong>
                                        <br> 
                                        週一至週五：09:00 - 18:00<br> 
                                        週六、週日：10:00 - 19:00 (國定假日另行公告)
                                    </div>                                     
                                </li>                                 
                                <li> 
                                    <span class="info-icon">📞</span> 
                                    <div> 
                                        <strong>聯絡電話</strong>
                                        <br> 
                                        06-254-3888
                                    </div>                                     
                                </li>                                 
                            </ul>                             
                            <h5 class="mt-5 mb-3 fw-bold">合作經銷通路 (點擊查看地圖)</h5> 
                            <div class="distributor-list"> 
                                <div class="distributor-item border-bottom pb-2 mb-2 active" data-lat="23.0438" data-lon="120.2461"> 
                                    <strong class="d-block text-dark">GINANA 旗艦店</strong> 
                                    <small class="text-muted">710 台南市永康區正南三路75號</small> 
                                </div>                                 
                                <div class="distributor-item border-bottom pb-2 mb-2" data-lat="25.0523" data-lon="121.5204"> 
                                    <strong class="d-block text-dark">誠品生活南西 (Eslite)</strong> 
                                    <small class="text-muted">台北市中山區南京西路14號 4F</small> 
                                </div>                                 
                                <div class="distributor-item border-bottom pb-2 mb-2" data-lat="25.0355" data-lon="121.5673"> 
                                    <strong class="d-block text-dark">新光三越 台北信義新天地 A11</strong> 
                                    <small class="text-muted">台北市信義區松壽路11號 2F</small> 
                                </div>                                 
                                <div class="distributor-item border-bottom pb-2 mb-2" data-lat="24.1633" data-lon="120.6415"> 
                                    <strong class="d-block text-dark">TSUTAYA BOOKSTORE 台中市政店</strong> 
                                    <small class="text-muted">台中市西屯區市政北二路18之1號 3F</small> 
                                </div>                                 
                                <div class="distributor-item border-bottom pb-2 mb-2" data-lat="25.0531" data-lon="121.5222"> 
                                    <strong class="d-block text-dark">瑪黑家居 (Marais) 台北中山</strong> 
                                    <small class="text-muted">台北市中山區中山北路二段36巷18號</small> 
                                </div>                                 
                            </div>                             
                        </div>                         
                    </div>                     
                    <div class="col-lg-6"> 
                        
                        <div class="map-container shadow-sm p-2 bg-white"> 
                            <iframe id="osm-map" width="100%" height="450" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="https://www.openstreetmap.org/export/embed.html?bbox=120.2393,23.0381,120.2529,23.0496&amp;layer=mapnik&amp;marker=23.0438,120.2461" style="border: 0"></iframe>                             
                            <br/>
                            <small><a id="osm-large-link" href="https://www.openstreetmap.org/?mlat=23.0438&amp;mlon=120.2461#map=16/23.0438/120.2461" target="_blank">查看更大的地圖</a></small> 
                        </div>                         
                    </div>                     
                </div>                 
            </div>             
        </section>         
        <!-- Footer -->         
        <?php include 'footer.php'; ?> 
        <!-- jQuery -->         
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>         
        <!-- Bootstrap JS -->         
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>         
        <script>
        function updateMap(lat, lon, element) {
            document.querySelectorAll('.distributor-item').forEach(item => {
                item.classList.remove('active');
            });
            if (element) {
                element.classList.add('active');
            }
            const bboxSize = 0.01;
            const minLon = lon - bboxSize;
            const minLat = lat - bboxSize;
            const maxLon = lon + bboxSize;
            const maxLat = lat + bboxSize;
            const embedUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${minLon},${minLat},${maxLon},${maxLat}&layer=mapnik&marker=${lat},${lon}`;
            const largeMapUrl = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lon}#map=16/${lat}/${lon}`;
            const mapFrame = document.getElementById('osm-map');
            const largeLink = document.getElementById('osm-large-link');
            if (mapFrame) mapFrame.src = embedUrl;
            if (largeLink) largeLink.href = largeMapUrl;
        }

        $(function () {
            $('.distributor-item').on('click', function () {
                const lat = parseFloat($(this).data('lat'));
                const lon = parseFloat($(this).data('lon'));
                updateMap(lat, lon, this);
            });
        });
    </script>         
    </body>     
</html>
