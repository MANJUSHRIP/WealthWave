<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user']['name'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_budget'])) {
    // Get form data
    $income = filter_input(INPUT_POST, 'income', FILTER_VALIDATE_FLOAT);
    $rent = filter_input(INPUT_POST, 'rent', FILTER_VALIDATE_FLOAT) ?: 0;
    $food = filter_input(INPUT_POST, 'food', FILTER_VALIDATE_FLOAT) ?: 0;
    $travel = filter_input(INPUT_POST, 'travel', FILTER_VALIDATE_FLOAT) ?: 0;
    $entertainment = filter_input(INPUT_POST, 'entertainment', FILTER_VALIDATE_FLOAT) ?: 0;
    $other = filter_input(INPUT_POST, 'other', FILTER_VALIDATE_FLOAT) ?: 0;
    $savings_goal = filter_input(INPUT_POST, 'savings_goal', FILTER_VALIDATE_FLOAT) ?: 0;

    // Calculate totals
    $total_expenses = $rent + $food + $travel + $entertainment + $other;
    $savings = $income - $total_expenses;
    $savings_percentage = $income > 0 ? ($savings / $income) * 100 : 0;

    // Update user data
    $_SESSION['user']['income'] = $income;
    $_SESSION['user']['expenses'] = [
        'rent' => $rent,
        'food' => $food,
        'travel' => $travel,
        'entertainment' => $entertainment,
        'other' => $other
    ];
    $_SESSION['user']['savings_goal'] = $savings_goal;
    $_SESSION['user']['savings'] = $savings;
    $_SESSION['user']['savings_percentage'] = $savings_percentage;

    // Check if this is the first time completing the budget
    if (!in_array('budget', $_SESSION['user']['completed_modules'] ?? [])) {
        $_SESSION['user']['completed_modules'][] = 'budget';
        $_SESSION['user']['xp'] = ($_SESSION['user']['xp'] ?? 0) + 10;
        $show_congrats = true;
    }

    // Update financial health score
    updateFinancialHealthScore();

    // Redirect to avoid form resubmission
    header('Location: budget.php?success=1');
    exit();
}

// Function to update financial health score
function updateFinancialHealthScore() {
    $user = &$_SESSION['user'];
    $score = 0;

    // Base score components (0-100)
    $savings_ratio = $user['savings'] / ($user['income'] ?: 1);
    
    // Savings component (up to 40 points)
    if ($savings_ratio >= 0.2) {
        $score += 40; // Saving 20% or more
    } else {
        $score += ($savings_ratio / 0.2) * 40; // Partial points for partial savings
    }

    // Expense distribution (up to 30 points)
    $needs = $user['expenses']['rent'] + $user['expenses']['food'] + $user['expenses']['travel'];
    $wants = $user['expenses']['entertainment'] + $user['expenses']['other'];
    
    $needs_ratio = $needs / ($user['income'] ?: 1);
    $wants_ratio = $wants / ($user['income'] ?: 1);
    
    // Ideal: needs <= 50%, wants <= 30%, savings >= 20%
    $needs_score = max(0, 30 - (max(0, $needs_ratio - 0.5) * 60) * 30);
    $wants_score = max(0, 30 - (max(0, $wants_ratio - 0.3) * 100) * 30);
    
    $score += $needs_score * 0.5; // Needs are more important
    $score += $wants_score * 0.5; // Wants are less critical

    // Emergency fund (up to 20 points)
    $emergency_fund_months = $user['savings'] > 0 ? $user['savings'] / ($needs / 3) : 0; // 3 months of needs
    $emergency_score = min(20, ($emergency_fund_months / 6) * 20); // 6 months is ideal
    $score += $emergency_score;

    // Budget adherence (up to 10 points) - for future use
    $score += 10;

    // Cap at 100
    $score = min(100, max(0, round($score)));

    // Update user's health score
    $user['health_score'] = $score;

    // Determine financial health status
    if ($score >= 90) {
        $user['financial_status'] = 'Excellent';
    } elseif ($score >= 70) {
        $user['financial_status'] = 'Good';
    } elseif ($score >= 50) {
        $user['financial_status'] = 'Fair';
    } else {
        $user['financial_status'] = 'Needs Improvement';
    }

    return $score;
}

// Get form values or defaults
$income = $user['income'] ?? 0;
$rent = $user['expenses']['rent'] ?? 0;
$food = $user['expenses']['food'] ?? 0;
$travel = $user['expenses']['travel'] ?? 0;
$entertainment = $user['expenses']['entertainment'] ?? 0;
$other = $user['expenses']['other'] ?? 0;
$savings_goal = $user['savings_goal'] ?? 0;

// Calculate totals
$total_expenses = $rent + $food + $travel + $entertainment + $other;
$savings = $income - $total_expenses;
$savings_percentage = $income > 0 ? ($savings / $income) * 100 : 0;

// Format currency for display
function format_currency($amount) {
    return '₹' . number_format($amount, 0, '.', ',');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget Planner - FinWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="budget-page">
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Budget Planner</li>
                    </ol>
                </nav>
                <h2 class="mb-3">Budget Planner</h2>
                <p class="text-muted">Plan and track your monthly budget to achieve your financial goals.</p>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Your budget has been updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Budget Form -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calculator me-2 text-primary"></i> Monthly Budget</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="budget-form">
                            <!-- Income -->
                            <div class="mb-4">
                                <label for="income" class="form-label fw-bold">Monthly Income (₹)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control form-control-lg" id="income" name="income" 
                                           value="<?php echo htmlspecialchars($income); ?>" required min="0" step="100">
                                </div>
                                <div class="form-text">Your total take-home pay after taxes and deductions.</div>
                            </div>

                            <!-- Expenses -->
                            <h5 class="mb-3">Monthly Expenses</h5>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="rent" class="form-label">Rent/Mortgage</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="rent" name="rent" 
                                               value="<?php echo htmlspecialchars($rent); ?>" min="0" step="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="food" class="form-label">Food & Groceries</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="food" name="food" 
                                               value="<?php echo htmlspecialchars($food); ?>" min="0" step="100">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="travel" class="form-label">Transportation</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="travel" name="travel" 
                                               value="<?php echo htmlspecialchars($travel); ?>" min="0" step="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="entertainment" class="form-label">Entertainment</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="entertainment" name="entertainment" 
                                               value="<?php echo htmlspecialchars($entertainment); ?>" min="0" step="100">
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="other" class="form-label">Other Expenses</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="other" name="other" 
                                               value="<?php echo htmlspecialchars($other); ?>" min="0" step="100">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="savings_goal" class="form-label">Monthly Savings Goal</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="savings_goal" name="savings_goal" 
                                               value="<?php echo htmlspecialchars($savings_goal); ?>" min="0" step="100">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="update_budget" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i> Update Budget
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Budget Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i> Budget Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <canvas id="budgetChart" height="200"></canvas>
                        </div>
                        
                        <div class="budget-summary">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Income:</span>
                                <strong><?php echo format_currency($income); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Expenses:</span>
                                <strong><?php echo format_currency($total_expenses); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Savings:</span>
                                <strong class="<?php echo $savings >= 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo format_currency($savings); ?> (<?php echo number_format($savings_percentage, 1); ?>%)
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Savings Goal:</span>
                                <strong><?php echo format_currency($savings_goal); ?></strong>
                            </div>
                            <div class="progress mb-3" style="height: 10px;">
                                <?php
                                $savings_progress = $savings_goal > 0 ? min(100, ($savings / $savings_goal) * 100) : 0;
                                $progress_class = $savings_progress >= 100 ? 'bg-success' : 'bg-primary';
                                ?>
                                <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" 
                                     style="width: <?php echo $savings_progress; ?>%" 
                                     aria-valuenow="<?php echo $savings_progress; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100"></div>
                            </div>
                            
                            <?php if ($savings_goal > 0): ?>
                                <?php if ($savings >= $savings_goal): ?>
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-check-circle me-2"></i>
                                        Great job! You've reached your savings goal this month.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        You're <?php echo format_currency($savings_goal - $savings); ?> away from your savings goal.
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 50/30/20 Rule -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-percentage me-2 text-primary"></i> 50/30/20 Rule</h5>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">A simple rule of thumb for budgeting:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span><span class="badge bg-primary me-2">50%</span> Needs</span>
                                    <span><?php echo format_currency($income * 0.5); ?></span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <?php
                                    $needs = $rent + $food + $travel; // Basic needs
                                    $needs_percentage = $income > 0 ? ($needs / $income) * 100 : 0;
                                    $needs_class = $needs_percentage > 50 ? 'bg-danger' : 'bg-primary';
                                    ?>
                                    <div class="progress-bar <?php echo $needs_class; ?>" role="progressbar" 
                                         style="width: <?php echo min(100, $needs_percentage); ?>%" 
                                         aria-valuenow="<?php echo $needs_percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted">Housing, food, transportation, utilities</small>
                            </li>
                            <li class="mb-2">
                                <div class="d-flex justify-content-between">
                                    <span><span class="badge bg-warning me-2">30%</span> Wants</span>
                                    <span><?php echo format_currency($income * 0.3); ?></span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <?php
                                    $wants = $entertainment + $other; // Discretionary spending
                                    $wants_percentage = $income > 0 ? ($wants / $income) * 100 : 0;
                                    $wants_class = $wants_percentage > 30 ? 'bg-danger' : 'bg-warning';
                                    ?>
                                    <div class="progress-bar <?php echo $wants_class; ?>" role="progressbar" 
                                         style="width: <?php echo min(100, $wants_percentage); ?>%" 
                                         aria-valuenow="<?php echo $wants_percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted">Dining out, entertainment, shopping</small>
                            </li>
                            <li>
                                <div class="d-flex justify-content-between">
                                    <span><span class="badge bg-success me-2">20%</span> Savings</span>
                                    <span><?php echo format_currency($income * 0.2); ?></span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <?php
                                    $savings_percentage = $income > 0 ? ($savings / $income) * 100 : 0;
                                    $savings_class = $savings_percentage >= 20 ? 'bg-success' : 'bg-danger';
                                    ?>
                                    <div class="progress-bar <?php echo $savings_class; ?>" role="progressbar" 
                                         style="width: <?php echo min(100, $savings_percentage); ?>%" 
                                         aria-valuenow="<?php echo $savings_percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted">Emergency fund, investments, debt repayment</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Analysis -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2 text-primary"></i> Budget Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Expense Breakdown</h6>
                                <canvas id="expenseChart" height="250"></canvas>
                            </div>
                            <div class="col-md-6">
                                <h6>Budget Recommendations</h6>
                                <?php
                                // Generate recommendations based on budget
                                $recommendations = [];
                                
                                // Check savings rate
                                if ($savings_percentage < 20) {
                                    $recommendations[] = [
                                        'icon' => 'piggy-bank',
                                        'title' => 'Increase Savings',
                                        'message' => 'Aim to save at least 20% of your income. Consider reducing discretionary spending to reach this goal.'
                                    ];
                                }
                                
                                // Check if needs exceed 50%
                                if ($needs_percentage > 50) {
                                    $recommendations[] = [
                                        'icon' => 'home',
                                        'title' => 'Reduce Essential Expenses',
                                        'message' => "Your needs (${needs_percentage}%) exceed the recommended 50%. Look for ways to reduce housing, food, or transportation costs."
                                    ];
                                }
                                
                                // Check if wants exceed 30%
                                if ($wants_percentage > 30) {
                                    $recommendations[] = [
                                        'icon' => 'shopping-bag',
                                        'title' => 'Limit Discretionary Spending',
                                        'message' => "Your wants (${wants_percentage}%) exceed the recommended 30%. Consider cutting back on non-essential expenses."
                                    ];
                                }
                                
                                // Check for high spending categories
                                $categories = [
                                    'rent' => 'Housing',
                                    'food' => 'Food',
                                    'travel' => 'Transportation',
                                    'entertainment' => 'Entertainment',
                                    'other' => 'Other'
                                ];
                                
                                foreach ($categories as $key => $label) {
                                    $category_percentage = $income > 0 ? (${$key} / $income) * 100 : 0;
                                    if ($category_percentage > 30 && $key !== 'rent') {
                                        $recommendations[] = [
                                            'icon' => 'exclamation-triangle',
                                            'title' => "High ${label} Spending",
                                            'message' => "Your ${label} spending (${category_percentage}% of income) is quite high. Look for ways to reduce this category."
                                        ];
                                    }
                                }
                                
                                // If no specific recommendations, show positive feedback
                                if (empty($recommendations)) {
                                    echo '<div class="alert alert-success">';
                                    echo '<i class="fas fa-check-circle me-2"></i> Your budget looks well-balanced! Keep up the good work!';
                                    echo '</div>';
                                } else {
                                    // Show up to 3 most important recommendations
                                    $displayed_recommendations = array_slice($recommendations, 0, 3);
                                    foreach ($displayed_recommendations as $rec) {
                                        echo '<div class="alert alert-light d-flex align-items-start mb-3">';
                                        echo '<i class="fas fa-' . $rec['icon'] . ' mt-1 me-3 text-primary"></i>';
                                        echo '<div>';
                                        echo '<h6 class="alert-heading mb-1">' . $rec['title'] . '</h6>';
                                        echo '<p class="mb-0 small">' . $rec['message'] . '</p>';
                                        echo '</div></div>';
                                    }
                                }
                                ?>
                                
                                <div class="mt-4">
                                    <h6>Quick Tips</h6>
                                    <ul class="list-unstyled">
                                        <li class="mb-2"><i class="fas fa-lightbulb text-warning me-2"></i> Track daily expenses to identify spending patterns</li>
                                        <li class="mb-2"><i class="fas fa-lightbulb text-warning me-2"></i> Set up automatic transfers to savings on payday</li>
                                        <li class="mb-2"><i class="fas fa-lightbulb text-warning me-2"></i> Review and cancel unused subscriptions</li>
                                        <li class="mb-2"><i class="fas fa-lightbulb text-warning me-2"></i> Use the 24-hour rule for non-essential purchases</li>
                                        <li><i class="fas fa-lightbulb text-warning me-2"></i> Plan meals to reduce food expenses</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Budget Chart
            const budgetCtx = document.getElementById('budgetChart').getContext('2d');
            const budgetChart = new Chart(budgetCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Needs', 'Wants', 'Savings', 'Remaining'],
                    datasets: [{
                        data: [
                            <?php echo $rent + $food + $travel; ?>,
                            <?php echo $entertainment + $other; ?>,
                            <?php echo max(0, $savings); ?>,
                            <?php echo max(0, $income - $total_expenses - max(0, $savings)); ?>
                        ],
                        backgroundColor: [
                            '#4361ee',
                            '#f72585',
                            '#4cc9f0',
                            '#e9ecef'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
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

            // Expense Chart
            const expenseCtx = document.getElementById('expenseChart').getContext('2d');
            const expenseChart = new Chart(expenseCtx, {
                type: 'bar',
                data: {
                    labels: ['Rent', 'Food', 'Transport', 'Entertainment', 'Other'],
                    datasets: [{
                        label: 'Amount (₹)',
                        data: [
                            <?php echo $rent; ?>,
                            <?php echo $food; ?>,
                            <?php echo $travel; ?>,
                            <?php echo $entertainment; ?>,
                            <?php echo $other; ?>
                        ],
                        backgroundColor: [
                            '#4361ee',
                            '#4cc9f0',
                            '#7209b7',
                            '#f72585',
                            '#f8961e'
                        ],
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `₹${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₹' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Auto-calculate totals
            const incomeInput = document.getElementById('income');
            const expenseInputs = document.querySelectorAll('input[name=\"rent\"], input[name=\"food\"], input[name=\"travel\"], input[name=\"entertainment\"], input[name=\"other\"]');
            
            function calculateTotals() {
                let totalExpenses = 0;
                expenseInputs.forEach(input => {
                    totalExpenses += parseFloat(input.value) || 0;
                });
                
                const income = parseFloat(incomeInput.value) || 0;
                const savings = income - totalExpenses;
                const savingsPercentage = income > 0 ? (savings / income) * 100 : 0;
                
                // Update summary
                document.getElementById('total-expenses').textContent = '₹' + Math.round(totalExpenses).toLocaleString();
                document.getElementById('total-savings').textContent = '₹' + Math.round(savings).toLocaleString();
                document.getElementById('savings-percentage').textContent = savingsPercentage.toFixed(1) + '%';
                
                // Update progress bar
                const savingsGoal = parseFloat(document.getElementById('savings_goal').value) || 0;
                let savingsProgress = 0;
                if (savingsGoal > 0) {
                    savingsProgress = Math.min(100, (savings / savingsGoal) * 100);
                }
                document.querySelector('.progress-bar').style.width = savingsProgress + '%';
                document.querySelector('.progress-bar').setAttribute('aria-valuenow', savingsProgress);
                
                // Update chart data
                updateChartData();
            }
            
            function updateChartData() {
                const rent = parseFloat(document.getElementById('rent').value) || 0;
                const food = parseFloat(document.getElementById('food').value) || 0;
                const travel = parseFloat(document.getElementById('travel').value) || 0;
                const entertainment = parseFloat(document.getElementById('entertainment').value) || 0;
                const other = parseFloat(document.getElementById('other').value) || 0;
                const income = parseFloat(incomeInput.value) || 0;
                const totalExpenses = rent + food + travel + entertainment + other;
                const savings = income - totalExpenses;
                
                // Update budget chart
                budgetChart.data.datasets[0].data = [
                    rent + food + travel,
                    entertainment + other,
                    Math.max(0, savings),
                    Math.max(0, income - totalExpenses - Math.max(0, savings))
                ];
                budgetChart.update();
                
                // Update expense chart
                expenseChart.data.datasets[0].data = [rent, food, travel, entertainment, other];
                expenseChart.update();
            }
            
            // Add event listeners
            incomeInput.addEventListener('input', calculateTotals);
            expenseInputs.forEach(input => {
                input.addEventListener('input', calculateTotals);
            });
            document.getElementById('savings_goal').addEventListener('input', calculateTotals);
            
            // Initial calculation
            calculateTotals();
            
            // Show tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>