<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user']['name'])) {
    header('Location: dashboard.php');
    exit();
}

$user = $_SESSION['user'];

// Calculate level based on XP
$level = 'Beginner';
$xp = $user['xp'] ?? 0;
$nextLevelXp = 50;
$levelProgress = ($xp / $nextLevelXp) * 100;

if ($xp >= 300) {
    $level = 'Financial Pro';
    $levelProgress = 100;
} elseif ($xp >= 150) {
    $level = 'Investor';
    $levelProgress = (($xp - 150) / 150) * 100;
} elseif ($xp >= 50) {
    $level = 'Smart Saver';
    $levelProgress = (($xp - 50) / 100) * 100;
} else {
    $levelProgress = ($xp / 50) * 100;
}

// Update user level if changed
if ($user['level'] !== $level) {
    $_SESSION['user']['level'] = $level;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FinWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="dashboard">
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-wallet me-2"></i>FinWise
            </a>
            <div class="d-flex align-items-center">
                <div class="me-3 text-white">
                    <i class="fas fa-coins me-1"></i> <span id="coin-count"><?php echo $user['coins'] ?? 0; ?></span>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($user['name']); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="rewards.php"><i class="fas fa-trophy me-2"></i>My Rewards</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="h4 mb-1">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h2>
                                <p class="text-muted mb-0">Your financial journey continues. Let's make smart money moves!</p>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold text-primary">Level: <?php echo $level; ?></div>
                                <div class="progress" style="width: 150px; height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min(100, $levelProgress); ?>%" 
                                         aria-valuenow="<?php echo $levelProgress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted"><?php echo $xp; ?> XP</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Health Score -->
        <div class="row mb-4">
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="financial-health-score">
                            <div class="score-circle" data-value="<?php echo $user['health_score'] ?? 0; ?>">
                                <span class="score-value"><?php echo $user['health_score'] ?? 0; ?></span>
                                <span class="score-label">Financial Health</span>
                            </div>
                        </div>
                        <a href="analysis.php" class="btn btn-sm btn-outline-primary mt-3">View Details</a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Quick Stats</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-coins text-warning me-2"></i>
                                <span>Coins: <strong><?php echo $user['coins'] ?? 0; ?></strong></span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-star text-info me-2"></i>
                                <span>Level: <strong><?php echo $level; ?></strong> (<?php echo $xp; ?> XP)</span>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-tasks text-success me-2"></i>
                                <span>Modules Completed: <strong><?php echo count($user['completed_modules'] ?? []); ?>/6</strong></span>
                            </li>
                            <li>
                                <i class="fas fa-trophy text-purple me-2"></i>
                                <span>Badges Earned: <strong><?php echo count($user['badges'] ?? []); ?></strong></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Daily Challenge -->
            <div class="col-md-12 col-lg-4 mb-3">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Daily Challenge</h5>
                            <span class="badge bg-primary">+5 XP</span>
                        </div>
                        <div class="challenge-card">
                            <div class="challenge-icon">
                                <i class="fas fa-bullseye"></i>
                            </div>
                            <div class="challenge-content">
                                <h6>Track Your Expenses</h6>
                                <p class="small text-muted">Log at least 3 expenses today to complete this challenge.</p>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 33%" 
                                         aria-valuenow="33" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted">1/3 completed</small>
                            </div>
                        </div>
                        <a href="challenge.php" class="btn btn-sm btn-outline-primary w-100 mt-3">View All Challenges</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modules Grid -->
        <h4 class="mb-3">Learning Modules</h4>
        <div class="row g-4">
            <!-- Budget Planner -->
            <div class="col-md-6 col-lg-4">
                <div class="card module-card h-100" onclick="window.location='budget.php'" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="module-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <h5 class="card-title mb-0 ms-3">Budget Planner</h5>
                        </div>
                        <p class="card-text text-muted">Plan and track your monthly budget with our smart budgeting tools.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="badge bg-light text-dark">
                                <i class="fas fa-coins text-warning me-1"></i> +10 XP
                            </div>
                            <?php if (in_array('budget', $user['completed_modules'] ?? [])): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark">Start</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Quiz -->
            <div class="col-md-6 col-lg-4">
                <div class="card module-card h-100" onclick="window.location='quiz.php'" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="module-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-question-circle"></i>
                            </div>
                            <h5 class="card-title mb-0 ms-3">Financial Quiz</h5>
                        </div>
                        <p class="card-text text-muted">Test your financial knowledge and earn coins with our interactive quizzes.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="badge bg-light text-dark">
                                <i class="fas fa-coins text-warning me-1"></i> +20 XP
                            </div>
                            <?php if (in_array('quiz', $user['completed_modules'] ?? [])): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark">Start</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Challenges -->
            <div class="col-md-6 col-lg-4">
                <div class="card module-card h-100" onclick="window.location='challenges.php'" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="module-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <h5 class="card-title mb-0 ms-3">Daily Challenges</h5>
                        </div>
                        <p class="card-text text-muted">Complete daily tasks to build better financial habits and earn rewards.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="badge bg-light text-dark">
                                <i class="fas fa-coins text-warning me-1"></i> +5 XP/day
                            </div>
                            <span class="badge bg-light text-dark">View</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Financial Coach -->
            <div class="col-md-6 col-lg-4">
                <div class="card module-card h-100" onclick="window.location='ai_coach.php'" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="module-icon bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-robot"></i>
                            </div>
                            <h5 class="card-title mb-0 ms-3">AI Financial Coach</h5>
                        </div>
                        <p class="card-text text-muted">Get personalized financial advice based on your spending and goals.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="badge bg-light text-dark">
                                <i class="fas fa-coins text-warning me-1"></i> +5 XP
                            </div>
                            <span class="badge bg-light text-dark">Chat Now</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Spending Analysis -->
            <div class="col-md-6 col-lg-4">
                <div class="card module-card h-100" onclick="window.location='analysis.php'" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="module-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <h5 class="card-title mb-0 ms-3">Spending Analysis</h5>
                        </div>
                        <p class="card-text text-muted">Analyze your spending patterns and get insights to save more.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="badge bg-light text-dark">
                                <i class="fas fa-coins text-warning me-1"></i> +10 XP
                            </div>
                            <?php if (in_array('analysis', $user['completed_modules'] ?? [])): ?>
                                <span class="badge bg-success">Completed</span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark">Analyze</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Rewards -->
            <div class="col-md-6 col-lg-4">
                <div class="card module-card h-100" onclick="window.location='rewards.php'" style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="module-icon bg-purple bg-opacity-10 text-purple">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <h5 class="card-title mb-0 ms-3">My Rewards</h5>
                        </div>
                        <p class="card-text text-muted">View your achievements, badges, and track your progress.</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="badge bg-light text-dark">
                                <i class="fas fa-award text-warning me-1"></i> <?php echo count($user['badges'] ?? []); ?> Badges
                            </div>
                            <span class="badge bg-light text-dark">View All</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">© <?php echo date('Y'); ?> FinWise. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-muted me-3">Terms</a>
                    <a href="#" class="text-muted me-3">Privacy</a>
                    <a href="#" class="text-muted">Help</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Animate financial health score
        document.addEventListener('DOMContentLoaded', function() {
            const scoreCircle = document.querySelector('.score-circle');
            const scoreValue = parseInt(scoreCircle.getAttribute('data-value'));
            let currentValue = 0;
            
            const animation = setInterval(() => {
                if (currentValue >= scoreValue) {
                    clearInterval(animation);
                } else {
                    currentValue++;
                    scoreCircle.querySelector('.score-value').textContent = currentValue;
                    
                    // Update circle progress (using CSS variables for the conic gradient)
                    const percentage = (currentValue / 100) * 360;
                    scoreCircle.style.setProperty('--progress', `${percentage}deg`);
                }
            }, 20);
        });
    </script>
</body>
</html>
