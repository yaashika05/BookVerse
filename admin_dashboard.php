<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}
?>

<h1>Admin Dashboard</h1>
<a href="logout.php">Logout</a>

<!-- Form to Add New Book -->
<form action="add_book.php" method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="Book Title" required><br>
    <input type="text" name="author" placeholder="Author" required><br>
    <input type="number" name="price" step="0.01" placeholder="Price" required><br>
    <textarea name="description" placeholder="Description"></textarea><br>
    <input type="text" name="category" placeholder="Category"><br>
    <input type="file" name="book_image" accept="image/*"><br>
    <button type="submit" name="add_book">Add Book</button>
</form>