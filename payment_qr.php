<?php
// 1. KẾT NỐI DATABASE
require_once 'config.php';

// Kiểm tra mã đơn hàng
if (!isset($_GET['order_id'])) die('Lỗi: Thiếu mã đơn hàng!');
$order_id = $_GET['order_id'];

// 2. LẤY THÔNG TIN CHUNG (Từ bảng orders)
// (Lưu ý: Nếu bảng orders của bạn lưu tên khách là 'ho_ten' thì sửa 'thong_tin' bên dưới cho phù hợp)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) die('Không tìm thấy đơn hàng!');

// 3. LẤY CHI TIẾT SẢN PHẨM (Từ bảng order_items) - QUAN TRỌNG
// Code này khớp với file cart.php bạn đã gửi trước đó
$stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Nếu vẫn không có dữ liệu thật thì báo lỗi để dễ sửa
if (empty($items)) {
    // Có thể do lúc Insert bị lỗi, tạm thời để mảng rỗng
    $items = [];
}

// --- CẤU HÌNH QR ---
$ngan_hang = 'OCB';
$stk       = '0848959556';
$ten_tk    = 'VO TRUONG VINH';
$so_tien   = $order['tong_tien']; // Code cũ bạn dùng 'tong_tien'
$noi_dung  = "THANHTOAN " . $order_id;
$qr_url    = "https://img.vietqr.io/image/{$ngan_hang}-{$stk}-compact.png?amount={$so_tien}&addInfo=" . urlencode($noi_dung) . "&accountName=" . urlencode($ten_tk);

// Xử lý thông tin khách hàng từ cột 'thong_tin' (Vì code insert cũ bạn gộp chung vào 1 cột)
// Nếu bạn đã tách cột ho_ten, sdt riêng thì sửa lại nhé.
$thong_tin_khach = $order['thong_tin'] ?? 'Khách lẻ';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thanh Toán Đơn #<?php echo $order_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        background: #f4f6f8;
        padding: 20px;
        font-family: 'Segoe UI', sans-serif;
    }

    .invoice-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .bg-left {
        background: #fff;
        padding: 30px;
    }

    .bg-right {
        background: #f8f9fa;
        padding: 30px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-left: 1px solid #eee;
    }

    .table-custom th {
        background: #f1f3f5;
        font-size: 0.85rem;
        text-transform: uppercase;
        color: #555;
    }

    .badge-size {
        background: #e9ecef;
        color: #333;
        font-weight: bold;
        border: 1px solid #ccc;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
    }

    .qr-frame {
        padding: 8px;
        border: 2px dashed #6f42c1;
        border-radius: 10px;
        background: #fff;
    }

    .qr-img {
        width: 100%;
        max-width: 220px;
    }

    /* CSS IN ẤN */
    @media print {
        body {
            background: white;
            padding: 0;
        }

        .bg-right,
        .btn-action {
            display: none !important;
        }

        /* Ẩn QR khi in */
        .bg-left {
            width: 100% !important;
            flex: 0 0 100%;
            max-width: 100%;
            padding: 0;
            border: none;
        }

        .invoice-card {
            box-shadow: none;
        }
    }
    </style>
</head>

<body>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="invoice-card row g-0">

                    <div class="col-lg-7 bg-left">
                        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                            <h4 class="fw-bold m-0 text-uppercase">Hóa Đơn Bán Hàng</h4>
                            <div class="text-end">
                                <div class="fw-bold fs-5">#<?php echo $order_id; ?></div>
                                <div class="text-muted small">
                                    <?php echo date('d/m/Y H:i', strtotime($order['ngay_dat'])); ?></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold text-muted small text-uppercase">Thông tin đơn hàng:</label>
                            <div class="p-2 bg-light rounded border">
                                <?php echo nl2br($thong_tin_khach); ?>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="text-center">SL</th>
                                        <th class="text-end">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($items) > 0): ?>
                                    <?php foreach ($items as $item):
                                            // TÍNH TOÁN DỰA TRÊN TÊN CỘT TRONG CART.PHP CỦA BẠN
                                            $ten_sp = $item['ten_sp_luc_mua'];
                                            $size   = $item['size_luc_mua'];
                                            $gia    = $item['gia_luc_mua'];
                                            $sl     = $item['so_luong'];
                                            $total  = $gia * $sl;
                                        ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo $ten_sp; ?></div>
                                            <?php if ($size): ?>
                                            <small class="text-muted">Size: <span
                                                    class="badge-size"><?php echo $size; ?></span></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center fw-bold"><?php echo $sl; ?></td>
                                        <td class="text-end"><?php echo number_format($total); ?>đ</td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-danger">Không tìm thấy chi tiết sản phẩm
                                            trong CSDL!</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="border-top border-dark border-2">
                                        <td colspan="2" class="fw-bold pt-3 text-end">TỔNG CỘNG:</td>
                                        <td class="pt-3 text-end fw-bold fs-4 text-danger">
                                            <?php echo number_format($order['tong_tien']); ?>đ
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-4 text-center text-muted fst-italic small btn-action">
                            (Cảm ơn quý khách đã mua hàng tại V Fashion! Hãy giữ lại hóa đơn này để đối chiếu khi cần
                            thiết.)
                        </div>
                    </div>

                    <div class="col-lg-5 bg-right">
                        <h5 class="fw-bold text-uppercase mb-3 text-primary">Thanh Toán QR</h5>

                        <div class="qr-frame mb-3">
                            <img src="<?php echo $qr_url; ?>" class="qr-img" alt="QR Code">
                        </div>

                        <div class="w-100 px-3">
                            <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                                <span class="text-muted">Ngân hàng:</span>
                                <span class="fw-bold"><?php echo $ngan_hang; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small border-bottom pb-2">
                                <span class="text-muted">Chủ TK:</span>
                                <span class="fw-bold text-uppercase"><?php echo $ten_tk; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 small border-bottom pb-2">
                                <span class="text-muted">Số TK:</span>
                                <span class="fw-bold text-primary"><?php echo $stk; ?> <i class="fa fa-copy"
                                        style="cursor:pointer"
                                        onclick="navigator.clipboard.writeText('<?php echo $stk; ?>')"></i></span>
                            </div>

                            <div class="alert alert-warning text-center p-2 mb-0" style="font-size: 0.9rem;">
                                Nội dung CK: <strong class="text-danger"><?php echo $noi_dung; ?></strong>
                            </div>
                        </div>

                        <div class="d-grid gap-2 w-100 mt-4 btn-action">
                            <button onclick="window.print()" class="btn btn-dark"><i class="fa fa-print me-2"></i> In
                                Hóa Đơn</button>
                            <a href="index.php" class="btn btn-outline-secondary">Quay lại trang chủ</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</body>

</html>