<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die('Lỗi');

$donhang_id = (int)$_POST['donhang_id'];
$rating     = (int)$_POST['rating'];
$noidung    = $conn->real_escape_string($_POST['noidung']);

// Kiểm tra đơn hợp lệ + chưa review
$check = $conn->query("SELECT khachhang_id FROM donhang WHERE id=$donhang_id AND trangthai IN('hoantat','danhan')")->fetch_assoc();
if (!$check || $conn->query("SELECT id FROM danhgia WHERE donhang_id=$donhang_id")->num_rows > 0) {
    die("Đơn hàng không hợp lệ hoặc đã được đánh giá!");
}

// Upload ảnh
$imgs = ['', '', ''];
$dir = 'images/reviews/';
if (!is_dir($dir)) mkdir($dir, 0777, true);

foreach ($_FILES['img']['name'] as $k => $name) {
    if ($k >= 3) break;
    $ext = pathinfo($name, PATHINFO_EXTENSION);
    $newname = $donhang_id . '_' . time() . '_' . ($k + 1) . '.' . $ext;
    move_uploaded_file($_FILES['img']['tmp_name'][$k], $dir . $newname);
    $imgs[$k] = $dir . $newname;
}

$sql = "INSERT INTO danhgia (donhang_id, khachhang_id, sosao, noidung, hinhanh1, hinhanh2, hinhanh3, ngay_danhgia) 
        VALUES ($donhang_id, {$check['khachhang_id']}, $rating, '$noidung', '{$imgs[0]}','{$imgs[1]}','{$imgs[2]}', NOW())";

if ($conn->query($sql)) {
    echo "Cảm ơn bạn nhiều lắm ạ!!! ❤️\nVoucher 30k đã gửi qua Zalo rồi nha, lần sau ghé tiếp nhé!";
    // Ở đây bạn thêm gửi Zalo OA / SMS tự động nếu có
} else {
    echo "Lỗi hệ thống, thử lại nha!";
}
