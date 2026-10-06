<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php';

$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = (int)$stmt->fetchColumn();
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top shadow-sm site-navbar">
    <div class="container"><a class="navbar-brand" href="/index">TopPun香．芬</a><button class="navbar-toggler"
            type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav"
            aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/全部分類">促銷區</a></li>
                <li class="nav-item"><a class="nav-link" href="/全部分類/香氛蠟燭系列">香氛蠟燭系列</a></li>
                <li class="nav-item"><a class="nav-link" href="/全部分類/香氛暖燈">香氛暖燈</a></li>
                <li class="nav-item"><a class="nav-link" href="/全部分類/個人香氛">個人香氛</a></li>
                <li class="nav-item"><a class="nav-link" href="/全部分類/居家香氛">居家香氛</a></li>
                <li class="nav-item"><a class="nav-link" href="/about">關於品牌</a></li>
                <li class="nav-item"><a class="nav-link" href="/blog-list">BLOG</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php
                    $dashboard_url = '/member';
                    $dashboard_text = '會員中心';
                    if (isset($_SESSION['role'])) {
                        switch ($_SESSION['role']) {
                            case 'admin':
                                $dashboard_url = '/admin';
                                $dashboard_text = '管理後台';
                                break;
                            case 'author':
                                $dashboard_url = '/author-dashboard';
                                $dashboard_text = '作家後台';
                                break;
                        }
                    }
                    ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $dashboard_url; ?>"><?php echo $dashboard_text; ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout">登出</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/login?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">會員登入</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="/contactus">CONTACT US</a></li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="/cart">
                        <i class="bi bi-cart3"></i> 購物車
                        <?php if ($cart_count > 0): ?>
                            <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <script>
        async function addToCart(productId, quantity = 1) {
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);

            try {
                const response = await fetch('/api/add-to-cart', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    let badge = document.getElementById('cart-badge');
                    if (badge) {
                        badge.textContent = result.cart_count;
                    } else {
                        location.reload(); 
                    }
                } else {
                    if (result.error_code === 'auth_required') {
                        if (confirm(result.message + ' 是否現在前往登入？')) {
                            window.location.href = '/login?redirect=' + encodeURIComponent(window.location.href);
                        }
                    } else {
                        alert(result.message);
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('加入購物車時發生錯誤，請稍後再試。');
            }
        }

        (function () {
            const nav = document.querySelector('.navbar.site-navbar');
            if (!nav) return;

            const SHRINK_AT = 100;
            const EXPAND_AT = 8;
            let shrunk = nav.classList.contains('navbar-shrink');
            let locked = false;

            const updateNavbarShrink = () => {
                if (locked) return;
                const y = window.scrollY || document.documentElement.scrollTop || 0;
                let next = shrunk;
                if (!shrunk && y > SHRINK_AT) next = true;
                else if (shrunk && y <= EXPAND_AT) next = false;
                if (next === shrunk) return;

                locked = true;
                shrunk = next;
                nav.classList.toggle('navbar-shrink', shrunk);
                requestAnimationFrame(() => {
                    locked = false;
                });
            };

            updateNavbarShrink();
            window.addEventListener('scroll', updateNavbarShrink, { passive: true });
        })();
    </script>
</nav>