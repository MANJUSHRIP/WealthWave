<?php
// budget.php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in to access the budget planner');
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get budget data for the current month
$current_month = date('Y-m-01');
$budget = null;

try {
    // Check if budgets table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'budgets'")->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->prepare("
            SELECT * FROM budgets 
            WHERE user_id = ? AND MONTH(budget_date) = MONTH(?) AND YEAR(budget_date) = YEAR(?)
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id, $current_month, $current_month]);
        $budget = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Log the error but don't stop execution
    error_log("Error fetching budget: " . $e->getMessage());
}

// Default categories
$categories = [
    'Housing' => 0,
    'Food' => 0,
    'Transportation' => 0,
    'Utilities' => 0,
    'Insurance' => 0,
    'Healthcare' => 0,
    'Savings' => 0,
    'Debt' => 0,
    'Entertainment' => 0,
    'Other' => 0
];

// If we have a budget, use those values
if ($budget) {
    $income = $budget['income'];
    $expenses = json_decode($budget['expenses'], true);
    $categories = array_merge($categories, $expenses);
} else {
    $income = 0;
}
?>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-wallet me-2"></i>Monthly Budget Planner</h3>
                </div>
                <div class="card-body">
                    <form id="budget-form">
                        <div class="mb-4">
                            <label for="income" class="form-label">Monthly Income</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control form-control-lg" id="income" 
                                       value="<?= number_format($income, 2, '.', '') ?>" 
                                       min="0" step="0.01" required>
                            </div>
                        </div>

                        <h4 class="mb-3">Monthly Expenses</h4>
                        
                        <div class="row g-3">
                            <?php foreach ($categories as $category => $amount): ?>
                            <div class="col-md-6 mb-3">
                                <label for="expense-<?= strtolower($category) ?>" class="form-label"><?= $category ?></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control expense-input" 
                                           id="expense-<?= strtolower($category) ?>" 
                                           data-category="<?= $category ?>"
                                           value="<?= number_format($amount, 2, '.', '') ?>" 
                                           min="0" step="0.01">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary" id="reset-budget">
                                <i class="fas fa-undo me-2"></i>Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="save-budget">
                                <i class="fas fa-save me-2"></i>Save Budget
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Budget Summary</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <canvas id="expenseChart" height="250"></canvas>
                    </div>
                    
                    <div class="budget-summary">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Income:</span>
                            <strong id="total-income">$<?= number_format($income, 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Expenses:</span>
                            <strong id="total-expenses">$0.00</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold">
                            <span>Remaining:</span>
                            <span id="remaining-amount" class="<?= $income >= 0 ? 'text-success' : 'text-danger' ?>">
                                $<?= number_format($income, 2) ?>
                            </span>
                        </div>
                        <div class="progress mt-3" style="height: 10px;">
                            <div id="savings-progress" class="progress-bar bg-success" role="progressbar" 
                                 style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="text-muted text-center small mt-1">
                            <span id="savings-percentage">0%</span> of income remaining
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Budgeting Tips</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5 class="alert-heading"><i class="fas fa-piggy-bank me-2"></i>50/30/20 Rule</h5>
                        <p class="mb-1">Aim to spend:</p>
                        <ul class="mb-0">
                            <li>50% on needs</li>
                            <li>30% on wants</li>
                            <li>20% on savings/debt</li>
                        </ul>
                    </div>
                    <div id="budget-tips">
                        <!-- Tips will be dynamically inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let expenseChart;
    let categories = <?= json_encode(array_keys($categories)) ?>;
    
    // Initialize the chart
    function initChart(expenseData) {
        const ctx = document.getElementById('expenseChart').getContext('2d');
        
        if (expenseChart) {
            expenseChart.destroy();
        }
        
        expenseChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categories,
                datasets: [{
                    data: expenseData,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
                        '#e74a3b', '#5a5c69', '#858796', '#5a5c69',
                        '#e74a3b', '#f6c23e'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
                                return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Calculate and update totals
    function updateTotals() {
        const income = parseFloat(document.getElementById('income').value) || 0;
        let totalExpenses = 0;
        const expenseData = [];
        
        // Calculate total expenses and collect data for chart
        categories.forEach(category => {
            const input = document.querySelector(`#expense-${category.toLowerCase().replace(' ', '-')}`);
            if (input) {
                const amount = parseFloat(input.value) || 0;
                totalExpenses += amount;
                expenseData.push(amount);
            }
        });
        
        const remaining = income - totalExpenses;
        const savingsPercentage = income > 0 ? Math.round((remaining / income) * 100) : 0;
        
        // Update UI
        document.getElementById('total-income').textContent = `$${income.toFixed(2)}`;
        document.getElementById('total-expenses').textContent = `$${totalExpenses.toFixed(2)}`;
        document.getElementById('remaining-amount').textContent = `$${remaining.toFixed(2)}`;
        document.getElementById('remaining-amount').className = remaining >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
        document.getElementById('savings-percentage').textContent = `${savingsPercentage}%`;
        document.getElementById('savings-progress').style.width = `${Math.min(100, Math.max(0, savingsPercentage))}%`;
        
        // Update chart
        initChart(expenseData);
        
        // Generate budget tips
        generateBudgetTips(income, totalExpenses, remaining);
    }

    // Generate budget tips based on user's budget
    function generateBudgetTips(income, expenses, remaining) {
        const tipsContainer = document.getElementById('budget-tips');
        let tips = [];
        
        if (income <= 0) {
            tips.push({
                icon: 'fa-exclamation-triangle',
                title: 'No Income Entered',
                message: 'Please enter your monthly income to get started with budgeting.'
            });
        } else {
            const needsPercentage = (expenses / income) * 100;
            
            if (remaining < 0) {
                tips.push({
                    icon: 'fa-exclamation-circle',
                    title: 'Over Budget',
                    message: `You're spending $${Math.abs(remaining).toFixed(2)} more than you earn. Consider reducing expenses.`
                });
            }
            
            if (needsPercentage > 60) {
                tips.push({
                    icon: 'fa-home',
                    title: 'High Fixed Expenses',
                    message: 'Your fixed expenses are high. Look for ways to reduce recurring costs.'
                });
            }
            
            if ((expenses / income) < 0.7) {
                tips.push({
                    icon: 'fa-piggy-bank',
                    title: 'Great Job!',
                    message: 'You\'re saving a good portion of your income. Keep it up!'
                });
            }
        }
        
        // Display tips
        if (tips.length === 0) {
            tips.push({
                icon: 'fa-check-circle',
                title: 'On Track',
                message: 'Your budget looks balanced. Keep monitoring your spending!'
            });
        }
        
        tipsContainer.innerHTML = tips.map(tip => `
            <div class="alert alert-light mb-2">
                <h6 class="alert-heading"><i class="fas ${tip.icon} me-2 text-primary"></i>${tip.title}</h6>
                <p class="mb-0 small">${tip.message}</p>
            </div>
        `).join('');
    }

    // Save budget
    document.getElementById('budget-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const income = parseFloat(document.getElementById('income').value) || 0;
        const expenses = {};
        
        categories.forEach(category => {
            const input = document.querySelector(`#expense-${category.toLowerCase()}`);
            if (input) {
                expenses[category] = parseFloat(input.value) || 0;
            }
        });
        
        try {
            const response = await fetch('modules/save_budget.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    income: income,
                    expenses: expenses
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('Budget saved successfully!', 'success');
            } else {
                throw new Error(result.message || 'Failed to save budget');
            }
        } catch (error) {
            console.error('Error saving budget:', error);
            showAlert('Error saving budget: ' + error.message, 'danger');
        }
    });

    // Reset form
    document.getElementById('reset-budget').addEventListener('click', function() {
        if (confirm('Are you sure you want to reset all budget values?')) {
            document.getElementById('income').value = '0.00';
            document.querySelectorAll('.expense-input').forEach(input => {
                input.value = '0.00';
            });
            updateTotals();
        }
    });

    // Show alert message
    function showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const form = document.getElementById('budget-form');
        form.prepend(alertDiv);
        
        // Auto-remove alert after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    // Initialize
    const inputs = document.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        input.addEventListener('input', updateTotals);
    });
    
    updateTotals();
});
</script>
