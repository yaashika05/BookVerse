<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "bookstore1";

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get all books or filtered by category
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;

    $query = "SELECT * FROM books";
    $conditions = [];
    $params = [];
    $types = '';

    if ($category) {
        $conditions[] = "category = ?";
        $params[] = $category;
        $types .= 's';
    }

    if ($search) {
        $conditions[] = "(title LIKE ? OR author LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'sss';
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY title ASC";

    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $books = $result->fetch_all(MYSQLI_ASSOC);

    // Get all categories for filter
    $categories_result = $conn->query("SELECT DISTINCT category FROM books ORDER BY category");
    $categories = $categories_result->fetch_all(MYSQLI_ASSOC);

    // Close statement
    $stmt->close();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
} finally {
    // Close connection in finally block to ensure it always happens
    if (isset($conn)) {
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books | BookVerse</title>
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
            background-color: #f5f7fa;
            color: #333;
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
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
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
        
        .auth-buttons .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
        }
        
        .btn-primary {
            background: white;
            color: var(--primary);
            margin-left: 1rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header */
        .page-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .page-header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        /* Filters */
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .filter-group button {
            padding: 10px 20px;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-group button:hover {
            background: #2980b9;
        }
        
        /* Books Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .book-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            position: relative;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .book-image {
            height: 300px;
            overflow: hidden;
            position: relative;
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
        
        .book-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--accent);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .book-info {
            padding: 20px;
        }
        
        .book-info h3 {
            font-size: 1.1rem;
            margin-bottom: 8px;
            color: var(--primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .book-info .author {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .book-info .price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 15px;
        }
        
        .book-actions {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--secondary);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: white;
            color: var(--secondary);
            border: 1px solid var(--secondary);
        }
        
        .btn-secondary:hover {
            background: #f0f8ff;
        }
        
        /* Book Details Popup */
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .popup-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .book-popup {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transform: scale(0.8);
            transition: transform 0.3s;
            position: relative;
        }
        
        .popup-overlay.active .book-popup {
            transform: scale(1);
        }
        
        .popup-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--accent);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            z-index: 10;
        }
        
        .popup-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            padding: 2rem;
        }
        
        .popup-image {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .popup-details h2 {
            margin-top: 0;
            color: var(--primary);
        }
        
        .popup-author {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .popup-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent);
            margin: 1rem 0;
        }
        
        .popup-description {
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .popup-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-add-to-cart {
    background: var(--secondary);
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
    flex: 1;
    transition: background 0.3s;
}

.btn-add-to-cart:hover {
    background: #2980b9;
}

.btn-wishlist {
    background: white;
    color: var(--primary);
    border: 1px solid var(--primary);
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
    flex: 1;
    transition: all 0.3s;
}

.btn-wishlist:hover {
    background: #f0f0f0;
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
            .books-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .book-image {
                height: 250px;
            }
            
            .popup-content {
                grid-template-columns: 1fr;
            }
            
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }
            
            .nav-links {
                margin: 1rem 0;
            }
        }
        
        @media (max-width: 576px) {
            .books-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .filters {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <a href="index.php" class="logo">BookVerse</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="categories.php">Categories</a>
            <a href="books.php" class="active">All Books</a>
            <a href="about.php">About</a>
            <a href="cart.php" class="cart-icon" title="Shopping Cart">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?= isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0 ?></span>
            </a>
        </div>
        <div class="auth-buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-primary">My Account</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary">Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <header class="page-header">
            <h1>Browse Our Collection</h1>
            <p>Discover your next favorite book from our carefully curated selection</p>
        </header>
        
        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label for="category">Category</label>
                <select id="category" name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                            <?php echo ($category == $cat['category']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Search</label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="search" name="search" placeholder="Title, author, or keyword" 
                           value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    <button type="submit">Search</button>
                </div>
            </div>
            
            <?php if ($category || $search): ?>
                <div class="filter-group" style="display: flex; align-items: flex-end;">
                    <a href="books.php" class="btn" style="text-decoration: none;">Clear Filters</a>
                </div>
            <?php endif; ?>
        </form>
        
        <!-- Books Grid -->
        <div class="books-grid">
            <?php if (empty($books)): ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No Books Found</h3>
                    <p>We couldn't find any books matching your criteria. Try adjusting your filters.</p>
                    <a href="books.php" class="btn btn-primary">Browse All Books</a>
                </div>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <div class="book-image">
                            <img src="<?php echo htmlspecialchars($book['image_path']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <?php if (isset($book['stock']) && $book['stock'] <= 0): ?>
                                <span class="book-badge">Out of Stock</span>
                            <?php elseif (isset($book['discount']) && $book['discount'] > 0): ?>
                                <span class="book-badge"><?php echo htmlspecialchars($book['discount']); ?>% Off</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="book-info">
                            <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="author">By <?php echo htmlspecialchars($book['author']); ?></p>
                            
                            <div class="price">
                                <?php if (isset($book['discount']) && $book['discount'] > 0): ?>
                                    <span style="text-decoration: line-through; color: #95a5a6; margin-right: 8px;">
                                        $<?php echo number_format($book['price'], 2); ?>
                                    </span>
                                    $<?php echo number_format($book['price'] * (1 - $book['discount'] / 100), 2); ?>
                                <?php else: ?>
                                    $<?php echo number_format($book['price'], 2); ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="book-actions">
                                <a href="javascript:void(0);" class="btn btn-primary view-book" 
                                   data-id="<?php echo $book['book_id']; ?>">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if (isset($book['stock']) && $book['stock'] > 0): ?>
                                    <a href="add_to_cart.php?id=<?php echo $book['book_id']; ?>" class="btn btn-secondary">
                                        <i class="fas fa-cart-plus"></i> Add
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-ban"></i> Unavailable
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Book Details Popup -->
    <div class="popup-overlay" id="bookPopup">
        <div class="book-popup">
            <div class="popup-close" id="closePopup">&times;</div>
            <div class="popup-content" id="popupContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>


function addToCart(bookId) {
    $.ajax({
        url: 'add_to_cart.php',
        type: 'POST',
        data: { 
            id: bookId,
            quantity: 1
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                if (data.success) {
                    // Update cart count
                    $('.cart-count').text(parseInt($('.cart-count').text()) + 1);
                    
                    // Show success message (you can use a better notification system)
                    alert('Book added to cart successfully!');
                } else {
                    alert(data.message);
                }
            } catch (e) {
                alert('Book added to cart successfully!');
                // Update cart count even if response isn't JSON
                $('.cart-count').text(parseInt($('.cart-count').text()) + 1);
            }
        },
        error: function() {
            alert('Error adding book to cart');
        }
    });
}


    $(document).ready(function() {
        // Open popup when a book is clicked
        $('.view-book').on('click', function() {
            const bookId = $(this).data('id');
            loadBookDetails(bookId);
        });
        
        // Close popup when X is clicked
        $('#closePopup').on('click', function() {
            $('#bookPopup').removeClass('active');
        });
        
        // Close popup when clicking outside the popup
        $('#bookPopup').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('active');
            }
        });
        
        // Function to load book details via AJAX
        function loadBookDetails(bookId) {
            $.ajax({
                url: 'get_book_details.php',
                type: 'GET',
                data: { id: bookId },
                beforeSend: function() {
                    $('#popupContent').html('<div style="padding: 2rem; text-align: center;">Loading...</div>');
                },
                success: function(response) {
                    $('#popupContent').html(response);
                    $('#bookPopup').addClass('active');
                },
                error: function() {
                    $('#popupContent').html('<div style="padding: 2rem; text-align: center;">Error loading book details. Please try again.</div>');
                    $('#popupContent').addClass('active');
                }
            });
        }
    });
    </script>
</body>
</html>