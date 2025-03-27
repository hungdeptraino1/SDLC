<?php
session_start();
include 'config.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Lấy danh sách sản phẩm
$sql = "SELECT p.*, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id";
$result = $conn->query($sql);

// Lấy danh sách danh mục
$categories = $conn->query("SELECT * FROM categories");

// Xử lý thêm sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT) ?: null; // Cho phép NULL
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT) ?: 0;

    $target_dir = "../uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $image_name = basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $image_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check === false) {
        $error = "Tệp không phải là ảnh!";
    } elseif ($_FILES["image"]["size"] > 2000000) {
        $error = "Ảnh quá lớn (tối đa 2MB)!";
    } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
        $error = "Chỉ chấp nhận định dạng JPG, JPEG, PNG, GIF!";
    } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO products (category_id, name, price, image, description, stock) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdssi", $category_id, $name, $price, $image_name, $description, $stock);
        if ($stmt->execute()) {
            header("Location: admin.php");
            exit();
        } else {
            $error = "Lỗi: " . $stmt->error;
        }
    } else {
        $error = "Không thể tải ảnh lên!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Admin Panel</a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php">Xem website</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_user.php">Quản lý người dùng</a></li>
        </ul>
    </nav>

    <div class="container mt-4">
        <h1>Quản lý sản phẩm</h1>
        <?php if (isset($error)) echo "<p class='alert alert-danger'>$error</p>"; ?>

        <!-- Form thêm sản phẩm -->
        <form action="admin.php" method="POST" enctype="multipart/form-data" class="mb-4 p-3 bg-light border rounded">
            <div class="mb-3">
                <label for="name" class="form-label">Tên sản phẩm</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Danh mục</label>
                <select class="form-control" id="category_id" name="category_id">
                    <option value="">Không chọn</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; $categories->data_seek(0); ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Giá (VND)</label>
                <input type="number" class="form-control" id="price" name="price" step="1000" required>
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Số lượng tồn kho</label>
                <input type="number" class="form-control" id="stock" name="stock" required>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Ảnh sản phẩm</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Mô tả</label>
                <textarea class="form-control" id="description" name="description"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Thêm sản phẩm</button>
        </form>

        <!-- Danh sách sản phẩm -->
        <h2>Danh sách sản phẩm</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Tồn kho</th>
                    <th>Ảnh</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['price']); ?></td>
                        <td><?php echo htmlspecialchars($row['stock']); ?></td>
                        <td><img src="../uploads/<?php echo htmlspecialchars($row['image']); ?>" width="50" alt="<?php echo htmlspecialchars($row['name']); ?>"></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $row['product_id']; ?>" class="btn btn-warning btn-sm">Sửa</a>
                            <a href="delete_product.php?id=<?php echo $row['product_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">Xóa</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>