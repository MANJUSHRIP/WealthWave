<?php
// rewards.php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user']['name'])) {
    header('Location: dashboard.php');
    exit();
}

$user = $_SESSION['user'];
$page_title = 'My Rewards';
$body_class = 'rewards-module';

// Include header
include 'includes/header.php';

// Initialize user data if not exists
if (!isset($user['rewards'])) {
    $user['rewards'] = [
        'coins' => 0,
        'xp' => 0,
        'level' => 1,
        'badges' => [],
        'completed_challenges' => 0,
        'quiz_scores' => [],
        'last_activity' => date('Y-m-d H:i:s')
    ];
    $_SESSION['user'] = $user;
}

// Function to calculate level based on XP
function calculateLevel($xp) {
    if ($xp >= 300) return ['level' => 4, 'title' => 'Financial Pro', 'next_level_xp' => null];
    if ($xp >= 150) return ['level' => 3, 'title' => 'Investor', 'next_level_xp' => 300 - $xp];
    if ($xp >= 50) return ['level' => 2, 'title' => 'Smart Saver', 'next_level_xp' => 150 - $xp];
    return ['level' => 1, 'title' => 'Beginner', 'next_level_xp' => 50 - $xp];
}

// Available badges with unlock conditions
$availableBadges = [
    'budget_master' => [
        'title' => 'Budget Master',
        'description' => 'Successfully complete the budget planner 5 times',
        'icon' => 'fa-calculator',
        'check' => function($user) {
            return ($user['completed_budgets'] ?? 0) >= 5;
        }
    ],
    'savings_star' => [
        'title' => 'Savings Star',
        'description' => 'Save more than 20% of income for 3 consecutive months',
        'icon' => 'fa-piggy-bank',
        'check' => function($user) {
            $history = $user['spending_analysis']['history'] ?? [];
            if (count($history) < 3) return false;
            return array_slice($history, 0, 3) === array_filter(
                array_slice($history, 0, 3),
                fn($h) => ($h['savings_percentage'] ?? 0) >= 20
            );
        }
    ],
    'investment_rookie' => [
        'title' => 'Investment Rookie',
        'description' => 'Complete the investment module and score above 70%',
        'icon' => 'fa-chart-line',
        'check' => function($user) {
            $scores = $user['quiz_scores']['investment'] ?? [];
            return !empty($scores) && end($scores) >= 70;
        }
    ],
    'fraud_fighter' => [
        'title' => 'Fraud Fighter',
        'description' => 'Complete all security-related quizzes with 100% score',
        'icon' => 'fa-shield-alt',
        'check' => function($user) {
            $securityQuizzes = ['digital_payment', 'fraud_prevention'] ?? [];
            foreach ($securityQuizzes as $quiz) {
                $scores = $user['quiz_scores'][$quiz] ?? [];
                if (empty($scores) || end($scores) < 100) return false;
            }
            return true;
        }
    ],
    'financial_pro' => [
        'title' => 'Financial Pro',
        'description' => 'Reach the highest level (Financial Pro)',
        'icon' => 'fa-trophy',
        'check' => function($user) {
            return ($user['rewards']['level'] ?? 0) >= 4;
        }
    ]
];

// Check and update badges
$updatedBadges = $user['rewards']['badges'] ?? [];
foreach ($availableBadges as $id => $badge) {
    if (!in_array($id, $updatedBadges) && $badge['check']($user)) {
        $updatedBadges[] = $id;
        // Add notification for new badge
        if (!isset($_SESSION['notifications'])) {
            $_SESSION['notifications'] = [];
        }
        $_SESSION['notifications'][] = [
            'type' => 'success',
            'message' => "🎉 You've earned the {$badge['title']} badge!",
            'timestamp' => time()
        ];
    }
}

// Update user data
$levelInfo = calculateLevel($user['rewards']['xp'] ?? 0);
$user['rewards'] = array_merge($user['rewards'], [
    'badges' => $updatedBadges,
    'level' => $levelInfo['level'],
    'level_title' => $levelInfo['title'],
    'next_level_xp' => $levelInfo['next_level_xp'],
    'progress' => $levelInfo['next_level_xp'] !== null 
        ? min(100, round((1 - ($levelInfo['next_level_xp'] / 
            ($levelInfo['level'] === 1 ? 50 : 
             ($levelInfo['level'] === 2 ? 100 : 150)))) * 100))
        : 100
]);
$_SESSION['user'] = $user;
?>

<div class="rewards-module">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">My Rewards</li>
                </ol>
            </nav>
            <h2 class="mb-3">My Rewards</h2>
            <p class="lead">Track your progress, earn badges, and level up your financial knowledge!</p>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="fas fa-coins fa-2x"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($user['rewards']['coins']); ?></h3>
                    <p class="text-muted mb-0">Total Coins</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($user['rewards']['xp']); ?> XP</h3>
                    <p class="text-muted mb-0">Experience Points</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="fas fa-trophy fa-2x"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $user['rewards']['level_title']; ?></h3>
                    <p class="text-muted mb-0">Level <?php echo $user['rewards']['level']; ?></p>
                    <?php if ($user['rewards']['next_level_xp'] !== null): ?>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?php echo $user['rewards']['progress']; ?>%" 
                                 aria-valuenow="<?php echo $user['rewards']['progress']; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted">
                            <?php echo $user['rewards']['next_level_xp']; ?> XP to next level
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Badges -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Badges</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach ($availableBadges as $id => $badge): 
                            $isEarned = in_array($id, $user['rewards']['badges']);
                        ?>
                            <div class="col-md-6">
                                <div class="card h-100 border-0 shadow-sm <?php echo $isEarned ? 'border-primary' : 'opacity-50'; ?>">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="badge-icon bg-<?php echo $isEarned ? 'primary' : 'light'; ?> text-<?php echo $isEarned ? 'white' : 'muted'; ?>">
                                                    <i class="fas <?php echo $badge['icon']; ?>"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-1"><?php echo $badge['title']; ?></h6>
                                                <p class="small text-muted mb-0"><?php echo $badge['description']; ?></p>
                                                <?php if ($isEarned): ?>
                                                    <span class="badge bg-success mt-2">
                                                        <i class="fas fa-check me-1"></i> Earned
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted mt-2">
                                                        <i class="fas fa-lock me-1"></i> Locked
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress & Stats -->
        <div class="col-lg-4">
            <!-- Quiz Performance -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quiz Performance</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($user['quiz_scores'])): ?>
                        <?php foreach ($user['quiz_scores'] as $quiz => $scores): 
                            $lastScore = end($scores);
                            $quizName = ucwords(str_replace('_', ' ', $quiz));
                        ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small"><?php echo $quizName; ?></span>
                                    <span class="small fw-bold"><?php echo $lastScore; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar 
                                        <?php 
                                            if ($lastScore >= 80) echo 'bg-success';
                                            elseif ($lastScore >= 60) echo 'bg-info';
                                            elseif ($lastScore >= 40) echo 'bg-warning';
                                            else echo 'bg-danger';
                                        ?>" 
                                        role="progressbar" 
                                        style="width: <?php echo $lastScore; ?>%" 
                                        aria-valuenow="<?php echo $lastScore; ?>" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-info-circle text-muted fa-2x mb-2"></i>
                            <p class="text-muted mb-0">Complete quizzes to see your performance</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Challenges Completed -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Challenges</h5>
                </div>
                <div class="card-body text-center">
                    <div class="display-4 text-primary mb-1">
                        <?php echo $user['rewards']['completed_challenges']; ?>
                    </div>
                    <p class="text-muted mb-3">Challenges Completed</p>
                    <a href="challenges.php" class="btn btn-outline-primary btn-sm">
                        View All Challenges
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Badge styles */
.badge-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

/* Card hover effects */
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
}

/* Progress bar customization */
.progress {
    border-radius: 10px;
    background-color: #f0f2f5;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .badge-icon {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>