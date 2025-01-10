<?php
include 'config.php'; // Include database connection

// Initialize variables
$emp_id = "";
$firstName = "";
$lastName = "";
$position = "";
$department = "";
$imgprofile = "";

// Check if emp_id is submitted for retrieval
if (isset($_POST['retrieve'])) {
    $emp_id = $_POST['emp_id'];
    
    // Prepare statement to fetch employee data
    $stmt = $conn->prepare("SELECT firstName, lastName, position, department, imgprofile FROM employee WHERE emp_id = ?");
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $stmt->bind_result($firstName, $lastName, $position, $department, $imgprofile);
    
    if ($stmt->fetch()) {
        // Employee found, populate fields
        $stmt->close();
    } else {
        echo "Error: Employee ID {$emp_id} not found.";
        $stmt->close();
    }
}

// Check if the update form is submitted
if (isset($_POST['update'])) {
    $emp_id = $_POST['emp_id'];
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $position = $_POST['position'];
    $department = $_POST['department'];

    // Handle image upload if a new image is provided
    if ($_FILES['imgprofile']['name']) {
        $target_dir = "assets/";
        $target_file = $target_dir . basename($_FILES["imgprofile"]["name"]);
        $filename = basename($_FILES["imgprofile"]["name"]);

        if (move_uploaded_file($_FILES["imgprofile"]["tmp_name"], $target_file)) {
            // Update employee record with new image
            $stmt = $conn->prepare("UPDATE employee SET firstName=?, lastName=?, position=?, department=?, imgprofile=? WHERE emp_id=?");
            $stmt->bind_param("sssssi", $firstName, $lastName, $position, $department, $filename, $emp_id);
        } else {
            echo "Sorry, there was an error uploading your file.";
            exit;
        }
    } else {
        // Update without changing the image
        $stmt = $conn->prepare("UPDATE employee SET firstName=?, lastName=?, position=?, department=? WHERE emp_id=?");
        $stmt->bind_param("ssssi", $firstName, $lastName, $position, $department, $emp_id);
    }

    if ($stmt->execute()) {
        echo "Employee record updated successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee</title>
</head>
<body>
    <h2>Retrieve Employee Details</h2>
    <form action="edit_account.php" method="post">
        Employee ID: <input type="number" name="emp_id" required><br>
        <input type="submit" name="retrieve" value="Retrieve">
    </form>

    <?php if ($emp_id): ?>
        <h2>Edit Employee Details</h2>
        <form action="edit_account.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($emp_id); ?>">
            First Name: <input type="text" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required><br>
            Last Name: <input type="text" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required><br>
            Position: <input type="text" name="position" value="<?php echo htmlspecialchars($position); ?>" required><br>
            Department: <input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>" required><br>
            Current Profile Image: <img src="<?php echo 'assets/' . htmlspecialchars($imgprofile); ?>" alt="Profile Image" width="100"><br>
            New Profile Image: <input type="file" name="imgprofile" accept="image/*"><br>
            <input type="submit" name="update" value="Update">
        </form>
    <?php endif; ?>
</body>
</html>
