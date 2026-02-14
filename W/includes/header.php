<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>FinWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="<?php echo $body_class ?? ''; ?>">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../dashboard.php">
                <i class="fas fa-wallet me-2"></i>FinWise
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php"><i class="fas fa-home me-1"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../budget.php"><i class="fas fa-calculator me-1"></i> Budget</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../expenses.php"><i class="fas fa-receipt me-1"></i> Expenses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../savings.php"><i class="fas fa-piggy-bank me-1"></i> Savings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="me-3 text-white">
                        <i class="fas fa-coins me-1"></i> 
                        <span id="coin-count"><?php echo $user['coins'] ?? 0; ?></span>
                        <span class="badge bg-light text-dark ms-2">
                            <i class="fas fa-star text-warning"></i> 
                            <span class="xp-count"><?php echo $user['xp'] ?? 0; ?></span> XP
                        </span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center" type="button" 
                                id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <span class="d-none d-md-inline">
                                <?php echo htmlspecialchars($user['name'] ?? 'User'); ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="../profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="../settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
