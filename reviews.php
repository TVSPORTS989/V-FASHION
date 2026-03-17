<?php
session_start();
// KẾT NỐI DATABASE
$pdo = new PDO("mysql:host=localhost;dbname=vmilk_takeaway_full;charset=utf8mb4", "root", "");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// XỬ LÝ GỬI ĐÁNH GIÁ
if (isset($_POST['submit_review'])) {
    $product_id = $_POST['product_id'];
    $stars = $_POST['rating'];
    $content = $_POST['content'];

    // Lưu vào DB
    $sql = "INSERT INTO reviews (user_id, product_id, so_sao, noi_dung, ngay_dang) VALUES (?, ?, ?, ?, NOW())";
    $pdo->prepare($sql)->execute([$user_id, $product_id, $stars, $content]);

    echo "<script>alert('Cảm ơn bạn đã đánh giá!'); window.location='reviews.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đánh giá của tôi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: sans-serif;
        }

        .rating-stars {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }

        .rating-stars input {
            display: none;
        }

        .rating-stars label {
            font-size: 2rem;
            color: #ccc;
            cursor: pointer;
            padding: 0 5px;
        }

        .rating-stars input:checked~label,
        .rating-stars label:hover,
        .rating-stars label:hover~label {
            color: #fbbf24;
        }

        .review-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-pen"></i> Viết đánh giá mới</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Chọn món muốn review:</label>
                                <select name="product_id" class="form-select" required>
                                    <option value="">-- Chọn sản phẩm --</option>
                                    <?php
                                    // Lấy danh sách sản phẩm để khách chọn
                                    $stmt = $pdo->query("SELECT id, ten FROM products");
                                    while ($p = $stmt->fetch()) {
                                        echo "<option value='{$p['id']}'>" . htmlspecialchars($p['ten']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Bạn chấm mấy sao?</label>
                                <div class="rating-stars">
                                    <input type="radio" name="rating" id="star5" value="5" required><label
                                        for="star5">★</label>
                                    <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
                                    <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
                                    <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
                                    <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nội dung đánh giá:</label>
                                <textarea name="content" class="form-control" rows="3" placeholder="Món này thế nào?..."
                                    required></textarea>
                            </div>

                            <button type="submit" name="submit_review" class="btn btn-dark w-100">Gửi đánh giá</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <h5 class="fw-bold mb-3">Lịch sử đánh giá của bạn</h5>
                <?php
                $sql = "SELECT r.*, p.ten, p.hinh_anh 
                    FROM reviews r 
                    JOIN products p ON r.product_id = p.id 
                    WHERE r.user_id = ? 
                    ORDER BY r.id DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id]);
                $my_reviews = $stmt->fetchAll();

                if (count($my_reviews) == 0) echo "<p class='text-muted'>Bạn chưa viết đánh giá nào.</p>";

                foreach ($my_reviews as $rev):
                    $stars = str_repeat('<i class="fas fa-star text-warning"></i>', $rev['so_sao']);
                    $stars_empty = str_repeat('<i class="far fa-star text-warning"></i>', 5 - $rev['so_sao']);

                    // ✅ FIX: DÙNG ĐƯỜNG DẪN GIỐNG INDEX.PHP
                    $img_src = !empty($rev['hinh_anh'])
                        ? 'images/' . htmlspecialchars($rev['hinh_anh'])
                        : 'https://via.placeholder.com/60?text=No+Img';
                ?>
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-body d-flex gap-3">
                            <img src="<?= $img_src ?>" class="review-thumb"
                                onerror="this.src='https://via.placeholder.com/60?text=No+Img'">
                            <div>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($rev['ten']) ?></h6>
                                <div class="mb-1 small">
                                    <?= $stars . $stars_empty ?>
                                    <span class="text-muted ms-2"><?= $rev['ngay_dang'] ?></span>
                                </div>
                                <p class="mb-0 text-secondary"><?= htmlspecialchars($rev['noi_dung']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</body>

</html>