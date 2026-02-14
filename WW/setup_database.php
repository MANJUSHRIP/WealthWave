<?php
// setup_database.php
require_once 'includes/config.php';

try {
    // Create users table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `email` varchar(100) NOT NULL,
            `password` varchar(255) NOT NULL,
            `points` int(11) DEFAULT 0,
            `level` varchar(50) DEFAULT 'Beginner',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create budgets table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `budgets` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `income` decimal(10,2) NOT NULL,
            `expenses` text NOT NULL,
            `budget_date` date NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `budget_date` (`budget_date`),
            CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "Database tables created successfully!";
} catch (PDOException $e) {
    die("Error creating database tables: " . $e->getMessage());
}
