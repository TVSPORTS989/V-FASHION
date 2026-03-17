<?php
require 'config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

/* 1. LẤY TỪ KHÓA TÌM KIẾM */
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

/* 2. LẤY DANH MỤC */
$stmtCat = $pdo->query("SELECT * FROM categories WHERE active = 1 ORDER BY id ASC");
$categories = $stmtCat->fetchAll();

/* 3. LẤY SẢN PHẨM (CÓ LỌC THEO SEARCH) */
$sql = "SELECT p.*, c.ten AS cat_ten,
    (SELECT SUM(so_luong_kho) FROM product_variants WHERE product_id = p.id) as total_stock,
    (SELECT AVG(so_sao) FROM reviews WHERE product_id = p.id) as rating
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.active = 1";

if ($search !== '') {
    $sql .= " AND (p.ten LIKE :search OR c.ten LIKE :search)";
}
$sql .= " ORDER BY p.id DESC";

$stmtProd = $pdo->prepare($sql);
if ($search !== '') {
    $stmtProd->execute(['search' => "%$search%"]);
} else {
    $stmtProd->execute();
}
$all_products = $stmtProd->fetchAll();

/* 4. PHÂN LOẠI SẢN PHẨM VÀO TAB */
$cat_products_list = [];
foreach ($categories as $cat) {
    $cat_products_list[$cat['id']] = [];
}
foreach ($all_products as $p) {
    if (isset($cat_products_list[$p['category_id']])) {
        $cat_products_list[$p['category_id']][] = $p;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>V-FASHION | Luxury Store</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --main-dark: #0f172a;
            --main-accent: #e11d48;
            --gold: #d4af37;
            --light-bg: #f8fafc;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-bg);
            color: #1e293b;
            scroll-behavior: smooth;
        }

        .brand-gradient {
            background: linear-gradient(90deg, #e11d48, #d4af37, #ffffff, #d4af37, #e11d48);
            background-size: 200% auto;
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shine 5s linear infinite;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
        }

        @keyframes shine {
            to {
                background-position: 200% center;
            }
        }

        .navbar {
            background: var(--main-dark);
            border-bottom: 3px solid var(--gold);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Tùy chỉnh thanh Search */
        .search-box {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 2px 15px;
            transition: 0.3s;
        }

        .search-box:focus-within {
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 0 0 2px var(--main-accent);
        }

        .search-box input {
            background: transparent !important;
            color: white !important;
            border: none !important;
            box-shadow: none !important;
        }

        .search-box input::placeholder {
            color: #cbd5e1;
        }

        .product-card {
            border: none;
            border-radius: 20px;
            transition: 0.4s;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .card-img-top-fixed {
            height: 320px;
            width: 100%;
            object-fit: cover;
            transition: 0.5s;
        }

        .product-card:hover .card-img-top-fixed {
            transform: scale(1.05);
        }

        .btn-fashion {
            background: var(--main-dark);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            transition: 0.3s;
            border: none;
        }

        .btn-fashion:hover {
            background: var(--main-accent);
            color: white;
            transform: scale(1.02);
        }

        .nav-tabs .nav-link {
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 10px 25px;
        }

        .nav-tabs .nav-link.active {
            color: var(--main-accent);
            border-bottom: 3px solid var(--main-accent);
            background: none;
        }

        .hover-gold:hover {
            color: var(--gold) !important;
            transition: 0.3s;
        }

        .x-small {
            font-size: 0.75rem;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="bi bi-gem text-danger me-2 fs-3"></i>
                <span class="brand-gradient fs-2">V-FASHION</span>
            </a>

            <form action="index.php" method="GET" class="d-none d-lg-flex mx-auto col-lg-4">
                <div class="input-group search-box">
                    <input type="text" name="search" class="form-control" placeholder="Tìm tên sản phẩm..."
                        value="<?= htmlspecialchars($search) ?>">
                    <button class="btn text-white" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>

            <div class="navbar-nav ms-auto flex-row align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="d-none d-md-block me-3 text-white small">
                        <i class="bi bi-wallet2 text-warning"></i> <?= number_format($_SESSION['user_vi_tien'] ?? 0) ?>đ
                    </div>
                    <a class="nav-link position-relative me-4" href="cart.php">
                        <i class="bi bi-bag-heart fs-4"></i>
                        <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">
                            <?= array_sum(array_column($_SESSION['cart'] ?? [], 'so_luong')) ?>
                        </span>
                    </a>
                    <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="logout.php">Thoát</a>
                <?php else: ?>
                    <a class="nav-link me-3 text-white small" href="login.php">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (isset($_GET['added']) || isset($_GET['error'])): ?>
        <div class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 9999;">
            <div class="alert alert-<?= isset($_GET['added']) ? 'success' : 'danger' ?> alert-dismissible fade show shadow-lg"
                role="alert">
                <i class="bi bi-<?= isset($_GET['added']) ? 'check-circle' : 'exclamation-triangle' ?>-fill me-2"></i>
                <strong><?= isset($_GET['added']) ? 'Thành công!' : 'Lỗi!' ?></strong>
                <?php
                if (isset($_GET['added'])) echo "Đã thêm vào giỏ hàng.";
                else {
                    $errors = ['invalid_input' => 'Chọn đầy đủ thông tin!', 'out_of_stock' => 'Hết hàng!', 'exceed_stock' => 'Vượt tồn kho!'];
                    echo $errors[$_GET['error']] ?? 'Có lỗi xảy ra!';
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <div class="container my-4">
        <div class="rounded-5 overflow-hidden shadow-lg position-relative" style="height: 450px; background: #000;">
            <video class="w-100 h-100 object-fit-cover opacity-75" autoplay muted loop playsinline>
                <source src="images/video.mp4" type="video/mp4">
            </video>
            <div class="position-absolute top-50 start-0 translate-middle-y ps-md-5 ps-4 text-white">
                <span class="badge bg-danger mb-2">COLLECTION 2026</span>
                <h1 class="display-3 fw-bold mb-0">ELEVATE YOUR STYLE</h1>
                <p class="fs-5 opacity-75 mb-4">Khám phá bản sắc thời trang thượng lưu</p>
                <a href="#shop-section" class="btn btn-light btn-lg rounded-pill px-5 fw-bold shadow">MUA NGAY</a>
            </div>
        </div>
    </div>

    <div class="container mb-5" id="shop-section">
        <?php if ($search !== ''): ?>
            <div class="mb-4">
                <h4>Kết quả tìm kiếm cho: "<span class="text-danger"><?= htmlspecialchars($search) ?></span>"</h4>
                <a href="index.php" class="small text-decoration-none"><i class="bi bi-x-circle"></i> Xóa bộ lọc</a>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs justify-content-center mb-5 border-0">
            <?php foreach ($categories as $i => $cat): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $i === 0 ? 'active' : '' ?>" data-bs-toggle="tab" href="#cat<?= $cat['id'] ?>">
                        <?= strtoupper(htmlspecialchars($cat['ten'])) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content">
            <?php foreach ($categories as $i => $cat): ?>
                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="cat<?= $cat['id'] ?>">
                    <div class="row g-4">
                        <?php if (empty($cat_products_list[$cat['id']])): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-box2 fs-1 text-muted"></i>
                                <p class="text-muted mt-2">Không tìm thấy sản phẩm nào...</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($cat_products_list[$cat['id']] as $row): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="card product-card">
                                    <div class="position-relative overflow-hidden">
                                        <a href="product_detail.php?id=<?= $row['id'] ?>">
                                            <img src="images/<?= htmlspecialchars($row['hinh_anh']) ?>"
                                                class="card-img-top card-img-top-fixed"
                                                onerror="this.src='https://via.placeholder.com/400x550?text=V-Fashion'">
                                        </a>
                                        <?php if ($row['gia_khuyen_mai'] > 0): ?>
                                            <span
                                                class="position-absolute top-0 start-0 bg-danger text-white px-3 py-1 m-3 rounded-pill fw-bold x-small shadow">SALE</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body d-flex flex-column p-3 p-md-4">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-warning fw-bold">
                                                <i class="bi bi-star-fill small"></i>
                                                <?= number_format($row['rating'] ?: 5, 1) ?>
                                            </small>
                                            <small class="text-muted x-small">Tồn: <?= $row['total_stock'] ?: 0 ?></small>
                                        </div>
                                        <h6 class="fw-bold mb-2 text-dark text-truncate">
                                            <a href="product_detail.php?id=<?= $row['id'] ?>"
                                                class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($row['ten']) ?>
                                            </a>
                                        </h6>
                                        <div class="mb-3">
                                            <?php if ($row['gia_khuyen_mai'] > 0): ?>
                                                <span
                                                    class="text-danger fw-bold fs-5"><?= number_format($row['gia_khuyen_mai']) ?>đ</span>
                                                <span
                                                    class="text-muted text-decoration-line-through x-small ms-1"><?= number_format($row['gia_goc']) ?>đ</span>
                                            <?php else: ?>
                                                <span class="text-dark fw-bold fs-5"><?= number_format($row['gia_goc']) ?>đ</span>
                                            <?php endif; ?>
                                        </div>

                                        <form method="POST" action="add_to_cart.php" class="mt-auto">
                                            <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                            <div class="row g-2 mb-3">
                                                <div class="col-7">
                                                    <select name="size"
                                                        class="form-select form-select-sm border-0 bg-light shadow-sm" required>
                                                        <option value="" hidden>Size...</option>
                                                        <?php
                                                        $stmtVar = $pdo->prepare("SELECT size FROM product_variants WHERE product_id = ? AND so_luong_kho > 0");
                                                        $stmtVar->execute([$row['id']]);
                                                        foreach ($stmtVar->fetchAll() as $v): ?>
                                                            <option value="<?= $v['size'] ?>"><?= $v['size'] ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-5">
                                                    <input type="number" name="so_luong" value="1" min="1"
                                                        max="<?= $row['total_stock'] ?>"
                                                        class="form-control form-control-sm border-0 bg-light text-center shadow-sm">
                                                </div>
                                            </div>
                                            <button class="btn btn-fashion w-100 py-2 shadow-sm"
                                                <?= ($row['total_stock'] <= 0) ? 'disabled' : '' ?>>
                                                <?= ($row['total_stock'] <= 0) ? 'HẾT HÀNG' : '<i class="bi bi-cart-plus me-1"></i> THÊM GIỎ' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>


    <footer class="pt-5 pb-3 text-light" style="background: #0f172a; border-top: 4px solid var(--gold);">
        <div class="container">
            <div class="row g-4 mb-5">
                <div class="col-lg-4">
                    <h2 class="brand-gradient mb-3">V-FASHION</h2>
                    <p class="text-secondary small mb-4" style="line-height: 1.8;">
                        Định hình phong cách thượng lưu. Chúng tôi mang đến những bộ sưu tập thời trang độc bản,
                        kết hợp giữa nghệ thuật thủ công và xu hướng hiện đại.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="https://www.facebook.com/share/1HeSN1TL5q/" target="_blank"
                            class="btn btn-outline-light btn-sm rounded-circle">
                            <i class="bi bi-facebook"></i>
                        </a>

                        <a href="https://www.instagram.com/vinh.votruong.2k2?igsh=MWl4bnF1N3BnZHhidQ==" target="_blank"
                            class="btn btn-outline-light btn-sm rounded-circle">
                            <i class="bi bi-instagram"></i>
                        </a>

                        <a href="https://www.tiktok.com/@zinhco19?_r=1&_t=ZS-94PGwBHRW0f" target="_blank"
                            class="btn btn-outline-light btn-sm rounded-circle">
                            <i class="bi bi-tiktok"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h6 class="text-white fw-bold mb-4 text-uppercase small" style="letter-spacing: 1px;">Danh mục</h6>
                    <ul class="list-unstyled small">
                        <?php foreach ($categories as $c): ?>
                            <li class="mb-2"><a href="#cat<?= $c['id'] ?>"
                                    class="text-secondary text-decoration-none hover-gold"><?= $c['ten'] ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h6 class="text-white fw-bold mb-4 text-uppercase small" style="letter-spacing: 1px;">Dịch vụ</h6>
                    <ul class="list-unstyled small text-secondary">
                        <li class="mb-2"><i class="bi bi-truck me-2 text-warning"></i> Giao hàng hỏa tốc</li>
                        <li class="mb-2"><i class="bi bi-arrow-left-right me-2 text-warning"></i> Đổi trả 30 ngày</li>
                        <li class="mb-2"><i class="bi bi-credit-card me-2 text-warning"></i> Thanh toán bảo mật</li>
                        <li class="mb-2"><i class="bi bi-headset me-2 text-warning"></i> Hỗ trợ 24/7</li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h6 class="text-white fw-bold mb-4 text-uppercase small" style="letter-spacing: 1px;">Liên hệ</h6>
                    <div class="small text-secondary">
                        <p class="mb-2"><i class="bi bi-geo-alt me-2"></i> Quận Ninh Kiều, TP. Cần Thơ</p>
                        <p class="mb-2"><i class="bi bi-telephone me-2"></i> 0848959556</p>
                        <p class="mb-3"><i class="bi bi-envelope me-2"></i> votruongvinh2004@gmail.com</p>
                    </div>
                </div>
            </div>
            <hr class="border-secondary opacity-25">

        </div>
    </footer>

    <button id="chatbot-toggle-btn"
        style="position: fixed; bottom: 20px; right: 20px; width: 60px; height: 60px; border-radius: 50%; background-color: var(--main-accent); color: white; border: none; font-size: 28px; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.3); z-index: 9999; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;">
        <i class="bi bi-chat-dots"></i>
    </button>

    <div id="chatbot-window"
        style="display: none; position: fixed; bottom: 90px; right: 20px; width: 350px; background: #fff; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); z-index: 10000; overflow: hidden; font-family: 'Poppins', sans-serif;">

        <div
            style="background: var(--main-dark); border-bottom: 2px solid var(--gold); color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h6 style="margin: 0; font-weight: 600;"><i class="bi bi-robot text-warning me-2"></i> Trợ lý V-FASHION</h6>
            <button id="chatbot-close-btn"
                style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div id="chatbot-messages"
            style="height: 320px; padding: 15px; overflow-y: auto; background: var(--light-bg); display: flex; flex-direction: column; gap: 12px; font-size: 14px;">
            <div
                style="background: #e2e8f0; color: #1e293b; padding: 10px 12px; border-radius: 15px; max-width: 80%; align-self: flex-start;">
                Chào bạn! Tôi là trợ lý ảo của V-FASHION. Bạn cần tìm kiếm sản phẩm hay tư vấn size trang phục nào?
            </div>
        </div>

        <div style="display: flex; padding: 12px; background: white; border-top: 1px solid #e2e8f0;">
            <input type="text" id="chatbot-input" placeholder="Nhập câu hỏi..."
                style="flex: 1; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 14px;">
            <button id="chatbot-send-btn"
                style="margin-left: 8px; background: var(--main-accent); color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; font-weight: 600;"><i
                    class="bi bi-send-fill"></i></button>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('chatbot-toggle-btn');
        const closeBtn = document.getElementById('chatbot-close-btn');
        const chatWindow = document.getElementById('chatbot-window');
        const sendBtn = document.getElementById('chatbot-send-btn');
        const chatInput = document.getElementById('chatbot-input');
        const chatMessages = document.getElementById('chatbot-messages');

        // Hiệu ứng hover cho nút chat
        toggleBtn.addEventListener('mouseenter', () => toggleBtn.style.transform = 'scale(1.1)');
        toggleBtn.addEventListener('mouseleave', () => toggleBtn.style.transform = 'scale(1)');

        // Mở / Đóng khung chat khi bấm nút
        toggleBtn.addEventListener('click', () => {
            chatWindow.style.display = chatWindow.style.display === 'none' || chatWindow.style.display === '' ?
                'block' : 'none';
        });

        closeBtn.addEventListener('click', () => {
            chatWindow.style.display = 'none';
        });

        // Hàm tạo giao diện tin nhắn
        function appendMessage(sender, text) {
            const msgDiv = document.createElement('div');
            msgDiv.style.padding = '10px 12px';
            msgDiv.style.borderRadius = '15px';
            msgDiv.style.maxWidth = '80%';
            msgDiv.style.wordWrap = 'break-word';

            if (sender === 'user') {
                msgDiv.style.background = 'var(--main-accent)';
                msgDiv.style.color = 'white';
                msgDiv.style.alignSelf = 'flex-end';
                msgDiv.innerHTML = text;
            } else {
                msgDiv.style.background = '#e2e8f0';
                msgDiv.style.color = '#1e293b';
                msgDiv.style.alignSelf = 'flex-start';
                msgDiv.innerHTML = text;
            }

            chatMessages.appendChild(msgDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            return msgDiv;
        }

        // Hàm gửi dữ liệu lên server
        async function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;

            appendMessage('user', message);
            chatInput.value = '';

            const loadingMsg = appendMessage('ai', '<em>Đang xử lý... <i class="bi bi-hourglass-split"></i></em>');

            try {
                // Gửi request tới file PHP (Nhớ tạo file api_chat.php cùng thư mục)
                const response = await fetch('api_chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        prompt: message
                    })
                });

                const data = await response.json();

                loadingMsg.remove();
                if (data.reply) {
                    appendMessage('ai', data.reply);
                } else {
                    appendMessage('ai', '<span class="text-danger">Lỗi: Không nhận được phản hồi.</span>');
                }
            } catch (error) {
                loadingMsg.remove();
                appendMessage('ai', '<span class="text-danger">Lỗi kết nối máy chủ. Vui lòng thử lại.</span>');
            }
        }

        sendBtn.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>