<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "bookstore1";

header('Content-Type: application/json');

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    if (!isset($_SESSION['user_id'])) {
        die(json_encode(['success' => false, 'message' => 'Please login to add items to cart']));
    }

    $bookId = $_POST['id'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;
    $userId = $_SESSION['user_id'];

    if (!$bookId) {
        die(json_encode(['success' => false, 'message' => 'Invalid book ID']));
    }

    // Check if book exists
    $stmt = $conn->prepare("SELECT book_id FROM books WHERE book_id = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die(json_encode(['success' => false, 'message' => 'Book not found']));
    }

    // Initialize cart if not exists
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Add item to cart or update quantity
    if (isset($_SESSION['cart'][$bookId])) {
        $_SESSION['cart'][$bookId] += $quantity;
    } else {
        $_SESSION['cart'][$bookId] = $quantity;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart',
        'cartCount' => array_sum($_SESSION['cart'])
    ]);

} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>