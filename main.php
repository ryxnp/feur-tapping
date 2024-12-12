<?php
    session_start();

    // Check if user is logged in and has stations role
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'station') {
        header("Location: login.php"); // Redirect to login page if not authorized
        exit();
    }
    
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

    // Initialize variables
    $employeeInfo = "";
    $alertClass = ""; // Variable to hold alert class
    $profileImagePath = ""; // Variable to hold profile image path

    // Check if form is submitted and employee_id is set
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['employee_id'])) {
        $employeeId = $_POST['employee_id'];

        // Prepare and execute SQL query to fetch employee information
        $sql = "SELECT * FROM employee WHERE employeeID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch the employee data
            while ($row = $result->fetch_assoc()) {
                $employeeInfo .= "Employee ID: " . htmlspecialchars($row['employeeID']) . "<br>";
                $employeeInfo .= "Name: " . htmlspecialchars($row['firstName']) . " " . htmlspecialchars($row['lastName']) . "<br>";
                $employeeInfo .= "Position: " . htmlspecialchars($row['position']) . "<br>";
                $employeeInfo .= "Department: " . htmlspecialchars($row['department']) . "<br>";
                
                // Get profile image path from imgprofile column
                if (!empty($row['imgprofile'])) {
                    $profileImagePath = 'assets/' . htmlspecialchars($row['imgprofile']); // Update to actual profile image path
                }
                
                // Get profile image path from imgprofile column
                $profileImagePath = htmlspecialchars($row['imgprofile']); // Assuming 'imgprofile' is the column name
            }
            // Set alert class for success message
            $alertClass = "alert-success";
        } else {
            // Set error message and alert class for no record found
            $employeeInfo = "No employee found with ID: " . htmlspecialchars($employeeId);
            $alertClass = "alert-danger";
        }

        // Close statement
        $stmt->close();
    }

    // Close the connection
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

<div class="container">
    <div class="row" style="height: 100%;">
        <div class="col-md-6 image-box d-flex justify-content-center align-items-center">
            <!-- Display profile picture -->
            <img id="profileImage" class="rounded" src="<?php echo !empty($profileImagePath) ? 'assets/' . $profileImagePath : 'assets/blank.png'; ?>" alt="Profile Picture" class="img-fluid" style="max-width: 100%; height: auto;">
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
                    <?php if ($employeeInfo): ?>
                        <div id="alertBox" class="alert <?php echo $alertClass; ?> mt-3"><?php echo $employeeInfo; ?></div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="js/main.js"></script>

</body>
</html>
