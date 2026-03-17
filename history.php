<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db   = "vmilk_takeaway_full";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Lỗi kết nối DB");
}

if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập");
}
$user_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Lịch sử đơn hàng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .thumb-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .btn-cancel {
            font-size: 0.85rem;
            padding: 2px 10px;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container mt-4">
        <h3 class="mb-4">Lịch sử đơn hàng</h3>

        <?php
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        $orders = $stmt->fetchAll();

        if (!$orders) {
            echo "<div class='alert alert-warning'>Bạn chưa có đơn hàng nào.</div>";
        }

        foreach ($orders as $order):
            $is_canceled = ($order['trang_thai'] == 'Đã hủy');
        ?>
            <div class="card mb-3 shadow-sm" style="<?= $is_canceled ? 'opacity: 0.8;' : '' ?>">
                <div class="card-header d-flex justify-content-between bg-white align-items-center">
                    <div>
                        <strong>Đơn hàng #<?= $order['id'] ?></strong>
                        <span class="badge <?= $is_canceled ? 'bg-secondary' : 'bg-info text-dark' ?> ms-2">
                            <?= $order['trang_thai'] ?? 'Mới đặt' ?>
                        </span>
                    </div>
                    <span class="fw-bold text-danger">
                        Tổng: <?= number_format($order['tong_tien']) ?>đ
                    </span>
                </div>

                <div class="card-body">
                    <p class="small text-muted mb-2">Ngày đặt: <?= $order['ngay_dat'] ?></p>

                    <?php
                    $sql = "SELECT oi.*, p.hinh_anh FROM order_items oi 
                            JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
                    $stmtItems = $pdo->prepare($sql);
                    $stmtItems->execute([$order['id']]);
                    $items = $stmtItems->fetchAll();

                    foreach ($items as $item):
                        $img_src = !empty($item['hinh_anh']) ? 'images/' . htmlspecialchars($item['hinh_anh']) : 'https://via.placeholder.com/60?text=No+Img';
                    ?>
                        <div class="d-flex align-items-center mb-2 border-bottom pb-2">
                            <img src="<?= $img_src ?>" class="thumb-img me-3"
                                onerror="this.src='https://via.placeholder.com/60?text=No+Img'">
                            <div class="flex-grow-1">
                                <div class="fw-bold small"><?= htmlspecialchars($item['ten_sp_luc_mua']) ?></div>
                                <div class="small text-muted">Size: <?= htmlspecialchars($item['size_luc_mua']) ?> | SL:
                                    x<?= (int)$item['so_luong'] ?></div>
                            </div>
                            <div class="text-end small fw-bold"><?= number_format($item['gia_luc_mua']) ?>đ</div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-end mt-2">
                        <?php if (!$is_canceled): ?>
                            <a href="cancel_order.php?id=<?= $order['id'] ?>" class="btn btn-outline-danger btn-cancel"
                                onclick="return confirm('Bạn có chắc muốn hủy đơn này? Admin sẽ không thấy đơn này nữa.')">
                                Hủy đơn
                            </a>
                        <?php else: ?>
                            <small class="text-danger italic">Đơn hàng đã được hủy và ẩn với Admin</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>

</html>