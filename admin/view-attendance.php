<?php
// --- START DEBUGGING SETTINGS (Remove or comment out in production) ---
error_reporting(E_ALL); // Report all errors
ini_set('display_errors', 1); // Display errors directly in the browser
// --- END DEBUGGING SETTINGS ---

session_start();
require_once "../connection.php"; // Path to your connection.php in the root

// Check if database connection was successful
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if admin is logged in
// Assuming your admin session email is stored in $_SESSION["email"]
if (!isset($_SESSION["email"]) || strlen($_SESSION["email"]) == 0) {
    header("Location: login.php"); // Redirect to admin login if not logged in
    exit();
}

// Fetch all attendance records with employee names
$query = "
    SELECT 
        a.id AS attendance_id,
        e.name AS employee_name,
        e.email AS employee_email,
        a.date,
        a.check_in,
        a.check_out
    FROM 
        attendance AS a
    JOIN 
        employee AS e ON a.employee_id = e.id
    ORDER BY 
        a.date DESC, a.check_in DESC
";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching attendance data: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card {
            border-radius: 15px;
        }
        .table-responsive {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php require_once(__DIR__ . '/include/header.php');?>
    <?php // Admin does not have a separate menubar.php as per previous discussion ?>

<div class="content-wrapper">
    <div class="container-fluid"> <div class="row">
            <div class="col-md-12">
                <h1 class="page-head-line">Manage Employee Attendance</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm p-4">
                    <h3 class="mb-4">All Attendance Records</h3>
                    
                    <?php if (mysqli_num_rows($result) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Employee Email</th>
                                        <th>Date</th>
                                        <th>Check-In Time</th>
                                        <th>Check-Out Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['employee_email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                                            <td><?php echo htmlspecialchars($row['check_in']); ?></td>
                                            <td><?php echo htmlspecialchars($row['check_out'] ? $row['check_out'] : 'N/A'); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No attendance records found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/include/footer.php');?>
</body>
</html>