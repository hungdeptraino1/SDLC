<?php
include 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;
    $role = 'customer';

    $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $password, $full_name, $email, $phone, $address, $role]);
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="/frontend/css/style.css">
</head>
<body>
    <div class="wrapper fadeInDown">
        <div id="formContent">

            <a href="login.php">
                <h2 class="inactive underlineHover">Sign In </h2>
            </a>
            <h2 class="active"> Sign Up </h2>
        
            <!-- <div class="fadeIn first">
                <img src="/img/logo.png" alt="User Icon" style="width: 80px;"/>
            </div> -->
      
            <form method="POST" action="">
                <input type="text" id="username" class="fadeIn second" name="username" placeholder="User name" required>
                <input type="password" id="password" class="fadeIn third" name="password" placeholder="Password" required>
                <input type="text" id="full_name" class="fadeIn second" name="full_name" placeholder="Full name" required>
                <input type="text" id="email" class="fadeIn third" name="email" placeholder="Email" required>
                <input type="text" id="phone" class="fadeIn third" name="phone" placeholder="Phone">
                <input type="text" id="address" class="fadeIn third" name="address" placeholder="Address">
                <!-- <input type="submit" class="fadeIn fourth" value="Register"> -->
                <button type="submit" class="btn btn-primary register-btn">Register</button>
            </form>
      
            <div id="formFooter">
                <a class="underlineHover" href="#">Forgot Password?</a>
            </div>
        </div>
    </div>

    <!-- <script>
        function validateForm() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            // const confirmPassword = document.getElementById('confirm_password').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();

            if (!username) {
                alert('Username is required');
                return false;
            }

            if (!password) {
                alert('Password is required');
                return false;
            }

            // if (password !== confirmPassword) {
            //     alert('Passwords do not match');
            //     return false;
            // }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                alert('Please enter a valid email');
                return false;
            }

            const phoneRegex = /^[0-9]{10}$/;
            if (!phone || !phoneRegex.test(phone)) {
                alert('Please enter a valid 10-digit phone number');
                return false;
            }

            alert('You have been sign up successfully!');
            return true;
        }
    </script> -->
</body>
</html>
