<?php
session_start();
include 'config.php';

// Lấy product_id từ URL
if (!isset($_GET['product_id']) || !filter_var($_GET['product_id'], FILTER_VALIDATE_INT)) {
    header("Location: index.php");
    exit();
}

$product_id = $_GET['product_id'];

// Lấy thông tin sản phẩm từ cơ sở dữ liệu
$stmt = $conn->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Chi tiết sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../frontend/css/style.css">
    <style>
        body {
            background-color: #f4f7fa;
            font-family: 'Arial', sans-serif;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-top: 30px;
        }
        .product-image img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .product-details h1 {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .product-details .price {
            font-size: 24px;
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .product-details .stock {
            font-size: 16px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        .product-details .category {
            font-size: 16px;
            color: #3498db;
            margin-bottom: 15px;
        }
        .product-details .description {
            font-size: 16px;
            color: #34495e;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn-custom {
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }
        .btn-primary:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        .btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Luisgaga Flower Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">Giỏ hàng</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="user.php">Tài khoản</a></li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="admin.php">Quản lý</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Đăng nhập</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Nội dung chi tiết sản phẩm -->
    <div class="container">
        <div class="row">
            <!-- Hình ảnh sản phẩm -->
            <div class="col-md-6 product-image">
                <img src="/uploads/<?php echo htmlspecialchars($product['image'] ?? 'default.png'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <!-- Thông tin sản phẩm -->
            <div class="col-md-6 product-details">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="price"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</p>
                <p class="stock">Tồn kho: <?php echo htmlspecialchars($product['stock']); ?></p>
                <p class="category">Danh mục: <?php echo htmlspecialchars($product['category_name'] ?? 'Không có'); ?></p>
                <p class="description"><?php echo htmlspecialchars($product['description'] ?? 'Không có mô tả.'); ?></p>
                
                <!-- Form thêm vào giỏ -->
                <form id="add-to-cart-form" class="d-flex align-items-center">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <div class="me-3">
                        <label for="quantity" class="form-label">Số lượng:</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" max="<?php echo $product['stock']; ?>" value="1" style="width: 100px;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-custom" <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Xử lý thêm vào giỏ hàng
        $('#add-to-cart-form').submit(function(e) {
            e.preventDefault();
            <?php if (isset($_SESSION['user_id'])): ?>
                $.post('cart.php', $(this).serialize() + '&add_to_cart=1', function(response) {
                    alert(response.message);
                    if (response.status === 'success') {
                        // Có thể cập nhật số lượng giỏ hàng ở đây nếu cần
                    }
                }, 'json').fail(function() {
                    alert('Đã xảy ra lỗi khi thêm sản phẩm vào giỏ!');
                });
            <?php else: ?>
                alert('Vui lòng đăng nhập để thêm sản phẩm vào giỏ!');
                window.location.href = 'login.php';
            <?php endif; ?>
        });
    });
    </script>
</body>
</html>