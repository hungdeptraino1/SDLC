<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode([]));
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT c.quantity, p.price, p.product_name 
                        FROM cart c 
                        JOIN products p ON c.product_id = p.product_id 
                        WHERE c.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($cart_items);
?>