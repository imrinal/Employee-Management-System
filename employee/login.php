<?php
// CRITICAL FIX 1: session_start() MUST be the very first executable line.
session_start(); 

$email_err = $pass_err = $login_Err = "";
$email = $pass = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate email
    if (empty($_POST["email"])) {
        $email_err = "<p style='color:#ffd6d6'> * Email Can Not Be Empty</p>";
    } else {
        $email = htmlspecialchars(trim($_POST["email"])); // Sanitize and trim
    }

    // Sanitize and validate password
    if (empty($_POST["password"])) {
        $pass_err = "<p style='color:#ffd6d6'> * Password Can Not Be Empty</p>";
    } else {
        // IMPORTANT: In a real application, you should hash and verify passwords.
        // For now, we're using plain text as per your existing structure, but
        // it's highly recommended to use password_hash() and password_verify().
        $pass = htmlspecialchars(trim($_POST["password"])); // Sanitize and trim
    }

    if (empty($email_err) && empty($pass_err)) {
        require_once "../connection.php";

        // CRITICAL FIX 2: Use prepared statements to prevent SQL Injection.
        // Do NOT use "SELECT *" unless you specifically need all columns.
        // Selecting only necessary columns (id, email) is good practice.
        $sql_query = "SELECT id, email FROM employee WHERE email = ? AND password = ?";
        
        $stmt = mysqli_prepare($conn, $sql_query);

        if ($stmt) {
            // Bind parameters to the prepared statement ("ss" for two strings)
            mysqli_stmt_bind_param($stmt, "ss", $email, $pass); 

            // Execute the prepared statement
            mysqli_stmt_execute($stmt);

            // Get the result set
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                // Fetch the user's data
                $rows = mysqli_fetch_assoc($result);
                
                // CRITICAL FIX 3: DO NOT use session_unset() here. 
                // It destroys the session variables you just set.
                // session_unset(); // REMOVE THIS LINE!

                // Set session variables
                $_SESSION["email_emp"] = $rows["email"];
                $_SESSION["id"] = $rows["id"]; // Store the employee ID

                // Close statement and connection before redirecting
                mysqli_stmt_close($stmt);
                mysqli_close($conn);

                // CRITICAL FIX 4: Add exit() after header() to stop script execution.
                // FIX 5: Corrected typo 'sucess' to 'success'.
                header("Location: dashboard.php?login-success"); 
                exit(); 
            } else {
                // Invalid credentials
                $login_Err = "<div class='alert alert-warning alert-dismissible fade show'>
                    <strong>Invalid Email/Password</strong>
                    <button type='button' class='close' data-dismiss='alert'>
                        <span aria-hidden='true'>&times;</span>
                    </button>
                </div>";
            }
            // Close statement if no rows found or if conditions above didn't exit
            if (isset($stmt) && is_object($stmt)) { 
                mysqli_stmt_close($stmt);
            }
        } else {
            // Error preparing statement
            $login_Err = "<div class='alert alert-danger'>Database query preparation failed: " . mysqli_error($conn) . "</div>";
        }
        // Close connection if statement prep failed or login failed
        if (isset($conn) && is_object($conn)) { 
             mysqli_close($conn);
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Employee Login - EMS</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">

    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);
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
            color: #ffffff;
            font-weight: 600;
            margin-bottom: 2rem;
        }

        label {
            color: #ffffff;
            font-weight: 500;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.25);
            border: none;
            border-radius: 10px;
            padding: 0.75rem;
            color: #ffffff;
            font-weight: 500;
        }

        .form-control::placeholder {
            color: #e6f7ff;
            opacity: 1;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.35);
            box-shadow: none;
            outline: none;
            color: #ffffff;
        }

        .btn-primary {
            background: #70c1ff;
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background: #a2d4ff;
        }

        .login-form__footer {
            color: #ffffff;
            font-size: 0.95rem;
            margin-top: 1.2rem;
        }

        .login-form__footer a {
            color: #ffe6b3 !important;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-form__footer a:hover {
            color: #fff6d6 !important;
            text-decoration: underline;
        }

        .alert-warning {
            background-color: rgba(255, 255, 255, 0.15);
            border: none;
            color: #ffffff;
            border-radius: 10px;
            font-weight: 500;
        }
        .alert-danger { /* Added style for alert-danger */
            background-color: rgba(255, 0, 0, 0.15);
            border: none;
            color: #fff;
            border-radius: 10px;
            font-weight: 500;
        }
    </style>

</head>
<body>

<div class="d-flex justify-content-center align-items-center h-100">
    <div class="col-md-6 col-lg-5">
        <div class="glass-card">
            <h4 class="text-center mb-4">Hello, Employee</h4>
            <div class="text-center mb-4"><?php echo $login_Err; ?></div>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">

                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email">
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

                <p class="login-form__footer text-center">Not an employee? <a href="../admin/login.php" class="text-primary">Log in as Admin</a></p>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>