<?php
session_start();
$key = $_POST['remove_id'] ?? '';
if ($key && isset($_SESSION['cart'][$key])) {
    unset($_SESSION['cart'][$key]);
}
header('Location: cart.php');
exit;
