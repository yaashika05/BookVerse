<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bookstore1");

// User login handling
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            header("Refresh:0");
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "Email not found!";
    }
}

// Fetch books
$books_result = $conn->query("SELECT * FROM books LIMIT 8");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookVerse - Your Online bookstore1</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }
        
        /* Navbar Styles */
        .navbar {
            background-color: var(--primary);
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
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            color: var(--secondary);
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--secondary);
        }
        
        .auth-buttons .btn {
            padding: 0.5rem 1.2rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-login {
            background-color: transparent;
            color: white;
            border: 1px solid white;
            margin-right: 1rem;
        }
        
        .btn-login:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .btn-register {
            background-color: var(--secondary);
            color: white;
            border: 1px solid var(--secondary);
        }
        
        .btn-register:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1507842217343-583bb7270b66?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            height: 60vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            padding: 0 2rem;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            margin-bottom: 2rem;
        }
        
        .search-bar {
            display: flex;
            width: 100%;
            max-width: 600px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 4px 0 0 4px;
            font-size: 1rem;
        }
        
        .search-bar button {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 0 1.5rem;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-bar button:hover {
            background-color: #2980b9;
        }
        
        /* Books Section */
        .books-section {
            padding: 4rem 2rem;
            background-color: white;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-title h2 {
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 1rem;
        }
        
        .section-title p {
            color: #7f8c8d;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .book-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .book-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .book-image {
            height: 350px;
            overflow: hidden;
        }
        
        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .book-card:hover .book-image img {
            transform: scale(1.05);
        }
        
        .book-info {
            padding: 1.5rem;
        }
        
        .book-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--dark);
        }
        
        .book-info .author {
            color: #7f8c8d;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .book-info .price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 1rem;
        }
        
        .add-to-cart {
            width: 100%;
            padding: 0.8rem;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .add-to-cart:hover {
            background-color: #2980b9;
        }
        
        /* Login Popup */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            backdrop-filter: blur(5px);
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            background-color: var(--primary);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .login-header h2 {
            margin-bottom: 0.5rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .login-btn {
            width: 100%;
            padding: 0.8rem;
            background-color: var(--secondary);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-btn:hover {
            background-color: #2980b9;
        }
        
        .login-footer {
            text-align: center;
            padding: 1rem;
            border-top: 1px solid #eee;
        }
        
        .login-footer a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .error-message {
            color: var(--accent);
            text-align: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        /* Footer */
        .footer {
            background-color: var(--primary);
            color: white;
            padding: 4rem 2rem 2rem;
        }
        
        .footer-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .footer-column h3 {
            font-size: 1.2rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background-color: var(--secondary);
        }
        
        .footer-column ul {
            list-style: none;
        }
        
        .footer-column ul li {
            margin-bottom: 0.8rem;
        }
        
        .footer-column ul li a {
            color: #bdc3c7;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-column ul li a:hover {
            color: white;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: white;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background-color: var(--secondary);
            transform: translateY(-3px);
        }
        
        .copyright {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #bdc3c7;
            font-size: 0.9rem;
        }
        /* Add to your existing CSS */
.cart-icon {
    position: relative;
    color: white;
    font-size: 1.2rem;
    margin-left: 1rem;
    padding: 0.5rem;
}

.cart-count {
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }
            
            .nav-links {
                margin: 1rem 0;
            }
            
            .hero h1 {
                font-size: 2.2rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
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
        <div class="auth-buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-login">My Account</a>
                <a href="logout.php" class="btn btn-register">Logout</a>
            <?php else: ?>
                <a href="#" class="btn btn-login" id="open-login">Login</a>
                <a href="register.php" class="btn btn-register">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Discover Your Next Favorite Book</h1>
        <p>Browse our extensive collection of books across all genres. From bestsellers to hidden gems, find what speaks to you.</p>
        <div class="search-bar">
            <input type="text" placeholder="Search for books, authors, or categories...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </div>
    </section>

    <!-- Featured Books -->
    <section class="books-section">
        <div class="section-title">
            <h2>Featured Books</h2>
            <p>Handpicked selections from our collection</p>
        </div>
        <div class="books-grid">
            <?php while ($row = $books_result->fetch_assoc()): ?>
            <div class="book-card">
                <div class="book-image">
                    <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                </div>
                <div class="book-info">
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p class="author"><?php echo htmlspecialchars($row['author']); ?></p>
                    <p class="price">$<?php echo htmlspecialchars($row['price']); ?></p>
                    <button class="add-to-cart">Add to Cart</button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>BookVerse</h3>
                <p>Your one-stop destination for all kinds of books. We believe in the power of reading to transform lives.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="books.php">Books</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="faq.php">FAQ</a></li>
                    <li><a href="shipping.php">Shipping Policy</a></li>
                    <li><a href="returns.php">Return Policy</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="terms.php">Terms & Conditions</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contact Us</h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Book Street, Knowledge City</li>
                    <li><i class="fas fa-phone"></i> +1 (234) 567-8900</li>
                    <li><i class="fas fa-envelope"></i> info@bookverse.com</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; <?php echo date('Y'); ?> BookVerse. All Rights Reserved.
        </div>
    </footer>

    <!-- Login Popup -->
    <?php if(!isset($_SESSION['user_id'])): ?>
    <div class="login-overlay" id="login-overlay">
        <div class="login-container">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Login to your BookVerse account</p>
            </div>
            <div class="login-body">
                <?php if(isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" name="login" class="login-btn">Login</button>
                </form>
            </div>
            <div class="login-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Toggle login popup
        document.getElementById('open-login')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('login-overlay').style.display = 'flex';
        });
        
        // Close login popup when clicking outside
        document.getElementById('login-overlay')?.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    </script>
</body>
</html>