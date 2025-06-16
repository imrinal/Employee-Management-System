<?php
// --- START DEBUGGING SETTINGS ---
error_reporting(E_ALL); // Report all errors
ini_set('display_errors', 1); // Display errors directly in the browser
// --- END DEBUGGING SETTINGS ---

session_start();
require_once "../connection.php"; // Path to your connection.php in the root

// Check if database connection was successful (from connection.php)
if (!$conn) {
    die("Database connection failed on mark-attendance.php: " . mysqli_connect_error());
}

// Check if employee is logged in
if (!isset($_SESSION["email_emp"]) || strlen($_SESSION["email_emp"]) == 0) {
    header("Location: login.php");
    exit();
}

// Get employee info using the $conn variable from connection.php
$email = $_SESSION["email_emp"];
$employee_id = null; // Initialize employee_id

if ($email) {
    $query = "SELECT id, name FROM employee WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $employee_id = $row['id'];
        $name = $row['name'];

        // Crucial: Ensure 'id' is set in session for consistent use across pages
        if (!isset($_SESSION['id'])) {
            $_SESSION['id'] = $employee_id;
        }
    } else {
        // If employee not found by email, redirect to login
        $_SESSION['error_message'] = "Employee data not found. Please log in again.";
        header("Location: login.php");
        exit();
    }
} else {
    // If email_emp is not set in session, redirect to login
    $_SESSION['error_message'] = "Session expired or invalid. Please log in again.";
    header("Location: login.php");
    exit();
}

// If employee_id is still null, something is very wrong, redirect
if (is_null($employee_id)) {
    $_SESSION['error_message'] = "Could not determine employee ID. Please log in again.";
    header("Location: login.php");
    exit();
}


$today = date("Y-m-d");

// Check existing attendance for today
$check_query = "SELECT * FROM attendance WHERE employee_id = $employee_id AND date = '$today'";
$check_result = mysqli_query($conn, $check_query);
$attendance = mysqli_fetch_assoc($check_result);

// Handle check in
if (isset($_POST['check_in'])) {
    $checkin_time = date("H:i:s");
    $insert = "INSERT INTO attendance (employee_id, date, check_in) VALUES ($employee_id, '$today', '$checkin_time')";
    if (mysqli_query($conn, $insert)) {
        $_SESSION['attendance_message'] = "Checked in successfully at: " . $checkin_time;
    } else {
        $_SESSION['attendance_message'] = "Error checking in: " . mysqli_error($conn);
    }
    header("Location: mark-attendance.php"); // Redirect to refresh page and show status
    exit();
}

// Handle check out
if (isset($_POST['check_out'])) {
    $checkout_time = date("H:i:s");
    $update = "UPDATE attendance SET check_out = '$checkout_time' WHERE employee_id = $employee_id AND date = '$today'";
    if (mysqli_query($conn, $update)) {
        $_SESSION['attendance_message'] = "Checked out successfully at: " . $checkout_time;
    } else {
        $_SESSION['attendance_message'] = "Error checking out: " . mysqli_error($conn);
    }
    header("Location: mark-attendance.php"); // Redirect to refresh page and show status
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card {
            border-radius: 15px;
        }
        .btn-lg {
            padding: 12px 24px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <?php
    // Display error messages from previous redirects, if any
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
        unset($_SESSION['error_message']); // Clear the message after displaying
    }
    ?>
    <?php require_once(__DIR__ . '/include/header.php');?>   <?php // Removed: require_once(__DIR__ . '/include/menubar.php'); ?> <div class="content-wrapper">
    <div class="container-fluid"> <div class="row">
            <div class="col-md-12">
                <h1 class="page-head-line">Mark Attendance</h1>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm p-4">
                    <h3 class="mb-3">👋 Welcome, <?php echo htmlspecialchars($name); ?></h3>
                    <h5 class="text-muted mb-4">Date: <?php echo $today; ?></h5>

                    <?php
                    // Display session message if any
                    if (isset($_SESSION['attendance_message'])) {
                        echo '<div class="alert alert-info">' . htmlspecialchars($_SESSION['attendance_message']) . '</div>';
                        unset($_SESSION['attendance_message']); // Clear the message after displaying
                    }
                    ?>

                    <?php if (!$attendance): ?>
                        <form method="post">
                            <button type="submit" name="check_in" class="btn btn-success btn-lg w-100">
                                🕒 Check In
                            </button>
                        </form>
                    <?php elseif ($attendance && !$attendance['check_out']): ?>
                        <div class="alert alert-info">
                            Checked in at: <strong><?php echo htmlspecialchars($attendance['check_in']); ?></strong>
                        </div>
                        <form method="post">
                            <button type="submit" name="check_out" class="btn btn-danger btn-lg w-100">
                                ✅ Check Out
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <p>You have completed attendance for today.</p>
                            <p><strong>Check In:</strong> <?php echo htmlspecialchars($attendance['check_in']); ?></p>
                            <p><strong>Check Out:</strong> <?php echo htmlspecialchars($attendance['check_out']); ?></p>
                        </div>
                    <?php endif; ?>

                    <a href="dashboard.php" class="btn btn-secondary mt-3 w-100">← Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once(__DIR__ . '/include/footer.php');?> </body>
</html>