<?php
require 'config.php';
if (session_status() == PHP_SESSION_NONE) session_start();

// 1. LẤY ID SẢN PHẨM
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. TRUY VẤN CHI TIẾT SẢN PHẨM
$stmt = $pdo->prepare("
    SELECT p.*, c.ten AS cat_ten 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.active = 1
");
$stmt->execute([$id]);
$product = $stmt->fetch();

// Nếu không có sản phẩm, quay về trang chủ
if (!$product) {
    header('Location: index.php');
    exit();
}

// 3. LẤY CÁC BIẾN THỂ (SIZE/MÀU)
$stmtVar = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND so_luong_kho > 0");
$stmtVar->execute([$id]);
$variants = $stmtVar->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['ten']) ?> | V-FASHION</title>
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
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8fafc;
        }

        .product-img-main {
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            object-fit: cover;
            width: 100%;
            max-height: 600px;
        }

        .price-tag {
            color: var(--main-accent);
            font-size: 2rem;
            font-weight: 700;
        }

        .old-price {
            text-decoration: line-through;
            color: #64748b;
            font-size: 1.2rem;
        }

        .variant-selector input[type="radio"] {
            display: none;
        }

        .variant-selector label {
            padding: 10px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: 600;
        }

        .variant-selector input[type="radio"]:checked+label {
            border-color: var(--main-accent);
            background: var(--main-accent);
            color: white;
        }

        .btn-buy {
            background: var(--main-dark);
            color: white;
            padding: 15px 30px;
            border-radius: 15px;
            font-weight: 600;
            border: none;
            width: 100%;
            transition: 0.3s;
        }

        .btn-buy:hover {
            background: var(--main-accent);
            transform: translateY(-3px);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark p-3" style="background: var(--main-dark);">
        <div class="container">
            <a href="index.php" class="text-white text-decoration-none"><i class="bi bi-arrow-left me-2"></i> QUAY
                LẠI</a>
            <span class="text-white fw-bold">CHI TIẾT SẢN PHẨM</span>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row g-5">
            <div class="col-lg-6">
                <img src="images/<?= htmlspecialchars($product['hinh_anh']) ?>" class="product-img-main"
                    onerror="this.src='https://via.placeholder.com/600x800'">
            </div>

            <div class="col-lg-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Cửa hàng</a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($product['cat_ten']) ?></li>
                    </ol>
                </nav>

                <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($product['ten']) ?></h1>

                <div class="mb-4">
                    <?php if ($product['gia_khuyen_mai'] > 0): ?>
                        <span class="price-tag"><?= number_format($product['gia_khuyen_mai']) ?>đ</span>
                        <span class="old-price ms-3"><?= number_format($product['gia_goc']) ?>đ</span>
                    <?php else: ?>
                        <span class="price-tag"><?= number_format($product['gia_goc']) ?>đ</span>
                    <?php endif; ?>
                </div>

                <p class="text-secondary mb-5" style="line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($product['mo_ta'] ?? 'Chưa có mô tả cho sản phẩm này.')) ?>
                </p>

                <form action="add_to_cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= $id ?>">

                    <h6 class="fw-bold mb-3">LỰA CHỌN KÍCH CỠ/LOẠI:</h6>
                    <div class="d-flex flex-wrap gap-2 variant-selector mb-4">
                        <?php foreach ($variants as $index => $v): ?>
                            <input type="radio" name="size" id="v<?= $v['id'] ?>" value="<?= $v['size'] ?>"
                                <?= $index === 0 ? 'checked' : '' ?> required>
                            <label for="v<?= $v['id'] ?>"><?= $v['size'] ?></label>
                        <?php endforeach; ?>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <h6 class="fw-bold mb-3">SỐ LƯỢNG:</h6>
                            <input type="number" name="so_luong" value="1" min="1"
                                class="form-control p-3 border-0 bg-white shadow-sm rounded-3 text-center">
                        </div>
                    </div>

                    <button type="submit" class="btn-buy shadow-lg">
                        <i class="bi bi-cart-plus me-2"></i> THÊM VÀO GIỎ HÀNG
                    </button>
                </form>

                <div class="mt-5 p-4 bg-white rounded-4 shadow-sm">
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-shield-check text-success fs-4 me-3"></i>
                        <span>Bảo hành chính hãng 12 tháng</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-truck text-primary fs-4 me-3"></i>
                        <span>Giao hàng miễn phí toàn quốc</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-arrow-repeat text-danger fs-4 me-3"></i>
                        <span>Đổi trả trong vòng 30 ngày nếu có lỗi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0 opacity-50">&copy; 2026 V-FASHION Luxury Store. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>