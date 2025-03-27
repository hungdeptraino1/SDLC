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
        <div class="container mt-4">
            <div class="row">
                <!-- Thông tin thanh toán -->
                <div class="col-md-6">
                    <form id="checkout-form" class="p-4 border rounded bg-light">
                        <h3 class="mb-4">Thông tin thanh toán</h3>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Địa chỉ giao hàng</label>
                            <input type="text" class="form-control" id="shipping_address" name="shipping_address" required>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="credit_card">Thẻ tín dụng</option>
                                <option value="e_wallet">Ví điện tử</option>
                                <option value="bank_transfer" selected>Chuyển khoản</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Thanh toán</button>
                    </form>
                </div>

                <!-- Tóm tắt giỏ hàng -->
                <div class="col-md-6">
                    <div id="cart-summary" class="p-4 border rounded bg-light">
                        <h3 class="mb-4">Tóm tắt giỏ hàng</h3>
                        <?php if (empty($cart_items)): ?>
                            <p class="text-center">Giỏ hàng của bạn đang trống!</p>
                        <?php else: ?>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Giá</th>
                                        <th>Số lượng</th>
                                        <th>Tổng</th>
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
                                        <th colspan="3" class="text-end">Tổng cộng:</th>
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
                    if (confirm(`Tổng tiền: ${data.total_amount} VNĐ\nMã đơn: ${data.order_id}\nXác nhận thanh toán?`)) {
                        alert("Thanh toán thành công!");
                        window.location.href = "index.php";
                    }
                }, 'json').fail(function() {
                    alert('Đã xảy ra lỗi khi xử lý thanh toán!');
                });
            });
        });
        </script>
    </body>
    </html>