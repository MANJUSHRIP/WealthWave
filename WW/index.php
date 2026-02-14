<!-- index.php -->
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit();
}

// Get user data
require_once 'includes/config.php';
$stmt = $pdo->prepare("SELECT username, points, level FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Literacy Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .module-card { cursor: pointer; transition: all 0.3s; }
        .module-card:hover { transform: translateY(-5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .module-card i { font-size: 2.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">💰 FinEdu</a>
            <div class="ms-auto text-white">
                Welcome, <?= htmlspecialchars($user['username']) ?>! 
                <span class="badge bg-light text-primary ms-2">Level: <?= $user['level'] ?></span>
                <span class="badge bg-success ms-2">Points: <?= $user['points'] ?></span>
            </div>
            <a href="auth/logout.php" class="btn btn-outline-light ms-3">Logout</a>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="text-center mb-5">Financial Literacy Dashboard</h2>
        
        <div class="row g-4">
            <!-- Budget Planner Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 module-card" onclick="loadModule('budget')">
                    <div class="card-body text-center">
                        <i class="fas fa-wallet text-primary"></i>
                        <h5 class="card-title">Budget Planner</h5>
                        <p class="card-text">Plan and track your monthly budget</p>
                        <button class="btn btn-primary">Open</button>
                    </div>
                </div>
            </div>

            <!-- Financial Quiz Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 module-card" onclick="loadModule('quiz')">
                    <div class="card-body text-center">
                        <i class="fas fa-question-circle text-success"></i>
                        <h5 class="card-title">Financial Quiz</h5>
                        <p class="card-text">Test your financial knowledge</p>
                        <button class="btn btn-success">Start Quiz</button>
                    </div>
                </div>
            </div>

            <!-- Daily Challenges Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 module-card" onclick="loadModule('challenges')">
                    <div class="card-body text-center">
                        <i class="fas fa-tasks text-warning"></i>
                        <h5 class="card-title">Daily Challenges</h5>
                        <p class="card-text">Complete tasks to earn points</p>
                        <button class="btn btn-warning">View Challenges</button>
                    </div>
                </div>
            </div>

            <!-- AI Financial Coach Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 module-card" onclick="loadModule('ai_coach')">
                    <div class="card-body text-center">
                        <i class="fas fa-robot text-info"></i>
                        <h5 class="card-title">AI Financial Coach</h5>
                        <p class="card-text">Get personalized financial advice</p>
                        <button class="btn btn-info text-white">Chat Now</button>
                    </div>
                </div>
            </div>

            <!-- Spending Analysis Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 module-card" onclick="loadModule('spending')">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-pie text-danger"></i>
                        <h5 class="card-title">Spending Analysis</h5>
                        <p class="card-text">Analyze your expenses</p>
                        <button class="btn btn-danger">View Analysis</button>
                    </div>
                </div>
            </div>

            <!-- My Rewards Card -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 module-card" onclick="loadModule('rewards')">
                    <div class="card-body text-center">
                        <i class="fas fa-trophy text-warning"></i>
                        <h5 class="card-title">My Rewards</h5>
                        <p class="card-text">View your achievements</p>
                        <button class="btn btn-warning">View Rewards</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Module Content Container -->
        <div id="module-content" class="mt-5"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        async function loadModule(module) {
            try {
                const contentDiv = document.getElementById('module-content');
                contentDiv.innerHTML = `
                    <div class="text-center my-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading ${module.replace('_', ' ')}...</p>
                    </div>`;

                const response = await fetch(`modules/${module}.php`);
                if (!response.ok) throw new Error('Module not found');
                
                contentDiv.innerHTML = await response.text();
                
                // Scroll to the module content
                contentDiv.scrollIntoView({ behavior: 'smooth' });
                
            } catch (error) {
                document.getElementById('module-content').innerHTML = `
                    <div class="alert alert-danger">
                        Error loading ${module}: ${error.message}
                        <button class="btn btn-sm btn-outline-secondary ms-2" onclick="loadModule('${module}')">
                            <i class="fas fa-sync-alt me-1"></i> Retry
                        </button>
                    </div>`;
            }
        }

        // Handle back to dashboard
        function backToDashboard() {
            document.getElementById('module-content').innerHTML = '';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>