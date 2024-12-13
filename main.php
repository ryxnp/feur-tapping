<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost"; // Replace with your server name
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "feur-tapping-db"; // Replace with your actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables for JSON response and alerts
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

// Check if form is submitted and employee_id is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['employee_id'])) {
    $employeeId = $_POST['employee_id'];

    // Prepare SQL query to fetch employee information from users table
    $sqlUser = "
        SELECT u.user_id, s.station_name 
        FROM users u 
        LEFT JOIN stations s ON u.user_id = s.user_id 
        WHERE u.user_id = ?";
    
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("s", $_SESSION['user_id']); // Get user_id from session
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();

    if ($resultUser->num_rows > 0) {
        $userInfo = $resultUser->fetch_assoc();
        $stationName = htmlspecialchars($userInfo['station_name']); // Get station name

        // Prepare SQL query to fetch employee information
        $sqlEmployee = "
            SELECT e.* 
            FROM Employee e 
            WHERE e.emp_id = ?";
        
        $stmtEmployee = $conn->prepare($sqlEmployee);
        $stmtEmployee->bind_param("s", $employeeId);
        $stmtEmployee->execute();
        $resultEmployee = $stmtEmployee->get_result();

        if ($resultEmployee->num_rows > 0) {
            // Set current date and time for logs
            $currentTime = date('Y-m-d H:i:s');

            // Fetch the employee data along with station info
            while ($row = $resultEmployee->fetch_assoc()) {
                // Create an associative array for employee details
                $response['data'] = [
                    'employeeID' => htmlspecialchars($row['emp_id']),
                    'name' => htmlspecialchars($row['firstName'] . ' ' . $row['lastName']),
                    'position' => htmlspecialchars($row['position']),
                    'department' => htmlspecialchars($row['department']),
                    'stationName' => $stationName, // Use station name from the user session
                    'profileImage' => !empty($row['imgprofile']) ? 'assets/' . htmlspecialchars($row['imgprofile']) : 'assets/blank.png',
                    'logTimeIn' => $currentTime,
                    'logTimeOut' => $currentTime,
                    'logDate' => date('Y-m-d')
                ];
            }

            // Set success response
            $response['success'] = true;
        } else {
            // Set error message for no employee record found
            $response['message'] = "No employee found with ID: " . htmlspecialchars($employeeId);
        }

        // Close employee statement
        $stmtEmployee->close();
    } else {
        // Set error message for no user record found or no associated station
        $response['message'] = "No user found or no associated station.";
    }

    // Close user statement
    $stmtUser->close();
}

// Close the connection
$conn->close();

// Return JSON response if it's an AJAX request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tapping Station</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css"> 
</head>

<body>

<div class="container">
    <div class="row" style="height: 100%;">
        <div class="col-md-6 image-box d-flex justify-content-center align-items-center">
            <!-- Display profile picture -->
            <img id="profileImage" class="rounded" src="<?php echo !empty($profileImagePath) ? 'assets/' . htmlspecialchars($profileImagePath) : 'assets/blank.png'; ?>" alt="" class="img-fluid" style="max-width: 100%; height: auto;">
        </div>
        <div class="col-md-6 d-flex flex-column">
            <div class="small-box mb-3">
                <!-- Auto-updating time display -->
                <div id="currentTime" class="timeDisplay">
                    Loading current time...
                </div>  
            </div>
            <div class="big-box flex-grow-1 d-flex flex-column justify-content-between">
                <!-- Input group for text field and button -->
                <form method="POST" action="">
                    <div class="form-group">
                        <input type="text" name="employee_id" id="infoInput" class="form-control" placeholder="Enter Employee ID" style="border-radius: 5px;" onkeypress="if(event.keyCode==13){this.form.submit();}">
                    </div>
                    <!-- Display employee information -->
                    <?php if (!empty($employeeInfo)): ?>
                        <div id="alertBox" class="alert <?php echo !empty($alertClass) ? htmlspecialchars($alertClass) : ''; ?> mt-3"><?php echo !empty($employeeInfo) ? $employeeInfo : ''; ?></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/main.js"> </script>

<script>
// Handle form submission via AJAX for JSON response
$('form').on('submit', function(e) {
    e.preventDefault(); // Prevent default form submission

    $.ajax({
        type: 'POST',
        url: '', // Current URL
        data: $(this).serialize(),
        success: function(response) {
            console.log(response); // Log the entire response to the console for testing
            
            $('#alertBox').removeClass('alert-success alert-danger'); // Clear previous alerts

            if (response.success) {
                $('#alertBox').addClass('alert-success').html(response.data.info);
                $('#currentTime').text("Current Time: " + response.data.currentTime); // Update current time display if needed
            } else {
                $('#alertBox').addClass('alert-danger').html(response.message);
            }
        },
        error: function() {
            $('#alertBox').addClass('alert-danger').html('An error occurred while processing your request.');
        }
    });
});
</script>

</body>
</html>
