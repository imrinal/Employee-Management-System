<?php
session_start();
require_once "../connection.php";
require_once "include/header.php";

// Initialize variables to prevent undefined errors
$id = $name = $email = $gender = $dob = $dp = $dp_url = $age = 'Not Defined';
$final_display_image = 'upload/1.jpg'; // Default fallback image path
$upload_message = '';

// Handle profile photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 2 * 1024 * 1024; // 2MB
    $file = $_FILES['profile_photo'];

    if ($file['error'] === UPLOAD_ERR_OK && in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $upload_path = 'upload/' . $filename;

        // Make sure the 'upload' directory exists and is writable
        if (!is_dir('upload')) {
            mkdir('upload', 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Update database
            $stmt = $conn->prepare("UPDATE admin SET dp = ?, dp_url = NULL WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $filename, $_SESSION['email']);
                if ($stmt->execute()) {
                    $upload_message = "<div class='p-3 mb-4 text-sm text-green-700 bg-green-100 rounded-lg'>Profile photo updated successfully!</div>";
                } else {
                    $upload_message = "<div class='p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>Failed to update database: " . htmlspecialchars($stmt->error) . "</div>";
                    unlink($upload_path); // Remove file if DB update fails
                }
                $stmt->close();
            } else {
                $upload_message = "<div class='p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>Database prepare failed: " . htmlspecialchars($conn->error) . "</div>";
            }
        } else {
            $upload_message = "<div class='p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>Failed to upload file.</div>";
        }
    } else {
        $error_message = 'Unknown error.';
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_message = 'File is too large.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_message = 'File upload was interrupted.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error_message = 'No file was selected.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_message = 'Missing a temporary folder.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_message = 'Failed to write file to disk.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_message = 'A PHP extension stopped the file upload.';
                    break;
            }
        } elseif (!in_array($file['type'], $allowed_types)) {
            $error_message = 'Invalid file type. Only JPG, PNG, GIF allowed.';
        } elseif ($file['size'] > $max_size) {
            $error_message = 'File size exceeds 2MB limit.';
        }
        $upload_message = "<div class='p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>Upload error: " . htmlspecialchars($error_message) . "</div>";
    }
}


// Fetch admin data
if (isset($_SESSION['email']) && !empty($_SESSION['email'])) {
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['email']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $id = $admin['id'] ?? 'N/A';
            $name = ucwords($admin['name']) ?? 'N/A';
            $email = $admin['email'] ?? 'N/A';
            $gender = ucwords($admin['gender']) ?? 'Not Defined';
            $dob = $admin['dob'] ?? 'Not Defined';
            $dp = $admin['dp'] ?? '1.jpg'; // Local file name, default '1.jpg'
            $dp_url = $admin['dp_url'] ?? ''; // External URL, default empty

            // Determine the final image to display
            if (!empty($dp_url) && (strpos($dp_url, 'http://') === 0 || strpos($dp_url, 'https://') === 0)) {
                $final_display_image = htmlspecialchars($dp_url);
            } elseif (!empty($dp) && file_exists('upload/' . $dp)) {
                $final_display_image = 'upload/' . htmlspecialchars($dp);
            } else {
                $final_display_image = 'upload/1.jpg'; // Fallback to a default image
            }

            // Age calculation
            $age = 'Not Defined';
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
            $upload_message = "<div class='p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>Admin not found for email: " . htmlspecialchars($_SESSION['email']) . "</div>";
        }
        $stmt->close();
    } else {
        $upload_message = "<div class='p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>Database prepare failed: " . htmlspecialchars($conn->error) . "</div>";
    }
} else {
    $upload_message = "<div class='p-3 mb-4 text-sm text-red-700 bg-red-100 rounded-lg'>Session email not set. Please log in.</div>";
}
?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<style>
    /* Custom styles to refine Tailwind defaults or add specific overrides */
    /* Ensure the body background color is consistent */
    body {
        background-color: #f8f9fa;
        /* Light grey background */
    }

    /* Styles for the profile card container */
    .profile-card {
        border-radius: 1rem;
        /* More rounded corners */
        overflow: hidden;
        /* Ensures contents are clipped by border-radius */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        /* Stronger, modern shadow */
        transition: transform 0.3s ease-in-out;
        /* Smooth hover effect */
    }

    .profile-card:hover {
        transform: translateY(-5px);
        /* Lift effect on hover */
    }

    /* Profile image container */
    .profile-img-container {
        background: linear-gradient(to right, #007bff, #0056b3);
        /* Gradient background */
        position: relative;
        padding-bottom: 6rem;
        /* Space for the image to overlap into this area */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    /* Profile image styling */
    .profile-img {
        width: 150px;
        /* Fixed width */
        height: 150px;
        /* Fixed height for a perfect circle */
        object-fit: cover;
        /* Ensures image covers the area without distortion */
        border: 5px solid rgba(255, 255, 255, 0.9);
        /* White border */
        margin-top: 2rem;
        /* Push down from the top of the gradient */
        position: relative;
        /* For z-index if needed */
        z-index: 10;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        /* Subtle shadow for the image */
    }

    /* Tailwind overrides/enhancements for specific elements */
    .profile-detail-item {
        display: flex;
        /* Use flexbox for each detail row */
        justify-content: space-between;
        /* Push label to left, value to right */
        align-items: center;
        /* Vertically center content */
        padding: 0.75rem 0;
        /* Padding for each item */
        border-bottom: 1px solid #e2e8f0;
        /* Light border at the bottom */
    }

    .profile-detail-item:last-child {
        border-bottom: none;
        /* No border for the last item */
    }

    .profile-detail-label {
        font-weight: 600;
        /* Semi-bold for labels */
        color: #2d3748;
        /* Darker text for labels */
        display: flex;
        /* Flex for icon and text */
        align-items: center;
        /* Align icon and text vertically */
        min-width: 150px;
        /* Minimum width for label to ensure alignment */
    }

    .profile-detail-label i {
        margin-right: 0.75rem;
        /* Space between icon and label text */
        color: #718096;
        /* Muted color for icons */
        font-size: 1.1rem;
        /* Slightly larger icon size */
    }

    .profile-detail-value {
        text-align: right;
        /* Align value to the right */
        color: #4a5568;
        /* Standard text color for values */
        flex-grow: 1;
        /* Allows value to take remaining space */
    }

    /* Hide Bootstrap buttons if not intended */
    .btn.btn-outline-primary,
    .btn.btn-outline-secondary,
    .btn.btn-outline-info {
        display: none !important;
        /* Hide old Bootstrap buttons if they appear */
    }

    /*
     * !!! IMPORTANT: Header text override !!!
     * This targets the H2 element containing "Employee Management System" in header.php.
     * We use a very specific selector and !important to force the styles.
     */
    /* Custom CSS for centering elements */

/* Target the 'Admin Profile' H1 on the profile page */
main.col-md-9.ms-sm-auto.col-lg-10 h1 {
    text-align: center !important; /* Center the text */
    margin-left: auto !important;
    margin-right: auto !important;
    width: fit-content !important; /* Allow the text to take only its content width for centering */
}

/* Target the 'Employee Management System' H2 in the main header */
/* This will affect the H2 inside the header, making it centered */
.header-content.clearfix .text-center h2 {
    text-align: center !important; /* Center the text */
    margin-left: auto !important;
    margin-right: auto !important;
    width: fit-content !important; /* Ensure it respects its content width for centering */
    /* Retain previous important styles if you want them to remain, otherwise remove */
    font-size: 2rem !important; /* Example size, adjust as needed */
    font-weight: 500 !important; /* Example boldness, adjust as needed */
    letter-spacing: -0.025em !important;
    color: #1a202c !important;
    padding-top: 0 !important; /* Override Bootstrap's pt-3 if necessary */
    margin-top: 0 !important;
    margin-bottom: 0 !important;
    line-height: 1 !important;
}

/* Optional: If the hamburger menu pushes the "Employee Management System" text off-center */
/* If you want the title to be perfectly centered on the page *without* the hamburger pushing it,
   you might need to hide the hamburger control on this specific page or adjust its positioning.
   If the hamburger needs to stay, the title will center relative to the *remaining* space. */
.header-content.clearfix .nav-control {
    position: absolute !important; /* Take it out of flow */
    left: 20px !important; /* Position it absolutely on the left */
    top: 50% !important;
    transform: translateY(-50%) !important;
}
/* Adjust the header-content to allow space for nav-control without affecting centering */
.header-content.clearfix {
    position: relative !important; /* Essential for absolute positioning of children */
    display: flex !important;
    justify-content: center !important; /* Still center the main content block */
    align-items: center !important;
    width: 100% !important;
}
</style>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-4 py-6">
            <h1 class="text-4xl font-extrabold text-gray-800 mb-8 tracking-tight">Admin Profile</h1>
            <?php echo $upload_message; ?>
            <div class="flex justify-center mt-5 mb-5">
                <div class="w-full max-w-lg">
                    <div class="profile-card bg-white rounded-lg">
                        <div class="profile-img-container rounded-t-lg relative">
                            <img id="profileImage" src="<?php echo $final_display_image; ?>"
                                class="profile-img rounded-full mx-auto -mb-20" alt="Profile Photo">
                            <div class="absolute bottom-4 right-4 z-20"> <button onclick="document.getElementById('photoInput').click()"
                                    class="bg-white text-blue-600 px-4 py-2 rounded-full shadow-lg hover:bg-blue-50 text-sm font-semibold">
                                    <i class="fas fa-camera mr-2"></i> Change Photo
                                </button>
                            </div>
                        </div>

                        <form id="photoForm" enctype="multipart/form-data" method="POST" class="hidden">
                            <input type="file" id="photoInput" name="profile_photo" accept="image/*"
                                onchange="previewImage(event); document.getElementById('photoForm').submit();">
                        </form>

                        <div class="p-6 pt-24">
                            <h2 class="text-3xl font-bold text-gray-800 text-center mb-6"><?php echo htmlspecialchars($name); ?></h2>
                            <div class="space-y-3">
                                <div class="profile-detail-item">
                                    <span class="profile-detail-label"><i class="fas fa-id-card"></i> ID:</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($id); ?></span>
                                </div>
                                <div class="profile-detail-item">
                                    <span class="profile-detail-label"><i class="fas fa-envelope"></i> Email:</span>
                                    <span class="profile-detail-value"><?php echo htmlspecialchars($email); ?></span>
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
                            </div>

                            <div class="mt-8 flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                                <a href="edit-profile.php" class="btn-outline-custom"><i class="fas fa-edit mr-2"></i> Edit Profile</a>
                                <a href="change-password.php" class="btn-outline-custom"><i class="fas fa-key mr-2"></i> Change Password</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    // Preview uploaded image before submitting
    function previewImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profileImage').src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }
</script>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<?php
require_once "include/footer.php";
?>