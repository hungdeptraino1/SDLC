<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $shipping_address = filter_var($_POST['shipping_address'], FILTER_SANITIZE_STRING);

    // Lấy thông tin giỏ hàng từ database
    $stmt = $conn->prepare("
        SELECT c.quantity, p.price, p.name AS product_name, p.stock, p.product_id 
        FROM cart c 
        JOIN products p ON c.product_id = p.product_id 
        WHERE c.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($cart_items)) {
        echo json_encode(['status' => 'error', 'message' => "Giỏ hàng trống!"]);
        exit();
    }

    // Tính tổng tiền và kiểm tra tồn kho
    $total_amount = 0;
    foreach ($cart_items as $item) {
        if ($item['stock'] < $item['quantity']) {
            echo json_encode(['status' => 'error', 'message' => "Không đủ hàng cho sản phẩm: {$item['product_name']}"]);
            exit();
        }
        $total_amount += $item['quantity'] * $item['price'];
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

    // Tạo thanh toán (giả lập)
    $payment_method = filter_var($_POST['payment_method'] ?? 'bank_transfer', FILTER_SANITIZE_STRING);
    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_method, amount) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $order_id, $payment_method, $total_amount);
    $stmt->execute();

    // Xóa giỏ hàng sau khi thanh toán
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Trả về dữ liệu JSON cho frontend
    $qr_data = [
        'status' => 'success',
        'order_id' => $order_id,
        'total_amount' => $total_amount,
        'shipping_address' => $shipping_address,
        'date' => date('Y-m-d H:i:s'),
        'cart_items' => $cart_items // Thêm thông tin sản phẩm
    ];
    header('Content-Type: application/json');
    echo json_encode($qr_data);
    exit();
}

// Lấy dữ liệu giỏ hàng để hiển thị ban đầu (GET request)
$stmt = $conn->prepare("
    SELECT c.quantity, p.price, p.name AS product_name, p.product_id 
    FROM cart c 
    JOIN products p ON c.product_id = p.product_id 
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($cart_items);
exit();
?>


<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
                    <option value="bank_transfer">Chuyển khoản</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success w-100">Thanh toán</button>
        </form>

        <div id="cart-summary" class="mt-5">
            <h3 class="mb-4">Tóm tắt giỏ hàng</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Tổng</th>
                    </tr>
                </thead>
                <tbody id="cart-items"></tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Tổng cộng:</th>
                        <th id="total-price">0 VNĐ</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div id="qrcode" class="mt-3"></div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    $(document).ready(function() {
        // Lấy dữ liệu giỏ hàng từ checkout.php
        $.get('checkout.php', function(data) {
            const cartItemsContainer = $('#cart-items');
            let totalPrice = 0;

            if (data.length === 0) {
                cartItemsContainer.append('<tr><td colspan="4" class="text-center">Giỏ hàng trống</td></tr>');
            } else {
                data.forEach(item => {
                    const row = `<tr>
                        <td>${item.product_name}</td>
                        <td>${parseFloat(item.price).toLocaleString('vi-VN')} VNĐ</td>
                        <td>${item.quantity}</td>
                        <td>${(item.quantity * item.price).toLocaleString('vi-VN')} VNĐ</td>
                    </tr>`;
                    cartItemsContainer.append(row);
                    totalPrice += item.quantity * item.price;
                });
            }

            $('#total-price').text(`${totalPrice.toLocaleString('vi-VN')} VNĐ`);
        }, 'json');

        // Xử lý thanh toán
        $('#checkout-form').submit(function(e) {
            e.preventDefault();
            $.post('checkout.php', $(this).serialize(), function(data) {
                if (data.status === 'error') {
                    alert(data.message);
                    return;
                }

                // Tạo QR code với thông tin đơn hàng
                const qrContent = JSON.stringify({
                    order_id: data.order_id,
                    total_amount: data.total_amount,
                    shipping_address: data.shipping_address,
                    date: data.date,
                    items: data.cart_items.map(item => ({
                        product_name: item.product_name,
                        quantity: item.quantity,
                        price: item.price
                    }))
                });
                new QRCode(document.getElementById("qrcode"), qrContent);

                // Hiển thị xác nhận với chi tiết đơn hàng
                let itemsSummary = data.cart_items.map(item => 
                    `${item.product_name}: ${item.quantity} x ${parseFloat(item.price).toLocaleString('vi-VN')} VNĐ`
                ).join('\n');
                if (confirm(`Tóm tắt đơn hàng:\n${itemsSummary}\nTổng tiền: ${data.total_amount.toLocaleString('vi-VN')} VNĐ\nMã đơn: ${data.order_id}\nXác nhận thanh toán?`)) {
                    alert("Thanh toán thành công!");
                    window.location.href = "index.php";
                }
            }, 'json').fail(function() {
                alert("Đã xảy ra lỗi trong quá trình thanh toán!");
            });
        });
    });
    </script>
</body>
</html>