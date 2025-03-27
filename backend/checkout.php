    <?php
    session_start();
    include 'config.php';

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    // Lấy dữ liệu giỏ hàng của người dùng
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT c.cart_id, c.quantity, p.product_id, p.name AS product_name, p.price, p.stock 
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

    // Xử lý thanh toán khi gửi form
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $shipping_address = filter_var($_POST['shipping_address'], FILTER_SANITIZE_STRING);

        // Kiểm tra tồn kho
        foreach ($cart_items as $item) {
            if ($item['stock'] < $item['quantity']) {
                echo json_encode(['status' => 'error', 'message' => "Không đủ hàng tồn kho cho {$item['product_name']} (Còn: {$item['stock']})"]);
                exit();
            }
        }

        // Tạo đơn hàng
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $user_id, $total_amount, $shipping_address);
        $stmt->execute();
        $order_id = $conn->insert_id;

        // Thêm chi tiết đơn hàng và cập nhật tồn kho
        foreach ($cart_items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
        }

        // Tạo thanh toán
        $payment_method = filter_var($_POST['payment_method'] ?? 'bank_transfer', FILTER_SANITIZE_STRING);
        $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, amount) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $order_id, $payment_method, $total_amount);
        $stmt->execute();

        // Xóa giỏ hàng
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Trả về dữ liệu QR
        $qr_data = [
            'order_id' => $order_id,
            'total_amount' => $total_amount,
            'shipping_address' => $shipping_address,
            'date' => date('Y-m-d H:i:s')
        ];
        header('Content-Type: application/json');
        echo json_encode($qr_data);
        exit();
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Checkout</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="../frontend/css/style.css">
    </head>
    <body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="/index.php">Luisgaga Flower Shop</a>
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
                            <li class="nav-item"><a class="nav-link" href="admin.php">Product Manager</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

        <div class="container mt-4">
            <div class="row">
                <!-- Thông tin thanh toán -->
                <div class="col-md-6">
                    <form id="checkout-form" class="p-4 border rounded bg-light">
                        <h3 class="mb-4">Purchase Detail</h3>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address</label>
                            <input type="text" class="form-control" id="shipping_address" name="shipping_address" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="credit_card">Credit Card</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="bank_transfer" selected>Transfer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Purchase</button>
                    </form>
                </div>

                <!-- Tóm tắt giỏ hàng -->
                <div class="col-md-6">
                    <div id="cart-summary" class="p-4 border rounded bg-light">
                        <h3 class="mb-4">Cart Summary</h3>
                        <?php if (empty($cart_items)): ?>
                            <p class="text-center">Your cart is empty!</p>
                        <?php else: ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Products</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td><?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?> VNĐ</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total Price:</th>
                                        <th><?php echo number_format($total_amount, 0, ',', '.'); ?> VNĐ</th>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="qrcode" class="mt-3 text-center"></div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
        $(document).ready(function() {
            // Xử lý thanh toán
            $('#checkout-form').submit(function(e) {
                e.preventDefault();
                $.post('checkout.php', $(this).serialize(), function(data) {
                    if (data.status === 'error') {
                        alert(data.message);
                        return;
                    }
                    // Hiển thị mã QR
                    new QRCode(document.getElementById("qrcode"), {
                        text: JSON.stringify(data),
                        width: 200,
                        height: 200
                    });
                    if (confirm(`Total Price: ${data.total_amount} VNĐ\nOrder ID: ${data.order_id}\nPayment Confirm?`)) {
                        alert("Payment successful!");
                        window.location.href = "index.php";
                    }
                }, 'json').fail(function() {
                    alert('An error occurred while processing payment.!');
                });
            });
        });
        </script>
    </body>
    </html>