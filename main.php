<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "feur-tapping-db"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Clear previous employee data from session
unset($_SESSION['employeeData']); // Place this line to clear previous employee data

// Function to create a Bootstrap alert
function createAlert($type, $bold, $message) { 
    return '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert"> <strong>' . htmlspecialchars($bold) . '</strong> ' . htmlspecialchars($message) . ' <button type="button" class="close" data-dismiss="alert" aria-label="Close"> <span aria-hidden="true">&times;</span> </button> </div>'; 
}

// Function to execute prepared statements
function executeStatement($stmt) {
    if (!$stmt->execute()) {
        return createAlert('danger', 'Error!', 'Execution failed: ' . htmlspecialchars($stmt->error));
    }
    return true;
}

// Function to save log entry
function saveLog($conn, $empId, $logTimeIn, $logDate, $stationName) {
    $sql = "INSERT INTO logs (emp_id, logTimeIn, logTimeOut, logDate, station_name) VALUES (?, ?, NULL, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) return createAlert('danger', 'Error!', 'Error preparing statement: ' . htmlspecialchars($conn->error));

    $stmt->bind_param("ssss", $empId, $logTimeIn, $logDate, $stationName);
    
    return executeStatement($stmt) ? createAlert('success', 'Success!', 'Employee has timed in successfully.') : null;
}

// Function to update log entry (timeOut)
function updateLogOut($conn, $empId, $logDate) {
    date_default_timezone_set('Asia/Singapore'); 
    $currentLogTimeOut = date('H:i:s');
    
    $sql = "UPDATE logs SET logTimeOut = ? WHERE emp_id = ? AND logDate = ? AND logTimeOut IS NULL";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) return createAlert('danger', 'Error!', 'Error preparing statement: ' . htmlspecialchars($conn->error));

    $stmt->bind_param("sss", $currentLogTimeOut, $empId, $logDate);
    
    return executeStatement($stmt) ? createAlert('success', 'Success!', 'Employee has timed out successfully.') : null;
}

// Function to fetch employee details
function fetchEmployeeDetails($conn, $empId) {
    $sql = "SELECT firstName, lastName, position, department, imgprofile FROM Employee WHERE emp_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt || !$stmt->bind_param("s", $empId)) return null;

    if ($stmt->execute()) {
        return $stmt->get_result()->fetch_assoc();
    }

    return null;
}

// Initialize variables
$employeeData = null; // To hold employee data
$alertMessage = ''; // To hold alert messages

// Main logic for handling POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['employee_id'])) {
    date_default_timezone_set('Asia/Singapore'); 
    // Clear previous session data and alerts
    unset($_SESSION['employeeData']);
    
    // Fetch employee details for validation
    if ($employeeData = fetchEmployeeDetails($conn, htmlspecialchars($_POST['employee_id']))) {
        $_SESSION['employeeData'] = $employeeData; // Store in session for later use

        // Check for existing timeIn record
        $currentLogDate = date('Y-m-d');
        $sqlCheckLog = "SELECT * FROM logs WHERE emp_id = ? AND logDate = ? AND logTimeOut IS NULL";
        $stmtCheckLog = $conn->prepare($sqlCheckLog);

        if (!$stmtCheckLog || !$stmtCheckLog->bind_param("ss", $_POST['employee_id'], $currentLogDate)) {
            echo createAlert('danger', 'Error!', 'Error preparing check statement: ' . htmlspecialchars($conn->error));
            exit();
        }

       // Execute and get results
       if ($stmtCheckLog->execute()) {
           if ($stmtCheckLog->get_result()->num_rows > 0) {
               // Update existing timeOut record
               echo updateLogOut($conn, htmlspecialchars($_POST['employee_id']), htmlspecialchars($currentLogDate));
           } else {
               // No existing timeIn record; create a new one
               echo saveLog($conn, htmlspecialchars($_POST['employee_id']), date('H:i:s'), htmlspecialchars($currentLogDate), htmlspecialchars($_SESSION['station_name']));
           }
       }

       // Close statements
       if (isset($stmtCheckLog)) {
           $stmtCheckLog->close();
       }
   } else {
       // Employee ID does not exist
       echo createAlert('danger', 'Error!', 'The employee ID does not exist.');
   }
}

// Close the database connection
$conn->close();
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
<div class="container mt-4">
    <div class="row justify-content-center"> <!-- Center the row -->
        <div class="col-md-6 d-flex flex-column align-items-center"> <!-- Center align items -->
            <div class="image-box d-flex justify-content-center align-items-center mb-3">
                <!-- Display profile picture -->
                <img id="profileImage" class="rounded" src="<?php echo !empty($_SESSION['employeeData']['imgprofile']) ? 'assets/' . htmlspecialchars($_SESSION['employeeData']['imgprofile']) : 'assets/default-profile.png'; ?>" alt="" class="img-fluid" style="max-width: 100%; height: auto;">
            </div>
            <!-- Display alert messages -->
            <?php if (!empty($alertMessage)): ?>
                <div class="alert alert-warning mb-3" style="width: 100%; text-align: center;">
                    <?php echo $alertMessage; ?>
                </div>
            <?php endif; ?>
            <div class="small-box mb-3 w-100">
                <!-- Display current time -->
                <div id="currentTime" class="timeDisplay text-center mb-2">Loading current time...</div>
                <!-- Input group for text field -->
                <form method="POST" action="">
                    <div class="form-group mt-3">
                        <input type="text" name="employee_id" id="infoInput" class="form-control" placeholder="Enter Employee ID" style="border-radius: 5px;" onkeypress="if(event.keyCode==13){this.form.submit();}">
                    </div>
                </form>
            </div>
            <div class="big-box flex-grow-1 d-flex flex-column justify-content-center align-items-center w-100"> <!-- Center align employee information -->
                <!-- Display employee information -->
                <?php if (isset($_SESSION['employeeData'])): ?>
                    <div class="text-center mt-3"> <!-- Center text within this div -->
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['employeeData']['firstName'] . ' ' . $_SESSION['employeeData']['lastName']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



    <!-- Include Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <!-- Include custom JavaScript file -->
    <script src="js/main.js"></script>

    <!-- Log current user ID to console -->
    <script>
        // Check if user_id is set in the PHP session
        <?php if (isset($_SESSION['user_id'])): ?>
            console.log("Current User ID: <?php echo htmlspecialchars($_SESSION['user_id']); ?>");
        <?php else: ?>
            console.log("User ID not found in session.");
        <?php endif; ?>

        function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const currentTimeString = `${hours}:${minutes}:${seconds}`;
        
        document.getElementById('currentTime').textContent = currentTimeString;
    }

    // Update time immediately and then every second
    updateTime();
    setInterval(updateTime, 1000);
    </script>

</body>
</html>

