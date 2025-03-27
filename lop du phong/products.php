<?php
include 'config.php';

$stmt = $conn->prepare("SELECT p.*, c.name AS category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id");
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

header('Content-Type: application/json');
echo json_encode($products);
?>