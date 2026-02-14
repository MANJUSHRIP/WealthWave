<?php
// challenges.php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in to view challenges');
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT points, level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Sample challenges (in a real app, these would come from a database)
$challenges = [
    [
        'id' => 1,
        'title' => 'Complete Your First Budget',
        'description' => 'Create and save your first monthly budget',
        'points' => 50,
        'completed' => false,
        'icon' => 'fa-wallet'
    ],
    [
        'id' => 2,
        'title' => 'Quiz Master',
        'description' => 'Complete a financial literacy quiz with 80% or higher',
        'points' => 100,
        'completed' => false,
        'icon' => 'fa-trophy'
    ],
    [
        'id' => 3,
        'title' => 'Savings Starter',
        'description' => 'Save at least 10% of your income this month',
        'points' => 75,
        'completed' => false,
        'icon' => 'fa-piggy-bank'
    ],
    [
        'id' => 4,
        'title' => 'Early Bird',
        'description' => 'Check your budget for 3 consecutive days',
        'points' => 30,
        'completed' => false,
        'icon' => 'fa-calendar-check'
    ]
];
?>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-tasks me-2"></i>Daily Challenges</h3>
                </div>
                <div class="card-body">
                    <div class="row" id="challenges-list">
                        <?php foreach ($challenges as $challenge): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-<?= $challenge['completed'] ? 'success' : 'secondary' ?> shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="icon-circle bg-<?= $challenge['completed'] ? 'success' : 'light' ?> text-<?= $challenge['completed'] ? 'white' : 'primary' ?> me-3">
                                            <i class="fas <?= $challenge['icon'] ?> fa-fw"></i>
                                        </div>
                                        <h5 class="card-title mb-0"><?= htmlspecialchars($challenge['title']) ?></h5>
                                    </div>
                                    <p class="card-text"><?= htmlspecialchars($challenge['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-<?= $challenge['completed'] ? 'success' : 'secondary' ?>">
                                            <i class="fas fa-star me-1"></i> <?= $challenge['points'] ?> points
                                        </span>
                                        <?php if ($challenge['completed']): ?>
                                            <span class="text-success"><i class="fas fa-check-circle me-1"></i> Completed</span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="completeChallenge(<?= $challenge['id'] ?>, this)">
                                                Mark Complete
                                            </button>
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
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-trophy me-2"></i>Your Progress</h4>
                </div>
                <div class="card-body text-center">
                    <div class="position-relative d-inline-block mb-4">
                        <div class="progress-circle" data-value="<?= min(($user['points'] / 1000) * 100, 100) ?>">
                            <span class="progress-left">
                                <span class="progress-bar border-primary"></span>
                            </span>
                            <span class="progress-right">
                                <span class="progress-bar border-primary"></span>
                            </span>
                            <div class="progress-value w-100 h-100 rounded-circle d-flex align-items-center justify-content-center">
                                <div>
                                    <h2 class="mb-0"><?= $user['points'] ?? 0 ?></h2>
                                    <span class="text-muted small">Points</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Level: <?= htmlspecialchars($user['level'] ?? 'Beginner') ?></h5>
                    
                    <div class="progress mb-3" style="height: 10px;">
                        <?php $levelProgress = ($user['points'] % 1000 / 10); ?>
                        <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: <?= $levelProgress ?>%" 
                             aria-valuenow="<?= $levelProgress ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted small mb-0">
                        <?= 1000 - ($user['points'] % 1000) ?> points to next level
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-lightbulb me-2"></i>How It Works</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <h6 class="mb-1"><i class="fas fa-star text-warning me-2"></i>Earn Points</h6>
                            <p class="small text-muted mb-0">Complete challenges to earn points and level up.</p>
                        </li>
                        <li class="mb-3">
                            <h6 class="mb-1"><i class="fas fa-trophy text-warning me-2"></i>Level Up</h6>
                            <p class="small text-muted mb-0">Earn 1000 points to reach the next level.</p>
                        </li>
                        <li>
                            <h6 class="mb-1"><i class="fas fa-gem text-warning me-2"></i>Unlock Rewards</h6>
                            <p class="small text-muted mb-0">Higher levels unlock special rewards and features.</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.progress-circle {
    width: 160px;
    height: 160px;
    position: relative;
    color: #4e73df;
}

.progress-circle:after {
    content: "";
    display: inline-block;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background-color: #f8f9fc;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 0;
}

.progress-circle > span {
    width: 50%;
    height: 100%;
    overflow: hidden;
    position: absolute;
    top: 0;
    z-index: 1;
}

.progress-circle .progress-left {
    left: 0;
}

.progress-circle .progress-bar {
    width: 100%;
    height: 100%;
    background: none;
    border-width: 10px;
    border-style: solid;
    position: absolute;
    top: 0;
}

.progress-circle .progress-left .progress-bar {
    left: 100%;
    border-top-right-radius: 80px;
    border-bottom-right-radius: 80px;
    border-left: 0;
    -webkit-transform-origin: center left;
    transform-origin: center left;
}

.progress-circle .progress-right {
    right: 0;
}

.progress-circle .progress-right .progress-bar {
    left: -100%;
    border-top-left-radius: 80px;
    border-bottom-left-radius: 80px;
    border-right: 0;
    -webkit-transform-origin: center right;
    transform-origin: center right;
}

.progress-circle .progress-value {
    position: absolute;
    top: 5px;
    left: 5px;
    right: 5px;
    bottom: 5px;
    background-color: #fff;
    border-radius: 50%;
    z-index: 2;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.icon-circle {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
</style>

<script>
async function completeChallenge(challengeId, button) {
    try {
        // In a real app, this would make an API call to your backend
        // For now, we'll simulate a successful response
        const response = await new Promise(resolve => {
            setTimeout(() => {
                resolve({
                    success: true,
                    points: 50, // This would come from the server
                    newPoints: <?= $user['points'] + 50 ?>,
                    level: '<?= $user['level'] ?>'
                });
            }, 1000);
        });
        
        if (response.success) {
            // Update UI
            const card = button.closest('.card');
            card.classList.remove('border-secondary');
            card.classList.add('border-success');
            
            const icon = card.querySelector('.icon-circle');
            icon.classList.remove('bg-light', 'text-primary');
            icon.classList.add('bg-success', 'text-white');
            
            const badge = card.querySelector('.badge');
            badge.classList.remove('bg-secondary');
            badge.classList.add('bg-success');
            
            button.outerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> Completed</span>';
            
            // Show success message
            const alert = document.createElement('div');
            alert.className = 'alert alert-success alert-dismissible fade show';
            alert.innerHTML = `
                <strong>Challenge completed!</strong> You earned ${response.points} points!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.getElementById('challenges-list').prepend(alert);
            
            // Update points display
            const pointsDisplay = document.querySelector('.progress-value h2');
            if (pointsDisplay) {
                pointsDisplay.textContent = response.newPoints;
            }
            
            // Update progress circle (simplified)
            const progress = Math.min((response.newPoints / 1000) * 100, 100);
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                if (bar.style) {
                    bar.style.width = `${progress}%`;
                }
            });
            
            // Update level progress text
            const pointsToNext = 1000 - (response.newPoints % 1000);
            const progressText = document.querySelector('.progress + .text-muted');
            if (progressText) {
                progressText.textContent = `${pointsToNext} points to next level`;
            }
        }
    } catch (error) {
        console.error('Error completing challenge:', error);
        alert('Failed to complete challenge. Please try again.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize progress circles
    const progressCircles = document.querySelectorAll('.progress-circle');
    progressCircles.forEach(circle => {
        const value = parseFloat(circle.getAttribute('data-value')) || 0;
        const left = circle.querySelector('.progress-left .progress-bar');
        const right = circle.querySelector('.progress-right .progress-bar');
        
        if (value > 0) {
            if (value <= 50) {
                right.style.transform = `rotate(${value * 3.6}deg)`;
            } else {
                right.style.transform = 'rotate(180deg)';
                left.style.transform = `rotate(${(value - 50) * 3.6}deg)`;
            }
        }
    });
});
</script>
