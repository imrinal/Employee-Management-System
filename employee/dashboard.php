<?php
// --- NEW CODE ADDED HERE ---
// 1. Start the session at the very first executable line of the file.
session_start();

// 2. CRITICAL: Check if the employee is logged in.
//    If not, redirect them to the login page immediately.
if (!isset($_SESSION["email_emp"]) || strlen($_SESSION["email_emp"]) == 0 || !isset($_SESSION["id"])) {
    header("Location: login.php");
    exit(); // Always exit after a header redirect to prevent further script execution.
}
// --- END OF NEW CODE ---

require_once "include/header.php";
?>
<?php
// database connection
require_once "../connection.php";

$i = 1;


// Applied leaves summary --------------------------------------------------------------------------------------------
$total_accepted_leaves = $total_pending_leaves = $total_canceled_leaves = $total_applied_leaves = 0;
// Use prepared statements to prevent SQL Injection, especially when using $_SESSION data in queries.
$leave_sql = "SELECT status FROM emp_leave WHERE email = ?";
$stmt_leave = mysqli_prepare($conn, $leave_sql);
if ($stmt_leave) {
    mysqli_stmt_bind_param($stmt_leave, "s", $_SESSION['email_emp']);
    mysqli_stmt_execute($stmt_leave);
    $result = mysqli_stmt_get_result($stmt_leave);

    if (mysqli_num_rows($result) > 0) {
        $total_applied_leaves = mysqli_num_rows($result);
        while ($leave_info = mysqli_fetch_assoc($result)) {
            $status = $leave_info["status"];

            if ($status == "pending") {
                $total_pending_leaves += 1;
            } elseif ($status == "Accepted") {
                $total_accepted_leaves += 1;
            } elseif ($status == "Canceled") {
                $total_canceled_leaves += 1;
            }
        }
    }
    mysqli_stmt_close($stmt_leave);
} else {
    // Handle error if statement preparation fails
    error_log("Error preparing leave status query: " . mysqli_error($conn));
}


// Leave status (Last & Upcoming) ------------------------------------------------------------------------------------
$currentDay = date('Y-m-d', strtotime("today"));

$last_leave_status = "No leave applied";
$upcoming_leave_date = "N/A";

// For last leave status (using prepared statement)
$check_leave_sql = "SELECT status FROM emp_leave WHERE email = ? ORDER BY id DESC LIMIT 1";
$stmt_check_leave = mysqli_prepare($conn, $check_leave_sql);
if ($stmt_check_leave) {
    mysqli_stmt_bind_param($stmt_check_leave, "s", $_SESSION['email_emp']);
    mysqli_stmt_execute($stmt_check_leave);
    $s = mysqli_stmt_get_result($stmt_check_leave);
    if (mysqli_num_rows($s) > 0) {
        $info = mysqli_fetch_assoc($s);
        $last_leave_status = $info["status"];
    }
    mysqli_stmt_close($stmt_check_leave);
} else {
    error_log("Error preparing last leave status query: " . mysqli_error($conn));
}

// For next accepted leave date (using prepared statement)
$check_sql_next_leave = "SELECT start_date FROM emp_leave WHERE email = ? AND status = 'Accepted' AND start_date >= ? ORDER BY start_date ASC LIMIT 1";
$stmt_next_leave = mysqli_prepare($conn, $check_sql_next_leave);
if ($stmt_next_leave) {
    mysqli_stmt_bind_param($stmt_next_leave, "ss", $_SESSION['email_emp'], $currentDay);
    mysqli_stmt_execute($stmt_next_leave);
    $e = mysqli_stmt_get_result($stmt_next_leave);
    if (mysqli_num_rows($e) > 0) {
        $info = mysqli_fetch_assoc($e);
        $date = $info["start_date"];
        $upcoming_leave_date = date('jS F, Y', strtotime($date));
    }
    mysqli_stmt_close($stmt_next_leave);
} else {
    error_log("Error preparing next leave date query: " . mysqli_error($conn));
}


// Total employees (for leadership board) --------------------------------------------------------------------
$select_emp = "SELECT COUNT(id) AS total_employees FROM employee";
$result_total_employees = mysqli_query($conn, $select_emp);
$total_employees = 0;
if ($result_total_employees && mysqli_num_rows($result_total_employees) > 0) {
    $row = mysqli_fetch_assoc($result_total_employees);
    $total_employees = $row['total_employees'];
}


// Highest paid employee (Leadership Board) ------------------------------------------------------------------
$sql_leadership_board = "SELECT id, name, email, salary FROM employee ORDER BY salary DESC";
$leadership_board_result = mysqli_query($conn, $sql_leadership_board);

// Payslip Summary ------------------------------------------------------------------------------------------
$last_payslip_date = "N/A";
$sql_payslip = "SELECT generation_date FROM pay_slips WHERE employee_id = ? ORDER BY generation_date DESC LIMIT 1";
$stmt_payslip = mysqli_prepare($conn, $sql_payslip);
if ($stmt_payslip) {
    mysqli_stmt_bind_param($stmt_payslip, "i", $_SESSION['id']); // Assuming $_SESSION['id'] holds the employee's ID
    mysqli_stmt_execute($stmt_payslip);
    $result_payslip = mysqli_stmt_get_result($stmt_payslip);
    if (mysqli_num_rows($result_payslip) > 0) {
        $payslip_info = mysqli_fetch_assoc($result_payslip);
        $last_payslip_date = date('jS F, Y', strtotime($payslip_info['generation_date']));
    }
    mysqli_stmt_close($stmt_payslip);
} else {
    error_log("Error preparing payslip query: " . mysqli_error($conn));
}

// Attendance Summary ---------------------------------------------------------------------------------------
$last_attendance_status = "N/A";
$today_check_in = "N/A";
$today_check_out = "N/A";

$sql_attendance = "SELECT check_in, check_out FROM attendance WHERE employee_id = ? AND date = CURDATE()";
$stmt_attendance = mysqli_prepare($conn, $sql_attendance);
if ($stmt_attendance) {
    mysqli_stmt_bind_param($stmt_attendance, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt_attendance);
    $result_attendance = mysqli_stmt_get_result($stmt_attendance);
    if (mysqli_num_rows($result_attendance) > 0) {
        $attendance_info = mysqli_fetch_assoc($result_attendance);
        $today_check_in = $attendance_info['check_in'] ? date('h:i A', strtotime($attendance_info['check_in'])) : "Not yet";
        $today_check_out = $attendance_info['check_out'] ? date('h:i A', strtotime($attendance_info['check_out'])) : "Not yet";

        if ($attendance_info['check_in'] && $attendance_info['check_out']) {
            $last_attendance_status = "Checked Out";
        } elseif ($attendance_info['check_in']) {
            $last_attendance_status = "Checked In";
        }
    } else {
        $last_attendance_status = "Not marked today";
    }
    mysqli_stmt_close($stmt_attendance);
} else {
    error_log("Error preparing attendance query: " . mysqli_error($conn));
}

// Upcoming Tasks Summary -----------------------------------------------------------------------------------
$upcoming_tasks_count = 0;
$next_task_title = "No upcoming tasks";
$sql_upcoming_tasks = "SELECT task_title, deadline FROM employee_tasks WHERE employee_id = ? AND status IN ('Not Started', 'In Progress') AND deadline >= CURDATE() ORDER BY deadline ASC LIMIT 1";
$stmt_upcoming_tasks = mysqli_prepare($conn, $sql_upcoming_tasks);
if ($stmt_upcoming_tasks) {
    mysqli_stmt_bind_param($stmt_upcoming_tasks, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt_upcoming_tasks);
    $result_upcoming_tasks = mysqli_stmt_get_result($stmt_upcoming_tasks);

    $count_sql = "SELECT COUNT(id) AS total_upcoming_tasks FROM employee_tasks WHERE employee_id = ? AND status IN ('Not Started', 'In Progress') AND deadline >= CURDATE()";
    $count_stmt = mysqli_prepare($conn, $count_sql);
    if ($count_stmt) {
        mysqli_stmt_bind_param($count_stmt, "i", $_SESSION['id']);
        mysqli_stmt_execute($count_stmt);
        $count_result = mysqli_stmt_get_result($count_stmt);
        if ($count_row = mysqli_fetch_assoc($count_result)) {
            $upcoming_tasks_count = $count_row['total_upcoming_tasks'];
        }
        mysqli_stmt_close($count_stmt);
    }

    if (mysqli_num_rows($result_upcoming_tasks) > 0) {
        $task_info = mysqli_fetch_assoc($result_upcoming_tasks);
        $next_task_title = htmlspecialchars($task_info['task_title']) . " (Due: " . date('jS F', strtotime($task_info['deadline'])) . ")";
    }
    mysqli_stmt_close($stmt_upcoming_tasks);
} else {
    error_log("Error preparing upcoming tasks query: " . mysqli_error($conn));
}

// Average Rating -------------------------------------------------------------------------------------------
$average_rating = "N/A";
$sql_avg_rating = "SELECT AVG(rating_score) AS avg_score FROM employee_ratings WHERE employee_id = ?";
$stmt_avg_rating = mysqli_prepare($conn, $sql_avg_rating);
if ($stmt_avg_rating) {
    mysqli_stmt_bind_param($stmt_avg_rating, "i", $_SESSION['id']);
    mysqli_stmt_execute($stmt_avg_rating);
    $result_avg_rating = mysqli_stmt_get_result($stmt_avg_rating);
    if (mysqli_num_rows($result_avg_rating) > 0) {
        $rating_info = mysqli_fetch_assoc($result_avg_rating);
        if ($rating_info['avg_score'] !== null) {
            $average_rating = number_format($rating_info['avg_score'], 1) . " / 5";
        } else {
            $average_rating = "No ratings yet";
        }
    } else {
        $average_rating = "No ratings yet";
    }
    mysqli_stmt_close($stmt_avg_rating);
} else {
    error_log("Error preparing average rating query: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-card {
            background-color: #fff;
            border-radius: 8px;
            /* Slightly less rounded for a tighter look */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            /* Smaller shadow */
            padding: 15px;
            /* Reduced padding for smaller cards */
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
            /* Smaller lift on hover */
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
            /* Slightly larger shadow on hover */
        }

        .dashboard-card .icon {
            font-size: 2.5rem;
            /* Smaller icons */
            color: #007bff;
            margin-bottom: 10px;
            /* Reduced margin */
        }

        .dashboard-card h5 {
            font-size: 1.1rem;
            /* Smaller heading */
            color: #343a40;
            margin-bottom: 8px;
            /* Reduced margin */
            font-weight: 600;
        }

        .dashboard-card p {
            font-size: 0.9rem;
            /* Smaller paragraph text */
            color: #6c757d;
            margin-bottom: 3px;
            /* Reduced margin */
        }

        .dashboard-card .metric-value {
            font-size: 1.8rem;
            /* Smaller metric value */
            font-weight: 700;
            margin-bottom: 8px;
            /* Reduced margin */
        }

        .dashboard-card .metric-value.green {
            color: #28a745;
        }

        .dashboard-card .metric-value.red {
            color: #dc3545;
        }

        .dashboard-card .metric-value.blue {
            color: #007bff;
        }

        .dashboard-card .metric-value.orange {
            color: #fd7e14;
        }

        .dashboard-card .metric-value.purple {
            color: #6f42c1;
        }

        /* New color for ratings */

        /* Specific style for small metric values within cards */
        .dashboard-card .metric-value-small {
            font-size: 1.1rem;
            /* For "Total Applied Leaves" breakdown */
            font-weight: 600;
        }

        .dashboard-card .btn-custom {
            margin-top: 10px;
            /* Reduced margin */
            background-color: #6f42c1;
            border-color: #6f42c1;
            color: #fff;
            padding: 6px 15px;
            /* Smaller padding for button */
            font-size: 0.9rem;
            /* Smaller button font */
            border-radius: 5px;
            transition: background-color 0.2s ease;
            width: 100%;
            /* Make button full width of card */
        }

        .dashboard-card .btn-custom:hover {
            background-color: #5a359c;
            border-color: #5a359c;
        }

        /* Styles for the leadership board table */
        .leadership-board {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }

        .leadership-board h4 {
            color: #343a40;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        .leadership-board .table th,
        .leadership-board .table td {
            vertical-align: middle;
            text-align: center;
            font-size: 0.9rem;
            /* Smaller table text */
        }

        .leadership-board .table thead th {
            background-color: #343a40;
            color: #fff;
            border-color: #495057;
        }

        .leadership-board .table tbody tr:nth-of-type(even) {
            background-color: rgba(0, 0, 0, 0.03);
        }

        .leadership-board .table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.075);
        }

        .container-fluid {
            padding-top: 25px;
            /* Slightly reduced overall padding */
            padding-bottom: 25px;
        }

        /* Custom styles for condensed leave metrics */
        .leave-metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* Two columns */
            gap: 5px 10px;
            /* Vertical and horizontal gap */
            margin-bottom: 10px;
            /* Space before button */
            font-size: 0.9rem;
            /* Slightly smaller font for these labels */
        }

        .leave-metrics-grid .metric-item {
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .leave-metrics-grid .metric-item span {
            font-weight: 600;
            /* Make the numbers bold */
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <h5>Total Employees</h5>
                    <span class="metric-value blue"><?php echo $total_employees; ?></span>
                    <a href="view-employee.php" class="btn btn-primary btn-custom">View All Employees</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                    <h5>Next Leave</h5>
                    <span class="metric-value blue mb-0"><?php echo htmlspecialchars($upcoming_leave_date); ?></span>
                    <p class="mb-2" style="font-size:0.8rem;">Last Status: <span class="fw-bold <?php echo ($last_leave_status == 'Accepted') ? 'text-success' : (($last_leave_status == 'pending') ? 'text-warning' : 'text-danger'); ?>"><?php echo ucwords(htmlspecialchars($last_leave_status)); ?></span></p>
                    <a href="leave-status.php" class="btn btn-primary btn-custom">View Leave Status</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                    <h5>Applied Leaves</h5>
                    <div class="leave-metrics-grid">
                        <div class="metric-item">Total: <span class="blue"><?php echo $total_applied_leaves; ?></span></div>
                        <div class="metric-item">Accepted: <span class="green"><?php echo $total_accepted_leaves; ?></span></div>
                        <div class="metric-item">Pending: <span class="orange"><?php echo $total_pending_leaves; ?></span></div>
                        <div class="metric-item">Canceled: <span class="red"><?php echo $total_canceled_leaves; ?></span></div>
                    </div>
                    <a href="apply-leave.php" class="btn btn-primary btn-custom">Apply New Leave</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-money-check-alt"></i></div>
                    <h5>Last Payslip</h5>
                    <span class="metric-value purple"><?php echo htmlspecialchars($last_payslip_date); ?></span>
                    <a href="view-my-payslips.php" class="btn btn-primary btn-custom">View My Payslips</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-user-clock"></i></div>
                    <h5>Today's Attendance</h5>
                    <p class="mb-0">Status: <span class="fw-bold text-info"><?php echo htmlspecialchars($last_attendance_status); ?></span></p>
                    <p class="mb-0" style="font-size:0.8rem;">Check-in: <span class="fw-bold"><?php echo htmlspecialchars($today_check_in); ?></span></p>
                    <p class="mb-0" style="font-size:0.8rem;">Check-out: <span class="fw-bold"><?php echo htmlspecialchars($today_check_out); ?></span></p>
                    <a href="mark-attendance.php" class="btn btn-primary btn-custom">Mark Attendance</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-tasks"></i></div>
                    <h5>Upcoming Tasks</h5>
                    <p class="mb-0">Total: <span class="metric-value-small blue"><?php echo $upcoming_tasks_count; ?></span></p>
                    <p class="mb-0" style="font-size:0.9rem;">Next: <span class="fw-bold"><?php echo htmlspecialchars($next_task_title); ?></span></p>
                    <a href="my-tasks.php" class="btn btn-primary btn-custom">View My Tasks</a>
                </div>
            </div>

            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-star"></i></div>
                    <h5>My Average Rating</h5>
                    <span class="metric-value purple"><?php echo htmlspecialchars($average_rating); ?></span>
                    <a href="view-ratings.php" class="btn btn-primary btn-custom">View My Ratings</a>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="leadership-board">
                    <h4>Employee Leadership Board</h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th scope="col">S.No.</th>
                                    <th scope="col">Employee's Id</th>
                                    <th scope="col">Employee's Name</th>
                                    <th scope="col">Employee's Email</th>
                                    <th scope="col">Salary in Rs.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1; // Reset counter for the table
                                if (mysqli_num_rows($leadership_board_result) > 0) {
                                    while ($emp_info = mysqli_fetch_assoc($leadership_board_result)) {
                                        $emp_id = $emp_info["id"];
                                        $emp_name = $emp_info["name"];
                                        $emp_email = $emp_info["email"];
                                        $emp_salary = $emp_info["salary"];
                                ?>
                                        <tr>
                                            <td><?php echo "$i. "; ?></td>
                                            <td><?php echo htmlspecialchars($emp_id); ?></td>
                                            <td><?php echo htmlspecialchars($emp_name); ?></td>
                                            <td><?php echo htmlspecialchars($emp_email); ?></td>
                                            <td><?php echo htmlspecialchars($emp_salary); ?></td>
                                        </tr>
                                <?php
                                        $i++;
                                    }
                                } else {
                                    echo '<tr><td colspan="5">No employees found.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    require_once "include/footer.php";
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</body>

</html>