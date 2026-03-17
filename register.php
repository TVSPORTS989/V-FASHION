<?php
session_start();
require 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = trim($_POST['ten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['mat_khau'] ?? '';
    $sdt = trim($_POST['sdt'] ?? '');
    $dia_chi = trim($_POST['dia_chi'] ?? '');

    if (empty($ten) || empty($email) || empty($pass)) {
        $error = 'Vui lòng nhập đầy đủ họ tên, email và mật khẩu!';
    } else {
        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("INSERT INTO users (ho_ten, email, mat_khau, vai_tro, sdt, dia_chi, vi_tien) 
                                   VALUES (?, ?, ?, 'customer', ?, ?, 0)");
            $stmt->execute([$ten, $email, $hashed_pass, $sdt, $dia_chi]);
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            $error = "Lỗi: Email đã tồn tại hoặc dữ liệu không hợp lệ.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Vmilk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-card {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            transition: transform 0.3s ease;
        }

        .register-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            text-align: center;
            padding: 25px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }

        .links a {
            color: #667eea;
            font-weight: 500;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="col-md-6 col-lg-5">
        <div class="card register-card">
            <div class="card-header">
                <h2 class="mb-0"><i class="bi bi-person-plus me-2"></i>Đăng Ký Tài Khoản</h2>
                <small class="opacity-75">Tạo tài khoản mới để bắt đầu!</small>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                <form method="POST" autocomplete="off">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="ten" class="form-control" placeholder="Họ và tên" autocomplete="off"
                            required>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Email" autocomplete="off"
                            required>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="mat_khau" class="form-control" placeholder="Mật khẩu"
                            autocomplete="new-password" required>
                    </div>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="sdt" class="form-control" placeholder="Số điện thoại"
                            autocomplete="off">
                    </div>
                    <div class="input-group mb-4">
                        <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                        <textarea name="dia_chi" class="form-control" placeholder="Địa chỉ" rows="3"
                            autocomplete="off"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-check-circle me-2"></i>Đăng Ký
                    </button>
                </form>
                <div class="links text-center mt-3">
                    <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
                </div>
                <div class="text-center mt-2">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-house-door-fill me-1"></i>Về Trang Chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>