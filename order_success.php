<?php
session_start();
require_once 'db_connect.php';

if (!isset($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$orderId = $_GET['order_id'];

// Get order details
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, b.title, b.author, b.image_path 
    FROM order_items oi
    JOIN books b ON oi.book_id = b.book_id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | BookVerse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
        }
        
        .navbar {
            background: var(--primary);
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
            transition: all 0.3s;
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
        
        .nav-links a:hover {
            opacity: 0.9;
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
        
        .cart-count, .user-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .success-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .success-icon i {
            font-size: 4rem;
            color: #2ecc71;
        }
        
        .order-details {
            margin-top: 2rem;
        }
        
        .order-items {
            margin-top: 1.5rem;
        }
        
        .order-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-item img {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .order-item-details h4 {
            margin: 0 0 0.5rem 0;
            color: var(--primary);
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
            text-decoration: none;
            transition: background 0.3s;
            margin-top: 1.5rem;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">
            <i class="fas fa-book-open"></i>
            BookVerse
        </a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="books.php">Books</a>
            <a href="categories.php">Categories</a>
            <a href="about.php">About</a>
            <a href="cart.php" class="cart-icon" title="Shopping Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count">0</span>
            </a>
        </div>
    </nav>

    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <div class="text-center">
            <h1>Thank You for Your Order!</h1>
            <p>Your order has been placed successfully. We'll send you a confirmation email shortly.</p>
            <p><strong>Order ID:</strong> #<?= htmlspecialchars($orderId) ?></p>
        </div>
        
        <div class="order-details">
            <h2>Order Summary</h2>
            <p><strong>Order Date:</strong> <?= date('F j, Y', strtotime($order['order_date'])) ?></p>
            <p><strong>Total Amount:</strong> $<?= number_format($order['total_amount'], 2) ?></p>
            <p><strong>Payment Method:</strong> <?= ucfirst($order['payment_method']) ?></p>
            
            <h3>Items Ordered:</h3>
            <div class="order-items">
                <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <img src="<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                        <div class="order-item-details">
                            <h4><?= htmlspecialchars($item['title']) ?></h4>
                            <p>by <?= htmlspecialchars($item['author']) ?></p>
                            <p>Quantity: <?= $item['quantity'] ?></p>
                            <p>Price: $<?= number_format($item['unit_price'], 2) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="text-center">
            <a href="index.php" class="btn">Continue Shopping</a>
        </div>
    </div>
</body>
</html>