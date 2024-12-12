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

// Initialize variables
$errorMessage = "";
$successMessage = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];
    $inputRole = $_POST['role']; // Role selected by the user
    $stationName = $_POST['station_name'] ?? ''; // Get station name if provided
    $adminPermissions = $_POST['permissions'] ?? null; // Get permissions if provided

    // Validate input (you can add more validation as needed)
    if (empty($inputUsername) || empty($inputPassword) || empty($inputRole)) {
        $errorMessage = "All fields are required.";
    } else {
        // Check if username already exists
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $inputUsername);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errorMessage = "Username already exists. Please choose another.";
        } else {
            // Hash the password before storing it
            $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);

            // Insert new user into the database
            $sqlInsert = "INSERT INTO users (username, password, role, created_at) VALUES (?, ?, ?, NOW())";
            $stmtInsert = $conn->prepare($sqlInsert);
            $stmtInsert->bind_param("sss", $inputUsername, $hashedPassword, $inputRole);

            if ($stmtInsert->execute()) {
                // Get the user_id of the newly created user
                $userId = $conn->insert_id;

                // If role is admin, insert into admin table
                if ($inputRole === 'admin') {
                    // Insert admin details into admin table
                    $sqlAdminInsert = "INSERT INTO admin (user_id, permissions) VALUES (?, ?)";
                    $stmtAdminInsert = $conn->prepare($sqlAdminInsert);
                    $stmtAdminInsert->bind_param("is", $userId, $adminPermissions);
                    if (!$stmtAdminInsert->execute()) {
                        // Handle error if admin insertion fails
                        $errorMessage = "Error: Could not register admin.";
                    }
                    // Close admin insert statement
                    $stmtAdminInsert->close();
                }

                // If role is station, insert into stations table (if needed)
                if ($inputRole === 'station' && !empty($stationName)) {
                    $sqlStationInsert = "INSERT INTO stations (user_id, station_name) VALUES (?, ?)";
                    $stmtStationInsert = $conn->prepare($sqlStationInsert);
                    $stmtStationInsert->bind_param("is", $userId, $stationName);
                    if (!$stmtStationInsert->execute()) {
                        // Handle error if station insertion fails
                        $errorMessage = "Error: Could not register station.";
                    }
                    // Close station insert statement
                    $stmtStationInsert->close();
                }

                // Registration successful message
                $successMessage = "Registration successful! You can now log in.";
            } else {
                $errorMessage = "Error: Could not register user.";
            }

            // Close insert statement for users
            $stmtInsert->close();
        }

        // Close check statement for existing username
        $stmt->close();
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
        function toggleFields() {
            const roleSelect = document.getElementById('role');
            const adminFields = document.getElementById('adminFields');
            const stationFields = document.getElementById('stationFields');

            if (roleSelect.value === 'admin') {
                adminFields.style.display = 'block';
                stationFields.style.display = 'none';
            } else if (roleSelect.value === 'station') {
                adminFields.style.display = 'none';
                stationFields.style.display = 'block';
            } else {
                adminFields.style.display = 'none';
                stationFields.style.display = 'none';
            }
        }
    </script>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Register</h2>
    
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>
    
    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="role">Role</label>
            <select name="role" id="role" class="form-control" onchange="toggleFields()" required>
                <option value="">Select Role</option>
                <option value="station">Station User</option>
                <option value="admin">Admin</option>
                <!-- Add more roles as needed -->
            </select>
        </div>

        <!-- Admin specific fields -->
        <div id="adminFields" style="display: none;">
            <h5>Admin Permissions</h5>
            <div class="form-group">
                <label for="permissions">Permissions (optional)</label>
                <input type="text" name="permissions" id="permissions" class="form-control">
            </div>
        </div>

        <!-- Station specific fields -->
        <div id="stationFields" style="display: none;">
            <h5>Station Details</h5>
            <div class="form-group">
                <label for="station_name">Station Name</label>
                <input type="text" name="station_name" id="station_name" class="form-control">
            </div>
        </div>

        <button type="submit" name="register" class="btn btn-primary btn-block">Register</button>
    </form>

    <p class="mt-3 text-center">Already have an account? <a href="landing.php">Login here</a>.</p>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>
</html>
