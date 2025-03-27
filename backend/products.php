<?php
session_start();
include 'config.php';

// Lấy tất cả sản phẩm từ cơ sở dữ liệu cùng với danh mục
$stmt = $conn->prepare("
    SELECT p.product_id, p.name, p.description, p.price, p.image, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy số lượng sản phẩm trong giỏ hàng (nếu đã đăng nhập)
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Luisgaga Flower Shop</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body>
    <h1>Luisgaga Flower Shop</h1>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <button class="logo-img">
                        <img src="../frontend/img/logo.png" alt="User Icon" style="width: 50px; border:none" onclick="window.location.href='index.php'"/>
                    </button>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Home</a>
                </li>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] !== null): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user.php">My Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item active">
                    <a class="nav-link" href="products.php">Products <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">Shopping Cart</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../frontend/contact.html">Contact us</a>
                </li>
            </ul>
            <form class="form-inline my-2 my-lg-0">
                <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
            </form>
        </div>
    </nav>

    <main>
        <div class="container-fluid mt-4">
            <!-- Shopping Cart Icon -->
            <div class="shopping-cart text-right mb-3">
                <a href="cart.php" class="btn btn-primary" style="background-color: #3CD198; border: 1px solid">
                    <img src="../frontend/img/cart.png" alt="Cart" style="width: 30px;">
                    <span id="cart-count" class="badge badge-light"><?php echo $cart_count; ?></span>
                </a>
            </div>

            <!-- Products Section -->
            <section id="all-products">
                <h2>All Products</h2>
                <div class="row">
                    <?php if (empty($products)): ?>
                        <p>No products available.</p>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: auto;">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="card-text">
                                            <?php echo htmlspecialchars($product['price']); ?> VNĐ<br>
                                            <small>Category: <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></small>
                                        </p>
                                        <button class="btn btn-success add-to-cart" data-product-id="<?php echo $product['product_id']; ?>">Thêm vào giỏ</button>
                                        <a href="product_detail.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script>
        let cartCount = <?php echo $cart_count; ?>;
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                <?php if (isset($_SESSION['user_id'])): ?>
                    fetch('cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `product_id=${productId}&add_to_cart=1`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            cartCount++;
                            document.getElementById('cart-count').innerText = cartCount;
                            alert('Sản phẩm đã được thêm vào giỏ hàng!');
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
                <?php else: ?>
                    alert('Vui lòng đăng nhập để thêm sản phẩm vào giỏ!');
                    window.location.href = 'login.php';
                <?php endif; ?>
            });
        });
    </script>
</body>
</html>