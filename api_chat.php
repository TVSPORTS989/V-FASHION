<?php
header('Content-Type: application/json; charset=utf-8');

include "db_connect.php";

/* Nhận tin nhắn */
$data = json_decode(file_get_contents("php://input"), true);
$user_message = strtolower(trim($data['prompt'] ?? ''));

$user_message = $conn->real_escape_string($user_message);

/* ======================
LẤY SẢN PHẨM DATABASE
====================== */

$product_text = "";

/* tìm theo từ khóa */
$sql = "SELECT ten, mo_ta, gia_goc
        FROM products
        WHERE active = 1
        AND ten LIKE '%$user_message%'
        LIMIT 5";

$result = $conn->query($sql);

/* nếu không có thì lấy random */
if (!$result || $result->num_rows == 0) {

    $sql = "SELECT ten, mo_ta, gia_goc
        FROM products
        WHERE active = 1
        ORDER BY RAND()
        LIMIT 6";

    $result = $conn->query($sql);
}

while ($row = $result->fetch_assoc()) {

    $product_text .= "
Tên: {$row['ten']}
Giá: " . number_format($row['gia_goc']) . " VND
Mô tả: {$row['mo_ta']}

";
}

/* ======================
GEMINI API
====================== */

$api_key = "AIzaSyBAEgu64nKwRu9cNt3VoBxWL3jYUVz1V18";

$url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $api_key;

$prompt = "

Bạn là stylist của shop thời trang.

Danh sách sản phẩm của shop:

$product_text

Khách hỏi: $user_message

Hãy trả lời tự nhiên như nhân viên shop.

Nếu khách hỏi:

- đồ rẻ
- quần
- áo
- váy
- phối đồ
- mặc gì

hãy tư vấn dựa trên danh sách sản phẩm.

Nếu khách hỏi đi đâu thì phối outfit gồm:

Áo
Quần hoặc váy
Giày
Phụ kiện

Trả lời thân thiện và dễ hiểu.
";

$post_data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
curl_close($ch);

$result_ai = json_decode($response, true);

/* kiểm tra lỗi API */

// Thay thế đoạn kiểm tra lỗi cũ bằng đoạn này để biết lỗi ở đâu
if (isset($result_ai['candidates'][0]['content']['parts'][0]['text'])) {
    $ai_reply = $result_ai['candidates'][0]['content']['parts'][0]['text'];
} elseif (isset($result_ai['error'])) {
    // Nếu API trả về lỗi cụ thể từ Google
    $ai_reply = "Lỗi API: " . $result_ai['error']['message'];
} else {
    // In toàn bộ response để bạn copy gửi mình xem nếu vẫn lỗi
    // $ai_reply = "Response: " . json_encode($result_ai); 
    $ai_reply = "Xin lỗi, hiện tại hệ thống tư vấn đang bận. Bạn thử lại nhé.";
}

echo json_encode([
    "reply" => nl2br($ai_reply)
]);
