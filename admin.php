<?php
session_start();

// --- CẤU HÌNH KẾT NỐI DATABASE ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "vmilk_takeaway_full";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("<div style='color:red; padding:20px;'>Lỗi kết nối: " . $e->getMessage() . "</div>");
}

// =========================================================================
// --- BẢO MẬT: CHỈ CHO PHÉP ADMIN CỤ THỂ TRUY CẬP ---
// =========================================================================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmtCheck = $pdo->prepare("SELECT email, vai_tro FROM users WHERE id = ?");
$stmtCheck->execute([$_SESSION['user_id']]);
$currentUser = $stmtCheck->fetch();

$allowed_email = 'votruongvinh2004@gmail.com';

if (!$currentUser || $currentUser['email'] !== $allowed_email || $currentUser['vai_tro'] !== 'admin') {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h1 style='color:red;'>TRUY CẬP BỊ TỪ CHỐI!</h1>
            <p>Trang này chỉ dành riêng cho Admin: <strong>$allowed_email</strong></p>
            <a href='index.php'>Quay về trang chủ</a>
         </div>");
}

// =========================================================================
// --- XỬ LÝ LOGIC (PHP) ---
// =========================================================================

// 1. Xóa sản phẩm
if (isset($_GET['delete_product'])) {
    $id = $_GET['delete_product'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header("Location: admin.php?tab=products&deleted=1");
    exit();
}

// 2. Thêm sản phẩm
if (isset($_POST['add_product'])) {
    $ten    = $_POST['name'];
    $gia    = $_POST['price'];
    $mota   = $_POST['desc'];
    $cat_id = $_POST['category_id'];
    $image  = "default.jpg";
    if (!empty($_FILES['image']['name'])) {
        $image = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $image);
    }
    $sql = "INSERT INTO products (ten, gia_goc, mo_ta, hinh_anh, category_id, active, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())";
    $pdo->prepare($sql)->execute([$ten, $gia, $mota, $image, $cat_id]);
    header("Location: admin.php?tab=products&added=1");
    exit();
}

// 3. Sửa sản phẩm
if (isset($_POST['edit_product'])) {
    $id     = $_POST['product_id'];
    $ten    = $_POST['name'];
    $gia    = $_POST['price'];
    $mota   = $_POST['desc'];
    $cat_id = $_POST['category_id'];
    $stmtOld = $pdo->prepare("SELECT hinh_anh FROM products WHERE id = ?");
    $stmtOld->execute([$id]);
    $oldImg = $stmtOld->fetchColumn();
    $image  = $oldImg;
    if (!empty($_FILES['image']['name'])) {
        $image = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], "images/" . $image);
    }
    $sql = "UPDATE products SET ten = ?, gia_goc = ?, mo_ta = ?, hinh_anh = ?, category_id = ? WHERE id = ?";
    $pdo->prepare($sql)->execute([$ten, $gia, $mota, $image, $cat_id, $id]);
    header("Location: admin.php?tab=products&updated=1");
    exit();
}

// 4. Cập nhật trạng thái đơn hàng (CHỈ DUYỆT)
if (isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id_to_update'] ?? 0);
    if ($order_id > 0) {
        $stmt = $pdo->prepare("UPDATE orders SET trang_thai = 'Duyệt' WHERE id = ?");
        $stmt->execute([$order_id]);
    }
    header("Location: admin.php?tab=orders&updated=1");
    exit();
}

// 5. Xóa User
if (isset($_GET['delete_user'])) {
    $id = $_GET['delete_user'];
    if ($id != $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
    }
    header("Location: admin.php?tab=users");
    exit();
}

// 6. Xóa Review
if (isset($_GET['delete_review'])) {
    $id = $_GET['delete_review'];
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
    header("Location: admin.php?tab=reviews");
    exit();
}

$tab = $_GET['tab'] ?? 'dashboard';

// =========================================================================
// --- PHÂN TRANG SẢN PHẨM ---
// =========================================================================
$products_per_page = 10;
$current_page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset        = ($current_page - 1) * $products_per_page;
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_pages   = ceil($total_products / $products_per_page);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V FASHION - Administrator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Montserrat:wght@400;600&display=swap"
        rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Montserrat', sans-serif;
        }

        .sidebar {
            width: 250px;
            background: #111827;
            min-height: 100vh;
            position: fixed;
            color: white;
            z-index: 100;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            text-align: center;
            padding: 30px 0;
            color: #d4af37;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .nav-link {
            color: #9ca3af;
            padding: 15px 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(212, 175, 55, 0.1);
            color: #d4af37;
            border-left: 4px solid #d4af37;
        }

        .nav-link i {
            width: 30px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .card-vip {
            border: none;
            border-radius: 10px;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header-vip {
            background: white;
            padding: 20px;
            font-weight: bold;
            font-size: 1.1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }

        .stars {
            color: #fbbf24;
        }

        .alert-float {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .page-link {
            color: #111827;
        }

        .page-item.active .page-link {
            background-color: #d4af37;
            border-color: #d4af37;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-crown me-2"></i>V FASHION</div>
        <nav class="nav flex-column mt-4">
            <a href="?tab=dashboard" class="nav-link <?= $tab == 'dashboard' ? 'active' : '' ?>"><i
                    class="fas fa-chart-pie"></i> Tổng quan</a>
            <a href="?tab=products" class="nav-link <?= $tab == 'products'  ? 'active' : '' ?>"><i
                    class="fas fa-tshirt"></i> Sản phẩm</a>
            <a href="?tab=orders" class="nav-link <?= $tab == 'orders'    ? 'active' : '' ?>"><i
                    class="fas fa-shopping-cart"></i> Đơn hàng</a>
            <a href="?tab=users" class="nav-link <?= $tab == 'users'     ? 'active' : '' ?>"><i
                    class="fas fa-users"></i> Khách hàng</a>
            <a href="?tab=reviews" class="nav-link <?= $tab == 'reviews'   ? 'active' : '' ?>"><i
                    class="fas fa-star"></i> Đánh giá</a>
            <a href="index.php" class="nav-link mt-5 text-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        </nav>
    </div>

    <div class="main-content">

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show alert-float">
                <i class="fas fa-check-circle me-2"></i><strong>Thành công!</strong> Đã cập nhật.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show alert-float">
                <i class="fas fa-check-circle me-2"></i><strong>Thành công!</strong> Đã thêm sản phẩm.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-danger alert-dismissible fade show alert-float">
                <i class="fas fa-trash me-2"></i><strong>Đã xóa!</strong> Sản phẩm đã được xóa.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <script>
            setTimeout(() => {
                document.querySelectorAll('.alert-float').forEach(el => el.remove());
            }, 3000);
        </script>

        <?php
        // =========================================================================
        // TAB: DASHBOARD
        // =========================================================================
        if ($tab == 'dashboard'):
            $count_p = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            $count_u = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

            // Lọc đơn hủy: dùng is_hidden_admin = 1 (cột cancel_order.php set)
            // Nếu cột chưa tồn tại thì fallback về trang_thai != 'Đã hủy'
            try {
                $count_o = $pdo->query("SELECT COUNT(*) FROM orders WHERE (is_hidden_admin IS NULL OR is_hidden_admin = 0)")->fetchColumn();
                $rev     = $pdo->query("SELECT SUM(tong_tien) FROM orders WHERE (is_hidden_admin IS NULL OR is_hidden_admin = 0)")->fetchColumn() ?: 0;
            } catch (Exception $e) {
                // Fallback nếu cột is_hidden_admin chưa có
                $count_o = $pdo->query("SELECT COUNT(*) FROM orders WHERE trang_thai != 'Đã hủy' OR trang_thai IS NULL")->fetchColumn();
                $rev     = $pdo->query("SELECT SUM(tong_tien) FROM orders WHERE trang_thai != 'Đã hủy' OR trang_thai IS NULL")->fetchColumn() ?: 0;
            }
        ?>
            <h3 class="fw-bold mb-4">Dashboard</h3>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="card-vip p-4 border-start border-4 border-warning">
                        <small class="text-muted">DOANH THU</small>
                        <h3 class="fw-bold text-dark mt-2"><?= number_format($rev) ?> đ</h3>
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Không bao gồm đơn đã hủy</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-vip p-4 border-start border-4 border-primary">
                        <small class="text-muted">ĐƠN HÀNG</small>
                        <h3 class="fw-bold text-dark mt-2"><?= $count_o ?></h3>
                        <small class="text-muted"><i class="fas fa-info-circle"></i> Không bao gồm đơn đã hủy</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-vip p-4 border-start border-4 border-success">
                        <small class="text-muted">SẢN PHẨM</small>
                        <h3 class="fw-bold text-dark mt-2"><?= $count_p ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card-vip p-4 border-start border-4 border-danger">
                        <small class="text-muted">KHÁCH HÀNG</small>
                        <h3 class="fw-bold text-dark mt-2"><?= $count_u ?></h3>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // =========================================================================
        // TAB: SẢN PHẨM
        // =========================================================================
        if ($tab == 'products'):
            $limit_value  = (int)$products_per_page;
            $offset_value = (int)$offset;
            $stmt = $pdo->query("SELECT p.*, c.ten as cat_name FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             ORDER BY p.id DESC LIMIT $limit_value OFFSET $offset_value");
        ?>
            <div class="card-vip">
                <div class="card-header-vip">
                    <span>Danh Sách Sản Phẩm (Trang <?= $current_page ?>/<?= max($total_pages, 1) ?>)</span>
                    <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#modalAdd">
                        <i class="fas fa-plus"></i> Thêm Mới
                    </button>
                </div>
                <div class="p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Hình</th>
                                <th>Tên sản phẩm</th>
                                <th>Giá gốc</th>
                                <th>Danh mục</th>
                                <th>Trạng thái</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $stmt->fetch()):
                                $img_src = !empty($row['hinh_anh'])
                                    ? 'images/' . htmlspecialchars($row['hinh_anh'])
                                    : 'https://via.placeholder.com/60x80?text=No+Img';
                            ?>
                                <tr>
                                    <td class="ps-4"><img src="<?= $img_src ?>"
                                            onerror="this.src='https://via.placeholder.com/60x80?text=Error'"></td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['ten']) ?></td>
                                    <td class="text-danger fw-bold"><?= number_format($row['gia_goc']) ?> đ</td>
                                    <td><span class="badge bg-secondary"><?= $row['cat_name'] ?? 'N/A' ?></span></td>
                                    <td><?= ($row['active'] == 1) ? '<span class="badge bg-success">Hiện</span>' : '<span class="badge bg-secondary">Ẩn</span>' ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary border-0" data-bs-toggle="modal"
                                            data-bs-target="#modalEdit<?= $row['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?delete_product=<?= $row['id'] ?>&tab=products"
                                            onclick="return confirm('Xóa sản phẩm này?')"
                                            class="btn btn-sm btn-outline-danger border-0">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Modal Sửa -->
                                <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Sửa Sản Phẩm</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
                                                    <div class="mb-3"><label>Tên sản phẩm</label><input type="text" name="name"
                                                            class="form-control" value="<?= htmlspecialchars($row['ten']) ?>"
                                                            required></div>
                                                    <div class="mb-3"><label>Giá gốc</label><input type="number" name="price"
                                                            class="form-control" value="<?= $row['gia_goc'] ?>" required></div>
                                                    <div class="mb-3"><label>Danh mục (ID)</label><input type="number"
                                                            name="category_id" class="form-control"
                                                            value="<?= $row['category_id'] ?>" required></div>
                                                    <div class="mb-3"><label>Hình ảnh hiện tại</label><br><img
                                                            src="<?= $img_src ?>" style="width:100px;height:auto;"></div>
                                                    <div class="mb-3"><label>Thay đổi hình ảnh (nếu muốn)</label><input
                                                            type="file" name="image" class="form-control"></div>
                                                    <div class="mb-3"><label>Mô tả</label><textarea name="desc"
                                                            class="form-control"><?= htmlspecialchars($row['mo_ta']) ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" name="edit_product" class="btn btn-primary">Cập
                                                        nhật</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center p-3">
                            <ul class="pagination mb-0">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item"><a class="page-link"
                                            href="?tab=products&page=<?= $current_page - 1 ?>">Trước</a></li>
                                <?php endif; ?>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="?tab=products&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item"><a class="page-link"
                                            href="?tab=products&page=<?= $current_page + 1 ?>">Sau</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // =========================================================================
        // TAB: ĐƠN HÀNG
        // *** FIX CHÍNH: Lọc theo is_hidden_admin = 1 (set bởi cancel_order.php) ***
        // Fallback thêm trang_thai != 'Đã hủy' để chắc chắn 100%
        // =========================================================================
        if ($tab == 'orders'):
            try {
                $stmt = $pdo->prepare(
                    "SELECT o.id as madonhang, o.*, u.ho_ten, u.sdt, u.email, u.dia_chi as diachi_user
                 FROM orders o
                 LEFT JOIN users u ON o.user_id = u.id
                 WHERE (o.is_hidden_admin IS NULL OR o.is_hidden_admin = 0)
                   AND (o.trang_thai IS NULL OR o.trang_thai != 'Đã hủy')
                 ORDER BY o.id DESC"
                );
                $stmt->execute();
            } catch (Exception $e) {
                // Fallback nếu cột is_hidden_admin chưa tồn tại trong DB
                $stmt = $pdo->prepare(
                    "SELECT o.id as madonhang, o.*, u.ho_ten, u.sdt, u.email, u.dia_chi as diachi_user
                 FROM orders o
                 LEFT JOIN users u ON o.user_id = u.id
                 WHERE o.trang_thai IS NULL OR o.trang_thai != 'Đã hủy'
                 ORDER BY o.id DESC"
                );
                $stmt->execute();
            }
        ?>
            <div class="card-vip">
                <div class="card-header-vip">
                    Quản Lý Đơn Hàng
                    <small class="text-muted fw-normal fs-6 ms-2">(Đã ẩn đơn khách hủy)</small>
                </div>
                <div class="p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $stmt->fetch()):
                                $khach_hang    = $row['ho_ten'] ?? 'Khách vãng lai';
                                $sdt_khach     = $row['sdt'] ?? '---';
                                $dia_chi_giao  = !empty($row['dia_chi']) ? $row['dia_chi'] : ($row['diachi_user'] ?? 'Chưa có địa chỉ');
                                $current_color = ($row['trang_thai'] == 'Duyệt') ? 'success' : 'warning';
                            ?>
                                <tr>
                                    <td class="ps-4">#<?= $row['madonhang'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($khach_hang) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($sdt_khach) ?></small>
                                    </td>
                                    <td class="text-danger fw-bold"><?= number_format($row['tong_tien']) ?> đ</td>
                                    <td>
                                        <?php if ($row['trang_thai'] == 'Duyệt'): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Đã duyệt</span>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="update_order_status" value="1">
                                                <input type="hidden" name="order_id_to_update" value="<?= $row['madonhang'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Xác nhận duyệt đơn hàng #<?= $row['madonhang'] ?>?')">
                                                    <i class="fas fa-check me-1"></i>Duyệt đơn
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal"
                                            data-bs-target="#orderDetail<?= $row['madonhang'] ?>">
                                            <i class="fas fa-eye"></i> Xem
                                        </button>

                                        <!-- Modal Chi tiết -->
                                        <div class="modal fade" id="orderDetail<?= $row['madonhang'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Đơn hàng #<?= $row['madonhang'] ?> -
                                                            <?= htmlspecialchars($khach_hang) ?></h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <span
                                                                class="badge bg-<?= $current_color ?> fs-6"><?= $row['trang_thai'] ?? 'Mới đặt' ?></span>
                                                        </div>
                                                        <h6 class="fw-bold mb-3"><i
                                                                class="fas fa-user text-primary me-2"></i>Thông tin khách hàng
                                                        </h6>
                                                        <div class="mb-3 ps-4">
                                                            <p class="mb-2"><strong>Họ tên:</strong>
                                                                <?= htmlspecialchars($khach_hang) ?></p>
                                                            <p class="mb-2"><strong>SĐT:</strong>
                                                                <?= htmlspecialchars($sdt_khach) ?></p>
                                                            <p class="mb-2"><strong>Email:</strong>
                                                                <?= htmlspecialchars($row['email'] ?? 'N/A') ?></p>
                                                            <p class="mb-0"><i
                                                                    class="fas fa-map-marker-alt text-danger me-2"></i><strong>Địa
                                                                    chỉ giao:</strong> <?= htmlspecialchars($dia_chi_giao) ?>
                                                            </p>
                                                        </div>
                                                        <p><i class="fas fa-clock text-primary me-2"></i><strong>Ngày
                                                                đặt:</strong> <?= $row['ngay_dat'] ?></p>
                                                        <hr>
                                                        <h6 class="fw-bold mb-3">Chi tiết đơn hàng:</h6>
                                                        <table class="table table-bordered table-sm">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Sản phẩm</th>
                                                                    <th>Size</th>
                                                                    <th>SL</th>
                                                                    <th>Đơn giá</th>
                                                                    <th>Thành tiền</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $stmt2 = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                                                                $stmt2->execute([$row['madonhang']]);
                                                                while ($item = $stmt2->fetch()):
                                                                    $thanh_tien = $item['gia_luc_mua'] * $item['so_luong'];
                                                                ?>
                                                                    <tr>
                                                                        <td><?= htmlspecialchars($item['ten_sp_luc_mua']) ?></td>
                                                                        <td><?= htmlspecialchars($item['size_luc_mua']) ?></td>
                                                                        <td class="text-center"><?= $item['so_luong'] ?></td>
                                                                        <td class="text-end">
                                                                            <?= number_format($item['gia_luc_mua']) ?> đ</td>
                                                                        <td class="text-end fw-bold text-danger">
                                                                            <?= number_format($thanh_tien) ?> đ</td>
                                                                    </tr>
                                                                <?php endwhile; ?>
                                                            </tbody>
                                                        </table>
                                                        <div class="text-end h5 text-danger mt-3">
                                                            <strong>Tổng cộng: <?= number_format($row['tong_tien']) ?>
                                                                đ</strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // =========================================================================
        // TAB: KHÁCH HÀNG
        // =========================================================================
        if ($tab == 'users'):
            $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
        ?>
            <div class="card-vip">
                <div class="card-header-vip">Danh Sách Khách Hàng</div>
                <div class="p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Họ tên</th>
                                <th>Liên hệ</th>
                                <th>Địa chỉ</th>
                                <th>Vai trò</th>
                                <th>Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $stmt->fetch()): ?>
                                <tr>
                                    <td class="ps-4"><?= $row['id'] ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($row['ho_ten']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($row['email']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($row['sdt']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($row['dia_chi']) ?></td>
                                    <td><?= ($row['vai_tro'] == 'admin') ? '<span class="badge bg-dark">Admin</span>' : '<span class="badge bg-light text-dark border">User</span>' ?>
                                    </td>
                                    <td>
                                        <?php if ($row['vai_tro'] != 'admin'): ?>
                                            <a href="?delete_user=<?= $row['id'] ?>" onclick="return confirm('Xóa user này?')"
                                                class="text-danger">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php
        // =========================================================================
        // TAB: ĐÁNH GIÁ
        // =========================================================================
        if ($tab == 'reviews'):
            $stmt = $pdo->query("SELECT r.*, u.ho_ten, p.ten 
                             FROM reviews r 
                             LEFT JOIN users u ON r.user_id = u.id 
                             LEFT JOIN products p ON r.product_id = p.id 
                             ORDER BY r.id DESC");
        ?>
            <div class="card-vip">
                <div class="card-header-vip">Đánh Giá Từ Khách Hàng</div>
                <div class="p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Sản phẩm</th>
                                <th>Khách hàng</th>
                                <th>Đánh giá</th>
                                <th>Nội dung</th>
                                <th>Ngày</th>
                                <th>Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $stmt->fetch()):
                                $stars = '';
                                for ($i = 1; $i <= 5; $i++) {
                                    $stars .= ($i <= $row['so_sao']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                            ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary"><?= htmlspecialchars($row['ten'] ?? 'Đã xóa') ?></td>
                                    <td><?= htmlspecialchars($row['ho_ten'] ?? 'Ẩn danh') ?></td>
                                    <td class="stars"><?= $stars ?></td>
                                    <td><?= htmlspecialchars($row['noi_dung']) ?></td>
                                    <td class="text-muted small"><?= $row['ngay_dang'] ?></td>
                                    <td>
                                        <a href="?delete_review=<?= $row['id'] ?>" onclick="return confirm('Xóa đánh giá này?')"
                                            class="btn btn-sm btn-outline-danger border-0">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Modal Thêm Sản Phẩm -->
    <div class="modal fade" id="modalAdd" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Thêm Sản Phẩm</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Tên sản phẩm</label><input type="text" name="name" class="form-control"
                                required></div>
                        <div class="mb-3"><label>Giá gốc</label><input type="number" name="price" class="form-control"
                                required></div>
                        <div class="mb-3"><label>Danh mục (ID)</label><input type="number" name="category_id"
                                class="form-control" value="1" required></div>
                        <div class="mb-3"><label>Hình ảnh</label><input type="file" name="image" class="form-control"
                                required></div>
                        <div class="mb-3"><label>Mô tả</label><textarea name="desc" class="form-control"
                                rows="3"></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_product" class="btn btn-dark"><i
                                class="fas fa-save me-2"></i>Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>