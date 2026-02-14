<?php
session_start();

// Initialize user data if not exists
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'name' => '',
        'email' => '',
        'coins' => 0,
        'xp' => 0,
        'level' => 'Beginner',
        'health_score' => 0,
        'completed_modules' => [],
        'badges' => [],
        'savings_goal' => 0,
        'savings_current' => 0,
        'expenses' => [
            'rent' => 0,
            'food' => 0,
            'travel' => 0,
            'entertainment' => 0,
            'other' => 0
        ]
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $_SESSION['user']['name'] = trim($_POST['name']);
    $_SESSION['user']['email'] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FinWise - Financial Literacy Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <img src="assets/images/logo.png" alt="FinWise" class="mb-4" style="max-width: 120px;">
                            <h2 class="fw-bold text-primary">Welcome to FinWise</h2>
                            <p class="text-muted">Your personal financial literacy companion</p>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="name" name="name" required 
                                           placeholder="Enter your name" value="<?php echo htmlspecialchars($_SESSION['user']['name']); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           placeholder="Enter your email" value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>">
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Get Started
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0 text-muted">By continuing, you agree to our <a href="#">Terms</a> and <a href="#">Privacy Policy</a></p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p class="text-muted">© <?php echo date('Y'); ?> FinWise. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
