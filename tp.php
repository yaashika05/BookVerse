<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "prac15";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$success = "";
$student = [];
$error = "";

// Check if ID exists and is valid
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error = "Please provide a valid student ID in the URL (e.g., update_student.php?id=1)";
} else {
    $id = (int)$_GET['id'];
    
    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
        // Get form data
        $id = $_POST['id'];
        $student_name = $conn->real_escape_string($_POST['student_name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $course = $conn->real_escape_string($_POST['course']);
        $year = (int)$_POST['year'];
        $status = $conn->real_escape_string($_POST['status']);

        // Update query with basic SQL injection protection
        $sql = "UPDATE students SET 
                student_name='$student_name', 
                email='$email', 
                phone='$phone', 
                course='$course', 
                year=$year, 
                status='$status' 
                WHERE id=$id";

        if ($conn->query($sql) === TRUE) {
            $success = "Record updated successfully";
        } else {
            $error = "Error updating record: " . $conn->error;
        }
    }

    // Get student data
    $sql = "SELECT * FROM students WHERE id=$id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $student = $result->fetch_assoc();
    } else {
        $error = "No student found with ID $id";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Student</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: inline-block; width: 120px; }
        input, select { padding: 5px; width: 300px; }
        button { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Update Student</h2>
        
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        
        <?php if (empty($error) || isset($student)): ?>
        <form method="post" action="">
            <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
            
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="student_name" value="<?php echo htmlspecialchars($student['student_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Phone:</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Course:</label>
                <input type="text" name="course" value="<?php echo htmlspecialchars($student['course'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Year:</label>
                <input type="number" name="year" min="1" max="5" value="<?php echo htmlspecialchars($student['year'] ?? 1); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Status:</label>
                <select name="status" required>
                    <option value="Active" <?php echo ($student['status'] ?? '') == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo ($student['status'] ?? '') == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="Suspended" <?php echo ($student['status'] ?? '') == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>
            
            <button type="submit">Update</button>
            <a href="list_students.php" style="margin-left: 10px;">Back to List</a>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>