<?php
// Bắt đầu session để có thể sử dụng các biến session hoặc kết nối DB
session_start();

// 1. INCLUDE CONFIGURATION VÀ KẾT NỐI DATABASE
require 'config.php'; // Chứa $pdo, các hàm như isStaff(), getCSRF()

// 2. KIỂM TRA QUYỀN TRUY CẬP (Nên có để đảm bảo bảo mật)
// Tùy chọn: Bạn có thể bỏ qua bước này nếu đã kiểm tra isStaff() ở admin.php 
// nhưng để tăng cường bảo mật cho API endpoint, nên kiểm tra lại.
// if (!isStaff()) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Truy cập bị từ chối.']);
//     exit;
// }

// 3. XỬ LÝ REQUEST
header('Content-Type: application/json'); // Đảm bảo trả về JSON

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Thiếu hoặc ID sản phẩm không hợp lệ.']);
    exit;
}

$product_id = (int)$_GET['id'];

try {
    // 4. TRUY VẤN DATABASE ĐỂ LẤY DỮ LIỆU
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        // 5. TRẢ VỀ DỮ LIỆU SẢN PHẨM DƯỚI DẠNG JSON
        echo json_encode($product);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Không tìm thấy sản phẩm.']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    // Tùy chọn: không nên hiển thị $e->getMessage() trong môi trường production
    echo json_encode(['error' => 'Lỗi kết nối hoặc truy vấn database: ' . $e->getMessage()]);
}
