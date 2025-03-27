<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    header("Location: admin.php");
    exit();
}

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) {
    header("Location: admin.php");
    exit();
}

// Lấy danh sách danh mục
$categories = $conn->query("SELECT * FROM categories");

// Xử lý cập nhật sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT) ?: null;
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT) ?: 0;

    if (!empty($_FILES["image"]["name"])) {
        $target_dir = "../uploads/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false || $_FILES["image"]["size"] > 2000000 || !in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $error = "Invalid image!";
        } elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, price = ?, image = ?, description = ?, stock = ? WHERE product_id = ?");
            $stmt->bind_param("isdssii", $category_id, $name, $price, $image_name, $description, $stock, $product_id);
        } else {
            $error = "Unable to upload image!";
        }
    } else {
        $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, price = ?, description = ?, stock = ? WHERE product_id = ?");
        $stmt->bind_param("isdssi", $category_id, $name, $price, $description, $stock, $product_id);
    }

    if (!isset($error) && $stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        $error = $error ?? "Lỗi: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Admin Panel</a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php">View website</a></li>
            <li class="nav-item"><a class="nav-link" href="admin_users.php">Users Manager</a></li>
        </ul>
    </nav>

    <div class="container mt-4">
        <h2>Edit product</h2>
        <?php if (isset($error)) echo "<p class='alert alert-danger'>$error</p>"; ?>
        <form method="POST" enctype="multipart/form-data" class="p-3 bg-light border rounded">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-control" id="category_id" name="category_id">
                    <option value="">No Category</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $cat['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; $categories->data_seek(0); ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Price (VND)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="stock" class="form-label">Storage quantity</label>
                <input type="number" class="form-control" id="stock" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Product image (leave blank if not changed)</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <small>Hiện tại: <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" width="50" alt="<?php echo htmlspecialchars($product['name']); ?>"></small>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="admin.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>