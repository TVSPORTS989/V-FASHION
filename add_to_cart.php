<?php
require_once 'config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?msg=please_login');
    exit();
}

// Kiểm tra phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Lấy và validate dữ liệu
$product_id = intval($_POST['product_id'] ?? 0);
$size = trim($_POST['size'] ?? '');
$so_luong = max(1, intval($_POST['so_luong'] ?? 1));

// Validate đầu vào
if ($product_id <= 0) {
    header('Location: index.php?error=invalid_input');
    exit();
}

if (empty($size)) {
    header('Location: index.php?error=invalid_input');
    exit();
}

try {
    // Lấy thông tin sản phẩm từ bảng products
    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
            p.ten, 
            p.hinh_anh,
            CASE 
                WHEN p.gia_khuyen_mai > 0 THEN p.gia_khuyen_mai
                ELSE p.gia_goc
            END as gia
        FROM products p
        WHERE p.id = ? AND p.active = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header('Location: index.php?error=product_not_found');
        exit();
    }

    // Kiểm tra tồn kho của biến thể (size) cụ thể
    $stmtVar = $pdo->prepare("
        SELECT so_luong_kho 
        FROM product_variants 
        WHERE product_id = ? AND size = ?
    ");
    $stmtVar->execute([$product_id, $size]);
    $variant = $stmtVar->fetch(PDO::FETCH_ASSOC);

    if (!$variant) {
        header('Location: index.php?error=size_not_found');
        exit();
    }

    if ($variant['so_luong_kho'] < $so_luong) {
        header('Location: index.php?error=out_of_stock&max=' . $variant['so_luong_kho']);
        exit();
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Tạo key duy nhất cho sản phẩm + size
    $cart_key = $product_id . '-' . $size;

    // Kiểm tra sản phẩm đã có trong giỏ chưa
    if (isset($_SESSION['cart'][$cart_key])) {
        // Cộng dồn số lượng
        $new_quantity = $_SESSION['cart'][$cart_key]['so_luong'] + $so_luong;

        // Kiểm tra không vượt quá tồn kho
        if ($new_quantity > $variant['so_luong_kho']) {
            header('Location: index.php?error=exceed_stock&max=' . $variant['so_luong_kho']);
            exit();
        }

        $_SESSION['cart'][$cart_key]['so_luong'] = $new_quantity;
    } else {
        // Thêm mới vào giỏ
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $product_id,
            'ten_san_pham' => $product['ten'],
            'gia' => $product['gia'],
            'hinh_anh' => $product['hinh_anh'],
            'size' => $size,
            'so_luong' => $so_luong
        ];
    }

    // Redirect về trang trước với thông báo thành công
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php') . '?added=1');
    exit();
} catch (PDOException $e) {
    error_log("Add to cart error: " . $e->getMessage());
    header('Location: index.php?error=system_error');
    exit();
}
