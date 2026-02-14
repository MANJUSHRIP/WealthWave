<?php
// spending_analysis.php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user']['name'])) {
    header('Location: dashboard.php');
    exit();
}

$user = $_SESSION['user'];
$page_title = 'Spending Analysis';
$body_class = 'spending-analysis-module';

// Include header
include 'includes/header.php';

// Initialize spending analysis data if not exists
if (!isset($_SESSION['user']['spending_analysis'])) {
    $_SESSION['user']['spending_analysis'] = [
        'history' => [],
        'monthly_income' => 0,
        'categories' => [
            'Housing' => 0,
            'Food' => 0,
            'Transportation' => 0,
            'Utilities' => 0,
            'Entertainment' => 0,
            'Shopping' => 0,
            'Healthcare' => 0,
            'Education' => 0,
            'Savings' => 0,
            'Others' => 0
        ]
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update monthly income
    if (isset($_POST['monthly_income'])) {
        $income = filter_var($_POST['monthly_income'], FILTER_VALIDATE_FLOAT);
        if ($income !== false && $income >= 0) {
            $_SESSION['user']['spending_analysis']['monthly_income'] = $income;
        }
    }
    
    // Update category spending
    foreach ($_POST['categories'] as $category => $amount) {
        $amount = filter_var($amount, FILTER_VALIDATE_FLOAT);
        if ($amount !== false && $amount >= 0 && array_key_exists($category, $_SESSION['user']['spending_analysis']['categories'])) {
            $_SESSION['user']['spending_analysis']['categories'][$category] = $amount;
        }
    }
    
    // Add to history
    $analysis = analyzeSpending($_SESSION['user']['spending_analysis']);
    $analysis['date'] = date('Y-m-d H:i:s');
    array_unshift($_SESSION['user']['spending_analysis']['history'], $analysis);
    
    // Keep only last 6 months of history
    $_SESSION['user']['spending_analysis']['history'] = array_slice(
        $_SESSION['user']['spending_analysis']['history'], 0, 6
    );
    
    // Redirect to prevent form resubmission
    header('Location: spending_analysis.php');
    exit();
}

// Get current analysis
$currentAnalysis = !empty($_SESSION['user']['spending_analysis']['history']) 
    ? $_SESSION['user']['spending_analysis']['history'][0]
    : analyzeSpending($_SESSION['user']['spending_analysis']);

// Get category data for chart
$categories = array_keys($_SESSION['user']['spending_analysis']['categories']);
$amounts = array_values($_SESSION['user']['spending_analysis']['categories']);
$colors = [
    '#4361ee', '#3a0ca3', '#4cc9f0', '#4895ef', 
    '#f72585', '#b5179e', '#7209b7', '#560bad',
    '#480ca8', '#3a0ca3'
];

// Function to analyze spending and calculate financial health score
function analyzeSpending($data) {
    $totalSpending = array_sum($data['categories']);
    $savings = $data['monthly_income'] - $totalSpending;
    $savingsPercentage = $data['monthly_income'] > 0 ? ($savings / $data['monthly_income']) * 100 : 0;
    
    // Get highest spending category
    arsort($data['categories']);
    $highestCategory = key($data['categories']);
    $highestAmount = current($data['categories']);
    
    // Calculate financial health score (0-100)
    $score = 0;
    
    // 1. Savings rate (max 40 points)
    $score += min(40, $savingsPercentage * 0.8); // 50% savings = 40 points
    
    // 2. Essential vs non-essential ratio (max 30 points)
    $essentials = ($data['categories']['Housing'] ?? 0) + 
                 ($data['categories']['Food'] ?? 0) + 
                 ($data['categories']['Transportation'] ?? 0) + 
                 ($data['categories']['Utilities'] ?? 0) + 
                 ($data['categories']['Healthcare'] ?? 0);
    
    $nonEssentials = $totalSpending - $essentials;
    $essentialRatio = $totalSpending > 0 ? ($essentials / $totalSpending) * 100 : 0;
    
    if ($essentialRatio <= 50) $score += 30; // 50% or less on essentials
    elseif ($essentialRatio <= 70) $score += 20; // 50-70% on essentials
    elseif ($essentialRatio <= 90) $score += 10; // 70-90% on essentials
    
    // 3. Debt-to-income ratio (max 20 points)
    $debt = ($data['categories']['Loan'] ?? 0) + ($data['categories']['Credit Card'] ?? 0);
    $debtToIncome = $data['monthly_income'] > 0 ? ($debt / $data['monthly_income']) * 100 : 0;
    
    if ($debtToIncome <= 10) $score += 20;
    elseif ($debtToIncome <= 20) $score += 15;
    elseif ($debtToIncome <= 30) $score += 10;
    elseif ($debtToIncome <= 40) $score += 5;
    
    // 4. Savings consistency (max 10 points)
    $historyCount = count($data['history']);
    if ($historyCount > 0) {
        $consistentMonths = 0;
        $lastSavings = $savings;
        
        for ($i = 1; $i < min(6, $historyCount); $i++) {
            $prevSavings = $data['history'][$i]['savings'] ?? 0;
            if ($lastSavings >= $prevSavings * 0.9) { // Within 10% of last month
                $consistentMonths++;
            }
            $lastSavings = $prevSavings;
        }
        
        $score += min(10, $consistentMonths * 2);
    }
    
    // Ensure score is between 0 and 100
    $score = max(0, min(100, round($score)));
    
    // Determine level
    $level = 'Beginner';
    if ($score >= 90) $level = 'Financial Pro';
    elseif ($score >= 70) $level = 'Smart Saver';
    elseif ($score >= 40) $level = 'Improving';
    
    // Generate improvement tips
    $tips = generateImprovementTips($data, $savingsPercentage, $highestCategory);
    
    // Calculate suggested monthly saving amount (aim for 20% of income)
    $suggestedSavings = $data['monthly_income'] * 0.2;
    $savingsGap = $suggestedSavings - $savings;
    
    return [
        'score' => $score,
        'level' => $level,
        'total_spending' => $totalSpending,
        'savings' => $savings,
        'savings_percentage' => $savingsPercentage,
        'highest_category' => $highestCategory,
        'highest_amount' => $highestAmount,
        'tips' => $tips,
        'suggested_savings' => $suggestedSavings,
        'savings_gap' => $savingsGap > 0 ? $savingsGap : 0,
        'essential_ratio' => $essentialRatio
    ];
}

// Function to generate improvement tips
function generateImprovementTips($data, $savingsPercentage, $highestCategory) {
    $tips = [];
    $totalSpending = array_sum($data['categories']);
    $savings = $data['monthly_income'] - $totalSpending;
    
    // Savings rate tip
    if ($savingsPercentage < 20) {
        $target = $data['monthly_income'] * 0.2;
        $needed = $target - $savings;
        if ($needed > 0) {
            $tips[] = "Aim to save at least 20% of your income. You need to save ₹" . 
                     number_format($needed) . " more per month to reach this goal.";
        }
    } else {
        $tips[] = "Great job! You're saving " . number_format($savingsPercentage, 1) . 
                 "% of your income, which is above the recommended 20%.";
    }
    
    // Highest spending category tip
    $highestSpend = $data['categories'][$highestCategory] ?? 0;
    $highestPercentage = $totalSpending > 0 ? ($highestSpend / $totalSpending) * 100 : 0;
    
    if ($highestPercentage > 30) {
        $tips[] = "Your highest spending category is <strong>$highestCategory</strong> at " . 
                 number_format($highestPercentage, 1) . "% of your total spending. " .
                 "Consider ways to reduce this expense.";
    }
    
    // Essential vs non-essential tip
    $essentials = ($data['categories']['Housing'] ?? 0) + 
                 ($data['categories']['Food'] ?? 0) + 
                 ($data['categories']['Transportation'] ?? 0) + 
                 ($data['categories']['Utilities'] ?? 0) + 
                 ($data['categories']['Healthcare'] ?? 0);
    $nonEssentials = $totalSpending - $essentials;
    $essentialRatio = $totalSpending > 0 ? ($essentials / $totalSpending) * 100 : 0;
    
    if ($essentialRatio > 70) {
        $tips[] = "You're spending " . number_format($essentialRatio, 1) . 
                 "% of your income on essential expenses. " .
                 "Look for ways to reduce fixed costs like housing or transportation.";
    } elseif ($nonEssentials > ($data['monthly_income'] * 0.3)) {
        $tips[] = "You're spending " . number_format(($nonEssentials / $data['monthly_income']) * 100, 1) . 
                 "% of your income on non-essentials. " .
                 "Consider reducing discretionary spending to increase your savings.";
    }
    
    // Debt tip
    $debt = ($data['categories']['Loan'] ?? 0) + ($data['categories']['Credit Card'] ?? 0);
    if ($debt > 0) {
        $debtToIncome = $data['monthly_income'] > 0 ? ($debt / $data['monthly_income']) * 100 : 0;
        if ($debtToIncome > 20) {
            $tips[] = "Your debt payments are " . number_format($debtToIncome, 1) . 
                     "% of your income. Aim to keep this below 20% for better financial health.";
        }
    }
    
    // If no specific tips, give a general one
    if (empty($tips)) {
        $tips[] = "Your spending looks balanced! Consider increasing your savings rate or " .
                 "investing for long-term goals.";
    }
    
    return $tips;
}
?>

<div class="spending-analysis-module">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Spending Analysis</li>
                </ol>
            </nav>
            <h2 class="mb-3">Spending Analysis</h2>
            <p class="lead">Track your expenses, analyze your spending habits, and improve your financial health.</p>
        </div>
    </div>

    <div class="row">
        <!-- Financial Health Score -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <h5 class="card-title">Financial Health Score</h5>
                    <div class="score-circle mx-auto my-4" id="scoreCircle" 
                         data-score="<?php echo $currentAnalysis['score']; ?>">
                        <div class="score-value"><?php echo $currentAnalysis['score']; ?></div>
                        <div class="score-label">/100</div>
                    </div>
                    <h4 class="mb-3"><?php echo $currentAnalysis['level']; ?></h4>
                    
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $currentAnalysis['score']; ?>%" 
                             aria-valuenow="<?php echo $currentAnalysis['score']; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100"></div>
                    </div>
                    
                    <div class="d-flex justify-content-between small text-muted mb-3">
                        <span>0 - Beginner</span>
                        <span>100 - Financial Pro</span>
                    </div>
                    
                    <div class="alert alert-light">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <?php 
                        $nextLevel = '';
                        if ($currentAnalysis['score'] < 40) $nextLevel = 'Improving (40+)';
                        elseif ($currentAnalysis['score'] < 70) $nextLevel = 'Smart Saver (70+)';
                        elseif ($currentAnalysis['score'] < 90) $nextLevel = 'Financial Pro (90+)';
                        
                        if ($nextLevel) {
                            echo "Keep going! Reach <strong>$nextLevel</strong> by improving your financial habits.";
                        } else {
                            echo "Congratulations! You've reached the highest level. Keep up the great work!";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Quick Stats</h5>
                    
                    <div class="stat-item d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <div class="text-muted small">Monthly Income</div>
                            <div class="h5 mb-0">₹<?php echo number_format($_SESSION['user']['spending_analysis']['monthly_income']); ?></div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Total Spending</div>
                            <div class="h5 mb-0">₹<?php echo number_format($currentAnalysis['total_spending']); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-item d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                        <div>
                            <div class="text-muted small">Monthly Savings</div>
                            <div class="h5 mb-0 <?php echo $currentAnalysis['savings'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                ₹<?php echo number_format(abs($currentAnalysis['savings'])); ?>
                                <?php if ($currentAnalysis['savings'] < 0): ?>
                                    <small class="text-danger">(Deficit)</small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted small">Savings Rate</div>
                            <div class="h5 mb-0 <?php echo $currentAnalysis['savings_percentage'] >= 20 ? 'text-success' : 'text-warning'; ?>">
                                <?php echo number_format($currentAnalysis['savings_percentage'], 1); ?>%
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="text-muted small mb-1">Highest Spending Category</div>
                        <div class="d-flex align-items-center">
                            <div class="category-badge me-2" style="background-color: #4361ee;"></div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-bold"><?php echo $currentAnalysis['highest_category']; ?></span>
                                    <span>₹<?php echo number_format($currentAnalysis['highest_amount']); ?></span>
                                </div>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo ($currentAnalysis['highest_amount'] / $currentAnalysis['total_spending']) * 100; ?>%;" 
                                         aria-valuenow="<?php echo $currentAnalysis['highest_amount']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="<?php echo $currentAnalysis['total_spending']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Spending Analysis -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">Spending by Category</h5>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSpendingModal">
                            <i class="fas fa-edit me-1"></i> Edit
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-7">
                            <canvas id="spendingChart" height="250"></canvas>
                        </div>
                        <div class="col-md-5">
                            <div class="spending-legend" id="spendingLegend"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Improvement Tips -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Improvement Tips</h5>
                    
                    <?php if (!empty($currentAnalysis['tips'])): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($currentAnalysis['tips'] as $tip): ?>
                                <div class="list-group-item border-0 px-0 py-2">
                                    <div class="d-flex">
                                        <div class="flex-shrink-0 mt-1">
                                            <i class="fas fa-lightbulb text-warning"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <?php echo $tip; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Your spending looks great! Keep up the good work with your financial habits.
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($currentAnalysis['savings_gap'] > 0): ?>
                        <div class="alert alert-info mt-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <i class="fas fa-bullseye fa-2x text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="alert-heading">Savings Goal</h6>
                                    <p class="mb-0">
                                        To reach the recommended 20% savings rate, try to save an additional 
                                        <strong>₹<?php echo number_format($currentAnalysis['savings_gap']); ?></strong> per month.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Spending History -->
            <?php if (!empty($_SESSION['user']['spending_analysis']['history'])): ?>
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Score History</h5>
                    <canvas id="scoreHistoryChart" height="100"></canvas>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Spending Modal -->
<div class="modal fade" id="editSpendingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Monthly Budget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label for="monthly_income" class="form-label">Monthly Income (₹)</label>
                        <input type="number" class="form-control form-control-lg" id="monthly_income" name="monthly_income" 
                               value="<?php echo $_SESSION['user']['spending_analysis']['monthly_income']; ?>" required>
                    </div>
                    
                    <h6 class="mb-3">Monthly Spending by Category</h6>
                    <div class="row g-3">
                        <?php foreach ($_SESSION['user']['spending_analysis']['categories'] as $category => $amount): ?>
                            <div class="col-md-6">
                                <label for="cat_<?php echo strtolower($category); ?>" class="form-label small text-muted"><?php echo $category; ?></label>
                                <div class="input-group mb-3">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="cat_<?php echo strtolower($category); ?>" 
                                           name="categories[<?php echo $category; ?>]" value="<?php echo $amount; ?>" min="0" step="0.01">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize score circle animation
    const scoreCircle = document.getElementById('scoreCircle');
    const score = parseInt(scoreCircle.dataset.score);
    const circle = new ProgressBar.Circle(scoreCircle, {
        color: getScoreColor(score),
        strokeWidth: 10,
        trailWidth: 8,
        trailColor: '#eee',
        duration: 1500,
        easing: 'easeInOut',
        text: {
            value: '0'
        },
        step: function(state, circle) {
            circle.setText(Math.round(circle.value() * score));
        }
    });
    
    circle.animate(1.0);
    
    // Initialize spending chart
    const ctx = document.getElementById('spendingChart').getContext('2d');
    const spendingChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($categories); ?>,
            datasets: [{
                data: <?php echo json_encode($amounts); ?>,
                backgroundColor: <?php echo json_encode($colors); ?>,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ₹${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Initialize score history chart if exists
    const scoreHistoryCtx = document.getElementById('scoreHistoryChart');
    if (scoreHistoryCtx) {
        const history = <?php echo json_encode(array_slice($_SESSION['user']['spending_analysis']['history'], 0, 6)); ?>;
        const labels = history.map((_, index) => {
            const date = new Date();
            date.setMonth(date.getMonth() - index);
            return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
        }).reverse();
        
        const scores = history.map(entry => entry.score).reverse();
        
        new Chart(scoreHistoryCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Financial Health Score',
                    data: scores,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 2,
                    pointBackgroundColor: '#4361ee',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Function to get color based on score
    function getScoreColor(score) {
        if (score >= 90) return '#28a745'; // Green
        if (score >= 70) return '#17a2b8'; // Teal
        if (score >= 40) return '#ffc107'; // Yellow
        return '#dc3545'; // Red
    }
    
    // Update score circle color when score changes
    function updateScoreCircle(score) {
        const circlePath = scoreCircle.querySelector('path:last-child');
        if (circlePath) {
            circlePath.style.stroke = getScoreColor(score);
        }
    }
    
    // Initial update
    updateScoreCircle(score);
});

// Format currency inputs
document.querySelectorAll('input[type="number"]').forEach(input => {
    input.addEventListener('change', function() {
        if (this.value && !isNaN(this.value)) {
            this.value = parseFloat(this.value).toFixed(2);
        }
    });
});
</script>

<style>
/* Score circle */
.score-circle {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background: #f8f9fa;
    position: relative;
    margin: 0 auto;
}

.score-value {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
}

.score-label {
    font-size: 1rem;
    color: #6c757d;
    margin-top: -5px;
}

/* Category badges */
.category-badge {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

/* Spending legend */
.spending-legend {
    padding: 1rem 0;
}

.legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.2s;
}

.legend-item:hover {
    background-color: #f8f9fa;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
    margin-right: 0.75rem;
}

.legend-label {
    flex-grow: 1;
    font-size: 0.875rem;
}

.legend-amount {
    font-weight: 500;
    font-size: 0.875rem;
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
    .score-circle {
        width: 120px;
        height: 120px;
    }
    
    .score-value {
        font-size: 2.25rem;
    }
    
    .spending-legend {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #eee;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>