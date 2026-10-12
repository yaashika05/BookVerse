<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Get user details
$userStmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Get user orders
$ordersStmt = $conn->prepare("
    SELECT o.*, COUNT(oi.item_id) as item_count 
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");
$ordersStmt->bind_param("i", $userId);
$ordersStmt->execute();
$orders = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $bookId = $_POST['book_id'];
    $rating = $_POST['rating'];
    $reviewText = $_POST['review_text'];
    
    $reviewStmt = $conn->prepare("
        INSERT INTO reviews (user_id, book_id, rating, review_text, review_date)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $reviewStmt->bind_param("iiis", $userId, $bookId, $rating, $reviewText);
    $reviewStmt->execute();
    
    // Refresh to show new review
    header("Location: profile.php");
    exit;
}

// Get user's reviews
$reviewsStmt = $conn->prepare("
    SELECT r.*, b.title, b.image_path 
    FROM reviews r
    JOIN books b ON r.book_id = b.book_id
    WHERE r.user_id = ?
    ORDER BY r.review_date DESC
");
$reviewsStmt->bind_param("i", $userId);
$reviewsStmt->execute();
$reviews = $reviewsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | BookVerse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .navbar {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo i {
            font-size: 1.8rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 0;
            position: relative;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: white;
            transition: width 0.3s;
        }
        
        .nav-links a:hover::after,
        .nav-links a.active::after {
            width: 100%;
        }
        
        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .cart-icon, .user-icon {
            position: relative;
            color: white;
            font-size: 1.2rem;
            padding: 0.5rem;
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--secondary);
        }
        
        .profile-info h1 {
            margin: 0;
            color: var(--primary);
        }
        
        .profile-info p {
            margin: 0.5rem 0 0;
            color: #666;
        }
        
        .section-title {
            color: var(--primary);
            margin: 2rem 0 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--secondary);
        }
        
        .orders-container, .reviews-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .order-card, .review-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .order-header, .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .order-id, .review-book {
            font-weight: 600;
            color: var(--primary);
        }
        
        .order-date, .review-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-details {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .order-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .review-rating {
            color: #f39c12;
            margin: 0.5rem 0;
        }
        
        .review-text {
            line-height: 1.6;
        }
        
        .review-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .stars {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .star {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .star.active {
            color: #f39c12;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .order-header, .review-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fas fa-book-open"></i>
            <span>BookVerse</span>
        </a>
        
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="books.php">Books</a>
            <a href="categories.php">Categories</a>
            <a href="about.php">About</a>
            <a href="profile.php" class="active">Profile</a>
        </div>
        
        <div class="nav-actions">
            <a href="cart.php" class="cart-icon" title="Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?></span>
            </a>
            <a href="logout.php" class="user-icon" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </nav>
    
    <div class="container">
        <div class="profile-header">
            <img src="<?= htmlspecialchars($user['profile_pic'] ?? 'default_profile.jpg') ?>" alt="Profile Picture" class="profile-pic">
            <div class="profile-info">
                <h1><?= htmlspecialchars($user['name']) ?></h1>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <p>Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
            </div>
        </div>
        
        <h2 class="section-title">My Orders</h2>
        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <p>You haven't placed any orders yet.</p>
                <a href="books.php" class="btn">Browse Books</a>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Order #<?= $order['order_id'] ?></span>
                            <span class="order-date"><?= date('F j, Y', strtotime($order['order_date'])) ?></span>
                        </div>
                        <div>
                            <p><strong>Total:</strong> $<?= number_format($order['total_amount'], 2) ?></p>
                            <p><strong>Items:</strong> <?= $order['item_count'] ?></p>
                            <p><strong>Status:</strong> 
                                <span class="order-status status-<?= $order['payment_status'] ?>">
                                    <?= ucfirst($order['payment_status']) ?>
                                </span>
                            </p>
                        </div>
                        <div class="order-details">
                            <a href="order_details.php?id=<?= $order['order_id'] ?>" class="btn">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <h2 class="section-title">My Reviews</h2>
        <div class="reviews-container">
            <?php if (empty($reviews)): ?>
                <p>You haven't written any reviews yet.</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <span class="review-book"><?= htmlspecialchars($review['title']) ?></span>
                            <span class="review-date"><?= date('F j, Y', strtotime($review['review_date'])) ?></span>
                        </div>
                        <div class="review-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?= $i <= $review['rating'] ? ' active' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="review-text">
                            <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <h2 class="section-title">Write a Review</h2>
        <div class="review-form">
            <form method="POST" action="profile.php">
                <div class="form-group">
                    <label for="book_id">Select Book:</label>
                    <select id="book_id" name="book_id" required>
                        <option value="">-- Select a book you've purchased --</option>
                        <?php
                        // Get books user has purchased but not reviewed
                        $booksStmt = $conn->prepare("
                            SELECT DISTINCT b.book_id, b.title 
                            FROM order_items oi
                            JOIN orders o ON oi.order_id = o.order_id
                            JOIN books b ON oi.book_id = b.book_id
                            LEFT JOIN reviews r ON b.book_id = r.book_id AND r.user_id = ?
                            WHERE o.user_id = ? AND r.review_id IS NULL
                        ");
                        $booksStmt->bind_param("ii", $userId, $userId);
                        $booksStmt->execute();
                        $books = $booksStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        
                        foreach ($books as $book): ?>
                            <option value="<?= $book['book_id'] ?>"><?= htmlspecialchars($book['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Rating:</label>
                    <div class="stars" id="ratingStars">
                        <i class="fas fa-star star" data-rating="1"></i>
                        <i class="fas fa-star star" data-rating="2"></i>
                        <i class="fas fa-star star" data-rating="3"></i>
                        <i class="fas fa-star star" data-rating="4"></i>
                        <i class="fas fa-star star" data-rating="5"></i>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="5" required>
                </div>
                
                <div class="form-group">
                    <label for="review_text">Your Review:</label>
                    <textarea id="review_text" name="review_text" required></textarea>
                </div>
                
                <button type="submit" name="submit_review" class="btn">Submit Review</button>
            </form>
        </div>
    </div>

    <script>
        // Star rating interaction
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('ratingValue');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingValue.value = rating;
                
                stars.forEach(s => {
                    s.classList.toggle('active', s.getAttribute('data-rating') <= rating);
                });
            });
        });
    </script>
</body>
</html>