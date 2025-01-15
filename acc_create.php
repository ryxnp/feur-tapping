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
    if (isset($_POST['emp_id'], $_POST['firstName'], $_POST['lastName'], $_POST['position'], $_POST['department'])) {
        $emp_id = $_POST['emp_id'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $position = $_POST['position'];
        $department = $_POST['department'];

        // Set default image profile
        $imgprofile = 'default-profile.png';

        // Check if an image has been uploaded
        if (isset($_FILES['imgprofile']) && $_FILES['imgprofile']['error'] === UPLOAD_ERR_OK) {
            // Move the uploaded file to the assets directory
            $imgprofile = $_FILES['imgprofile']['name'];
            move_uploaded_file($_FILES['imgprofile']['tmp_name'], "assets/" . basename($imgprofile));
        }

        // Check if emp_id already exists
        if (empIdExists($conn, $emp_id)) {
            echo "Error: Employee ID {$emp_id} already exists.<br>";
            return;
        }

        // Prepare and bind for insertion
        $stmt = $conn->prepare("INSERT INTO employee (emp_id, firstName, lastName, position, department, imgprofile) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssss", 
                              $emp_id,
                              $firstName,
                              $lastName,
                              $position,
                              $department,
                              $imgprofile);
            if ($stmt->execute()) {
                echo "Single registration successful for Employee ID {$emp_id}.<br>";
            } else {
                echo "Error inserting Employee ID {$emp_id}: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
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
                    // Combine headers with data and trim whitespace
                    $data = array_map('trim', array_combine($headers, $data));
                    list($emp_id, 
                          $firstName, 
                          $lastName, 
                          $position, 
                          $department, 
                          $imgprofile) = 
                        array(
                            $data['emp_id'], 
                            $data['firstName'], 
                            $data['lastName'], 
                            $data['position'], 
                            $data['department'], 
                            !empty($data['imgprofile']) ? $data['imgprofile'] : 'default-profile.png' // Set default if empty
                        );

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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body class="container mt-5">
    <div class="row">
        <div class="col-md-6">
        <h2 class="mb-4">Single Employee Registration</h2>
        <form action="" method="post" enctype="multipart/form-data" class="mb-4">
            <div class="form-group">
                <label for="emp_id">Employee ID:</label>
                <input type="number" class="form-control" id="emp_id" name="emp_id" required>
            </div>
            <div class="form-group">
                <label for="firstName">First Name:</label>
                <input type="text" class="form-control" id="firstName" name="firstName" required>
            </div>
            <div class="form-group">
                <label for="lastName">Last Name:</label>
                <input type="text" class="form-control" id="lastName" name="lastName" required>
            </div>
            <div class="form-group">
                <label for="position">Position:</label>
                <input type="text" class="form-control" id="position" name="position" required>
            </div>
            <div class="form-group">
                <label for="department">Department:</label>
                <input type="text" class="form-control" id="department" name="department" required>
            </div>
            <div class="form-group">
                <label for="imgprofile">Profile Image:</label>
                <input type="file" class="form-control-file" id="imgprofile" name="imgprofile" accept="image/*">
            </div>
            <button type="submit" name="single_submit" class="btn btn-primary">Register</button>
        </form>
    </div>


        <div class="col-md-6">
            <h2 class='mb-4'>Bulk Employee Registration</h2>
            <!-- Bulk Registration Form -->
            <form action="" method='post' enctype='multipart/form-data' class='mb-4'>
                <div class='form-group'>
                    <label for='csv_file'>Upload CSV File:</label>
                    <input type='file' class='form-control-file' id='csv_file' name='csv_file' accept='.csv' required>
                </div>
                <button type='submit' name='bulk_submit' class='btn btn-primary'>Upload CSV</button>
            </form>

            <!-- Image Upload Form -->
            <h2 class='mt-4'>Upload Images</h2>
            <form action="" method='post' enctype='multipart/form-data'>
                <div class='form-group'>
                    <label for=''>Upload Images:</label>
                    <input type='file' class='form-control-file' name='files[]' multiple accept='image/*'>
                </div>
                <button type='submit' name='image_upload_submit' class='btn btn-primary'>Upload Images</button>
            </form>
        </div>
    </div>

</body>
</html>
