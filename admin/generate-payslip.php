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
if (!isset($_SESSION["email"]) || strlen($_SESSION["email"]) == 0) {
    header("Location: login.php"); // Redirect to admin login if not logged in
    exit();
}

$message = ''; // For success/error messages

// Handle payslip generation form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_payslip'])) {
    // Sanitize input
    $employee_id = filter_var($_POST['employee_id'], FILTER_SANITIZE_NUMBER_INT);
    // Deprecated FILTER_SANITIZE_STRING removed, direct assignment is fine for prepared statements
    $month = $_POST['month'];
    $year = filter_var($_POST['year'], FILTER_SANITIZE_NUMBER_INT);
    $basic_salary = filter_var($_POST['basic_salary'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $allowances = filter_var($_POST['allowances'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $deductions = filter_var($_POST['deductions'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $generation_date = date('Y-m-d'); // Current date for generation in YYYY-MM-DD format

    // Basic validation
    if (empty($employee_id) || empty($month) || empty($year) || !is_numeric($basic_salary) || !is_numeric($allowances) || !is_numeric($deductions)) {
        $message = '<div class="alert alert-danger">Please fill all required fields with valid numbers.</div>';
    } else {
        // --- NEW: Check for existing payslip for this employee, month, and year ---
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM pay_slips WHERE employee_id = ? AND month = ? AND year = ?");
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "isi", $employee_id, $month, $year);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);

            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                // Payslip already exists for this month/year for this employee
                $message = '<div class="alert alert-warning">A payslip for ' . htmlspecialchars($month) . ' ' . htmlspecialchars($year) . ' for this employee already exists.</div>';
                mysqli_stmt_close($check_stmt);
            } else {
                // --- END NEW CHECK ---

                // Calculate net salary
                $net_salary = $basic_salary + $allowances - $deductions;

                // Prepare and execute the SQL insert statement
                $stmt = mysqli_prepare($conn, "INSERT INTO pay_slips (employee_id, month, year, basic_salary, allowances, deductions, net_salary, generation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "isddddds", $employee_id, $month, $year, $basic_salary, $allowances, $deductions, $net_salary, $generation_date);

                    if (mysqli_stmt_execute($stmt)) {
                        $message = '<div class="alert alert-success">Payslip for ' . htmlspecialchars($month) . ' ' . htmlspecialchars($year) . ' generated successfully!</div>';
                    } else {
                        $message = '<div class="alert alert-danger">Error generating payslip: ' . mysqli_stmt_error($stmt) . '</div>';
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $message = '<div class="alert alert-danger">Database statement preparation failed: ' . mysqli_error($conn) . '</div>';
                }
            }
            // Close the check_stmt if it was successful but no rows found, to avoid resource leaks
            if (isset($check_stmt) && is_object($check_stmt)) {
                mysqli_stmt_close($check_stmt);
            }

        } else {
            $message = '<div class="alert alert-danger">Error preparing duplicate check: ' . mysqli_error($conn) . '</div>';
        }
    }
}

// Fetch all employees for the dropdown
$employees_query = "SELECT id, name, email FROM employee ORDER BY name ASC";
$employees_result = mysqli_query($conn, $employees_query);
$employees = [];
if ($employees_result) {
    if (mysqli_num_rows($employees_result) > 0) {
        while ($row = mysqli_fetch_assoc($employees_result)) {
            $employees[] = $row;
        }
    }
} else {
    // Handle error if employees query fails
    $message .= '<div class="alert alert-danger">Error fetching employee list: ' . mysqli_error($conn) . '</div>';
}

// Get current month and year for defaults
$current_month = date('F'); // Full month name (e.g., "June")
$current_year = date('Y');
$months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Payslip - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card {
            border-radius: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php require_once(__DIR__ . '/include/header.php');?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-head-line">Generate Employee Payslip</h1>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm p-4">
                    <h3 class="mb-4">Payslip Details</h3>
                    <?php echo $message; // Display success/error messages ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="employee_id">Select Employee:</label>
                            <select class="form-control" id="employee_id" name="employee_id" required>
                                <option value="">-- Select Employee --</option>
                                <?php if (!empty($employees)): ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo htmlspecialchars($employee['id']); ?>">
                                            <?php echo htmlspecialchars($employee['name']) . ' (' . htmlspecialchars($employee['email']) . ')'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>No employees found.</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="month">Month:</label>
                                <select class="form-control" id="month" name="month" required>
                                    <?php foreach ($months as $month_name): ?>
                                        <option value="<?php echo htmlspecialchars($month_name); ?>" <?php echo ($month_name == $current_month) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($month_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="year">Year:</label>
                                <input type="number" class="form-control" id="year" name="year" value="<?php echo $current_year; ?>" required min="2000" max="2100">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="basic_salary">Basic Salary:</label>
                            <input type="number" step="0.01" class="form-control" id="basic_salary" name="basic_salary" required min="0">
                        </div>

                        <div class="form-group">
                            <label for="allowances">Allowances:</label>
                            <input type="number" step="0.01" class="form-control" id="allowances" name="allowances" value="0.00" min="0">
                        </div>

                        <div class="form-group">
                            <label for="deductions">Deductions:</label>
                            <input type="number" step="0.01" class="form-control" id="deductions" name="deductions" value="0.00" min="0">
                        </div>

                        <button type="submit" name="generate_payslip" class="btn btn-primary btn-lg w-100 mt-3">
                            Generate Payslip
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once(__DIR__ . '/include/footer.php');?>
</body>
</html>