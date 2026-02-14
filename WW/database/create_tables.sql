-- Create categories table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `color` varchar(7) DEFAULT '#6c757d',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create expenses table
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
    KEY `spent_date` (`spent_date`),
    CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT INTO `categories` (`name`, `color`) VALUES
    ('Food & Dining', '#4e73df'),
    ('Shopping', '#1cc88a'),
    ('Transportation', '#36b9cc'),
    ('Bills & Utilities', '#f6c23e'),
    ('Entertainment', '#e74a3b'),
    ('Health & Medical', '#6f42c1'),
    ('Education', '#fd7e14'),
    ('Travel', '#20c9a6'),
    ('Groceries', '#5a5c69'),
    ('Other', '#858796');
