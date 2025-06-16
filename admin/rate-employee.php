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
$admin_email = $_SESSION["email"]; // Get email from session

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

// If admin_id still null, something is wrong (e.g., session email is invalid or DB error)
if (is_null($admin_id)) {
    session_destroy(); // Destroy current session
    header("Location: login.php?error=admin_id_not_found");
    exit();
}
// --- END Admin ID Lookup ---


$employee_id_err = $rating_score_err = $success_msg = $error_msg = "";
$selected_employee_id = "";
$rating_comments = "";
$rating_score = "";

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


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Validate Inputs
    // Employee ID
    if (empty($_POST["employee_id"])) {
        $employee_id_err = "Please select an employee.";
    } else {
        $selected_employee_id = (int)$_POST["employee_id"];
        // Basic check to ensure it's a valid ID from the fetched list
        $found = false;
        foreach ($employees as $emp) {
            if ($emp['id'] == $selected_employee_id) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $employee_id_err = "Invalid employee selected.";
            $selected_employee_id = ""; // Reset to empty if invalid
        }
    }

    // Rating Score
    if (empty($_POST["rating_score"]) || !is_numeric($_POST["rating_score"])) {
        $rating_score_err = "Please select a valid rating score (1-5).";
    } else {
        $rating_score = (int)$_POST["rating_score"];
        if ($rating_score < 1 || $rating_score > 5) {
            $rating_score_err = "Rating must be between 1 and 5.";
        }
    }

    // Rating Comments (optional)
    $rating_comments = htmlspecialchars(trim($_POST["rating_comments"]));


    // 2. Insert into Database if no errors
    if (empty($employee_id_err) && empty($rating_score_err)) {
        $sql_insert = "INSERT INTO employee_ratings (employee_id, admin_id, rating_score, rating_comments) VALUES (?, ?, ?, ?)";

        $stmt_insert = mysqli_prepare($conn, $sql_insert);

        if ($stmt_insert) {
            mysqli_stmt_bind_param($stmt_insert, "iiss", $selected_employee_id, $admin_id, $rating_score, $rating_comments);

            if (mysqli_stmt_execute($stmt_insert)) {
                $success_msg = "Employee rated successfully!";
                // Clear form fields after successful submission
                $selected_employee_id = "";
                $rating_comments = "";
                $rating_score = ""; // Clear selected radio button
            } else {
                $error_msg = "Error submitting rating: " . mysqli_stmt_error($stmt_insert);
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $error_msg = "Database statement preparation failed: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Please correct the errors in the form.";
    }
}

// Close the main database connection at the end of the script
if (isset($conn) && is_object($conn)) {
    mysqli_close($conn);
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Employee - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 30px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-control,
        .btn {
            border-radius: 5px;
        }

        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
        }

        .rating-stars {
            display: flex;
            /* Use flexbox */
            flex-direction: row-reverse;
            /* Arrange stars visually from right to left */
            justify-content: flex-end;
            /* Align to the right so 1-5 appears left to right */
            gap: 0.5rem;
            /* Add some spacing between stars */
        }

        .rating-stars .form-check-inline {
            margin-right: 0;
            /* Remove default inline margin */
            padding: 0;
            /* Remove default inline padding */
        }

        .rating-stars .form-check-input {
            display: none;
            /* Hide the actual radio button */
        }

        .rating-stars .form-check-label {
            cursor: pointer;
            font-size: 1.8em;
            /* Adjusted star size for better visibility */
            color: #ddd;
            /* Default star color (light gray) */
            transition: color 0.2s ease-in-out;
        }

        /* Color the hovered star and all stars visually BEFORE it */
        .rating-stars .form-check-label:hover,
        .rating-stars .form-check-label:hover~.form-check-label {
            color: #ffc107;
            /* Gold color on hover */
        }

        /* Color the checked star and all stars visually BEFORE it */
        .rating-stars .form-check-input:checked~.form-check-label {
            color: #ffc107;
            /* Gold color when checked */
        }
    </style>
</head>

<body>
    <?php require_once(__DIR__ . '/include/header.php'); // Adjust path as needed 
    ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4">
                    <h2 class="card-title text-center mb-4">Rate Employee Performance</h2>

                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success_msg; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_msg; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Select Employee:</label>
                            <select class="form-control" id="employee_id" name="employee_id" required>
                                <option value="">-- Select Employee --</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo htmlspecialchars($employee['id']); ?>"
                                        <?php echo ($selected_employee_id == $employee['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['name'] . " (" . $employee['email'] . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message"><?php echo $employee_id_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rating Score (1-5):</label><br>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="rating_score" id="rating<?php echo $i; ?>" value="<?php echo $i; ?>" <?php echo ($rating_score == $i) ? 'checked' : ''; ?> required>
                                        <label class="form-check-label" for="rating<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <div class="error-message"><?php echo $rating_score_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="rating_comments" class="form-label">Comments (Optional):</label>
                            <textarea class="form-control" id="rating_comments" name="rating_comments" rows="4"><?php echo htmlspecialchars($rating_comments); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Submit Rating</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once(__DIR__ . '/include/footer.php'); // Adjust path as needed 
    ?>
</body>

</html>