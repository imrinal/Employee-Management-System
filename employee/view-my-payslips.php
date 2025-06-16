<?php
session_start();

if (!isset($_SESSION["email_emp"]) || strlen($_SESSION["email_emp"]) == 0 || !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

// CRITICAL FIX: Include the database connection file here.
require_once "../connection.php"; 

// Get employee_id from session
$employee_id = $_SESSION['id']; 

$message = '';
$payslips = [];

// Fetch payslips for the logged-in employee
$stmt = mysqli_prepare($conn, "SELECT month, year, basic_salary, allowances, deductions, net_salary, generation_date FROM pay_slips WHERE employee_id = ? ORDER BY year DESC, FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') DESC");

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $payslips[] = $row;
            }
        } else {
            $message = '<div class="alert alert-info">No payslips found for your account.</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Error fetching payslips: ' . mysqli_stmt_error($stmt) . '</div>';
    }
    mysqli_stmt_close($stmt);
} else {
    // This error will only happen if the query itself has a syntax error or $conn is null, 
    // but we've fixed $conn being null, so it's likely a query syntax issue if it still occurs.
    $message = '<div class="alert alert-danger">Database statement preparation failed: ' . mysqli_error($conn) . '</div>';
}

// Ensure database connection is closed at the end of the script or when no longer needed
if (isset($conn) && is_object($conn)) {
    mysqli_close($conn);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Payslips - Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card {
            border-radius: 15px;
        }
        .payslip-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fff;
        }
        .payslip-card h5 {
            color: #007bff;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .payslip-card p {
            margin-bottom: 5px;
        }
        .payslip-card .net-salary {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745; /* Green for net salary */
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <?php require_once(__DIR__ . '/include/header.php');?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-head-line">My Payslips</h1>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm p-4">
                    <h3 class="mb-4">Your Generated Payslips</h3>
                    <?php echo $message; // Display messages ?>

                    <?php if (!empty($payslips)): ?>
                        <?php foreach ($payslips as $payslip): ?>
                            <div class="payslip-card shadow-sm">
                                <h5>Payslip for <?php echo htmlspecialchars($payslip['month']); ?> <?php echo htmlspecialchars($payslip['year']); ?></h5>
                                <p><strong>Generation Date:</strong> <?php echo htmlspecialchars($payslip['generation_date']); ?></p>
                                <p><strong>Basic Salary:</strong> ₹<?php echo number_format(htmlspecialchars($payslip['basic_salary']), 2); ?></p>
                                <p><strong>Allowances:</strong> ₹<?php echo number_format(htmlspecialchars($payslip['allowances']), 2); ?></p>
                                <p><strong>Deductions:</strong> ₹<?php echo number_format(htmlspecialchars($payslip['deductions']), 2); ?></p>
                                <p class="net-salary"><strong>Net Salary:</strong> ₹<?php echo number_format(htmlspecialchars($payslip['net_salary']), 2); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/include/footer.php');?>
</body>
</html>