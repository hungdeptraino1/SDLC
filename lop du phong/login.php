<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $sql = "SELECT * FROM users WHERE username='$username'";
  $result = $conn->query($sql);
  if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user['password'])) {
          if ($user['role'] == 'admin'){
              $_SESSION['user_id'] = $user['id'];
              $_SESSION['role'] = $user['role'];
              header("Location: index.php");
          }
          else{
              $_SESSION['user_id'] = $user['id'];
              $_SESSION['role'] = $user['role'];

              header("Location: index.php");
      } 
  }
      else {
          echo "Sai mật khẩu!";
      }

  } else {
      echo "Tài khoản không tồn tại!";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="/frontend/css/style.css">
    <!-- <script>
        function validateForm() {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();

            if (username === '') {
                alert('Please enter your username.');
                return false;
            }

            if (password === '') {
                alert('Please enter your password.');
                return false;
            }

            return true;
        }
    </script> -->
</head>
<body>
    <div class="wrapper fadeInDown">
        <div id="formContent">
          
          <h2 class="active"> Sign In </h2>
    
          <a href="register.php">
            <h2 class="inactive underlineHover">Sign Up </h2>
        </a>
          
          <!-- <div class="fadeIn first">
            <img src="/img/logo.png" alt="User Icon" style="width: 80px;"/>
          </div> -->
          
          <form method="POST" onsubmit="return validateForm();">
            <input type="text" id="username" class="fadeIn second" name="username" placeholder="User name">
            <input type="password" id="password" class="fadeIn third" name="password" placeholder="Password">
            <input type="submit" class="fadeIn fourth" value="Login">
          </form>
      
          <div id="formFooter">
            <a class="underlineHover" href="#">Forgot Password?</a>
          </div>
      
        </div>
      </div>
</body>
</html>

