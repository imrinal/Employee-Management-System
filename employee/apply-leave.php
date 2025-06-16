<?php
// Ensure session is started and PHP timezone is set
session_start();
date_default_timezone_set('Asia/Kolkata'); // Set PHP's default timezone to India Standard Time

require_once "include/header.php";

$reasonErr = $startdateErr = $lastdateErr = "";
$reason = $startdate = $lastdate = "";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    if( empty($_REQUEST["reason"]) ){
        $reasonErr = "<p style='color:red'>* Reason is Required</p>";    
    }else{
        $reason = $_REQUEST["reason"];
    }
 
    if( empty($_REQUEST["startDate"]) ){
        $startdateErr = "<p style='color:red'>* Start Date is Required</p>";    
    }else{
        $startdate = $_REQUEST["startDate"];
    }
      
    if( empty($_REQUEST["lastDate"]) ){
        $lastdateErr = "<p style='color:red'>* Last Date is Required</p>";    
    }else{
        $lastdate = $_REQUEST["lastDate"];
    }

        if( !empty($reason) && !empty($startdate) && !empty($lastdate) ){
            
            // database connection 
            require_once "../connection.php";

            $sql = "INSERT INTO emp_leave( reason , start_date , last_date , email , status ) VALUES( '$reason' , '$startdate' , '$lastdate' , '$_SESSION[email_emp]' , 'pending' )";
            $result = mysqli_query($conn , $sql);
            if($result){
                $reason = $startdate = $lastdate = "";
                echo "<script>
            $(document).ready( function(){
                $('#showModal').modal('show');
                $('#addMsg').text('Leave Applied, Please Wait until it is approved!');
                $('#linkBtn').attr('href', 'leave-status.php');
                $('#linkBtn').text('Check Leave Status');
                $('#closeBtn').text('Apply Another');
            })
        </script>
        ";
            }
        }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Leave - Employee</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="css/style.css" rel="stylesheet" /> 

    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh; /* Ensure body takes full viewport height */
            display: flex;
            flex-direction: column;
        }
        /* Using a .content-wrapper to contain the main form content */
        .content-wrapper {
            flex-grow: 1; /* Allows content to expand and push footer down */
            padding-top: 30px; /* Space from top */
            padding-bottom: 30px; /* Space from bottom */
            display: flex; /* Use flexbox for centering content */
            align-items: center; /* Center vertically */
            justify-content: center; /* Center horizontally */
        }
        .page-head-line {
            color: #343a40;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 30px;
            font-weight: 700;
        }
        .card {
            border-radius: 15px; /* Rounded corners for the card */
            box-shadow: 0 8px 16px rgba(0,0,0,0.1); /* Soft shadow */
            border: none; /* Remove default Bootstrap border */
            width: 100%; /* Ensure card takes full width of its column */
        }
        .form-control, .btn {
            border-radius: 8px; /* Rounded corners for inputs and buttons */
        }
        .form-label {
            font-weight: 500; /* Slightly bolder labels */
            color: #343a40; /* Darker label color */
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.2s, border-color 0.2s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        /* Styling for the original error messages (p style='color:red') */
        .form-group p[style='color:red'] {
            font-size: 0.875em; /* Match Bootstrap's validation font size */
            margin-top: 0.25rem; /* Small space above the error text */
            margin-bottom: 0; /* Remove extra margin below */
            color: #dc3545 !important; /* Ensure the red color from Bootstrap's danger theme */
            padding-left: 0.25rem; /* Small indent */
        }
        /* Add Bootstrap's invalid input styling when an error is present */
        .form-control.is-invalid {
            border-color: #dc3545; /* Red border */
            padding-right: calc(1.5em + 0.75rem); /* Space for icon */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e"); /* Error icon */
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        /* --- Simple CSS to remove underlines from ALL links on THIS PAGE --- */
        /* This will affect any <a> tags on the page, including any within the main content or the sidebar, */
        /* but it is not specific to the menubar structure. */
        a,
        a:hover,
        a:focus {
            text-decoration: none !important; /* This is the key property to remove underlines */
        }
    </style>
</head>
<body>
    <?php require_once "include/header.php"; ?>

    <div class="content-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-7 col-md-8">
                    <div class="card p-4">
                        <div class="card-body">
                            <h4 class="text-center mb-4 page-head-line">Apply For Leave</h4>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                            
                                <div class="mb-3">
                                    <label for="reason" class="form-label">Reason :</label>
                                    <input 
                                        type="text" 
                                        class="form-control <?php echo (!empty($reasonErr)) ? 'is-invalid' : ''; ?>" 
                                        id="reason" 
                                        value="<?php echo htmlspecialchars($reason); ?>" 
                                        name="reason" 
                                        required 
                                    > 
                                    <?php echo $reasonErr; ?> </div>

                                <div class="mb-3">
                                    <label for="startDate" class="form-label">Starting Date :</label>
                                    <input 
                                        type="date" 
                                        class="form-control <?php echo (!empty($startdateErr)) ? 'is-invalid' : ''; ?>" 
                                        id="startDate" 
                                        value="<?php echo htmlspecialchars($startdate); ?>" 
                                        name="startDate" 
                                        required 
                                    >
                                    <?php echo $startdateErr; ?> </div>
                                
                                <div class="mb-3">
                                    <label for="lastDate" class="form-label">Last Date :</label>
                                    <input 
                                        type="date" 
                                        class="form-control <?php echo (!empty($lastdateErr)) ? 'is-invalid' : ''; ?>" 
                                        id="lastDate" 
                                        value="<?php echo htmlspecialchars($lastdate); ?>" 
                                        name="lastDate" 
                                        required 
                                    >
                                    <?php echo $lastdateErr; ?> </div>

                                <div class="d-grid mt-4">
                                    <input type="submit" value="Apply Now" class="btn btn-primary btn-lg" name="signin">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once "include/footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        // Your existing modal script is echoed directly by PHP on success.
        // Ensure that the modal HTML structure (e.g., a Bootstrap modal div with id="showModal")
        // is present either in your footer.php or directly in this file,
        // for the jQuery script to successfully find and show it.
    </script>
</body>
</html>