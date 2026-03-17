<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'config.php';

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id <= 0) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY ngay_tao DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch Sử Đơn Hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">
        <h2>Lịch Sử Đơn Hàng</h2>
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">Bạn chưa có đơn hàng nào.</div>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày tạo</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                        <th>Đánh giá</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= $o['ngay_tao'] ?></td>
                            <td><?= $o['payment_method'] ?></td>
                            <td><?= ucfirst($o['trang_thai']) ?></td>
                            <td><?= number_format($o['tong_tien']) ?> đ</td>
                            <td>
                                <a href="review.php?order_id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-success">Đánh
                                    giá</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>

</html>