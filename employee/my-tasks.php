<?php
session_start();
date_default_timezone_set('Asia/Kolkata'); 

// Redirect if employee is not logged in
if (!isset($_SESSION["email_emp"]) || strlen($_SESSION["email_emp"]) == 0 || !isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

require_once "../connection.php"; // Database connection

$employee_id = $_SESSION['id']; // Get the logged-in employee's ID
$tasks = [];
$error_msg = "";
$success_msg = "";

// Handle task status update POST request from employee
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['task_id']) && isset($_POST['new_status'])) {
    $task_id = (int)$_POST['task_id'];
    $new_status = $_POST['new_status'];

    // Validate new status against allowed ENUM values
    $valid_statuses = ['Not Started', 'In Progress', 'Done'];
    if (!in_array($new_status, $valid_statuses)) {
        $error_msg = "Invalid status provided.";
    } else {
        // IMPORTANT SECURITY CHECK: Ensure the task belongs to the logged-in employee
        $sql_check_owner = "SELECT id, status FROM employee_tasks WHERE id = ? AND employee_id = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check_owner);
        mysqli_stmt_bind_param($stmt_check, "ii", $task_id, $employee_id);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        $task_row = mysqli_fetch_assoc($result_check);

        if ($task_row) { // If task exists and belongs to employee
            $current_status = $task_row['status'];
            $completed_date_sql = "";

            if ($new_status == 'Done' && $current_status != 'Done') {
                $completed_date_sql = ", completed_date = NOW()"; // Set completed date only when changing to 'Done'
            } elseif ($new_status != 'Done' && $current_status == 'Done') {
                $completed_date_sql = ", completed_date = NULL"; // Clear completed date if changing from 'Done'
            }
            
            $sql_update_status = "UPDATE employee_tasks SET status = ? $completed_date_sql WHERE id = ?";
            
            if ($stmt_update = mysqli_prepare($conn, $sql_update_status)) {
                mysqli_stmt_bind_param($stmt_update, "si", $new_status, $task_id);
                if (mysqli_stmt_execute($stmt_update)) {
                    $success_msg = "Task status updated successfully!";
                } else {
                    $error_msg = "Error updating task status: " . mysqli_stmt_error($stmt_update);
                }
                mysqli_stmt_close($stmt_update);
            } else {
                $error_msg = "Database statement preparation failed: " . mysqli_error($conn);
            }
        } else {
            $error_msg = "Task not found or you are not authorized to update it.";
        }
        mysqli_stmt_close($stmt_check);
    }
}


// Fetch tasks for the logged-in employee
$sql_fetch_tasks = "SELECT et.id, et.task_title, et.task_description, et.deadline, et.status, et.assigned_date, et.completed_date,
                    a.name AS admin_name
                    FROM employee_tasks et
                    JOIN admin a ON et.admin_id = a.id
                    WHERE et.employee_id = ?
                    ORDER BY et.deadline ASC"; // Order by deadline

if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch_tasks)) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $employee_id);
    mysqli_stmt_execute($stmt_fetch);
    $result_tasks = mysqli_stmt_get_result($stmt_fetch);

    if (mysqli_num_rows($result_tasks) > 0) {
        while ($row = mysqli_fetch_assoc($result_tasks)) {
            $tasks[] = $row;
        }
    }
    mysqli_stmt_close($stmt_fetch);
} else {
    $error_msg = "Error fetching tasks: " . mysqli_error($conn);
}

// Close database connection
if (isset($conn) && is_object($conn)) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks - Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet" /> 

    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding-top: 30px; padding-bottom: 30px; }
        .page-head-line {
            color: #343a40;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
            font-weight: 700;
        }
        .task-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.2s ease-in-out;
            height: 100%; /* Ensures cards in a row have same height */
            position: relative; /* For timer positioning */
        }
        .task-card:hover {
            transform: translateY(-5px);
        }
        .task-card h5 {
            color: #007bff;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .task-card .badge {
            font-size: 0.8em;
            padding: 0.4em 0.7em;
            border-radius: 5px;
        }
        .task-card .description {
            font-size: 0.95em;
            color: #555;
            margin-bottom: 15px;
        }
        .task-card .meta-info {
            font-size: 0.85em;
            color: #6c757d;
            border-top: 1px solid #f0f2f5;
            padding-top: 10px;
            margin-top: 15px;
        }
        .status-dropdown {
            width: auto; /* Adjust width to content */
            display: inline-block; /* Keep it on one line with badge */
            font-size: 0.9em;
        }
        .deadline-timer {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: #f8f9fa; /* Light background for timer */
            border: 1px solid #e9ecef;
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.85em;
            font-weight: bold;
            color: #343a40;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .deadline-timer.overdue {
            background-color: #dc3545; /* Red for overdue */
            color: white;
        }
        .deadline-timer.warning {
            background-color: #ffc107; /* Yellow for warning (less than 24 hours) */
            color: #343a40;
        }
        .deadline-timer i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <?php require_once(__DIR__ . '/include/header.php'); // Path to your employee header ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line text-center">My Assigned Tasks</h1>
                </div>
            </div>

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

            <?php if (!empty($tasks)): ?>
                <div class="row">
                    <?php foreach ($tasks as $task): ?>
                        <div class="col-md-6 col-lg-4 d-flex">
                            <div class="task-card flex-fill" data-deadline="<?php echo strtotime(htmlspecialchars($task['deadline'])) * 1000; ?>" data-status="<?php echo htmlspecialchars($task['status']); ?>">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="flex-grow-1 me-2"><?php echo htmlspecialchars($task['task_title']); ?></h5>
                                    <span class="badge 
                                        <?php 
                                            // Dynamic badge coloring based on status
                                            if ($task['status'] == 'Not Started') echo 'bg-secondary';
                                            elseif ($task['status'] == 'In Progress') echo 'bg-info';
                                            elseif ($task['status'] == 'Done') echo 'bg-success';
                                        ?>
                                    ">
                                        <?php echo htmlspecialchars($task['status']); ?>
                                    </span>
                                </div>
                                <div class="deadline-timer" id="timer-<?php echo htmlspecialchars($task['id']); ?>">
                                    </div>
                                <p class="description">
                                    <?php echo nl2br(htmlspecialchars($task['task_description'])); ?>
                                </p>
                                <div class="meta-info">
                                    <p class="mb-1"><strong>Assigned by:</strong> <?php echo htmlspecialchars($task['admin_name']); ?></p>
                                    <p class="mb-1"><strong>Deadline:</strong> <?php echo date('F j, Y, h:i A', strtotime(htmlspecialchars($task['deadline']))); ?></p>
                                    <p class="mb-1"><strong>Assigned Date:</strong> <?php echo date('F j, Y', strtotime(htmlspecialchars($task['assigned_date']))); ?></p>
                                    <?php if ($task['status'] == 'Done'): ?>
                                        <p class="mb-1"><strong>Completed Date:</strong> <?php echo date('F j, Y, h:i A', strtotime(htmlspecialchars($task['completed_date']))); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-3">
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                                        <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($task['id']); ?>">
                                        <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($task['status']); ?>">
                                        <label for="status-<?php echo htmlspecialchars($task['id']); ?>" class="form-label mb-1">Update Status:</label>
                                        <select name="new_status" id="status-<?php echo htmlspecialchars($task['id']); ?>" class="form-select form-select-sm status-dropdown" onchange="this.form.submit()">
                                            <option value="Not Started" <?php echo ($task['status'] == 'Not Started') ? 'selected' : ''; ?>>Not Started</option>
                                            <option value="In Progress" <?php echo ($task['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="Done" <?php echo ($task['status'] == 'Done') ? 'selected' : ''; ?>>Done</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center py-4 my-5">
                    <h3>No Tasks Assigned!</h3>
                    <p>It looks like you don't have any tasks assigned to you yet. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once(__DIR__ . '/include/footer.php'); // Path to your employee footer ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // JavaScript for Countdown Timer functionality
    document.addEventListener('DOMContentLoaded', function() {
        const taskCards = document.querySelectorAll('.task-card');

        function updateTimers() {
            const currentTime = new Date().getTime(); // Current time in milliseconds

            taskCards.forEach(card => {
                const deadlineTimestamp = parseInt(card.dataset.deadline); // Deadline in milliseconds
                const taskStatus = card.dataset.status;
                const timerElement = card.querySelector('.deadline-timer');

                // If task is 'Done', display a 'Completed' message and stop countdown
                if (taskStatus === 'Done') {
                    timerElement.innerHTML = '<i class="fas fa-check-circle"></i> Completed';
                    timerElement.classList.remove('overdue', 'warning');
                    timerElement.classList.add('bg-light', 'text-success'); // Neutral background for completed tasks
                    return; // Stop processing this card's timer
                }

                const distance = deadlineTimestamp - currentTime; // Time remaining in milliseconds

                // Clear previous classes
                timerElement.classList.remove('overdue', 'warning', 'bg-light', 'text-success');

                if (distance < 0) {
                    // Task is Overdue
                    timerElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Overdue!';
                    timerElement.classList.add('overdue');
                } else {
                    // Task is still active, calculate remaining time
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    let timerText = '<i class="fas fa-hourglass-half"></i> ';
                    if (days > 0) {
                        timerText += days + "d ";
                    }
                    timerText += hours + "h " + minutes + "m " + seconds + "s";
                    
                    timerElement.innerHTML = timerText;

                    // Add warning styling if less than 24 hours remaining
                    if (distance < (1000 * 60 * 60 * 24)) {
                        timerElement.classList.add('warning');
                    }
                }
            });
        }

        // Update timers every second
        setInterval(updateTimers, 1000);
        // Initial call to display timers immediately when page loads
        updateTimers();
    });
    </script>
</body>
</html>