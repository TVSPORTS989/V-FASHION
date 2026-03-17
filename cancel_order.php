<?php
session_start();
// Cấu hình kết nối giống hệt history.php của bạn
$host = "localhost";
$user = "root";
$pass = "";
$db = "vmilk_takeaway_full";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
        header("Location: history.php");
        exit();
    }

    $order_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // 1. Cập nhật trạng thái và ẩn đơn với Admin
    $sql = "UPDATE orders SET trang_thai = 'Đã hủy', is_hidden_admin = 1 
            WHERE id = ? AND user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$order_id, $user_id]);

    // 2. Tự động quay lại trang lịch sử
    header("Location: history.php?status=success");
    exit();
} catch (PDOException $e) {
    die("Lỗi: " . $e->getMessage());
}
