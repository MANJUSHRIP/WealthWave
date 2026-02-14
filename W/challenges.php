<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user']['name'])) {
    header('Location: dashboard.php');
    exit();
}

$user = $_SESSION['user'];
$page_title = 'Daily Challenges';
$body_class = 'challenges-module';

// Include header
include 'includes/header.php';

// Initialize user challenges if not set
if (!isset($_SESSION['user']['challenges'])) {
    $_SESSION['user']['challenges'] = [
        'last_reset' => date('Y-m-d'),
        'completed' => [],
        'streak' => 0,
        'last_completed' => null
    ];
}

// Reset challenges if it's a new day
$today = date('Y-m-d');
if ($_SESSION['user']['challenges']['last_reset'] < $today) {
    // Check for streak
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($_SESSION['user']['challenges']['last_completed'] === $yesterday) {
        $_SESSION['user']['challenges']['streak']++;
    } elseif ($_SESSION['user']['challenges']['last_completed'] < $yesterday) {
        $_SESSION['user']['challenges']['streak'] = 0; // Reset streak if missed a day
    }
    
    // Reset daily challenges
    $_SESSION['user']['challenges']['last_reset'] = $today;
    $_SESSION['user']['challenges']['completed'] = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_challenge'])) {
    $challengeId = $_POST['challenge_id'];
    $validation = validateChallengeCompletion($challengeId, $_POST);
    
    if ($validation['success']) {
        // Mark as completed
        $_SESSION['user']['challenges']['completed'][$challengeId] = [
            'completed_at' => date('Y-m-d H:i:s'),
            'data' => $validation['data'] ?? null
        ];
        $_SESSION['user']['challenges']['last_completed'] = $today;
        
        // Add coins and XP
        $rewards = getChallengeRewards($challengeId);
        $_SESSION['user']['coins'] = ($_SESSION['user']['coins'] ?? 0) + $rewards['coins'];
        $_SESSION['user']['xp'] = ($_SESSION['user']['xp'] ?? 0) + $rewards['xp'];
        
        // Check for badge
        $badgeEarned = checkForBadge($_SESSION['user']);
        
        // Set success message
        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Challenge completed! +' . $rewards['coins'] . ' coins and +' . $rewards['xp'] . ' XP earned.' . 
                        ($badgeEarned ? ' New badge unlocked: ' . $badgeEarned . '!' : '')
        ];
        
        // Redirect to prevent form resubmission
        header('Location: challenges.php');
        exit();
    } else {
        $error = $validation['message'] ?? 'Failed to complete challenge. Please try again.';
    }
}

// Get available challenges
$challenges = getDailyChallenges();
$completedCount = count($_SESSION['user']['challenges']['completed']);
$totalChallenges = count($challenges);
$progress = $totalChallenges > 0 ? ($completedCount / $totalChallenges) * 100 : 0;

// Get user's current streak
$streak = $_SESSION['user']['challenges']['streak'] ?? 0;
?>

<div class="challenges-module">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Daily Challenges</li>
                </ol>
            </nav>
            <h2 class="mb-3">Daily Challenges</h2>
            <p class="lead">Complete daily tasks to build better financial habits and earn rewards!</p>
            
            <?php if (isset($_SESSION['flash'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash']['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['flash']['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Streak Counter -->
            <div class="streak-counter mb-4">
                <div class="d-flex align-items-center">
                    <div class="streak-icon me-2">
                        <i class="fas fa-fire text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Current Streak</div>
                        <div class="h4 mb-0">
                            <?php echo $streak; ?> day<?php echo $streak != 1 ? 's' : ''; ?>
                            <?php if ($streak >= 3): ?>
                                <span class="badge bg-warning text-dark ms-2">
                                    <i class="fas fa-bolt"></i> On Fire!
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Progress -->
            <div class="progress mb-4" style="height: 10px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: <?php echo $progress; ?>%" 
                     aria-valuenow="<?php echo $progress; ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
            </div>
            <div class="d-flex justify-content-between mb-4">
                <span class="text-muted">
                    <?php echo $completedCount; ?> of <?php echo $totalChallenges; ?> challenges completed
                </span>
                <span class="fw-bold">
                    <?php echo number_format($progress, 0); ?>%
                </span>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks text-primary me-2"></i>
                        Today's Challenges
                        <span class="badge bg-primary ms-2"><?php echo date('F j, Y'); ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($challenges as $id => $challenge): 
                            $isCompleted = isset($_SESSION['user']['challenges']['completed'][$id]);
                            $cardClass = $isCompleted ? 'completed' : '';
                        ?>
                            <div class="list-group-item <?php echo $cardClass; ?> challenge-card" data-challenge-id="<?php echo $id; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="challenge-checkbox me-3">
                                        <?php if ($isCompleted): ?>
                                            <span class="badge bg-success rounded-circle p-2">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light border rounded-circle p-2">
                                                <i class="far fa-circle text-muted"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo $challenge['title']; ?></h6>
                                        <p class="mb-1 text-muted small"><?php echo $challenge['description']; ?></p>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-warning text-dark me-2">
                                                <i class="fas fa-coins"></i> +<?php echo $challenge['rewards']['coins']; ?> coins
                                            </span>
                                            <span class="badge bg-info text-white">
                                                <i class="fas fa-star"></i> +<?php echo $challenge['rewards']['xp']; ?> XP
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ms-3">
                                        <?php if (!$isCompleted): ?>
                                            <button class="btn btn-sm btn-outline-primary complete-challenge" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#challengeModal"
                                                    data-challenge-id="<?php echo $id; ?>">
                                                Start
                                            </button>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                Completed <i class="fas fa-check ms-1"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Challenge Completion Modal -->
    <div class="modal fade" id="challengeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" id="challengeForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="challengeModalTitle">Complete Challenge</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="challengeModalBody">
                        <!-- Dynamic content will be loaded here -->
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="challenge_id" id="challengeIdInput">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="complete_challenge" class="btn btn-primary">
                            Complete Challenge
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Include JavaScript -->
<script src="assets/js/challenges.js"></script>

<style>
.challenges-module .challenge-card {
    transition: all 0.2s;
    border-left: 4px solid transparent;
}

.challenges-module .challenge-card:hover {
    background-color: #f8f9fa;
}

.challenges-module .challenge-card.completed {
    background-color: #f8f9fa;
    opacity: 0.8;
    border-left-color: #198754;
}

.challenges-module .challenge-card.completed .challenge-checkbox {
    color: #198754;
}

.challenges-module .streak-counter {
    background-color: #fff8e1;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    border: 1px solid #ffe082;
}

.challenges-module .streak-icon {
    font-size: 2rem;
    line-height: 1;
}

.challenges-module .challenge-checkbox {
    font-size: 1.25rem;
    line-height: 1;
}

/* Animation for completed challenges */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.challenge-card {
    animation: fadeIn 0.3s ease-out forwards;
    opacity: 0;
}

/* Add delay for each card */
.challenge-card:nth-child(1) { animation-delay: 0.1s; }
.challenge-card:nth-child(2) { animation-delay: 0.2s; }
.challenge-card:nth-child(3) { animation-delay: 0.3s; }
.challenge-card:nth-child(4) { animation-delay: 0.4s; }
.challenge-card:nth-child(5) { animation-delay: 0.5s; }
</style>

<?php
// Include footer
include 'includes/footer.php';

/**
 * Get daily challenges
 */
function getDailyChallenges() {
    return [
        'save_money' => [
            'title' => 'Save ₹100 Today',
            'description' => 'Set aside ₹100 from your daily spending and add it to your savings.',
            'type' => 'save_money',
            'validation' => [
                'min_amount' => 100,
                'currency' => 'INR'
            ],
            'rewards' => [
                'coins' => 10,
                'xp' => 20
            ]
        ],
        'track_expenses' => [
            'title' => 'Track 3 Expenses',
            'description' => 'Record at least 3 expenses in your expense tracker.',
            'type' => 'track_expenses',
            'validation' => [
                'min_count' => 3
            ],
            'rewards' => [
                'coins' => 15,
                'xp' => 30
            ]
        ],
        'avoid_purchase' => [
            'title' => 'Avoid an Unnecessary Purchase',
            'description' => 'Identify and skip one non-essential purchase today.',
            'type' => 'avoid_purchase',
            'validation' => [
                'description_required' => true
            ],
            'rewards' => [
                'coins' => 20,
                'xp' => 40
            ]
        ],
        'calculate_emi' => [
            'title' => 'Calculate EMI for a Purchase',
            'description' => 'Use the EMI calculator to understand loan costs for a purchase.',
            'type' => 'calculate_emi',
            'validation' => [
                'amount_required' => true,
                'tenure_required' => true,
                'interest_rate_required' => true
            ],
            'rewards' => [
                'coins' => 15,
                'xp' => 30
            ]
        ],
        'emergency_fund' => [
            'title' => 'Plan Your Emergency Fund',
            'description' => 'Calculate and plan your 3-6 month emergency fund target.',
            'type' => 'emergency_fund',
            'validation' => [
                'monthly_expenses_required' => true,
                'target_months_required' => true
            ],
            'rewards' => [
                'coins' => 25,
                'xp' => 50
            ]
        ]
    ];
}

/**
 * Validate challenge completion
 */
function validateChallengeCompletion($challengeId, $postData) {
    $challenges = getDailyChallenges();
    
    if (!isset($challenges[$challengeId])) {
        return ['success' => false, 'message' => 'Invalid challenge.'];
    }
    
    $challenge = $challenges[$challengeId];
    $validation = $challenge['validation'] ?? [];
    $data = [];
    
    // Validate based on challenge type
    switch ($challenge['type']) {
        case 'save_money':
            $amount = filter_var($postData['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
            if ($amount === false || $amount < $validation['min_amount']) {
                return [
                    'success' => false,
                    'message' => sprintf('Please enter an amount of at least %s%d', 
                        $validation['currency'] === 'INR' ? '₹' : '$', 
                        $validation['min_amount']
                    )
                ];
            }
            $data['amount'] = $amount;
            break;
            
        case 'track_expenses':
            $count = filter_var($postData['count'] ?? 0, FILTER_VALIDATE_INT);
            if ($count === false || $count < $validation['min_count']) {
                return [
                    'success' => false,
                    'message' => sprintf('Please track at least %d expenses.', $validation['min_count'])
                ];
            }
            $data['count'] = $count;
            break;
            
        case 'avoid_purchase':
            $description = trim($postData['description'] ?? '');
            if (empty($description)) {
                return [
                    'success' => false,
                    'message' => 'Please describe the purchase you avoided.'
                ];
            }
            $data['description'] = $description;
            break;
            
        case 'calculate_emi':
            $amount = filter_var($postData['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
            $tenure = filter_var($postData['tenure'] ?? 0, FILTER_VALIDATE_INT);
            $interestRate = filter_var($postData['interest_rate'] ?? 0, FILTER_VALIDATE_FLOAT);
            
            if ($amount === false || $amount <= 0) {
                return ['success' => false, 'message' => 'Please enter a valid loan amount.'];
            }
            
            if ($tenure === false || $tenure <= 0) {
                return ['success' => false, 'message' => 'Please enter a valid loan tenure.'];
            }
            
            if ($interestRate === false || $interestRate < 0) {
                return ['success' => false, 'message' => 'Please enter a valid interest rate.'];
            }
            
            $data = [
                'amount' => $amount,
                'tenure' => $tenure,
                'interest_rate' => $interestRate,
                'emi' => calculateEMI($amount, $interestRate, $tenure)
            ];
            break;
            
        case 'emergency_fund':
            $monthlyExpenses = filter_var($postData['monthly_expenses'] ?? 0, FILTER_VALIDATE_FLOAT);
            $targetMonths = filter_var($postData['target_months'] ?? 0, FILTER_VALIDATE_INT);
            
            if ($monthlyExpenses === false || $monthlyExpenses <= 0) {
                return ['success' => false, 'message' => 'Please enter your monthly expenses.'];
            }
            
            if ($targetMonths === false || $targetMonths <= 0) {
                return ['success' => false, 'message' => 'Please enter a target number of months.'];
            }
            
            $data = [
                'monthly_expenses' => $monthlyExpenses,
                'target_months' => $targetMonths,
                'target_amount' => $monthlyExpenses * $targetMonths
            ];
            break;
    }
    
    return ['success' => true, 'data' => $data];
}

/**
 * Calculate EMI
 */
function calculateEMI($principal, $annualRate, $tenureMonths) {
    if ($annualRate == 0) {
        return $principal / $tenureMonths;
    }
    
    $monthlyRate = ($annualRate / 12) / 100;
    $emi = $principal * $monthlyRate * pow(1 + $monthlyRate, $tenureMonths) / (pow(1 + $monthlyRate, $tenureMonths) - 1);
    return round($emi, 2);
}

/**
 * Get challenge rewards
 */
function getChallengeRewards($challengeId) {
    $challenges = getDailyChallenges();
    return $challenges[$challengeId]['rewards'] ?? ['coins' => 5, 'xp' => 10];
}

/**
 * Check if user earned a badge
 */
function checkForBadge($user) {
    $completedCount = count($user['challenges']['completed'] ?? []);
    $streak = $user['challenges']['streak'] ?? 0;
    
    // Check for completion badges
    if ($completedCount >= 10) {
        if (!isset($user['badges']['challenge_master'])) {
            $user['badges']['challenge_master'] = true;
            return 'Challenge Master';
        }
    }
    
    // Check for streak badges
    if ($streak >= 7) {
        if (!isset($user['badges']['weekly_warrior'])) {
            $user['badges']['weekly_warrior'] = true;
            return 'Weekly Warrior';
        }
    }
    
    if ($streak >= 30) {
        if (!isset($user['badges']['monthly_champion'])) {
            $user['badges']['monthly_champion'] = true;
            return 'Monthly Champion';
        }
    }
    
    return null;
}
?>
