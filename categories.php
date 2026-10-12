<?php
session_start();
require_once 'db_connect.php';

// Get all categories with book counts
$categories_query = $conn->query("
    SELECT category, COUNT(*) as book_count 
    FROM books 
    GROUP BY category 
    ORDER BY category
");
$categories = $categories_query->fetch_all(MYSQLI_ASSOC);

// Get featured books (1-2 per category)
$featured_by_category = [];
foreach($categories as $cat) {
    $stmt = $conn->prepare("
        SELECT book_id, title, author, image_path, price 
        FROM books 
        WHERE category = ? 
        ORDER BY RAND() 
        LIMIT 2
    ");
    $stmt->bind_param("s", $cat['category']);
    $stmt->execute();
    $featured_by_category[$cat['category']] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Categories | BookVerse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #212529;
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
        
        /* Main Content */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: var(--light);
            color: var(--dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        h1 {
            color: var(--primary);
            text-align: center;
            margin-bottom: 2rem;
        }
        
        /* Category Grid */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }
        
        .category-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%; /* Fixed height */
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .category-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            text-align: center;
        }
        
        .category-title {
            margin: 0;
            font-size: 1.25rem;
            text-transform: capitalize;
        }
        
        .book-count {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .book-previews {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            padding: 1rem;
            flex-grow: 1; /* Fill remaining space */
        }
        
        .preview-item {
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }
        
        .preview-item:hover {
            transform: scale(1.03);
        }
        
        .preview-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .preview-title {
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .view-all {
            display: block;
            text-align: center;
            padding: 0.75rem;
            background: var(--secondary);
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
            margin-top: auto; /* Push to bottom */
        }
        
        .view-all:hover {
            background: #2980b9;
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                padding: 1rem;
            }
            
            .nav-links {
                margin: 1rem 0;
            }
            
            .category-grid {
                grid-template-columns: 1fr;
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
            <a href="categories.php" class="active">Categories</a>
            <a href="books.php">All Books</a>
            <a href="about.php">About</a>
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
        <h1>Browse by Category</h1>
        
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-header">
                        <h3 class="category-title"><?= htmlspecialchars($category['category']) ?></h3>
                        <div class="book-count"><?= $category['book_count'] ?> book<?= $category['book_count'] > 1 ? 's' : '' ?></div>
                    </div>
                    
                    <div class="book-previews">
                        <?php foreach ($featured_by_category[$category['category']] as $book): ?>
                            <a href="book_details.php?id=<?= $book['book_id'] ?>" class="preview-item">
                                <img src="<?= htmlspecialchars($book['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($book['title']) ?>">
                                <p class="preview-title"><?= htmlspecialchars($book['title']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <a href="books.php?category=<?= urlencode($category['category']) ?>" class="view-all">
                        View All <?= htmlspecialchars($category['category']) ?> Books
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>