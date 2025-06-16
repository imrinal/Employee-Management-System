<?php
session_start(); // Start the session at the very beginning

// Check if employee is logged in
if (!isset($_SESSION["email_emp"]) || strlen($_SESSION["email_emp"]) == 0 || !isset($_SESSION['id'])) {
    header("Location: login.php"); // Redirect to employee login if not logged in
    exit(); // Always exit after a header redirect
}

require_once "../connection.php"; // Database connection

$employee_id = $_SESSION['id']; // Get the logged-in employee's ID
$employee_email = $_SESSION['email_emp']; // Get the logged-in employee's email

$average_rating = 0;
$total_ratings = 0;
$individual_ratings = [];
$message_display = '';

// Fetch all ratings for the logged-in employee, including admin name
$sql_fetch_ratings = "SELECT er.rating_score, er.rating_comments, er.rating_date, a.name AS admin_name 
                      FROM employee_ratings er
                      JOIN admin a ON er.admin_id = a.id
                      WHERE er.employee_id = ? 
                      ORDER BY er.rating_date DESC";

$stmt = mysqli_prepare($conn, $sql_fetch_ratings);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $employee_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $total_score = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $individual_ratings[] = $row;
            $total_score += $row['rating_score'];
        }
        $total_ratings = mysqli_num_rows($result);
        $average_rating = round($total_score / $total_ratings, 2); // Round to 2 decimal places
    } else {
        $message_display = '<div class="alert alert-info text-center py-4 my-5">
                                <h3>No Ratings Yet!</h3>
                                <p>It looks like you haven\'t received any performance ratings from the admin. Check back later!</p>
                            </div>';
    }
    mysqli_stmt_close($stmt);
} else {
    $error_msg = "Error preparing query: " . mysqli_error($conn);
    $message_display = '<div class="alert alert-danger text-center py-4 my-5">
                            <h3>Error Loading Ratings</h3>
                            <p>' . htmlspecialchars($error_msg) . '</p>
                        </div>';
}

// Close database connection
if (isset($conn) && is_object($conn)) {
    mysqli_close($conn);
}

// Function to display stars based on a score
function displayStars($score, $maxStars = 5)
{
    $output = '';
    for ($i = 1; $i <= $maxStars; $i++) {
        if ($i <= floor($score)) {
            $output .= '<i class="fas fa-star filled-star"></i>'; // Full star
        } elseif ($i - 0.5 == $score) {
            $output .= '<i class="fas fa-star-half-alt half-star"></i>'; // Half star
        } else {
            $output .= '<i class="fas fa-star empty-star"></i>'; // Empty star
        }
    }
    return $output;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ratings - Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet" />
    <style>
        body {
            background-color: #f0f2f5;
            /* Light background */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-fluid {
            padding-top: 30px;
            padding-bottom: 30px;
        }

        .page-head-line {
            color: #343a40;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
            font-weight: 700;
        }

        .summary-card {
            /* Changed to a softer blue gradient */
            background: linear-gradient(45deg, #5dade2, #2e86c1);
            /* Sky blue to a deeper serene blue */
            color: white;
            /* Text color is kept white for optimal readability on the dark background */
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            margin-bottom: 40px;
        }

        .summary-card h2 {
            font-size: 2.8em;
            margin-bottom: 10px;
            font-weight: 800;
        }

        .summary-card p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .summary-card .average-stars i {
            /* General styling for all stars in the summary card */
            font-size: 2.5em;
            margin: 0 5px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Specific coloring for filled/half stars within the summary card */
        .summary-card .average-stars .filled-star,
        .summary-card .average-stars .half-star {
            color: #ffc107;
            /* Gold color for rated stars */
        }

        /* Specific coloring for empty stars within the summary card */
        .summary-card .average-stars .empty-star {
            color: #aeb4c0;
            /* A light gray for unrated stars to show clearly on the blue background */
        }

        /* --- END UPDATED CSS FOR THE FIRST CARD --- */

        .rating-item-card {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: transform 0.2s ease-in-out;
        }

        .rating-item-card:hover {
            transform: translateY(-5px);
        }

        .rating-item-card h5 {
            color: #007bff;
            margin-bottom: 15px;
            font-weight: 600;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .rating-item-card .rating-stars-display i {
            color: #ffc107;
            /* Gold for individual rating stars */
            font-size: 1.2em;
            margin-right: 2px;
        }

        .rating-item-card .comments {
            margin-top: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 10px 15px;
            border-radius: 5px;
            font-style: italic;
            color: #495057;
        }

        .rating-item-card .meta-info {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 15px;
            border-top: 1px solid #f0f2f5;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Base star colors (general, applies to individual cards too) */
        .empty-star {
            color: #e9ecef;
            /* Color for unrated stars */
        }

        .filled-star {
            color: #ffc107;
            /* Color for filled stars */
        }

        .half-star {
            color: #ffc107;
            /* Color for half stars */
        }
    </style>
</head>

<body>
    <?php require_once(__DIR__ . '/include/header.php'); // Adjust path as needed 
    ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="page-head-line">My Performance Ratings</h1>
                </div>
            </div>

            <?php if (!empty($individual_ratings)): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8 col-lg-6">
                        <div class="summary-card">
                            <h2>Your Average Rating</h2>
                            <div class="average-stars mb-3">
                                <?php echo displayStars($average_rating); ?>
                            </div>
                            <p><strong><?php echo htmlspecialchars($average_rating); ?> out of 5</strong> based on <?php echo htmlspecialchars($total_ratings); ?> ratings.</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <h3 class="mb-4 text-center" style="color: #343a40;">Individual Ratings Received</h3>
                    </div>
                    <?php foreach ($individual_ratings as $rating): ?>
                        <div class="col-md-6 col-lg-4 d-flex">
                            <div class="rating-item-card flex-fill">
                                <h5>Rating from Admin: <?php echo htmlspecialchars($rating['admin_name']); ?></h5>
                                <div class="rating-stars-display mb-3">
                                    <?php echo displayStars($rating['rating_score']); ?>
                                    <span class="ms-2"> (<?php echo htmlspecialchars($rating['rating_score']); ?>/5)</span>
                                </div>
                                <?php if (!empty($rating['rating_comments'])): ?>
                                    <div class="comments">
                                        "<?php echo nl2br(htmlspecialchars($rating['rating_comments'])); ?>"
                                    </div>
                                <?php else: ?>
                                    <div class="comments text-muted">
                                        <i>No specific comments provided.</i>
                                    </div>
                                <?php endif; ?>
                                <div class="meta-info">
                                    <span>Rated on: <?php echo date('F j, Y', strtotime(htmlspecialchars($rating['rating_date']))); ?></span>
                                    <span>Time: <?php echo date('g:i a', strtotime(htmlspecialchars($rating['rating_date']))); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php echo $message_display; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once(__DIR__ . '/include/footer.php'); // Adjust path as needed 
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>