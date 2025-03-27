<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id']; // Sửa thành user_id
            $_SESSION['role'] = $user['role'];
            header("Location: index.php"); // Cả hai file trong /backend
            exit();
        } else {
            $error = "Sai mật khẩu!";
        }
    } else {
        $error = "Tài khoản không tồn tại!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../frontend/css/style.css">
</head>
<body>
    <div class="wrapper fadeInDown">
        <div id="formContent">
            <h2 class="active">Sign In</h2>
            <a href="register.php"><h2 class="inactive underlineHover">Sign Up</h2></a>
            <?php if (isset($error)) echo "<p class='text-danger'>$error</p>"; ?>
            <form method="POST">
                <input type="text" id="username" class="fadeIn second" name="username" placeholder="User name" required>
                <input type="password" id="password" class="fadeIn third" name="password" placeholder="Password" required>
                <input type="submit" class="fadeIn fourth" value="Login">
            </form>
            <div id="formFooter">
                <a class="underlineHover" href="#">Forgot Password?</a>
            </div>
        </div>
    </div>
</body>
</html>