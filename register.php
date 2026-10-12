<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bookstore1");

// Handle registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if username/email exists
    if (empty($errors)) {
        $check_query = "SELECT * FROM users WHERE name = ? OR email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Username or email already exists";
        }
    }

    // Register user if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $insert_query = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION["success"] = "Registration successful! Please login.";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | BookVerse</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .register-container {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 2rem;
        }
        
        .register-header {
            background: var(--primary);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .register-header h2 {
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .register-header p {
            opacity: 0.8;
        }
        
        .register-header::after {
            content: '';
            position: absolute;
            bottom: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 40px;
            background: white;
            rotate: 45deg;
            z-index: 1;
        }
        
        .register-body {
            padding: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }
        
        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            outline: none;
        }
        
        .form-group i {
            position: absolute;
            left: 1rem;
            top: 2.5rem;
            color: #7f8c8d;
        }
        
        .btn-register {
            width: 100%;
            padding: 1rem;
            background: var(--secondary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
        }
        
        .btn-register:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #7f8c8d;
        }
        
        .login-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: var(--accent);
            background: rgba(231, 76, 60, 0.1);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .success-message {
            color: #27ae60;
            background: rgba(39, 174, 96, 0.1);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .password-strength {
            height: 5px;
            background: #eee;
            border-radius: 5px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0%;
            background: #e74c3c;
            transition: all 0.3s;
        }
        
        @media (max-width: 576px) {
            .register-container {
                margin: 1rem;
            }
            
            .register-header {
                padding: 1.5rem;
            }
            
            .register-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h2>Create Your Account</h2>
            <p>Join our community of book lovers</p>
        </div>
        
        <div class="register-body">
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION["success"])): ?>
                <div class="success-message">
                    <p><?php echo htmlspecialchars($_SESSION["success"]); ?></p>
                </div>
                <?php unset($_SESSION["success"]); ?>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required
                           oninput="checkPasswordStrength(this.value)">
                    <div class="password-strength">
                        <div class="strength-meter" id="strengthMeter"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn-register">Register Now</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="index.php">Sign in</a>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthMeter = document.getElementById('strengthMeter');
            let strength = 0;
            
            // Check length
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Check for mixed case
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
            
            // Check for numbers
            if (/\d/.test(password)) strength += 1;
            
            // Check for special chars
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            // Update meter
            const width = strength * 20;
            strengthMeter.style.width = `${width}%`;
            
            // Update color
            if (width < 40) {
                strengthMeter.style.backgroundColor = '#e74c3c'; // Red
            } else if (width < 80) {
                strengthMeter.style.backgroundColor = '#f39c12'; // Orange
            } else {
                strengthMeter.style.backgroundColor = '#27ae60'; // Green
            }
        }
    </script>
</body>
</html>