<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$host = '127.0.0.1';
$port = '3306';
$db   = 'vmilk_takeaway_full';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    $GLOBALS['pdo'] = $pdo;
} catch (PDOException $e) {
    echo "<div style='color:red; border:2px solid red; padding:15px; background:#fff;'>";
    echo "<h3>❌ Lỗi kết nối CSDL:</h3>";
    echo "Thông báo: " . $e->getMessage() . "<br>";
    echo "<b>Hướng dẫn:</b> Vào phpMyAdmin tạo Database tên là <u>$db</u> ngay!";
    echo "</div>";
    exit;
}
