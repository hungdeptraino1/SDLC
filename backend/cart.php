<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Xử lý thêm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = filter_var($_POST['product_id'], FILTER_VALIDATE_INT);
    $quantity = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT) ?: 1;

    if ($product_id === false || $quantity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product information or quantity!']);
        exit();
    }

    $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stock = $stmt->get_result()->fetch_assoc()['stock'] ?? 0;

    if ($stock >= $quantity) {
        $stmt = $conn->prepare("
            INSERT INTO cart (user_id, product_id, quantity) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $stmt->bind_param("iiii", $user_id, $product_id, $quantity, $quantity);
        $stmt->execute();
        echo json_encode(['status' => 'success', 'message' => 'Added to cart!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Insufficient stock for this product! (Remaining Products: $stock)"]);
    }
    exit();
}

// Xử lý xóa khỏi giỏ hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_cart'])) {
    $cart_id = filter_var($_POST['cart_id'], FILTER_VALIDATE_INT);
    if ($cart_id === false) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid cart ID!']);
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Removed from cart!']);
    exit();
}

// Lấy danh sách sản phẩm trong giỏ hàng
$stmt = $conn->prepare("
    SELECT c.cart_id, c.quantity, p.name, p.price 
    FROM cart c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tính tổng tiền
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['quantity'] * $item['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../frontend/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Luisgaga Flower Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="user.php">Account</a></li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="admin.php">Products Manager</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Sign up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Your Cart</h1>

        <?php if (empty($cart_items)): ?>
            <p class="text-center">Your Cart is empty!</p>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="cart-table">
                    <?php foreach ($cart_items as $item): ?>
                        <tr data-cart-id="<?php echo $item['cart_id']; ?>">
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo number_format($item['price'], 0, ',', '.') ?> VNĐ</td>
                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                            <td><?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.') ?> VNĐ</td>
                            <td>
                                <button class="btn btn-danger remove-item" data-cart-id="<?php echo $item['cart_id']; ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total Price:</th>
                        <th colspan="2"><?php echo number_format($total_amount, 0, ',', '.') ?> VNĐ</th>
                    </tr>
                </tfoot>
            </table>
            <a href="checkout.php" class="btn btn-success">Purchase</a>
        <?php endif; ?>

        <!-- Form thêm vào giỏ hàng (dành cho thử nghiệm) -->
        <form id="add-to-cart-form" class="mt-4">
            <h2>Add product to cart</h2>
            <div class="mb-3">
                <label for="product_id" class="form-label">Product ID:</label>
                <input type="number" name="product_id" id="product_id" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Stock:</label>
                <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="1" required>
            </div>
            <button type="submit" class="btn btn-primary">Add To Cart</button>
        </form>
    </div>

    <script>
    $(document).ready(function() {
        // Xử lý thêm sản phẩm vào giỏ
        $('#add-to-cart-form').submit(function(e) {
            e.preventDefault();
            $.post('cart.php', $(this).serialize() + '&add_to_cart=1', function(response) {
                alert(response.message);
                if (response.status === 'success') {
                    location.reload();
                }
            }, 'json').fail(function() {
                alert('An error occurred while adding the product.!');
            });
        });

        // Xử lý xóa sản phẩm khỏi giỏ
        $('.remove-item').click(function() {
            const cartId = $(this).data('cart-id');
            if (confirm('Are you sure you want to remove this product from your cart??')) {
                $.post('cart.php', { cart_id: cartId, remove_from_cart: 1 }, function(response) {
                    alert(response.message);
                    if (response.status === 'success') {
                        location.reload();
                    }
                }, 'json').fail(function() {
                    alert('An error occurred while deleting the product.!');
                });
            }
        });
    });
    </script>
</body>
</html>