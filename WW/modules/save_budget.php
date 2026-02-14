<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$income = filter_input(INPUT_POST, 'income', FILTER_VALIDATE_FLOAT);
$food = filter_input(INPUT_POST, 'food', FILTER_VALIDATE_FLOAT);
$rent = filter_input(INPUT_POST, 'rent', FILTER_VALIDATE_FLOAT);
$travel = filter_input(INPUT_POST, 'travel', FILTER_VALIDATE_FLOAT);
$entertainment = filter_input(INPUT_POST, 'entertainment', FILTER_VALIDATE_FLOAT);

// Validate inputs
if ($income === false || $food === false || $rent === false || $travel === false || $entertainment === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Save budget
    $stmt = $pdo->prepare("
        INSERT INTO budgets (user_id, monthly_income, food, rent, travel, entertainment)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            monthly_income = VALUES(monthly_income),
            food = VALUES(food),
            rent = VALUES(rent),
            travel = VALUES(travel),
            entertainment = VALUES(entertainment),
            updated_at = NOW()
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $income,
        $food,
        $rent,
        $travel,
        $entertainment
    ]);
    
    // Check if this is the first time saving budget
    $isFirstSave = ($pdo->lastInsertId() > 0);
    
    // Add points for first time saving budget
    $pointsAdded = 0;
    if ($isFirstSave) {
        $pointsAdded = 5;
        addPoints($pdo, $_SESSION['user_id'], $pointsAdded);
    }
    
    // Calculate savings rate
    $totalExpenses = $food + $rent + $travel + $entertainment;
    $savings = $income - $totalExpenses;
    $savingsRate = ($income > 0) ? ($savings / $income) * 100 : 0;
    
    // Get user's current points and level for response
    $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Commit transaction
    $pdo->commit();
    
    // Prepare response
    $response = [
        'success' => true,
        'pointsAdded' => $pointsAdded,
        'newPoints' => $user['points'] + $pointsAdded,
        'savingsRate' => round($savingsRate, 2),
        'isHealthy' => $savingsRate >= 20,
        'message' => $savingsRate >= 20 ?
            '✅ Great job! You\'re saving ' . round($savingsRate, 1) . '% of your income.' :
            '⚠ You\'re saving ' . round($savingsRate, 1) . '% of your income. Try to save at least 20%.',
        'advice' => $savingsRate < 20 ?
            'Tip: According to the 50-30-20 rule, aim to save at least 20% of your income.' :
            'Great! You\'re following the 50-30-20 rule by saving at least 20% of your income.'
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error saving budget: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while saving your budget.'
    ]);
}
<!-- modules/budget.php -->
<div class="budget-module">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Budget Planner</h2>
        <button class="btn btn-outline-secondary" onclick="backToDashboard()">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </button>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-wallet me-2"></i> Monthly Budget Planner
                </div>
                <div class="card-body">
                    <form id="budget-form">
                        <div class="mb-4">
                            <h5>Monthly Income</h5>
                            <div class="input-group mb-3">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" name="income" 
                                       placeholder="Enter your monthly income" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-3">Monthly Expenses</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Food & Groceries</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="food" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Transportation</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="transport" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Housing (Rent/EMI)</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="housing" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Utilities</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="utilities" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Entertainment</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="entertainment" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Other Expenses</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" name="other" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-calculator me-2"></i> Calculate Budget
                            </button>
                        </div>
                    </form>

                    <div id="budget-results" class="mt-4" style="display: none;">
                        <!-- Results will be shown here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('budget-form');
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        try {
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Calculating...';
            
            // Simulate API call (replace with actual fetch to save_budget.php)
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Calculate totals
            let totalExpenses = 0;
            let income = parseFloat(formData.get('income')) || 0;
            
            // Sum all expenses
            for (let [key, value] of formData.entries()) {
                if (key !== 'income') {
                    totalExpenses += parseFloat(value) || 0;
                }
            }
            
            const savings = income - totalExpenses;
            const savingsRate = income > 0 ? ((savings / income) * 100).toFixed(1) : 0;
            
            // Show results
            showBudgetResults({
                income: income,
                totalExpenses: totalExpenses,
                savings: savings,
                savingsRate: savingsRate,
                success: true
            });
            
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
});

function showBudgetResults(data) {
    const resultsDiv = document.getElementById('budget-results');
    resultsDiv.style.display = 'block';
    
    // Create chart data
    const expenseData = {
        labels: ['Food', 'Transport', 'Housing', 'Utilities', 'Entertainment', 'Other'],
        datasets: [{
            data: [
                parseFloat(document.querySelector('input[name="food"]').value) || 0,
                parseFloat(document.querySelector('input[name="transport"]').value) || 0,
                parseFloat(document.querySelector('input[name="housing"]').value) || 0,
                parseFloat(document.querySelector('input[name="utilities"]').value) || 0,
                parseFloat(document.querySelector('input[name="entertainment"]').value) || 0,
                parseFloat(document.querySelector('input[name="other"]').value) || 0
            ],
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
            ]
        }]
    };
    
    // Destroy previous chart if exists
    if (window.budgetChart) {
        window.budgetChart.destroy();
    }
    
    // Create chart
    const ctx = document.createElement('canvas');
    resultsDiv.innerHTML = '';
    resultsDiv.appendChild(ctx);
    
    window.budgetChart = new Chart(ctx, {
        type: 'pie',
        data: expenseData,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                },
                title: {
                    display: true,
                    text: 'Expense Distribution'
                }
            }
        }
    });
    
    // Add summary
    const summary = document.createElement('div');
    summary.className = 'mt-4';
    summary.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3 ${data.savings >= 0 ? 'border-success' : 'border-danger'}">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Income:</span>
                            <strong>₹${data.income.toLocaleString()}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Expenses:</span>
                            <strong>₹${data.totalExpenses.toLocaleString()}</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Monthly Savings:</span>
                            <span class="${data.savings >= 0 ? 'text-success' : 'text-danger'}">
                                ₹${Math.abs(data.savings).toLocaleString()}
                                ${data.savings < 0 ? '(Deficit)' : ''}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100 ${data.savingsRate >= 20 ? 'border-success' : 'border-warning'}">
                    <div class="card-body">
                        <h5 class="card-title">Savings Rate</h5>
                        <div class="text-center my-4">
                            <div class="display-3 fw-bold">${data.savingsRate}%</div>
                            <div class="progress mt-3" style="height: 20px;">
                                <div class="progress-bar ${data.savingsRate >= 20 ? 'bg-success' : 'bg-warning'}" 
                                     role="progressbar" 
                                     style="width: ${Math.min(100, data.savingsRate)}%" 
                                     aria-valuenow="${data.savingsRate}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                        </div>
                        <p class="card-text text-center">
                            ${data.savingsRate >= 20 ? 
                                '✅ Great job! You are saving a healthy amount.' : 
                                '💡 Aim to save at least 20% of your income.'}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-body">
                <h5 class="card-title">Tips to Improve Your Savings</h5>
                <ul class="mb-0">
                    ${data.savingsRate < 20 ? `
                        <li>Review your expenses and identify areas to cut back</li>
                        <li>Try the 50/30/20 rule: 50% needs, 30% wants, 20% savings</li>
                        <li>Set up automatic transfers to your savings account</li>
                        <li>Track your spending regularly to stay on budget</li>
                    ` : `
                        <li>Great job on your savings rate! Consider investing your savings for better returns</li>
                        <li>Review your budget to see if you can optimize further</li>
                        <li>Set specific financial goals for your savings</li>
                    `}
                </ul>
            </div>
        </div>
    `;
    
    resultsDiv.appendChild(summary);
    
    // Scroll to results
    resultsDiv.scrollIntoView({ behavior: 'smooth' });
}
</script>