<?php
session_start(); // Start the session at the very beginning

// Check if employee is logged in
if (!isset($_SESSION["email_emp"]) || strlen($_SESSION["email_emp"]) == 0 || !isset($_SESSION['id'])) {
    header("Location: login.php"); // Redirect to employee login if not logged in
    exit(); // Always exit after a header redirect
}

require_once "../connection.php"; // Database connection

$employee_id = $_SESSION['id']; // Get the logged-in employee's ID

$announcements = [];
$message_display = ''; // For messages like "No announcements found"

// Define the base URL for downloads (adjust if your project root is different)
// Assuming 'uploads' folder is parallel to 'employee' folder
$base_download_url = '../uploads/announcements/';

// Fetch announcements: general ones (target_employee_id IS NULL) OR specific to this employee
$sql_fetch_announcements = "SELECT title, message, file_path, created_at, target_employee_id 
                            FROM announcements 
                            WHERE target_employee_id IS NULL OR target_employee_id = ? 
                            ORDER BY created_at DESC";

$stmt = mysqli_prepare($conn, $sql_fetch_announcements);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $employee_id); // "i" for integer employee_id
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $announcements[] = $row;
        }
    } else {
        $message_display = '<div class="alert alert-info">No announcements available for you at the moment.</div>';
    }
    mysqli_stmt_close($stmt);
} else {
    $message_display = '<div class="alert alert-danger">Error preparing query: ' . mysqli_error($conn) . '</div>';
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
    <title>Announcements - Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet" /> <style>
        body {
            background-color: #f4f6f9;
        }
        .announcement-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .announcement-card h5 {
            color: #007bff;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .announcement-card .badge {
            font-size: 0.8em;
            padding: 0.4em 0.6em;
        }
        .announcement-card p {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .announcement-card .attachment-link {
            display: inline-block;
            margin-top: 10px;
            font-size: 0.9em;
        }
        .announcement-card .timestamp {
            font-size: 0.8em;
            color: #777;
            text-align: right;
            margin-top: 15px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <?php require_once(__DIR__ . '/include/header.php'); // Adjust path as needed ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">Announcements</h1>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card shadow-sm p-4">
                        <h3 class="mb-4">Company Announcements & Notices</h3>
                        <?php echo $message_display; // Display messages like "No announcements" ?>

                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-card">
                                    <h5>
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                        <?php if (!is_null($announcement['target_employee_id'])): ?>
                                            <span class="badge bg-primary text-white">Personal Message</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary text-white">Company-Wide</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                                    
                                    <?php if (!empty($announcement['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($base_download_url . basename($announcement['file_path'])); ?>" 
                                           class="attachment-link btn btn-sm btn-outline-info" 
                                           download>
                                            <i class="fas fa-download"></i> Download Attachment
                                        </a>
                                    <?php endif; ?>

                                    <div class="timestamp">
                                        Posted on: <?php echo date('F j, Y, g:i a', strtotime(htmlspecialchars($announcement['created_at']))); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once(__DIR__ . '/include/footer.php'); // Adjust path as needed ?>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit-id.js" crossorigin="anonymous"></script> 
    </body>
</html>