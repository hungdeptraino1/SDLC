<?php
session_start();
include 'config.php';

// Kiểm tra xem người dùng có đăng nhập và có vai trò admin không
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Lấy danh sách người dùng từ cơ sở dữ liệu
$stmt = $conn->prepare("SELECT * FROM users");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người Dùng</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../frontend/css/styleadmin.css">


</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="admin.php">Products Manager</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php">View Website</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Nội dung chính -->
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users me-2"></i> Users Manager</h2>
            <a href="add_user.php" class="btn btn-primary btn-custom"><i class="fas fa-user-plus"></i>Add User</a>
        </div>

        <?php if ($result->num_rows === 0): ?>
            <div class="alert alert-info text-center">There are no users in the system yet!</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Phone Number</th>
                            <th>Role</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['role'] === 'admin' ? 'bg-success' : 'bg-primary'; ?>">
                                        <?php echo htmlspecialchars($row['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-warning btn-sm btn-custom">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="delete_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-danger btn-sm btn-custom" onclick="return confirm('Are you sure you want to delete this user?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>