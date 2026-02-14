<?php
// spending.php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in to view spending analysis');
}

$user_id = $_SESSION['user_id'];
$current_year = date('Y');
$current_month = date('n');

// Get available years with data
$years = [];
$stmt = $pdo->prepare("
    SELECT DISTINCT YEAR(spent_date) as year 
    FROM expenses 
    WHERE user_id = ? 
    ORDER BY year DESC
");
$stmt->execute([$user_id]);
$years = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($years)) {
    $years = [date('Y')]; // Default to current year if no data
}

// Get selected year and month from request or use current
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : $current_month;

// Get spending data
$spending_data = [];
$monthly_total = 0;
$category_totals = [];
$daily_totals = array_fill(1, date('t', strtotime("$selected_year-$selected_month-01")), 0);

// Get expenses for the selected period
$stmt = $pdo->prepare("
    SELECT 
        e.amount, 
        e.spent_date,
        c.name as category_name,
        c.color as category_color
    FROM expenses e
    LEFT JOIN categories c ON e.category_id = c.id
    WHERE e.user_id = ? 
    AND YEAR(e.spent_date) = ? 
    AND MONTH(e.spent_date) = ?
    ORDER BY e.spent_date DESC
");
$stmt->execute([$user_id, $selected_year, $selected_month]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($expenses as $expense) {
    $day = (int)date('j', strtotime($expense['spent_date']));
    $category = $expense['category_name'] ?: 'Uncategorized';
    $amount = (float)$expense['amount'];
    
    // Update monthly total
    $monthly_total += $amount;
    
    // Update category totals
    if (!isset($category_totals[$category])) {
        $category_totals[$category] = [
            'amount' => 0,
            'color' => $expense['category_color'] ?: '#6c757d'
        ];
    }
    $category_totals[$category]['amount'] += $amount;
    
    // Update daily totals
    $daily_totals[$day] += $amount;
    
    // Add to spending data
    $spending_data[] = [
        'date' => $expense['spent_date'],
        'amount' => $amount,
        'category' => $category,
        'color' => $expense['category_color'] ?: '#6c757d'
    ];
}

// Sort categories by amount (descending)
uasort($category_totals, function($a, $b) {
    return $b['amount'] <=> $a['amount'];
});

// Get monthly comparison data
$monthly_comparison = [];
$stmt = $pdo->prepare("
    SELECT 
        MONTH(spent_date) as month,
        SUM(amount) as total
    FROM expenses
    WHERE user_id = ? 
    AND YEAR(spent_date) = ?
    GROUP BY MONTH(spent_date)
    ORDER BY month
");
$stmt->execute([$user_id, $selected_year]);
$monthly_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fill in missing months with 0
for ($i = 1; $i <= 12; $i++) {
    $monthly_comparison[] = [
        'month' => date('M', mktime(0, 0, 0, $i, 1)),
        'amount' => $monthly_data[$i] ?? 0
    ];
}

// Get top expenses
$top_expenses = array_slice($spending_data, 0, 5);

// Get spending trends (last 6 months)
$trend_months = [];
$trend_amounts = [];
for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $trend_months[] = date('M y', strtotime($date));
    $trend_amounts[] = 0; // Will be updated if data exists
}

// Get trend data
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(spent_date, '%b %y') as month,
        SUM(amount) as total
    FROM expenses
    WHERE user_id = ?
    AND spent_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(spent_date), MONTH(spent_date)
    ORDER BY YEAR(spent_date), MONTH(spent_date)
");
$stmt->execute([$user_id]);
$trend_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Update trend amounts with actual data
foreach ($trend_months as $index => $month) {
    if (isset($trend_data[$month])) {
        $trend_amounts[$index] = (float)$trend_data[$month];
    }
}
?>

<div class="container my-4">
    <!-- Header with title and filters -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-pie me-2"></i>Spending Analysis</h2>
        <div class="d-flex gap-2">
            <select id="year-select" class="form-select form-select-sm" style="width: auto;">
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="month-select" class="form-select form-select-sm" style="width: auto;">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>" <?= $i == $selected_month ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Spent</h6>
                            <h3 class="mb-0">$<?= number_format($monthly_total, 2) ?></h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-wallet text-primary fa-2x"></i>
                        </div>
                    </div>
                    <?php
                    $last_month = $selected_month > 1 ? $selected_month - 1 : 12;
                    $last_month_year = $selected_month > 1 ? $selected_year : $selected_year - 1;
                    
                    $stmt = $pdo->prepare("
                        SELECT COALESCE(SUM(amount), 0) as total
                        FROM expenses
                        WHERE user_id = ?
                        AND YEAR(spent_date) = ?
                        AND MONTH(spent_date) = ?
                    ");
                    $stmt->execute([$user_id, $last_month_year, $last_month]);
                    $last_month_total = $stmt->fetchColumn();
                    
                    $difference = $last_month_total > 0 
                        ? (($monthly_total - $last_month_total) / $last_month_total) * 100 
                        : 0;
                    $is_higher = $difference > 0;
                    $difference = abs($difference);
                    ?>
                    <p class="mt-3 mb-0">
                        <?php if ($last_month_total > 0): ?>
                            <span class="<?= $is_higher ? 'text-danger' : 'text-success' ?>">
                                <i class="fas fa-arrow-<?= $is_higher ? 'up' : 'down' ?> me-1"></i>
                                <?= number_format($difference, 1) ?>% 
                                <?= $is_higher ? 'more' : 'less' ?> than last month
                            </span>
                        <?php else: ?>
                            <span class="text-muted">No data for comparison</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Daily Average</h6>
                            <h3 class="mb-0">$<?= number_format($monthly_total / date('t'), 2) ?></h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-calendar-day text-success fa-2x"></i>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-muted">
                        <?= date('F Y', strtotime("$selected_year-$selected_month-01")) ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Transactions</h6>
                            <h3 class="mb-0"><?= count($spending_data) ?></h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded-circle">
                            <i class="fas fa-exchange-alt text-info fa-2x"></i>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-muted">
                        <?= count($category_totals) ?> categories
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Spending by Category (Pie Chart) -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Spending by Category</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                    <div class="mt-3" id="category-legend"></div>
                </div>
            </div>
        </div>

        <!-- Daily Spending (Bar Chart) -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Daily Spending</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dailyViewDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            This Month
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dailyViewDropdown">
                            <li><a class="dropdown-item" href="#" data-value="week">This Week</a></li>
                            <li><a class="dropdown-item active" href="#" data-value="month">This Month</a></li>
                            <li><a class="dropdown-item" href="#" data-value="year">This Year</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="dailySpendingChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Monthly Comparison (Bar Chart) -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Monthly Comparison (<?= $selected_year ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="monthlyComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spending Trends (Line Chart) -->
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Spending Trends (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="position: relative; height: 300px;">
                        <canvas id="spendingTrendsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Transactions</h5>
            <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Category</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($spending_data) > 0): ?>
                            <?php foreach (array_slice($spending_data, 0, 5) as $expense): ?>
                                <tr>
                                    <td><?= date('M j, Y', strtotime($expense['date'])) ?></td>
                                    <td>Expense</td>
                                    <td>
                                        <span class="badge" style="background-color: <?= $expense['color'] ?>20; color: <?= $expense['color'] ?>">
                                            <?= htmlspecialchars($expense['category']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">$<?= number_format($expense['amount'], 2) ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-link text-muted" type="button" id="expenseActions" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="expenseActions">
                                                <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No transactions found for this period
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    initCategoryChart();
    initDailySpendingChart();
    initMonthlyComparisonChart();
    initSpendingTrendsChart();
    
    // Handle year/month select change
    document.getElementById('year-select').addEventListener('change', updateCharts);
    document.getElementById('month-select').addEventListener('change', updateCharts);
    
    // Handle daily view dropdown
    document.querySelectorAll('.dropdown-item[data-value]').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const value = this.getAttribute('data-value');
            
            // Update active state
            document.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Update button text
            document.querySelector('#dailyViewDropdown').textContent = this.textContent.trim();
            
            // Update chart based on selection
            updateDailyChart(value);
        });
    });
    
    function updateCharts() {
        const year = document.getElementById('year-select').value;
        const month = document.getElementById('month-select').value;
        
        // Reload the page with new filters
        window.location.href = `?module=spending&year=${year}&month=${month}`;
    }
    
    function initCategoryChart() {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        const categories = <?= json_encode(array_keys($category_totals)) ?>;
        const amounts = <?= json_encode(array_column($category_totals, 'amount')) ?>;
        const colors = <?= json_encode(array_column($category_totals, 'color')) ?>;
        
        // Generate lighter colors for the chart
        const backgroundColors = colors.map(color => {
            // Convert hex to RGB and add opacity
            return color + '40'; // 25% opacity
        });
        
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categories,
                datasets: [{
                    data: amounts,
                    backgroundColor: backgroundColors,
                    borderColor: colors,
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
                },
                cutout: '70%',
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
        
        // Create custom legend
        const legendContainer = document.getElementById('category-legend');
        if (legendContainer) {
            categories.forEach((category, index) => {
                const legendItem = document.createElement('div');
                legendItem.className = 'd-flex align-items-center mb-2';
                
                const colorBox = document.createElement('div');
                colorBox.className = 'me-2';
                colorBox.style.width = '16px';
                colorBox.style.height = '16px';
                colorBox.style.backgroundColor = backgroundColors[index];
                colorBox.style.border = `1px solid ${colors[index]}`;
                colorBox.style.borderRadius = '3px';
                
                const label = document.createElement('div');
                label.className = 'small text-muted';
                label.style.flex = '1';
                label.textContent = category;
                
                const amount = document.createElement('div');
                amount.className = 'ms-2 fw-bold';
                amount.textContent = `$${amounts[index].toFixed(2)}`;
                
                legendItem.appendChild(colorBox);
                legendItem.appendChild(label);
                legendItem.appendChild(amount);
                legendContainer.appendChild(legendItem);
            });
        }
    }
    
    function initDailySpendingChart() {
        const ctx = document.getElementById('dailySpendingChart').getContext('2d');
        const daysInMonth = new Date(<?= $selected_year ?>, <?= $selected_month ?>, 0).getDate();
        const days = Array.from({ length: daysInMonth }, (_, i) => i + 1);
        const amounts = <?= json_encode(array_values($daily_totals)) ?>;
        
        // Calculate weekly averages
        const weeklyAverages = [];
        let weekTotal = 0;
        let dayCount = 0;
        
        days.forEach((day, index) => {
            weekTotal += amounts[day - 1] || 0;
            dayCount++;
            
            if (dayCount === 7 || index === days.length - 1) {
                const average = weekTotal / dayCount;
                for (let i = 0; i < dayCount; i++) {
                    weeklyAverages.push(average);
                }
                weekTotal = 0;
                dayCount = 0;
            }
        });
        
        window.dailyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [
                    {
                        label: 'Daily Spending',
                        data: amounts,
                        backgroundColor: '#4e73df',
                        borderColor: '#2e59d9',
                        borderWidth: 1,
                        borderRadius: 4,
                        borderSkipped: false
                    },
                    {
                        label: 'Weekly Average',
                        data: weeklyAverages,
                        type: 'line',
                        borderColor: '#e74a3b',
                        borderWidth: 2,
                        pointRadius: 0,
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#e74a3b',
                        pointHoverBorderColor: 'white',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        fill: false
                    }
                ]
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
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += '$' + context.parsed.y.toFixed(2);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Day of Month'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount ($)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
    }
    
    function updateDailyChart(view) {
        // In a real app, this would fetch new data based on the selected view
        // For now, we'll just update the existing chart with sample data
        
        let labels, data, weeklyAverages;
        
        if (view === 'week') {
            // Last 7 days
            labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            data = [120, 190, 90, 150, 200, 250, 180];
            weeklyAverages = Array(7).fill(data.reduce((a, b) => a + b, 0) / 7);
        } else if (view === 'year') {
            // Last 12 months
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            data = [2800, 3200, 2900, 3100, 3500, 3800, 4000, 4200, 3900, 4100, 4300, 4500];
            weeklyAverages = Array(12).fill(0); // Not applicable for yearly view
        } else {
            // Default to month view
            const daysInMonth = new Date(<?= $selected_year ?>, <?= $selected_month ?>, 0).getDate();
            labels = Array.from({ length: daysInMonth }, (_, i) => i + 1);
            data = <?= json_encode(array_values($daily_totals)) ?>;
            
            // Recalculate weekly averages
            weeklyAverages = [];
            let weekTotal = 0;
            let dayCount = 0;
            
            labels.forEach((day, index) => {
                weekTotal += data[day - 1] || 0;
                dayCount++;
                
                if (dayCount === 7 || index === labels.length - 1) {
                    const average = weekTotal / dayCount;
                    for (let i = 0; i < dayCount; i++) {
                        weeklyAverages.push(average);
                    }
                    weekTotal = 0;
                    dayCount = 0;
                }
            });
        }
        
        // Update chart data
        window.dailyChart.data.labels = labels;
        window.dailyChart.data.datasets[0].data = data;
        window.dailyChart.data.datasets[1].data = view === 'year' ? [] : weeklyAverages;
        window.dailyChart.update();
    }
    
    function initMonthlyComparisonChart() {
        const ctx = document.getElementById('monthlyComparisonChart').getContext('2d');
        const months = <?= json_encode(array_column($monthly_comparison, 'month')) ?>;
        const amounts = <?= json_encode(array_column($monthly_comparison, 'amount')) ?>;
        
        window.monthlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Monthly Spending',
                    data: amounts,
                    backgroundColor: '#1cc88a',
                    borderColor: '#1aae7e',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false
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
                                return '$' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
    
    function initSpendingTrendsChart() {
        const ctx = document.getElementById('spendingTrendsChart').getContext('2d');
        const months = <?= json_encode($trend_months) ?>;
        const amounts = <?= json_encode($trend_amounts) ?>;
        
        window.trendsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Total Spending',
                    data: amounts,
                    borderColor: '#36b9cc',
                    backgroundColor: 'rgba(54, 185, 204, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#36b9cc',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#36b9cc',
                    pointHoverBorderWidth: 2
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
                                return '$' + context.parsed.y.toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
