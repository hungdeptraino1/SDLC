<?php
session_start();
include 'config.php';

// Lấy số lượng sản phẩm trong giỏ hàng
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $cart_count = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// Lấy danh sách danh mục và sản phẩm từ database
$categories_query = $conn->query("SELECT category_id, name FROM categories");
$categories = [];
while ($row = $categories_query->fetch_assoc()) {
    $categories[$row['category_id']] = $row['name'];
}

$products_query = $conn->prepare("
    SELECT p.product_id, p.name, p.price, p.image, p.category_id 
    FROM products p 
    WHERE p.stock > 0 
    ORDER BY p.product_id ASC
");
$products_query->execute();
$products_result = $products_query->get_result();
$products_by_category = [];
while ($product = $products_result->fetch_assoc()) {
    $category_id = $product['category_id'] ?? 0; // 0 cho sản phẩm không có danh mục
    $products_by_category[$category_id][] = $product;
}
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luisgaga Flower Shop</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="frontend/css/style.css">
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
                    <button class="logo-img" onclick="window.location.href='index.php'">
                        <img src="/frontend/img/logo.png" alt="Logo" style="width: 50px; border:none"/>
                    </button>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user.php">My Account</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">Product Manager</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_users.php">User Manager</a>
                        </li>
                    <?php endif; ?>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">Products</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cart.php">Shopping Cart</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/frontend/contact.html">Contact Us</a>
                </li>
            </ul>
            <form class="form-inline my-2 my-lg-0">
                <input class="form-control mr-sm-2" type="search" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
            </form>
        </div>
    </nav>

    <main>
        <div class="container-fluid">
            <!-- Main Banner -->
            <div class="main-banner text-center py-5" style="background-color: #C3F3A0; height: 120px; display: flex; flex-direction: column; justify-content: center;">
                <h1 class="display-4">HOT Deal Everyday</h1>
                <p class="mt-3">Follow us: 
                    <a href="https://www.facebook.com/nguyenphu.hung" target="_blank">Facebook</a> | 
                    <a href="https://www.instagram.com/beu.69/" target="_blank">Instagram</a> | 
                    <a href="https://github.com/hungdeptraino1" target="_blank">Github</a>
                </p>
            </div>

            <!-- Left Sidebar Menu -->
            <div class="row">
                <div class="col-md-3">
                    <div class="sidebar bg-light p-3">
                        <h4>Danh mục sản phẩm</h4>
                        <ul class="list-group">
                            <?php foreach ($categories as $cat_id => $cat_name): ?>
                                <li class="list-group-item">
                                    <a href="#category-<?php echo $cat_id; ?>"><?php echo htmlspecialchars($cat_name); ?></a>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item"><a href="#other">Sản phẩm khác</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9">
                    <!-- Shopping Cart Icon -->
                    <div class="shopping-cart text-right mb-3">
                        <a href="cart.php" class="btn btn-primary" style="background-color: #3CD198; border: 1px solid;">
                            <img src="/frontend/img/cart.png" alt="Cart" style="width: 30px;">
                            <span id="cart-count" class="badge badge-light"><?php echo $cart_count; ?></span>
                        </a>
                    </div>

                    <!-- Dynamic Product Sections -->
                    <?php foreach ($products_by_category as $category_id => $products): ?>
                        <section id="category-<?php echo $category_id; ?>" class="mt-5">
                            <h3><?php echo htmlspecialchars($categories[$category_id] ?? 'Sản phẩm khác'); ?></h3>
                            <div class="row">
                                <?php foreach ($products as $product): ?>
                                    <div class="col-md-4">
                                        <div class="card mb-4">
                                            <img src="/uploads/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50%; margin: auto;">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                                <p class="card-text">Giá: <?php echo number_format($product['price'], 0, ',', '.') ?> VNĐ</p>
                                                <button class="btn btn-success" onclick="addToCart(<?php echo $product['product_id']; ?>)">Thêm vào giỏ</button>
                                                <a href="product.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-info">Xem chi tiết</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script>
    let cartCount = <?php echo $cart_count; ?>;

    function addToCart(productId) {
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
            .catch(error => {
                console.error('Error:', error);
                alert('Đã xảy ra lỗi khi thêm sản phẩm!');
            });
        <?php else: ?>
            alert('Vui lòng đăng nhập để thêm sản phẩm vào giỏ!');
            window.location.href = 'login.php';
        <?php endif; ?>
    }

    // Cuộn mượt
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
    </script>
</body>
</html>