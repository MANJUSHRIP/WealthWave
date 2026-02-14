<?php
// rewards.php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in to view rewards');
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT points, level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Define available rewards
$rewards = [
    ['id' => 1, 'name' => 'Beginner Saver', 'points_required' => 100, 'description' => 'Unlock basic financial tips', 'icon' => 'fa-medal'],
    ['id' => 2, 'name' => 'Budget Master', 'points_required' => 300, 'description' => 'Advanced budgeting tools', 'icon' => 'fa-chart-pie'],
    ['id' => 3, 'name' => 'Investment Guru', 'points_required' => 500, 'description' => 'Investment guide access', 'icon' => 'fa-chart-line'],
    ['id' => 4, 'name' => 'Financial Wizard', 'points_required' => 1000, 'description' => 'Premium financial advisor', 'icon' => 'fa-hat-wizard']
];
?>

<div class="container my-4">
    <h2><i class="fas fa-trophy me-2"></i>My Rewards</h2>
    <div class="row">
        <!-- Points and Level Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-primary">
                <div class="card-body text-center">
                    <h3 class="card-title">Your Status</h3>
                    <div class="display-4 text-primary mb-3">
                        <i class="fas fa-star"></i> Level <?= htmlspecialchars($user['level'] ?? 'Beginner') ?>
                    </div>
                    <div class="h4">
                        <span class="badge bg-success">
                            <i class="fas fa-coins me-2"></i>
                            <?= number_format($user['points'] ?? 0) ?> Points
                        </span>
                    </div>
                    <div class="progress mt-3" style="height: 20px;">
                        <?php $progress = min(100, (($user['points'] ?? 0) % 1000) / 10); ?>
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: <?= $progress ?>%" 
                             aria-valuenow="<?= $progress ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?= $progress ?>%
                        </div>
                    </div>
                    <p class="text-muted mt-2 mb-0">To next level</p>
                </div>
            </div>
        </div>

        <!-- Available Rewards -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Available Rewards</h4>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <?php foreach ($rewards as $reward): 
                            $unlocked = ($user['points'] ?? 0) >= $reward['points_required'];
                            $cardClass = $unlocked ? 'border-success' : 'border-secondary';
                            $textClass = $unlocked ? 'text-success' : 'text-muted';
                        ?>
                        <div class="col">
                            <div class="card h-100 <?= $cardClass ?>">
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <i class="fas <?= $reward['icon'] ?> fa-3x <?= $unlocked ? 'text-primary' : 'text-muted' ?>"></i>
                                    </div>
                                    <h5 class="card-title <?= $textClass ?>">
                                        <?= htmlspecialchars($reward['name']) ?>
                                        <?php if ($unlocked): ?>
                                            <span class="badge bg-success">Unlocked</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="card-text"><?= htmlspecialchars($reward['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= $unlocked ? 'success' : 'secondary' ?>">
                                            <?= $reward['points_required'] ?> points
                                        </span>
                                        <?php if (!$unlocked): ?>
                                            <small class="text-muted">
                                                <?= $reward['points_required'] - ($user['points'] ?? 0) ?> more points needed
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .progress {
        border-radius: 10px;
        overflow: hidden;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add any client-side interactions here
});
</script>
