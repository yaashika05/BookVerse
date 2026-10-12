<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "bookstore");

if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $category = $_POST['category'];

    // Handle image upload
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["book_image"]["name"]);
    move_uploaded_file($_FILES["book_image"]["tmp_name"], $target_file);

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO books (title, author, price, description, category, image_path) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsss", $title, $author, $price, $description, $category, $target_file);
    $stmt->execute();

    header("Location: admin_dashboard.php?success=1");
}
?>