<?php
require_once '../includes/config.php';

// Initialize variables
$username = '';
$email = '';
$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Username or email already exists.';
            } else {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, points, level) 
                    VALUES (?, ?, ?, 0, 'Beginner')
                ");
                
                if ($stmt->execute([$username, $email, $hashedPassword])) {
                    $success = 'Registration successful! You can now login.';
                    // Clear form
                    $username = $email = '';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Financial Literacy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .register-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-logo i {
            font-size: 3rem;
            color: #0d6efd;
        }
        .btn-register {
            background-color: #0d6efd;
            border: none;
            padding: 0.5rem;
        }
        .btn-register:hover {
            background-color: #0b5ed7;
        }
        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-logo">
                <i class="fas fa-piggy-bank"></i>
                <h2 class="mt-2">FinEdu</h2>
                <p class="text-muted">Create your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?> <a href="login.php" class="alert-link">Click here to login</a>.
                </div>
            <?php else: ?>
                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="form-text">Choose a unique username (letters, numbers, and underscores only)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="form-text">We'll never share your email with anyone else.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-register">
                            <i class="fas fa-user-plus me-2"></i> Create Account
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password match validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (password.value !== confirmPassword.value) {
                        event.preventDefault();
                        alert('Passwords do not match!');
                        confirmPassword.focus();
                    }
                });
            }
            
            // Toggle password visibility
            const togglePassword = document.createElement('span');
            togglePassword.innerHTML = ' <i class="fas fa-eye toggle-password" style="cursor: pointer;"></i>';
            password.parentNode.insertBefore(togglePassword, password.nextSibling);
            
            const toggleConfirmPassword = document.createElement('span');
            toggleConfirmPassword.innerHTML = ' <i class="fas fa-eye toggle-password" style="cursor: pointer;"></i>';
            confirmPassword.parentNode.insertBefore(toggleConfirmPassword, confirmPassword.nextSibling);
            
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('toggle-password')) {
                    const input = e.target.parentNode.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    e.target.classList.toggle('fa-eye');
                    e.target.classList.toggle('fa-eye-slash');
                }
            });
        });
    </script>
</body>
</html>
