<?php
// setup_expenses_fixed.php
require_once 'includes/config.php';

try {
    // Create categories table
    $sql = "
        CREATE TABLE IF NOT EXISTS `categories` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `color` varchar(7) DEFAULT '#6c757d',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);
    
    // Create expenses table
    $sql = "
        CREATE TABLE IF NOT EXISTS `expenses` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `category_id` int(11) DEFAULT NULL,
            `spent_date` date NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `category_id` (`category_id`),
            KEY `spent_date` (`spent_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sql);
    
    // Add foreign key for user_id
    try {
        $pdo->exec("
            ALTER TABLE `expenses`
            ADD CONSTRAINT `fk_expenses_user_id`
            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        
        // Add foreign key for category_id
        $pdo->exec("
            ALTER TABLE `expenses`
            ADD CONSTRAINT `fk_expenses_category_id`
            FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
        
    } catch (PDOException $e) {
        // Ignore if foreign keys already exist
        if (!str_contains($e->getMessage(), 'Duplicate key name')) {
            throw $e;
        }
    }

    // Insert default categories
    $categories = [
        ['Food & Dining', '#4e73df'],
        ['Shopping', '#1cc88a'],
        ['Transportation', '#36b9cc'],
        ['Bills & Utilities', '#f6c23e'],
        ['Entertainment', '#e74a3b'],
        ['Health & Medical', '#6f42c1'],
        ['Education', '#fd7e14'],
        ['Travel', '#20c9a6'],
        ['Groceries', '#5a5c69'],
        ['Other', '#858796']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `categories` (`name`, `color`) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }

    echo "<h2>Database Setup Complete</h2>";
    echo "<p>Tables created and default categories added successfully!</p>";
    echo "<p><a href='index.php' class='btn btn-primary'>Go to Dashboard</a></p>";
    
    // Add some sample data for testing (optional)
    try {
        $sampleExpenses = [
            [1, 1, 25.50, 'Lunch at cafe', 1, date('Y-m-d')],
            [1, 2, 45.99, 'New shirt', 2, date('Y-m-d', strtotime('-1 day'))],
            [1, 3, 12.50, 'Bus tickets', 3, date('Y-m-d', strtotime('-2 days'))]
        ];
        
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO `expenses` 
            (`id`, `user_id`, `amount`, `description`, `category_id`, `spent_date`)
            VALUES (?, ?, ?, ?, ?, ?)
        
        foreach ($sampleExpenses as $expense) {
            $stmt->execute($expense);
        }
        
        echo "<div class='alert alert-info mt-3'>Added sample expense data for testing.</div>";
    } catch (Exception $e) {
        // Ignore errors in sample data insertion
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<style>
body { padding: 20px; font-family: Arial, sans-serif; }
.alert { margin-top: 20px; padding: 15px; border-radius: 4px; }
.alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.alert-info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
.btn { display: inline-block; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
.btn:hover { background: #0056b3; }
</style>
