<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

$payment_method = $_POST['payment_method'] ?? 'COD';
$dia_chi = trim($_POST['dia_chi'] ?? '');
$ghi_chu = trim($_POST['ghi_chu'] ?? '');
$total_amount = floatval($_POST['total_amount'] ?? 0);

if (empty($dia_chi)) {
    header('Location: cart.php?error=missing_address');
    exit();
}

try {
    $pdo->beginTransaction();

    // Lấy thông tin từ session
    $summary = $_SESSION['order_summary'];
    $sdt_vip = $_SESSION['sdt_vip'] ?? '';
    $promo_code = !empty($sdt_vip) && $summary['discount'] > 0 ? "VIP_$sdt_vip" : '';

    // Tạo đơn hàng
    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, tong_tien, trang_thai, ghi_chu, promo_code, delivery_fee, payment_method, ngay_tao) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $order_status = ($payment_method === 'COD') ? 'completed' : 'pending';

    $stmt->execute([
        $_SESSION['user_id'],
        $total_amount,
        $order_status,
        $dia_chi . ($ghi_chu ? " | Ghi chú: $ghi_chu" : ''),
        $promo_code,
        $summary['shipping'],
        $payment_method
    ]);

    $order_id = $pdo->lastInsertId();

    // Lưu chi tiết đơn hàng
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, so_luong, gia, size) 
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($_SESSION['cart'] as $item) {
        $stmtItem->execute([
            $order_id,
            $item['product_id'],
            $item['so_luong'],
            $item['gia'],
            $item['size']
        ]);
    }

    // Lưu thanh toán
    $payment_status = ($payment_method === 'COD') ? 'completed' : 'pending';
    $stmt = $pdo->prepare("
        INSERT INTO payments (order_id, phuong_thuc, so_tien, trang_thai) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$order_id, $payment_method, $total_amount, $payment_status]);

    // Lưu địa chỉ giao hàng
    $stmt = $pdo->prepare("INSERT INTO deliveries (order_id, dia_chi_giao) VALUES (?, ?)");
    $stmt->execute([$order_id, $dia_chi]);

    $pdo->commit();

    // Lưu order_id vào session để dùng cho review
    $_SESSION['last_order_id'] = $order_id;

    // Nếu là COD thì dọn giỏ và chuyển trang review
    if ($payment_method === 'COD') {
        unset($_SESSION['cart']);
        unset($_SESSION['sdt_vip']);
        unset($_SESSION['order_summary']);
        header("Location: order_success.php?order_id=$order_id");
        exit();
    }

    // Nếu là BANK thì hiển thị QR
    if ($payment_method === 'BANK') {
        // Không dọn giỏ hàng ngay, chờ admin xác nhận thanh toán
        header("Location: payment_qr.php?order_id=$order_id");
        exit();
    }
} catch (Exception $e) {
    $pdo->rollBack();
    header('Location: cart.php?error=checkout_failed');
    exit();
}
