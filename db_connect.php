<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vmilk_takeaway_full";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra lỗi
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập tiếng Việt
$conn->set_charset("utf8mb4");

// LƯU Ý: Không echo gì cả, không close kết nối ở đây!