<?php
session_start(); // Start the session at the very beginning

// Check if admin is logged in (using the email, as ID is not set by your login.php)
if (!isset($_SESSION["email"]) || strlen($_SESSION["email"]) == 0) {
    header("Location: login.php"); // Redirect to admin login page
    exit(); // Always exit after a header redirect
}

require_once "../connection.php"; // Database connection

// --- WORKAROUND START: Fetch admin_id from database because login.php doesn't set it ---
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
    // This is a critical state, force logout or show a serious error.
    // It means the email in session doesn't correspond to an admin ID.
    session_destroy(); // Destroy current session
    header("Location: login.php?error=admin_id_not_found");
    exit();
}
// --- WORKAROUND END ---


$title_err = $message_err = $target_err = $employee_id_err = $file_err = "";
$title = $message = $target_type = $selected_employee_id = "";
$upload_file_path = null;
$success_msg = $error_msg = "";

// Define upload directory relative to the connection.php or a common root.
// Assuming 'uploads' is parallel to 'admin' folder.
$upload_dir = '../uploads/announcements/';

// Create the directory if it doesn't exist
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0775, true); // Create recursively and set permissions
}

// Fetch all employees for the dropdown list
$employees = [];
$sql_employees = "SELECT id, name FROM employee ORDER BY name ASC";
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
    // Title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title for the announcement.";
    } else {
        $title = htmlspecialchars(trim($_POST["title"]));
    }

    // Message
    if (empty(trim($_POST["message"]))) {
        $message_err = "Please enter a message for the announcement.";
    } else {
        $message = htmlspecialchars(trim($_POST["message"]));
    }

    // Target Type
    if (empty($_POST["target_type"])) {
        $target_err = "Please select a target audience.";
    } else {
        $target_type = $_POST["target_type"];
        if ($target_type === "individual") {
            if (empty($_POST["employee_id"])) {
                $employee_id_err = "Please select an employee for individual announcement.";
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
                    $selected_employee_id = null; // Reset to null if invalid
                }
            }
        } else {
            $selected_employee_id = null; // For 'all', target_employee_id should be NULL
        }
    }

    // 2. Handle File Upload
    if (isset($_FILES["attachment"]) && $_FILES["attachment"]["error"] == UPLOAD_ERR_OK) {
        $file_name = basename($_FILES["attachment"]["name"]);
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES["attachment"]["size"];

        // Allowed file types (you can customize this list)
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_type, $allowed_types)) {
            $file_err = "Only PDF, DOC, DOCX, JPG, JPEG, PNG, GIF files are allowed.";
        } elseif ($file_size > $max_file_size) {
            $file_err = "File size exceeds the 5MB limit.";
        } else {
            // Generate a unique filename to prevent overwrites
            $unique_filename = uniqid('announcement_', true) . '.' . $file_type;
            $destination_path = $upload_dir . $unique_filename;

            if (move_uploaded_file($_FILES["attachment"]["tmp_name"], $destination_path)) {
                $upload_file_path = $destination_path; // Store the path for database
            } else {
                $file_err = "Error uploading file. Check directory permissions.";
            }
        }
    } elseif (isset($_FILES["attachment"]) && $_FILES["attachment"]["error"] != UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $file_err = "File upload error: " . $_FILES["attachment"]["error"];
    }


    // 3. Insert into Database if no errors
    if (empty($title_err) && empty($message_err) && empty($target_err) && empty($employee_id_err) && empty($file_err)) {
        // $admin_id is now retrieved at the top of the script using the workaround.

        $sql_insert = "INSERT INTO announcements (title, message, file_path, target_employee_id, admin_id) VALUES (?, ?, ?, ?, ?)";

        $stmt_insert = mysqli_prepare($conn, $sql_insert);

        if ($stmt_insert) {
            // Bind parameters (s=string, s=string, s=string/null, i=int/null, i=int)
            // Note: For target_employee_id, it will be int if an employee is selected, otherwise null.
            // mysqli_stmt_bind_param can't dynamically change types, so if $selected_employee_id can be null
            // and the database column is INT NULL, it will work. If it were a string, we'd need 's' or 'b'.
            // For INT NULL, 'i' is appropriate, and PHP will handle null correctly when binding to INT.

            // For string $upload_file_path (can be null), 's' is also correct.
            mysqli_stmt_bind_param($stmt_insert, "sssis", $title, $message, $upload_file_path, $selected_employee_id, $admin_id);

            if (mysqli_stmt_execute($stmt_insert)) {
                $success_msg = "Announcement created successfully!";
                // Clear form fields after successful submission
                $title = $message = $target_type = $selected_employee_id = "";
                $upload_file_path = null; // Reset file path
            } else {
                $error_msg = "Error creating announcement: " . mysqli_stmt_error($stmt_insert);
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
    <title>Create Announcement - Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
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
    </style>
</head>

<body>
    <?php require_once(__DIR__ . '/include/header.php'); // Adjust path as needed 
    ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4">
                    <h2 class="card-title text-center mb-4">Create New Announcement</h2>

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

                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="title" class="form-label">Announcement Title:</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                            <div class="error-message"><?php echo $title_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="message" class="form-label">Message:</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message); ?></textarea>
                            <div class="error-message"><?php echo $message_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Target Audience:</label><br>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target_type" id="targetAll" value="all" <?php echo ($target_type == "all" || empty($target_type)) ? "checked" : ""; ?>>
                                <label class="form-check-label" for="targetAll">All Employees</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="target_type" id="targetIndividual" value="individual" <?php echo ($target_type == "individual") ? "checked" : ""; ?>>
                                <label class="form-check-label" for="targetIndividual">Specific Employee</label>
                            </div>
                            <div class="error-message"><?php echo $target_err; ?></div>
                        </div>

                        <div class="mb-3" id="employeeSelectDiv" style="<?php echo ($target_type == 'individual') ? 'display: block;' : 'display: none;'; ?>">
                            <label for="employee_id" class="form-label">Select Employee:</label>
                            <select class="form-control" id="employee_id" name="employee_id">
                                <option value="">-- Select Employee --</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo htmlspecialchars($employee['id']); ?>" <?php echo ($selected_employee_id == $employee['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($employee['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="error-message"><?php echo $employee_id_err; ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="attachment" class="form-label">Attach Document (Optional):</label>
                            <input type="file" class="form-control-file" id="attachment" name="attachment">
                            <div class="error-message"><?php echo $file_err; ?></div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Create Announcement</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript to show/hide employee dropdown based on radio button
        $(document).ready(function() {
            function toggleEmployeeSelect() {
                if ($('#targetIndividual').is(':checked')) {
                    $('#employeeSelectDiv').slideDown();
                    // Optionally make required: $('#employee_id').prop('required', true); 
                    // Be careful with required for dynamic fields, server-side validation is key.
                } else {
                    $('#employeeSelectDiv').slideUp();
                    // $('#employee_id').prop('required', false);
                    $('#employee_id').val(''); // Clear selection when hidden
                }
            }

            // Call on page load
            toggleEmployeeSelect();

            // Call on radio button change
            $('input[name="target_type"]').change(function() {
                toggleEmployeeSelect();
            });
        });
    </script>
    <?php require_once(__DIR__ . '/include/footer.php'); // Adjust path as needed 
    ?>
</body>

</html>