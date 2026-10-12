<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in or cart is empty
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

// Process payment when form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $conn->begin_transaction();
        
        // 1. Calculate total
        $total = 0;
        $bookIds = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
        
        $stmt = $conn->prepare("SELECT book_id, price FROM books WHERE book_id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($bookIds)), ...$bookIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($book = $result->fetch_assoc()) {
            $quantity = $_SESSION['cart'][$book['book_id']];
            $total += $book['price'] * $quantity;
        }
        
        // 2. Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, billing_address, payment_method) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("idsss", 
            $_SESSION['user_id'],
            $total,
            $_POST['shipping_address'],
            $_POST['billing_address'],
            $_POST['payment_method']
        );
        $stmt->execute();
        $orderId = $conn->insert_id;
        
        // 3. Add order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, book_id, quantity, unit_price) 
                               VALUES (?, ?, ?, ?)");
        
        foreach ($_SESSION['cart'] as $bookId => $quantity) {
            $bookStmt = $conn->prepare("SELECT price FROM books WHERE book_id = ?");
            $bookStmt->bind_param("i", $bookId);
            $bookStmt->execute();
            $bookResult = $bookStmt->get_result();
            $book = $bookResult->fetch_assoc();
            
            $stmt->bind_param("iiid", $orderId, $bookId, $quantity, $book['price']);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear cart
        unset($_SESSION['cart']);
        
        // Redirect to success page
        header("Location: order_success.php?order_id=$orderId");
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error processing your order: " . $e->getMessage();
    }
}

// Get cart items for display
$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $bookIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
    
    $stmt = $conn->prepare("SELECT * FROM books WHERE book_id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($bookIds)), ...$bookIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($book = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$book['book_id']];
        $subtotal = $book['price'] * $quantity;
        
        $cartItems[] = [
            'book' => $book,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
        
        $total += $subtotal;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | BookVerse</title>
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
        
        /* Navbar styles */
        .navbar {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .cart-icon {
            position: relative;
            color: white;
            font-size: 1.2rem;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
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
        
        /* Container styles */
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .checkout-summary, .checkout-form {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        h1, h2 {
            color: var(--primary);
            margin-top: 0;
        }
        
        .checkout-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .checkout-item img {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .checkout-total {
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 1rem;
            text-align: right;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
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
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .error {
            color: var(--accent);
            margin-top: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
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
                <span class="cart-count"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?></span>
            </a>
        </div>
    </nav>

    <div class="checkout-container">
        <div class="checkout-summary">
            <h2>Order Summary</h2>
            <?php foreach ($cartItems as $item): ?>
                <div class="checkout-item">
                    <img src="<?= htmlspecialchars($item['book']['image_path']) ?>" alt="<?= htmlspecialchars($item['book']['title']) ?>">
                    <div>
                        <h4><?= htmlspecialchars($item['book']['title']) ?></h4>
                        <p>Quantity: <?= $item['quantity'] ?></p>
                        <p>$<?= number_format($item['subtotal'], 2) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="checkout-total">
                Total: $<?= number_format($total, 2) ?>
            </div>
        </div>
        
        <div class="checkout-form">
            <h2>Payment Information</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="checkout.php">
                <div class="form-group">
                    <label for="shipping_address">Shipping Address</label>
                    <textarea id="shipping_address" name="shipping_address" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="billing_address">Billing Address</label>
                    <textarea id="billing_address" name="billing_address" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Select Payment Method</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                
                <div id="credit_card_fields" style="display: none;">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                    </div>
                    
                    <div class="form-group">
                        <label for="card_expiry">Expiry Date</label>
                        <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY">
                    </div>
                    
                    <div class="form-group">
                        <label for="card_cvv">CVV</label>
                        <input type="text" id="card_cvv" name="card_cvv" placeholder="123">
                    </div>
                </div>
                
                <button type="submit" class="btn">Complete Order</button>
            </form>
        </div>
    </div>

    <script>
        // Show/hide credit card fields based on payment method
        document.getElementById('payment_method').addEventListener('change', function() {
            const creditCardFields = document.getElementById('credit_card_fields');
            creditCardFields.style.display = this.value === 'credit_card' ? 'block' : 'none';
        });
    </script>
</body>
</html>