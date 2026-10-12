<?php
session_start();
require_once 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Handle remove item action
if (isset($_GET['remove'])) {
    $bookId = $_GET['remove'];
    if (isset($_SESSION['cart'][$bookId])) {
        unset($_SESSION['cart'][$bookId]);
    }
    header("Location: cart.php");
    exit;
}

// Handle quantity update
if (isset($_POST['update_quantity'])) {
    foreach ($_POST['quantity'] as $bookId => $quantity) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$bookId]);
        } else {
            $_SESSION['cart'][$bookId] = $quantity;
        }
    }
    header("Location: cart.php");
    exit;
}

// Get cart items details
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
        $price = isset($book['discount']) && $book['discount'] > 0 ? 
                 $book['price'] * (1 - $book['discount'] / 100) : $book['price'];
        $subtotal = $price * $quantity;
        
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
    <title>Your Shopping Cart | BookVerse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        /* Navbar Styles */
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
        
        .nav-links a:hover::after {
            width: 100%;
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
        
        /* Cart Page Styles (keep your existing cart styles) */
        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 20px;
        }
        
        .cart-header {
            margin-bottom: 30px;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .cart-table th, .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .cart-table th {
            background: var(--primary);
            color: white;
        }
        
        .cart-item-image {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            text-align: center;
        }
        
        .remove-item {
            color: var(--accent);
            cursor: pointer;
        }
        
        .cart-summary {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-top: 30px;
        }
        
        .cart-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent);
            margin-top: 20px;
        }
        
        .checkout-btn {
            display: inline-block;
            padding: 12px 30px;
            background: var(--secondary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .checkout-btn:hover {
            background: #2980b9;
        }
        
        .empty-cart {
            text-align: center;
            padding: 50px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: var(--secondary);
            color: white;
            border: none;
        }
        
        .btn-secondary {
            background: white;
            color: var(--secondary);
            border: 1px solid var(--secondary);
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }
            
            .nav-links {
                margin: 1rem 0;
            }
            
            .cart-table {
                display: block;
                overflow-x: auto;
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

    <div class="cart-container">
        <div class="cart-header">
            <h1>Your Shopping Cart</h1>
        </div>
        
        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; color: #bdc3c7;"></i>
                <h3>Your cart is empty</h3>
                <p>Browse our collection to find your next favorite book!</p>
                <a href="books.php" class="btn btn-primary">Browse Books</a>
            </div>
        <?php else: ?>
            <form method="post" action="cart.php">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <img src="<?= htmlspecialchars($item['book']['image_path']) ?>" 
                                             alt="<?= htmlspecialchars($item['book']['title']) ?>" 
                                             class="cart-item-image">
                                        <div>
                                            <h4><?= htmlspecialchars($item['book']['title']) ?></h4>
                                            <p>by <?= htmlspecialchars($item['book']['author']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    Rs.<?= number_format(isset($item['book']['discount']) && $item['book']['discount'] > 0 ? 
                                        $item['book']['price'] * (1 - $item['book']['discount'] / 100) : 
                                        $item['book']['price'], 2) ?>
                                </td>
                                <td>
                                    <input type="number" name="quantity[<?= $item['book']['book_id'] ?>]" 
                                           value="<?= $item['quantity'] ?>" min="1" class="quantity-input">
                                </td>
                                <td>Rs.<?= number_format($item['subtotal'], 2) ?></td>
                                <td>
                                    <a href="cart.php?remove=<?= $item['book']['book_id'] ?>" class="remove-item">
                                        <i class="fas fa-trash"></i> Remove
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="display: flex; justify-content: space-between;">
                    <a href="books.php" class="btn btn-secondary">Continue Shopping</a>
                    <button type="submit" name="update_quantity" class="btn btn-primary">Update Cart</button>
                </div>
                
                <div class="cart-summary">
                    <h3>Cart Summary</h3>
                    <div class="cart-total">
                        Total: Rs.<?= number_format($total, 2) ?>
                    </div>
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>