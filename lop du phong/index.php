<?php
session_start();
include 'config.php'; // Thêm include config để kiểm tra role từ database nếu cần

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
                            <a class="nav-link" href="admin_user.php">User Manager</a>
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
                    <a class="nav-link" href="contact.html">Contact Us</a>
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
                            <li class="list-group-item"><a href="#birthday-flowers">Hoa Sinh Nhật</a></li>
                            <li class="list-group-item"><a href="#opening-flowers">Hoa Khai Trương</a></li>
                            <li class="list-group-item"><a href="#theme">Chủ Đề</a></li>
                            <li class="list-group-item"><a href="#giangsinh">Thiết Kế</a></li>
                            <li class="list-group-item"><a href="#fresh-flowers">Hoa Tươi</a></li>
                            <li class="list-group-item"><a href="#discounted-flowers">Hoa Tươi Giảm Giá lên đến 99%</a></li>
                            <li class="list-group-item"><a href="#special-flowers">Hoa Đặc Biệt</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="col-md-9">
                    <!-- Shopping Cart Icon -->
                    <div class="shopping-cart text-right mb-3">
                        <a href="/backend/checkout.php" class="btn btn-primary" style="background-color: #3CD198; border: 1px solid;">
                            <img src="/frontend/img/cart.png" alt="Cart" style="width: 30px;">
                            <span id="cart-count" class="badge badge-light"><?php echo $cart_count; ?></span>
                        </a>
                    </div>

                    <!-- Featured Products Section -->
                    <section id="featured-products">
                        <h3>Sản phẩm nổi bật</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoacuc.png" class="card-img-top" alt="Sản phẩm nổi bật 1" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Chậu hoa bán chạy</h5>
                                        <p class="card-text">Giá: 250.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(101)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=101" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoanoibat.png" class="card-img-top" alt="Sản phẩm nổi bật 2" style="width: 36%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Bó hoa bán chạy</h5>
                                        <p class="card-text">Giá: 700.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(102)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=102" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/bohoa2.png" class="card-img-top" alt="Sản phẩm nổi bật 3" style="width: 44%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Bó hoa bán chạy</h5>
                                        <p class="card-text">Giá: 400.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(103)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=103" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Discounted Products Section -->
                    <section id="discounted-flowers" class="mt-5">
                        <h3>Hoa Tươi Giảm Giá</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoalan.png" class="card-img-top" alt="Hoa Giảm Giá 1" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Giảm Giá</h5>
                                        <p class="card-text">Giá: 300.000 VNĐ <span class="badge badge-danger">-15%</span></p>
                                        <button class="btn btn-success" onclick="addToCart(201)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=201" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoasen.png" class="card-img-top" alt="Hoa Giảm Giá 2" style="width: 31%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Giảm Giá</h5>
                                        <p class="card-text">Giá: 345.678 VNĐ <span class="badge badge-danger">-5%</span></p>
                                        <button class="btn btn-success" onclick="addToCart(202)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=202" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/bohoahen.png" class="card-img-top" alt="Hoa Giảm Giá 3" style="width: 31%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Giảm Giá</h5>
                                        <p class="card-text">Giá: 123.678 VNĐ <span class="badge badge-danger">-99%</span></p>
                                        <button class="btn btn-success" onclick="addToCart(203)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=203" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Birthday Flowers Section -->
                    <section id="birthday-flowers" class="mt-5">
                        <h3>Hoa Sinh Nhật</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoasinhnhat.png" class="card-img-top" alt="Hoa Sinh Nhật 1" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Sinh Nhật</h5>
                                        <p class="card-text">Giá: 200.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(301)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=301" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoasinhnhat1.png" class="card-img-top" alt="Hoa Sinh Nhật 2" style="width: 39%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Sinh Nhật</h5>
                                        <p class="card-text">Giá: 400.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(302)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=302" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoasinhnhat2.png" class="card-img-top" alt="Hoa Sinh Nhật 3" style="width: 24%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Sinh Nhật</h5>
                                        <p class="card-text">Giá: 600.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(303)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=303" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Opening Flowers Section -->
                    <section id="opening-flowers" class="mt-5">
                        <h3>Hoa Khai Trương</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoakhaitruong.png" class="card-img-top" alt="Hoa Khai Trương 1" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Khai Trương</h5>
                                        <p class="card-text">Giá: 800.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(401)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=401" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoakhaitruong2.png" class="card-img-top" alt="Hoa Khai Trương 2" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Khai Trương</h5>
                                        <p class="card-text">Giá: 1.600.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(402)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=402" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoakhaichuong3.png" class="card-img-top" alt="Hoa Khai Trương 3" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Khai Trương</h5>
                                        <p class="card-text">Giá: 600.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(403)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=403" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Theme Section -->
                    <section id="theme" class="mt-5">
                        <h3>Chủ Đề</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoahong2.png" class="card-img-top" alt="Chủ Đề 1" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Chủ Đề Đỏ</h5>
                                        <p class="card-text">Giá: 500.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(501)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=501" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoahong.png" class="card-img-top" alt="Chủ Đề 2" style="width: 37%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Chủ Đề Hồng</h5>
                                        <p class="card-text">Giá: 700.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(502)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=502" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoachude.png" class="card-img-top" alt="Chủ Đề 3" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Chủ Đề</h5>
                                        <p class="card-text">Giá: 900.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(503)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=503" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Christmas Section -->
                    <section id="giangsinh" class="mt-5">
                        <h3>Giáng Sinh</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoagiangsinh.png" class="card-img-top" alt="Giáng Sinh 1" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Giáng Sinh</h5>
                                        <p class="card-text">Giá: 900.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(601)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=601" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoagiangsinh2.png" class="card-img-top" alt="Giáng Sinh 2" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Giáng Sinh</h5>
                                        <p class="card-text">Giá: 200.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(602)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=602" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/giangsinhgau.png" class="card-img-top" alt="Giáng Sinh 3" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Giáng Sinh</h5>
                                        <p class="card-text">Giá: 1.300.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(603)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=603" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Fresh Flowers Section -->
                    <section id="fresh-flowers" class="mt-5">
                        <h3>Hoa Tươi</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoalan.png" class="card-img-top" alt="Hoa Tươi 1" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Lan</h5>
                                        <p class="card-text">Giá: 600.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(701)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=701" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoalaituoi.png" class="card-img-top" alt="Hoa Tươi 2" style="width: 38%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Lan</h5>
                                        <p class="card-text">Giá: 200.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(702)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=702" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoatuoi.png" class="card-img-top" alt="Hoa Tươi 3" style="width: 38%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Lan</h5>
                                        <p class="card-text">Giá: 500.000 VNĐ</p>
                                        <button class="btn btn-success" onclick="addToCart(703)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=703" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Special Flowers Section -->
                    <section id="special-flowers" class="mt-5">
                        <h3>Hoa Đặc Biệt</h3>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card mb-4">
                                    <img src="/frontend/img/hoa.png" class="card-img-top" alt="Hoa Đặc Biệt 1" style="width: 30%;">
                                    <div class="card-body">
                                        <h5 class="card-title">Hoa Đặc Biệt 1</h5>
                                        <p class="card-text">Giá: ∞ VNĐ (Hàng tặng không bán)</p>
                                        <button class="btn btn-success" onclick="addToCart(801)">Thêm vào giỏ</button>
                                        <a href="/backend/product.php?product_id=801" class="btn btn-info">Xem chi tiết</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
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
            .catch(error => console.error('Error:', error));
        <?php else: ?>
            alert('Vui lòng đăng nhập để thêm sản phẩm vào giỏ!');
            window.location.href = '/backend/login.php';
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