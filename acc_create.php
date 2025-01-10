<?php
include 'config.php'; // Include database connection

function empIdExists($conn, $emp_id) {
    $stmt = $conn->prepare("SELECT emp_id FROM employee WHERE emp_id = ?");
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0; // Returns true if exists
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle single registration
    if (isset($_POST['single_submit'])) {
        $emp_id = $_POST['emp_id'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $position = $_POST['position'];
        $department = $_POST['department'];
        
        // Check if emp_id already exists
        if (empIdExists($conn, $emp_id)) {
            echo "Error: Employee ID {$emp_id} already exists.";
        } else {
            // Handle image upload
            $target_dir = "assets/";
            $target_file = $target_dir . basename($_FILES["imgprofile"]["name"]);
            $filename = basename($_FILES["imgprofile"]["name"]);

            if (move_uploaded_file($_FILES["imgprofile"]["tmp_name"], $target_file)) {
                // Prepare and bind
                $stmt = $conn->prepare("INSERT INTO employee (emp_id, firstName, lastName, position, department, imgprofile) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssss", $emp_id, $firstName, $lastName, $position, $department, $filename);
                
                if ($stmt->execute()) {
                    echo "New record created successfully";
                } else {
                    echo "Error: " . $stmt->error;
                }
                
                $stmt->close();
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    }

    // Handle bulk registration from CSV
    if (isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        $bulkErrorOccurred = false; // Flag to track errors

        if (($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle); // Skip the header row
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                list($emp_id, $firstName, $lastName, $position, $department) = $data;

                // Check if emp_id already exists
                if (empIdExists($conn, $emp_id)) {
                    echo "Error: Employee ID {$emp_id} already exists.<br>";
                    $bulkErrorOccurred = true; // Set error flag
                    continue; // Skip this entry
                }

                // Handle multiple image uploads
                if (isset($_FILES['files'])) {
                    foreach ($_FILES['files']['name'] as $key => $image_name) {
                        // Check for each file's upload status
                        if ($_FILES['files']['error'][$key] == UPLOAD_ERR_OK) {
                            // Set target file path for each image
                            $image_tmp_name = $_FILES['files']['tmp_name'][$key];
                            $image_target_file = "assets/" . basename($image_name);

                            // Move uploaded file to the target directory
                            if (move_uploaded_file($image_tmp_name, $image_target_file)) {
                                // Prepare and bind for each uploaded image
                                $imgprofile = basename($image_name); 
                                // Insert into database for each image associated with the employee
                                $stmt = $conn->prepare("INSERT INTO employee (emp_id, firstName, lastName, position, department, imgprofile) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("isssss", $emp_id, $firstName, $lastName, $position, $department, $imgprofile);
                                
                                if (!$stmt->execute()) {
                                    echo "Error: " . $stmt->error;
                                    continue; // Skip this image on error
                                }
                            } else {
                                echo "Error uploading image: {$image_name}<br>";
                            }
                        } else {
                            echo "Error with file upload: {$image_name}<br>";
                        }
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
        Upload Images: <input type="file" name="files[]" multiple accept="image/*"><br>
        <input type="submit" value="Upload">
    </form>
</body>
</html>
