<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo '<div class="alert alert-danger">Không hợp lệ</div>';
    exit();
}

$order_id = intval($_GET['id']);

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("
    SELECT o.*, u.ho_ten, u.email, u.sdt
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    echo '<div class="alert alert-danger">Không tìm thấy đơn hàng</div>';
    exit();
}

// Lấy chi tiết sản phẩm
$stmtDetails = $pdo->prepare("
    SELECT * FROM order_details WHERE order_id = ?
");
$stmtDetails->execute([$order_id]);
$details = $stmtDetails->fetchAll();
?>

<div class="order-detail-content">
    <div class="mb-4">
        <h6 class="fw-bold mb-3">Thông tin đơn hàng</h6>
        <table class="table table-borderless">
            <tr>
                <td width="40%">Mã đơn hàng:</td>
                <td><strong class="text-primary"><?= $order['ma_don_hang'] ?></strong></td>
            </tr>
            <tr>
                <td>Ngày đặt:</td>
                <td><?= date('d/m/Y H:i', strtotime($order['ngay_dat'])) ?></td>
            </tr>
            <tr>
                <td>Trạng thái:</td>
                <td>
                    <?php
                    $badges = [
                        'pending' => '<span class="badge bg-warning">Chờ xác nhận</span>',
                        'paid' => '<span class="badge bg-info">Đã thanh toán</span>',
                        'confirmed' => '<span class="badge bg-primary">Đã xác nhận</span>',
                        'shipping' => '<span class="badge bg-info">Đang giao</span>',
                        'delivered' => '<span class="badge bg-success">Đã giao</span>',
                        'cancelled' => '<span class="badge bg-danger">Đã hủy</span>'
                    ];
                    echo $badges[$order['trang_thai']] ?? '<span class="badge bg-secondary">Không xác định</span>';
                    ?>
                </td>
            </tr>
            <tr>
                <td>Phương thức thanh toán:</td>
                <td><strong><?= $order['phuong_thuc_thanh_toan'] === 'COD' ? 'COD' : 'Chuyển khoản' ?></strong></td>
            </tr>
        </table>
    </div>

    <div class="mb-4">
        <h6 class="fw-bold mb-3">Thông tin người nhận</h6>
        <table class="table table-borderless">
            <tr>
                <td width="40%">Họ tên:</td>
                <td><?= htmlspecialchars($order['ho_ten']) ?></td>
            </tr>
            <tr>
                <td>Số điện thoại:</td>
                <td><?= htmlspecialchars($order['sdt']) ?></td>
            </tr>
            <tr>
                <td>Email:</td>
                <td><?= htmlspecialchars($order['email']) ?></td>
            </tr>
            <tr>
                <td>Địa chỉ:</td>
                <td><?= htmlspecialchars($order['dia_chi_giao']) ?></td>
            </tr>
            <?php if (!empty($order['ghi_chu'])): ?>
            <tr>
                <td>Ghi chú:</td>
                <td><em><?= htmlspecialchars($order['ghi_chu']) ?></em></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="mb-4">
        <h6 class="fw-bold mb-3">Sản phẩm đã đặt</h6>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Size</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['ten_san_pham']) ?></td>
                        <td><span class="badge bg-secondary"><?= $item['size'] ?></span></td>
                        <td><?= $item['so_luong'] ?></td>
                        <td><?= number_format($item['don_gia']) ?> đ</td>
                        <td><strong><?= number_format($item['thanh_tien']) ?> đ</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mb-3">
        <h6 class="fw-bold mb-3">Chi tiết thanh toán</h6>
        <table class="table table-borderless">
            <tr>
                <td>Tạm tính:</td>
                <td class="text-end"><?= number_format($order['tong_tien']) ?> đ</td>
            </tr>
            <?php if ($order['giam_gia'] > 0): ?>
            <tr style="color: #059669;">
                <td>Giảm giá VIP:</td>
                <td class="text-end">- <?= number_format($order['giam_gia']) ?> đ</td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>VAT (10%):</td>
                <td class="text-end"><?= number_format($order['vat']) ?> đ</td>
            </tr>
            <tr>
                <td>Phí ship:</td>
                <td class="text-end"><?= number_format($order['phi_ship']) ?> đ</td>
            </tr>
            <tr class="fw-bold" style="font-size: 1.2rem; color: #e11d48; border-top: 2px solid #e11d48;">
                <td>Tổng thanh toán:</td>
                <td class="text-end"><?= number_format($order['thanh_toan']) ?> đ</td>
            </tr>
        </table>
    </div>
</div>

<style>
.order-detail-content {
    padding: 10px;
}

.order-detail-content h6 {
    color: #0f172a;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 10px;
}
</style>