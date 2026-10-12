<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bookstore1");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if ($password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Username not found!";
    }
}
?>

<!-- HTML Form with Error Display -->
<form method="POST">
    <?php if (!empty($error)) echo "<p style='color: red'>$error</p>"; ?>
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit" name="login">Login</button>
</form>