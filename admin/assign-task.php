<?php
session_start(); // Start the session at the very beginning

// Check if admin is logged in
if (!isset($_SESSION["email"]) || strlen($_SESSION["email"]) == 0) {
    header("Location: login.php"); // Redirect to admin login page
    exit(); // Always exit after a header redirect
}

require_once "../connection.php"; // Database connection

// --- Admin ID Lookup (Workaround for login.php not setting $_SESSION['id']) ---
$admin_id = null;
$admin_email = $_SESSION["email"];

$sql_get_admin_id = "SELECT id FROM admin WHERE email = ?";
$stmt_admin_id = mysqli_prepare($conn, $sql_get_admin_id);

if ($stmt_admin_id) {
    mysqli_stmt_bind_param($stmt_admin_id, "s", $admin_email);
    mysqli_stmt_execute($stmt_admin_id);
    $result_admin_id = mysqli_stmt_get_result($stmt_admin_id);
    if ($admin_row = mysqli_fetch_assoc($result_admin_id)) {
        $admin_id = $admin_row['id'];
    }
    mysqli_stmt_close($stmt_admin_id);
}

// If admin_id still null, something is wrong
if (is_null($admin_id)) {
    session_destroy();
    header("Location: login.php?error=admin_id_not_found");
    exit();
}
// --- END Admin ID Lookup ---

$task_title = $task_description = $employee_id = $deadline = "";
$task_title_err = $employee_id_err = $deadline_err = $error_msg = $success_msg = "";

// Fetch all employees for the dropdown list
$employees = [];
$sql_employees = "SELECT id, name, email FROM employee ORDER BY name ASC";
$result_employees = mysqli_query($conn, $sql_employees);

if ($result_employees) {
    while ($row = mysqli_fetch_assoc($result_employees)) {
        $employees[] = $row;
    }
} else {
    $error_msg .= "Error fetching employee list: " . mysqli_error($conn);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate Task Title
    if (empty(trim($_POST["task_title"]))) {
        $task_title_err = "Please enter a task title.";
    } else {
        $task_title = trim($_POST["task_title"]);
    }

    // Validate Employee
    if (empty($_POST["employee_id"])) {
        $employee_id_err = "Please select an employee.";
    } else {
        $employee_id = (int)$_POST["employee_id"];
        // Ensure the selected employee_id actually exists in the fetched list
        $found_employee = false;
        foreach ($employees as $emp) {
            if ($emp['id'] == $employee_id) {
                $found_employee = true;
                break;
            }
        }
        if (!$found_employee) {
            $employee_id_err = "Invalid employee selected.";
            $employee_id = ""; // Reset to prevent using invalid ID
        }
    }

    // Validate Deadline
    if (empty(trim($_POST["deadline"]))) {
        $deadline_err = "Please select a deadline.";
    } else {
        $deadline = $_POST["deadline"];
        // Basic date validation: ensure it's a future date if desired
        $current_datetime = new DateTime();
        $selected_datetime = new DateTime($deadline);
        if ($selected_datetime < $current_datetime) {
            $deadline_err = "Deadline must be in the future.";
        }
    }

    // Task Description (optional)
    $task_description = trim($_POST["task_description"]);


    // If no errors, insert into database
    if (empty($task_title_err) && empty($employee_id_err) && empty($deadline_err)) {
        $sql_insert_task = "INSERT INTO employee_tasks (task_title, task_description, employee_id, admin_id, deadline) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($conn, $sql_insert_task)) {
            mysqli_stmt_bind_param($stmt, "ssiis", $task_title, $task_description, $employee_id, $admin_id, $deadline);

            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Task assigned successfully!";
                // Clear form fields after successful submission
                $task_title = $task_description = $employee_id = $deadline = "";
            } else {
                $error_msg = "Error assigning task: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_msg = "Database statement preparation failed: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Please correct the errors in the form.";
    }
}

// Close the main database connection
if (isset($conn) && is_object($conn)) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Task - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { margin-top: 30px; }
        .card { border-radius: 15px; box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        .form-control, .btn { border-radius: 8px; }
        .error-message { color: #dc3545; font-size: 0.9em; margin-top: 5px; }
        .page-head-line {
            color: #343a40;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <?php require_once(__DIR__ . '/include/header.php'); ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <h1 class="page-head-line text-center">Assign New Task</h1>
                <div class="card p-4">
                    <div class="card-body">
                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($success_msg); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($error_msg); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                            <div class="mb-3">
                                <label for="task_title" class="form-label">Task Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo (!empty($task_title_err)) ? 'is-invalid' : ''; ?>" id="task_title" name="task_title" value="<?php echo htmlspecialchars($task_title); ?>" required>
                                <div class="invalid-feedback"><?php echo $task_title_err; ?></div>
                            </div>

                            <div class="mb-3">
                                <label for="task_description" class="form-label">Task Description</label>
                                <textarea class="form-control" id="task_description" name="task_description" rows="5"><?php echo htmlspecialchars($task_description); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="employee_id" class="form-label">Assign to Employee <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo (!empty($employee_id_err)) ? 'is-invalid' : ''; ?>" id="employee_id" name="employee_id" required>
                                    <option value="">-- Select Employee --</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo htmlspecialchars($employee['id']); ?>" 
                                                <?php echo ($employee_id == $employee['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employee['name'] . " (" . $employee['email'] . ")"); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $employee_id_err; ?></div>
                            </div>

                            <div class="mb-3">
                                <label for="deadline" class="form-label">Deadline <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control <?php echo (!empty($deadline_err)) ? 'is-invalid' : ''; ?>" id="deadline" name="deadline" value="<?php echo htmlspecialchars($deadline); ?>" required>
                                <div class="invalid-feedback"><?php echo $deadline_err; ?></div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Assign Task</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once(__DIR__ . '/include/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>