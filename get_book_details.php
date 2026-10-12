<?php
require_once 'db_connect.php';

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "bookstore1";

if(isset($_GET['id'])) {
    $bookId = $_GET['id'];
    
    try {
        $conn = new mysqli($host, $username, $password, $database);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("
            SELECT * 
            FROM books 
            WHERE book_id = ?
        ");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        
        if($book) {
            echo '
            <div class="popup-image-container">
                <img src="'.htmlspecialchars($book['image_path']).'" alt="'.htmlspecialchars($book['title']).'" class="popup-image">
            </div>
            <div class="popup-details">
                <h2>'.htmlspecialchars($book['title']).'</h2>
                <div class="popup-author">by '.htmlspecialchars($book['author']).'</div>
                <div class="popup-category">Category: '.htmlspecialchars($book['category']).'</div>
                <div class="popup-price">
                    '.(isset($book['discount']) && $book['discount'] > 0 ? '
                    <span style="text-decoration: line-through; color: #95a5a6; margin-right: 8px;">
                        $'.number_format($book['price'], 2).'
                    </span>
                    $'.number_format($book['price'] * (1 - $book['discount'] / 100), 2) : '
                    $'.number_format($book['price'], 2)).'
                </div>
                <div class="popup-description">
                    <h3>Description</h3>
                    <p>'.(isset($book['description']) ? nl2br(htmlspecialchars($book['description'])) : 'No description available.').'</p>
                </div>
                <div class="popup-actions">
                    <button onclick="addToCart('.$book['book_id'].')" class="btn btn-add-to-cart">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                    <button class="btn btn-wishlist">
                        <i class="fas fa-heart"></i> Wishlist
                    </button>
                </div>
            </div>';
        } else {
            echo '<div style="padding: 2rem; text-align: center;">Book not found.</div>';
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo '<div style="padding: 2rem; text-align: center;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    } finally {
        if (isset($conn)) {
            $conn->close();
        }
    }
} else {
    echo '<div style="padding: 2rem; text-align: center;">Invalid request.</div>';
}
?>