<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

function getUserData($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_SESSION['user_id'];

    if (isset($_POST['update_profile'])) {
        $full_name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
        $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING);

        if (!$email) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email']);
            exit();
        }

        if (!empty($_POST['new_password'])) {
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();

            if (!password_verify($_POST['current_password'], $user['password']) || $_POST['new_password'] !== $_POST['confirm_password']) {
                echo json_encode(['status' => 'error', 'message' => 'Password mismatch or incorrect']);
                exit();
            }

            $hashedPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);      
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $hashedPassword, $userId);
        } else {
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $userId);
        }

        $success = $stmt->execute();
        echo json_encode(['status' => $success ? 'success' : 'error', 'message' => $success ? 'Profile updated' : $stmt->error]);
        exit();
    }

    if (isset($_POST['delete_account'])) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user['role'] !== 'admin') {
            echo json_encode(['status' => 'error', 'message' => 'Only admins can delete accounts']);
            exit();
        }

        $deleteUserId = filter_var($_POST['delete_user_id'], FILTER_VALIDATE_INT);
        if ($deleteUserId === $userId) {
            echo json_encode(['status' => 'error', 'message' => 'Cannot delete your own account']);
            exit();
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $deleteUserId);
        $success = $stmt->execute();
        echo json_encode(['status' => $success ? 'success' : 'error', 'message' => $success ? 'User deleted' : $stmt->error]);
        exit();
    }
}

$userData = getUserData($conn, $_SESSION['user_id']);
if (!$userData) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../frontend/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../frontend/contact.html">Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="checkout.php">Cart</a></li>
                <li class="nav-item"><a class="nav-link" href="user.php">My Account</a></li>
                <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <main class="container mt-4">
        <h1>My Account</h1>
        <form id="update-profile-form" class="p-3 bg-light border rounded">
            <div class="mb-3">
                <label for="name">Full Name</label>
                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email">Email</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="phone">Phone</label>
                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="address">Address</label>
                <textarea class="form-control" name="address"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
            </div>
            <div class="mb-3">
                <label for="current_password">Current Password</label>
                <input type="password" class="form-control" name="current_password" placeholder="Current Password">
            </div>
            <div class="mb-3">
                <label for="new_password">New Password</label>
                <input type="password" class="form-control" name="new_password" placeholder="New Password">
            </div>
            <div class="mb-3">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password">
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
        </form>

        <?php if ($userData['role'] === 'admin'): ?>
        <form id="delete-account-form" class="p-3 bg-light border rounded mt-3">
            <div class="mb-3">
                <label for="delete_user_id">User ID to Delete</label>
                <input type="number" class="form-control" name="delete_user_id" placeholder="User ID to delete" required>
            </div>
            <button type="submit" name="delete_account" class="btn btn-danger">Delete User</button>
        </form>
        <?php endif; ?>
    </main>

    <script>
        $(document).ready(function() {
            $('#update-profile-form').submit(function(e) {
                e.preventDefault();
                $.post('user.php', $(this).serialize() + '&update_profile=1', function(response) {
                    alert(response.message);
                    if (response.status === 'success') {
                        location.reload();
                    }
                }, 'json');
            });

            $('#delete-account-form').submit(function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this user?')) {
                    $.post('user.php', $(this).serialize() + '&delete_account=1', function(response) {
                        alert(response.message);
                        if (response.status === 'success') {
                            location.reload();
                        }
                    }, 'json');
                }
            });
        });
    </script>
</body>
</html>