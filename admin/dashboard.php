<?php
require_once "include/header.php";
?>
<?php

// database connection
require_once "../connection.php";

$currentDay = date('Y-m-d', strtotime("today"));
$tomarrow = date('Y-m-d', strtotime("+1 day"));

$today_leave = 0;
$tomarrow_leave = 0;
$this_week = 0;
$next_week = 0;
$i = 1;
// total admin
$select_admins = "SELECT * FROM admin";
$total_admins = mysqli_query($conn, $select_admins);

// total employee
$select_emp = "SELECT * FROM employee";
$total_emp = mysqli_query($conn, $select_emp);

// employee on leave
$emp_leave  = "SELECT * FROM emp_leave WHERE status = 'Accepted'";
$total_leaves = mysqli_query($conn, $emp_leave);

if (mysqli_num_rows($total_leaves) > 0) {
    while ($leave = mysqli_fetch_assoc($total_leaves)) {
        $leave_date = $leave["start_date"];

        //daywise
        if ($currentDay == $leave_date) {
            $today_leave += 1;
        } elseif ($tomarrow == $leave_date) {
            $tomarrow_leave += 1;
        }
    }
}


// highest paid employee / Leadership Board Data
$sql_leadership_board =  "SELECT * FROM employee ORDER BY salary DESC";
$emp_leadership_board_result = mysqli_query($conn, $sql_leadership_board);

// Total Announcements
$total_announcements_count = 0;
$select_announcements_sql = "SELECT * FROM announcements";
$result_announcements = mysqli_query($conn, $select_announcements_sql);
if ($result_announcements) {
    $total_announcements_count = mysqli_num_rows($result_announcements);
} else {
    $total_announcements_count = "N/A";
}

// Total Tasks (Corrected table name to 'employee_tasks')
$total_tasks_count = 0;
$select_tasks_sql = "SELECT * FROM employee_tasks";
$result_tasks = mysqli_query($conn, $select_tasks_sql);
if ($result_tasks) {
    $total_tasks_count = mysqli_num_rows($result_tasks);
} else {
    $total_tasks_count = "N/A";
}
// --- END NEW WIDGETS DATA FETCHING ---

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet" />
    <style>
        /* General body styling */
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Container padding */
        .container-fluid {
            padding-top: 25px;
            padding-bottom: 25px;
        }

        /* Custom Dashboard Card Styling */
        .dashboard-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            padding: 15px;
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
            /* Ensure cards in a row have equal height */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            /* Distribute space within card */
        }

        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        .dashboard-card .icon {
            font-size: 2.5rem;
            color: #007bff;
            /* Default blue for icons */
            margin-bottom: 10px;
        }

        .dashboard-card h5 {
            font-size: 1.1rem;
            color: #343a40;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .dashboard-card p {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 3px;
        }

        /* Metric values styling */
        .dashboard-card .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        /* Specific colors for metrics */
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

        /* Smaller metric values for combined displays */
        .dashboard-card .metric-value-small {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Custom button styling */
        .dashboard-card .btn-custom {
            margin-top: 10px;
            background-color: #6f42c1;
            /* Consistent button color */
            border-color: #6f42c1;
            color: #fff;
            padding: 6px 15px;
            font-size: 0.9rem;
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
            margin-top: 2rem;
            /* Add margin to separate from cards */
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

        /* Specific styles for condensed leave metrics in admin dashboard */
        .admin-leave-metrics-row {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin-bottom: 10px;
            /* Space above the button */
            font-size: 0.9rem;
            flex-wrap: wrap;
            /* Allows items to wrap on very small screens */
        }

        .admin-leave-metrics-row div {
            flex: 1;
            /* Each item takes equal width */
            text-align: center;
            padding: 0 5px;
            /* Add slight horizontal padding */
        }

        .admin-leave-metrics-row span {
            font-weight: 600;
            display: block;
            /* Make the number appear on a new line below its label */
            font-size: 1.1rem;
            /* Slightly larger for the count */
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row g-4 mt-3">
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-user-shield"></i></div>
                    <h5>Admins</h5>
                    <span class="metric-value blue"><?php echo mysqli_num_rows($total_admins); ?></span>
                    <a href="manage-admin.php" class="btn btn-primary btn-custom">View All Admins</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <h5>Employees</h5>
                    <span class="metric-value blue"><?php echo mysqli_num_rows($total_emp); ?></span>
                    <a href="manage-employee.php" class="btn btn-primary btn-custom">View All Employees</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-calendar-times"></i></div>
                    <h5>Employees on Leave (Daywise)</h5>
                    <div class="admin-leave-metrics-row">
                        <div>Today <span class="orange"><?php echo $today_leave; ?></span></div>
                        <div>Tomorrow <span class="purple"><?php echo $tomarrow_leave; ?></span></div>
                    </div>
                    <a href="manage-leave.php" class="btn btn-primary btn-custom">Manage Employee Leave</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-bullhorn"></i></div>
                    <h5>Total Announcements</h5>
                    <span class="metric-value green"><?php echo $total_announcements_count; ?></span>
                    <a href="create-announcement.php" class="btn btn-primary btn-custom">Create Announcement</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-tasks"></i></div>
                    <h5>Total Tasks Assigned</h5>
                    <span class="metric-value red"><?php echo $total_tasks_count; ?></span>
                    <a href="assign-task.php" class="btn btn-primary btn-custom">Assign New Task</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h5>Generate Payslips</h5>
                    <p>Access payslip generation tools.</p>
                    <a href="generate-payslip.php" class="btn btn-primary btn-custom">Generate Payslip</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-star"></i></div>
                    <h5>Rate Employee</h5>
                    <p>Provide feedback and ratings.</p>
                    <a href="rate-employee.php" class="btn btn-primary btn-custom">Rate Employee</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="dashboard-card">
                    <div class="icon"><i class="fas fa-user-check"></i></div>
                    <h5>Manage Attendance</h5>
                    <p>Oversee employee attendance records.</p>
                    <a href="view-attendance.php" class="btn btn-primary btn-custom">Manage Attendance</a>
                </div>
            </div>

        </div>

        <div class="row leadership-board">
            <div class="col-12">
                <div class=" text-center my-3 ">
                    <h4>Employee Leadership Board</h4>
                </div>
                <div class="table-responsive">
                    <table class="table  table-hover">
                        <thead>
                            <tr class="bg-dark text-white">
                                <th scope="col">S.No.</th>
                                <th scope="col">Employee's Id</th>
                                <th scope="col">Employee's Name</th>
                                <th scope="col">Employee's Email</th>
                                <th scope="col">Salary in Rs.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            // Check if the query result is valid before attempting to use it
                            if ($emp_leadership_board_result && mysqli_num_rows($emp_leadership_board_result) > 0) {
                                while ($emp_info = mysqli_fetch_assoc($emp_leadership_board_result)) {
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
                                echo '<tr><td colspan="5">No employees found for leadership board.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
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