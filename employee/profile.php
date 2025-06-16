<?php
session_start(); // Ensure session is started if not done in header.php
require_once "include/header.php";

// Initialize variables to prevent undefined errors
$id = $name = $email = $gender = $dob = $salary = $dp = 'Not Defined';
$final_display_image = 'upload/1.jpg'; // Default fallback image path
$age = 'Not Defined';

// database connection
require_once "../connection.php";

// Fetch employee data
if (isset($_SESSION['email_emp']) && !empty($_SESSION['email_emp'])) {
    // Use prepared statement for security
    $stmt = $conn->prepare("SELECT * FROM employee WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['email_emp']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $employee = $result->fetch_assoc();
            $id = $employee['id'] ?? 'N/A';
            $name = ucwords($employee['name']) ?? 'N/A';
            $email = $employee['email'] ?? 'N/A'; // Use $_SESSION['email_emp'] as primary source
            $gender = ucwords($employee['gender']) ?? 'Not Defined';
            $dob = $employee['dob'] ?? 'Not Defined';
            $salary = $employee['salary'] ?? 'Not Defined';
            $dp = $employee['dp'] ?? '1.jpg'; // Local file name, default '1.jpg'

            // Determine the final image to display
            if (!empty($dp) && file_exists('upload/' . $dp)) {
                $final_display_image = 'upload/' . htmlspecialchars($dp);
            } else {
                $final_display_image = 'upload/1.jpg'; // Fallback to a default image
            }

            // Age calculation
            if ($dob !== 'Not Defined' && !empty($dob)) {
                try {
                    $date1 = new DateTime($dob);
                    $date2 = new DateTime();
                    $diff = $date1->diff($date2);
                    $age = $diff->y . ' Years';
                } catch (Exception $e) {
                    $age = 'Invalid Date Format';
                }
            }
        } else {
            // Handle case where employee not found
            $email = $_SESSION['email_emp'] ?? 'N/A'; // Display email if session exists but user not in DB
        }
        $stmt->close();
    } else {
        // Handle prepare statement error
        $email = $_SESSION['email_emp'] ?? 'N/A'; // Display email if session exists but error occurs
    }
} else {
    // Session email not set, redirect or show error
    // For now, variables will remain 'Not Defined' or 'N/A'
    header("Location: ../login.php"); // Example: redirect to login if session not set
    exit();
}
?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<style>
    /* Custom styles to refine Tailwind defaults or add specific overrides */
    body {
        background-color: #f8f9fa;
        /* Light grey background */
    }

    .profile-card {
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transition: transform 0.3s ease-in-out;
    }

    .profile-card:hover {
        transform: translateY(-5px);
    }

    .profile-img-container {
        background: linear-gradient(to right, #007bff, #0056b3);
        position: relative;
        padding-bottom: 6rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .profile-img {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border: 5px solid rgba(255, 255, 255, 0.9);
        margin-top: 2rem;
        position: relative;
        z-index: 10;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    }

    .profile-detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
    }

    .profile-detail-item:last-child {
        border-bottom: none;
    }

    .profile-detail-label {
        font-weight: 600;
        color: #2d3748;
        display: flex;
        align-items: center;
        min-width: 150px;
    }

    .profile-detail-label i {
        margin-right: 0.75rem;
        color: #718096;
        font-size: 1.1rem;
    }

    .profile-detail-value {
        text-align: right;
        color: #4a5568;
        flex-grow: 1;
    }


    /* !!! IMPORTANT: Header text override - For 'Employee Management System' !!! */
    /* This targets the H2 element in header.php for centering, without changing its position. */
    .header-content.clearfix .text-center h2 {
        text-align: center !important;
        /* Center the text */
        margin-left: auto !important;
        margin-right: auto !important;
        width: fit-content !important;
        /* Allow the text to take only its content width for centering */
        font-size: 2rem !important;
        /* Example size, adjust as needed */
        font-weight: 500 !important;
        /* Example boldness, adjust as needed */
        letter-spacing: -0.025em !important;
        color: #1a202c !important;
        padding-top: 0 !important;
        /* Override Bootstrap's pt-3 if necessary */
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        line-height: 1.2 !important;
    }

    /* Center the 'Employee Profile' heading on THIS page */
    main.col-md-9.ms-sm-auto.col-lg-10 h1.employee-profile-heading {
        text-align: center !important;
        margin-left: auto !important;
        margin-right: auto !important;
        width: fit-content !important;
    }

    /* Optional: If the hamburger menu pushes the "Employee Management System" text off-center */
    /* If you want the title to be perfectly centered on the page *without* the hamburger pushing it,
       you might need to hide the hamburger control on this specific page or adjust its positioning.
       If the hamburger needs to stay, the title will center relative to the *remaining* space. */
    .header-content.clearfix .nav-control {
        position: absolute !important;
        /* Take it out of flow */
        left: 20px !important;
        /* Position it absolutely on the left */
        top: 50% !important;
        transform: translateY(-50%) !important;
    }

    /* Adjust the header-content to allow space for nav-control without affecting centering */
    .header-content.clearfix {
        position: relative !important;
        /* Essential for absolute positioning of children */
        display: flex !important;
        justify-content: center !important;
        /* Still center the main content block */
        align-items: center !important;
        width: 100% !important;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-6">
            <h1 class="text-4xl font-extrabold text-gray-800 mb-8 tracking-tight employee-profile-heading">Employee Profile</h1>
            <?php // echo $upload_message; // Uncomment if you add upload logic 
            ?>

            <div class="flex justify-center mt-5 mb-5">
                <div class="w-full max-w-lg">
                    <div class="profile-card bg-white rounded-lg">
                        <div class="profile-img-container rounded-t-lg relative">
                            <img id="profileImage" src="<?php echo $final_display_image; ?>"
                                class="profile-img rounded-full mx-auto -mb-20" alt="Profile Photo">

                            <div class="absolute bottom-4 right-4 z-20">
                                <a href="profile-photo.php"
                                    class="bg-white text-blue-600 px-4 py-2 rounded-full shadow-lg hover:bg-blue-50 text-sm font-semibold">
                                    <i class="fas fa-camera mr-2"></i> Change Photo
                                </a>
                            </div>
                        </div>

                        <div class="p-6 pt-24">
                            <h2 class="text-3xl font-bold text-gray-800 text-center mb-6"><?php echo htmlspecialchars($name); ?></h2>
                            <div class="space-y-3">
                                <div class="profile-detail-item">
                                    <span class="profile-detail-label"><i class="fas fa-envelope"></i> Email:</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($email); ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <span class="profile-detail-label"><i class="fas fa-id-badge"></i> Employee ID:</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($id); ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <span class="profile-detail-label"><i class="fas fa-venus-mars"></i> Gender:</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($gender); ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <span class="profile-detail-label"><i class="fas fa-calendar-alt"></i> Date of Birth:</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($dob); ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <span class="profile-detail-label"><i class="fas fa-history"></i> Age:</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($age); ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <span class="profile-detail-label"><i class="fas fa-money-bill-wave"></i> Salary:</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($salary); ?> Rs.</span>
                                </div>
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                                <a href="edit-profile.php" class="btn-outline-custom"><i class="fas fa-edit mr-2"></i> Edit Profile</a>
                                <a href="change-password.php" class="btn-outline-custom"><i class="fas fa-key mr-2"></i> Change Password</a>
                                <a href="profile-photo.php" class="btn-outline-custom"><i class="fas fa-camera mr-2"></i> Change Photo</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Note: The previewImage function is not strictly needed here since
    // 'Change Photo' links to a separate page (profile-photo.php).
    // If you plan to implement in-page photo upload for employees later,
    // you would uncomment and use this function.
    // function previewImage(event) {
    //     const file = event.target.files[0];
    //     if (file) {
    //         const reader = new FileReader();
    //         reader.onload = function(e) {
    //             document.getElementById('profileImage').src = e.target.result;
    //         };
    //         reader.readAsDataURL(file);
    //     }
    // }
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<?php
require_once "include/footer.php";
?>