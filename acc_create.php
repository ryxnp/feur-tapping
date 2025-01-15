<?php
include 'config.php'; // Include database connection

function empIdExists($conn, $emp_id) {
    $stmt = $conn->prepare("SELECT emp_id FROM employee WHERE emp_id = ?");
    $stmt->bind_param("s", $emp_id); // Use "s" for string type since emp_id is alphanumeric
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0; // Returns true if exists
}

function handleSingleRegistration($conn) {
    // Your existing single registration code...
    // (Keep this part unchanged)
}

function handleBulkRegistration($conn) {
    if (isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        $bulkErrorOccurred = false; // Flag to track errors

        if (($handle = fopen($file, "r")) !== FALSE) {
            // Get headers from CSV
            $headers = fgetcsv($handle);
            if ($headers !== FALSE) {
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $data = array_map('trim', array_combine($headers, $data));
                    list($emp_id, $firstName, $lastName, $position, $department, $imgprofile) = 
                        array($data['emp_id'], 
                              $data['firstName'], 
                              $data['lastName'], 
                              $data['position'], 
                              $data['department'], 
                              !empty($data['imgprofile']) ? $data['imgprofile'] : 'default-profile.png');

                    // Check if emp_id already exists
                    if (empIdExists($conn, $emp_id)) {
                        echo "Error: Employee ID {$emp_id} already exists.<br>";
                        $bulkErrorOccurred = true;
                        continue; // Skip this entry
                    }

                    // Prepare and bind for insertion
                    $stmt = $conn->prepare("INSERT INTO employee (emp_id, firstName, lastName, position, department, imgprofile) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt) {
                        // Bind parameters and execute
                        $stmt->bind_param("ssssss", 
                                          $emp_id,
                                          $firstName,
                                          $lastName,
                                          $position,
                                          $department,
                                          $imgprofile);
                        if (!$stmt->execute()) {
                            echo "Error inserting Employee ID {$emp_id}: " . mysqli_error($conn);
                            $bulkErrorOccurred = true;
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
            }

            fclose($handle);
            
            // Display completion message only if no errors occurred
            if (!$bulkErrorOccurred) {
                echo "Bulk registration completed.";
            }
        } else {
            echo "Error opening the file.";
        }
    }
}

function handleImageUpload() {
    if (!empty($_FILES['files']['name'])) {
        foreach ($_FILES['files']['name'] as $key => $name) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                // Move uploaded file to assets directory
                if (move_uploaded_file($_FILES['files']['tmp_name'][$key], "assets/" . basename($_FILES['files']['name'][$key]))) {
                    echo "Image uploaded successfully: " . basename($_FILES['files']['name'][$key]) . "<br>";
                } else {
                    echo "Failed to upload image: " . basename($_FILES['files']['name'][$key]) . "<br>";
                }
            }
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle single registration
    if (isset($_POST['single_submit'])) {
        handleSingleRegistration($conn);
    }

    // Handle bulk registration from CSV
    if (isset($_POST['bulk_submit'])) {
        handleBulkRegistration($conn);
    }

    // Handle image uploads separately
    if (isset($_POST['image_upload_submit'])) {
        handleImageUpload();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration</title>
</head>
<body>
    <h2>Single Employee Registration</h2>
    <form action="acc_create.php" method="post" enctype="multipart/form-data">
        Employee ID: <input type="number" name="emp_id" required><br>
        First Name: <input type="text" name="firstName" required><br>
        Last Name: <input type="text" name="lastName" required><br>
        Position: <input type="text" name="position" required><br>
        Department: <input type="text" name="department" required><br>
        Profile Image: <input type="file" name="imgprofile" accept="image/*" required><br>
        <input type="submit" name="single_submit" value="Register">
    </form>

    <h2>Bulk Employee Registration</h2>
    <form action="acc_create.php" method="post" enctype="multipart/form-data">
        Upload CSV File: <input type="file" name="csv_file" accept=".csv" required><br>
        <input type="submit" name="bulk_submit" value="Upload">
    </form>

    <h2>Upload Images</h2>
    <form action="acc_create.php" method="post" enctype="multipart/form-data">
        Upload Images: <input type="file" name="files[]" multiple accept="image/*"><br>
        <input type="submit" name="image_upload_submit" value="Upload Images">
    </form>
</body>
</html>
