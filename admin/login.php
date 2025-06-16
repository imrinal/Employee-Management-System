<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <title>Admin Login - Employee Management System</title>

  <style>
    body, html {
      height: 100%;
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #f3904f 0%, #3b4371 100%);
      background-size: cover;
      background-attachment: fixed;
    }

    .glass-card {
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 2.5rem;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
    }

    .glass-card h4 {
      color: #fff;
    }

    .form-control {
      background: rgba(255, 255, 255, 0.25);
      border: none;
      border-radius: 10px;
      padding: 0.75rem;
      color: #fff;
    }

    .form-control::placeholder {
      color: #eee;
    }

    .form-control:focus {
      background: rgba(255, 255, 255, 0.35);
      box-shadow: none;
      color: #fff;
    }

    label {
      color: #fff;
    }

    .btn-primary {
      background: #ff7e5f;
      border: none;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background: #feb47b;
    }

    .login-form__footer {
      color: #fff;
      font-size: 0.95rem;
    }

    .text-primary {
      color: #ffcab0 !important;
    }

    .alert-warning {
      background-color: rgba(255, 255, 255, 0.15);
      border: none;
      color: #fff;
    }
  </style>
</head>
<body>

<?php 
  $email_err = $pass_err = $login_Err = "";
  $email = $pass = "";

  if( $_SERVER["REQUEST_METHOD"] == "POST" ){
    if( empty($_REQUEST["email"]) ){
      $email_err = "<p style='color:#ffd6d6'> * Email Can Not Be Empty</p>";
    } else {
      $email = $_REQUEST["email"];
    }

    if ( empty($_REQUEST["password"]) ){
      $pass_err = "<p style='color:#ffd6d6'> * Password Can Not Be Empty</p>";
    } else {
      $pass = $_REQUEST["password"];
    }

    if( !empty($email) && !empty($pass) ){
      require_once "../connection.php";
      $sql_query = "SELECT * FROM admin WHERE email='$email' && password = '$pass'";
      $result = mysqli_query($conn , $sql_query);
      if ( mysqli_num_rows($result) > 0 ){
        while( $rows = mysqli_fetch_assoc($result) ){
          session_start();
          session_unset();
          $_SESSION["email"] = $rows["email"];
          header("Location: dashboard.php?login-sucess");
        }
      } else {
        $login_Err = "<div class='alert alert-warning alert-dismissible fade show'>
        <strong>Invalid Email/Password</strong>
        <button type='button' class='close' data-dismiss='alert'>
          <span aria-hidden='true'>&times;</span>
        </button>
      </div>";
      }
    }
  }
?>

<div class="d-flex justify-content-center align-items-center h-100">
  <div class="col-md-6 col-lg-5">
    <div class="glass-card">
      <h4 class="text-center mb-4">Hello, Admin</h4>
      <div class="text-center mb-4"><?php echo $login_Err; ?></div>

      <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">

        <div class="form-group">
          <label>Email:</label>
          <input type="email" class="form-control" name="email" value="<?php echo $email; ?>" placeholder="Enter your email">
          <?php echo $email_err; ?>
        </div>

        <div class="form-group">
          <label>Password:</label>
          <input type="password" class="form-control" name="password" placeholder="Enter your password">
          <?php echo $pass_err; ?>
        </div>

        <div class="form-group">
          <input type="submit" value="Log In" class="btn btn-primary btn-block" name="signin">
        </div>

        <p class="login-form__footer text-center">Not an admin? <a href="../employee/login.php" class="text-primary">Log in as Employee</a></p>
      </form>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
