<?php
session_start();
require_once 'config.php'; // Gọi file cấu hình database

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['mat_khau'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu!';
    } else {
        try {
            // 1. Tìm tài khoản trong Database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 2. Kiểm tra mật khẩu (đã mã hóa)
            if ($user && password_verify($pass, $user['mat_khau'])) {

                // --- ĐĂNG NHẬP THÀNH CÔNG ---

                // Lưu thông tin vào Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['ho_ten'] = $user['ho_ten'];
                $_SESSION['vai_tro'] = $user['vai_tro'];

                // 3. CHUYỂN HƯỚNG THEO QUYỀN (QUAN TRỌNG)
                if ($user['vai_tro'] === 'admin') {
                    // Nếu là Admin -> Vào file admin.php (nằm cùng thư mục)
                    header('Location: admin.php');
                } else {
                    // Nếu là Khách -> Về trang chủ
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Sai email hoặc mật khẩu!';
            }
        } catch (PDOException $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị</title>
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

        .login-card {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-radius: 20px;
            transition: transform 0.3s ease;
            background: white;
            width: 100%;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            text-align: center;
            padding: 25px;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
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
    <div class="container d-flex justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card login-card">
                <div class="card-header">
                    <h2 class="mb-0 fs-3"><i class="bi bi-shield-lock me-2"></i>Đăng Nhập</h2>
                    <small class="opacity-75">Fashion Store</small>
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
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="Email" autocomplete="off"
                                required>
                        </div>

                        <div class="input-group mb-4">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="mat_khau" class="form-control" placeholder="Mật khẩu"
                                autocomplete="new-password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Truy cập hệ thống
                        </button>
                    </form>

                    <div class="links text-center mt-3">
                        <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
                    </div>
                    <div class="text-center mt-2 border-top pt-3">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-house-door-fill me-1"></i>Về Trang Chủ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>