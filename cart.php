<?php
// 1. KẾT NỐI VÀ CẤU HÌNH (GIỮ NGUYÊN)
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Khởi tạo giỏ hàng
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Lấy thông tin User để điền sẵn vào form
$user_info = ['ho_ten' => '', 'sdt' => '', 'dia_chi' => ''];
try {
    $stmtUser = $pdo->prepare("SELECT ho_ten, sdt, dia_chi FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user_info = $stmtUser->fetch() ?: $user_info;
} catch (Exception $e) {
}


// --- XỬ LÝ 1: XÓA SẢN PHẨM ---
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['key'])) {
    $key = $_GET['key'];
    if (isset($_SESSION['cart'][$key])) unset($_SESSION['cart'][$key]);
    header('Location: cart.php?removed=1');
    exit();
}

// --- XỬ LÝ 2: CẬP NHẬT SỐ LƯỢNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['so_luong'] ?? [] as $key => $qty) {
        $qty = max(1, intval($qty));
        if (isset($_SESSION['cart'][$key])) {
            // Check kho
            $pid = $_SESSION['cart'][$key]['product_id'];
            $size = $_SESSION['cart'][$key]['size'];
            $stmtCheck = $pdo->prepare("SELECT so_luong_kho FROM product_variants WHERE product_id=? AND size=?");
            $stmtCheck->execute([$pid, $size]);
            $stock = $stmtCheck->fetch();
            if ($stock && $qty <= $stock['so_luong_kho']) {
                $_SESSION['cart'][$key]['so_luong'] = $qty;
            }
        }
    }
    header('Location: cart.php?updated=1');
    exit();
}

// --- XỬ LÝ 3: MÃ VIP ---
$sdt_vip = $_SESSION['sdt_vip'] ?? '';
$discount_percent = 0;
$discount_message = '';

if (isset($_POST['apply_vip'])) {
    $sdt_vip = trim($_POST['sdt_vip'] ?? '');
    $_SESSION['sdt_vip'] = $sdt_vip;
}

if (!empty($sdt_vip) && preg_match('/^[0-9]{10}$/', $sdt_vip)) {
    $discount_percent = 10;
    $discount_message = "✅ Đã áp dụng mã VIP (Giảm 10%)";
}

// --- TÍNH TOÁN TIỀN ---
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) $subtotal += $item['gia'] * $item['so_luong'];

$discount_amount = 0;
if ($subtotal >= 200000 && $discount_percent > 0) {
    $discount_amount = $subtotal * ($discount_percent / 100);
}

$after_discount = $subtotal - $discount_amount;
$vat = $after_discount * 0.1;
$shipping = 15000;
$total_payment = $after_discount + $vat + $shipping;

// --- XỬ LÝ 4: ĐẶT HÀNG (CHECKOUT) ---
$error_order = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $ten = $_POST['ho_ten'] ?? '';
    $sdt = $_POST['sdt'] ?? '';
    $dc  = $_POST['dia_chi'] ?? '';
    $payment = $_POST['payment_method'] ?? 'COD';
    $note = $_POST['ghi_chu'] ?? '';

    if (empty($ten) || empty($sdt) || empty($dc)) {
        $error_order = "Vui lòng điền đủ Tên, SĐT và Địa chỉ!";
    } elseif (empty($_SESSION['cart'])) {
        $error_order = "Giỏ hàng trống!";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Trừ kho
            foreach ($_SESSION['cart'] as $item) {
                $check = $pdo->prepare("SELECT so_luong_kho FROM product_variants WHERE product_id=? AND size=?");
                $check->execute([$item['product_id'], $item['size']]);
                $stock = $check->fetch();

                if (!$stock || $stock['so_luong_kho'] < $item['so_luong']) {
                    throw new Exception("Sản phẩm {$item['ten_san_pham']} (Size: {$item['size']}) không đủ hàng!");
                }

                $pdo->prepare("UPDATE product_variants SET so_luong_kho = so_luong_kho - ? WHERE product_id=? AND size=?")
                    ->execute([$item['so_luong'], $item['product_id'], $item['size']]);
            }

            // 2. Tạo thông tin gộp
            $info_gop = "KH: $ten | SĐT: $sdt | ĐC: $dc\n";
            $info_gop .= "Note: $note | TT: $payment\n";
            $info_gop .= "Chi tiết: Tạm tính " . number_format($subtotal) . " - Giảm " . number_format($discount_amount) . " + VAT " . number_format($vat) . " + Ship " . number_format($shipping);

            // 3. Insert Orders
            $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, tong_tien, thong_tin, ngay_dat) VALUES (?, ?, ?, NOW())");
            $stmtOrder->execute([$_SESSION['user_id'], $total_payment, $info_gop]);
            $order_id = $pdo->lastInsertId();

            // 4. Insert Order Items
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, ten_sp_luc_mua, size_luc_mua, gia_luc_mua, so_luong) VALUES (?, ?, ?, ?, ?, ?)");

            foreach ($_SESSION['cart'] as $item) {
                $stmtItem->execute([
                    $order_id,
                    $item['product_id'],
                    $item['ten_san_pham'],
                    $item['size'],
                    $item['gia'],
                    $item['so_luong']
                ]);
            }

            $pdo->commit();

            // Xóa giỏ
            unset($_SESSION['cart']);
            unset($_SESSION['sdt_vip']);

            // Điều hướng
            if ($payment == 'BANK') {
                header("Location: payment_qr.php?order_id=$order_id");
            } else {
                echo "<script>alert('Đặt hàng thành công!'); window.location='index.php';</script>";
            }
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_order = "Lỗi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán | V-FASHION</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
    body {
        background: #f0f2f5;
        font-family: 'Nunito', sans-serif;
        color: #333;
    }

    .navbar-custom {
        background: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 15px 0;
    }

    .navbar-brand {
        font-weight: 800;
        color: #333;
        font-size: 1.5rem;
    }

    .back-link {
        text-decoration: none;
        color: #666;
        font-weight: 600;
        transition: 0.3s;
    }

    .back-link:hover {
        color: #000;
    }

    .nav-tabs-custom {
        background: #fff;
        padding: 10px;
        border-radius: 50px;
        display: inline-flex;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.03);
        margin-bottom: 30px;
    }

    .tab-btn {
        border: none;
        background: transparent;
        padding: 10px 25px;
        border-radius: 40px;
        font-weight: 700;
        color: #777;
        transition: all 0.3s ease;
    }

    .tab-btn:hover {
        color: #333;
        background: #f8f9fa;
    }

    .tab-btn.active {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
        color: white;
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }

    .tab-content-wrapper {
        display: none;
        animation: fadeIn 0.4s;
    }

    .tab-content-wrapper.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card-custom {
        border: none;
        border-radius: 16px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        background: white;
        overflow: hidden;
    }

    .table-custom th {
        background: #f8f9fa;
        border-top: none;
        font-weight: 700;
        color: #555;
    }

    .table-custom td {
        vertical-align: middle;
        border-bottom: 1px solid #eee;
        padding: 15px 10px;
    }

    .product-thumb {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 12px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .qty-input {
        width: 60px;
        text-align: center;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-weight: 600;
    }

    .summary-card {
        position: sticky;
        top: 20px;
        border: none;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    }

    .summary-header {
        background: linear-gradient(135deg, #212529, #343a40);
        color: white;
        padding: 20px;
        border-radius: 16px 16px 0 0;
    }

    .form-control-custom {
        border-radius: 10px;
        padding: 12px;
        border: 1px solid #eee;
        background: #f9f9f9;
    }

    .form-control-custom:focus {
        background: #fff;
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
    }

    .checkout-btn {
        background: linear-gradient(135deg, #e11d48, #be123c);
        border: none;
        border-radius: 12px;
        padding: 15px;
        width: 100%;
        font-weight: 800;
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 15px rgba(225, 29, 72, 0.3);
        transition: transform 0.2s;
    }

    .checkout-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(225, 29, 72, 0.4);
    }

    .iframe-container {
        width: 100%;
        height: 800px;
        border: none;
        border-radius: 16px;
        background: white;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }
    </style>
</head>

<body>

    <nav class="navbar navbar-custom mb-4">
        <div class="container">
            <a href="index.php" class="back-link"><i class="fas fa-arrow-left me-2"></i> Tiếp tục mua hàng</a>
            <span class="navbar-brand"><i class="fas fa-shopping-bag text-danger"></i> Thanh Toán & Đơn Hàng</span>
            <div>
                <span class="text-muted small"><i class="fas fa-user-circle"></i> Chào,
                    <?= htmlspecialchars($user_info['ho_ten'] ?: 'Bạn') ?></span>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-center">
            <div class="nav-tabs-custom">
                <button class="tab-btn active" onclick="switchTab('cart')">
                    <i class="fas fa-shopping-cart me-1"></i> Giỏ hàng (<?= count($_SESSION['cart']) ?>)
                </button>
                <button class="tab-btn" onclick="switchTab('orders')">
                    <i class="fas fa-file-invoice me-1"></i> Lịch sử đơn
                </button>
                <button class="tab-btn" onclick="switchTab('reviews')">
                    <i class="fas fa-star me-1"></i> Đánh giá
                </button>
            </div>
        </div>

        <div id="cart-tab" class="tab-content-wrapper active">
            <?php if ($error_order): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4"><i class="fas fa-exclamation-circle me-2"></i>
                <?= $error_order ?></div>
            <?php endif; ?>

            <?php if (empty($_SESSION['cart'])): ?>
            <div class="text-center py-5">
                <img src="https://cdn-icons-png.flaticon.com/512/11329/11329060.png" style="width: 150px; opacity: 0.8;"
                    alt="Empty Cart">
                <h4 class="mt-4 fw-bold text-muted">Giỏ hàng đang trống</h4>
                <p class="text-muted mb-4">Bạn chưa chọn sản phẩm nào. Hãy quay lại cửa hàng nhé!</p>
                <a href="index.php" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">Xem Sản Phẩm</a>
            </div>
            <?php else: ?>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card-custom p-3">
                        <form method="POST">
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">Số lượng</th>
                                            <th class="text-end">Tổng</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($_SESSION['cart'] as $key => $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                            // ✅ FIX: Dùng đường dẫn giống index.php
                                                            $img_src = !empty($item['hinh_anh'])
                                                                ? 'images/' . htmlspecialchars($item['hinh_anh'])
                                                                : 'https://via.placeholder.com/70?text=No+Image';
                                                            ?>
                                                    <img src="<?= $img_src ?>" class="product-thumb me-3"
                                                        onerror="this.src='https://via.placeholder.com/70?text=Error'">

                                                    <div>
                                                        <div class="fw-bold text-dark">
                                                            <?= htmlspecialchars($item['ten_san_pham']) ?>
                                                        </div>
                                                        <div class="small text-muted">Size:
                                                            <span class="badge bg-light text-dark border">
                                                                <?= htmlspecialchars($item['size']) ?>
                                                            </span>
                                                        </div>
                                                        <div class="small text-muted mt-1">
                                                            <?= number_format($item['gia']) ?>đ
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <input type="number" name="so_luong[<?= $key ?>]"
                                                    value="<?= $item['so_luong'] ?>" min="1" class="qty-input">
                                            </td>
                                            <td class="text-end fw-bold text-primary">
                                                <?= number_format($item['gia'] * $item['so_luong']) ?>đ
                                            </td>
                                            <td class="text-end">
                                                <a href="cart.php?action=remove&key=<?= $key ?>" class="text-danger p-2"
                                                    onclick="return confirm('Xóa sản phẩm này?')" title="Xóa">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <a href="index.php" class="text-decoration-none fw-bold text-secondary">
                                    <i class="fas fa-plus-circle"></i> Thêm sản phẩm khác
                                </a>
                                <button type="submit" name="update_cart"
                                    class="btn btn-outline-primary rounded-pill btn-sm fw-bold">
                                    <i class="fas fa-sync-alt me-1"></i> Cập nhật giỏ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card summary-card">
                        <div class="summary-header">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-wallet me-2"></i> Thông tin thanh toán</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" class="mb-4">
                                <label class="small fw-bold text-muted mb-1">Mã giảm giá / VIP</label>
                                <div class="input-group">
                                    <input type="text" name="sdt_vip" class="form-control form-control-custom"
                                        placeholder="Nhập SĐT VIP (10 số)" value="<?= htmlspecialchars($sdt_vip) ?>">
                                    <button type="submit" name="apply_vip" class="btn btn-dark fw-bold px-3">Áp
                                        dụng</button>
                                </div>
                                <?php if ($discount_message) echo "<small class='text-success fw-bold mt-2 d-block'><i class='fas fa-check-circle'></i> $discount_message</small>"; ?>
                            </form>

                            <hr class="my-4" style="opacity: 0.1">

                            <form method="POST">
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted">Họ và tên</label>
                                    <input type="text" name="ho_ten" class="form-control form-control-custom"
                                        value="<?= htmlspecialchars($user_info['ho_ten']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted">Số điện thoại</label>
                                    <input type="text" name="sdt" class="form-control form-control-custom"
                                        value="<?= htmlspecialchars($user_info['sdt']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="small fw-bold text-muted">Địa chỉ giao hàng</label>
                                    <textarea name="dia_chi" class="form-control form-control-custom" rows="2"
                                        required><?= htmlspecialchars($user_info['dia_chi']) ?></textarea>
                                </div>
                                <div class="mb-4">
                                    <label class="small fw-bold text-muted">Ghi chú (Tùy chọn)</label>
                                    <input type="text" name="ghi_chu" class="form-control form-control-custom"
                                        placeholder="VD: Giao hàng giờ hành chính...">
                                </div>

                                <div class="mb-4">
                                    <label class="small fw-bold text-muted mb-2">Phương thức thanh toán</label>
                                    <div class="d-flex gap-2">
                                        <div class="form-check p-3 border rounded-3 flex-fill bg-light">
                                            <input class="form-check-input" type="radio" name="payment_method"
                                                value="COD" checked id="payCOD">
                                            <label class="form-check-label fw-bold" for="payCOD">
                                                <i class="fas fa-money-bill-wave text-success me-1"></i> Tiền mặt
                                            </label>
                                        </div>
                                        <div class="form-check p-3 border rounded-3 flex-fill bg-light">
                                            <input class="form-check-input" type="radio" name="payment_method"
                                                value="BANK" id="payBank">
                                            <label class="form-check-label fw-bold" for="payBank">
                                                <i class="fas fa-qrcode text-primary me-1"></i> QR Code
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-light p-3 rounded-3 mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Tạm tính:</span>
                                        <span class="fw-bold"><?= number_format($subtotal) ?> đ</span>
                                    </div>
                                    <?php if ($discount_amount > 0): ?>
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span><i class="fas fa-tag"></i> Giảm giá VIP:</span>
                                        <span>-<?= number_format($discount_amount) ?> đ</span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between mb-2 text-muted small">
                                        <span>VAT (10%):</span>
                                        <span><?= number_format($vat) ?> đ</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 text-muted small">
                                        <span>Phí ship:</span>
                                        <span><?= number_format($shipping) ?> đ</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold fs-5 text-dark">TỔNG CỘNG:</span>
                                        <span class="fw-bold fs-4 text-danger"><?= number_format($total_payment) ?>
                                            đ</span>
                                    </div>
                                </div>

                                <button type="submit" name="place_order"
                                    class="btn btn-danger text-white w-100 checkout-btn">
                                    ĐẶT HÀNG NGAY <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div id="orders-tab" class="tab-content-wrapper">
            <iframe src="history.php" class="iframe-container"></iframe>
        </div>

        <div id="reviews-tab" class="tab-content-wrapper">
            <iframe src="reviews.php" class="iframe-container"></iframe>
        </div>
    </div>

    <script>
    function switchTab(name) {
        document.querySelectorAll('.tab-content-wrapper').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(name + '-tab').classList.add('active');
        event.currentTarget.classList.add('active');
    }
    </script>
</body>

</html>