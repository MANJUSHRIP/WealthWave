-- Create database
CREATE DATABASE IF NOT EXISTS financial_literacy;
USE financial_literacy;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    points INT DEFAULT 0,
    level VARCHAR(20) DEFAULT 'Beginner',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Budgets table
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    monthly_income DECIMAL(10,2) NOT NULL,
    food DECIMAL(10,2) NOT NULL,
    rent DECIMAL(10,2) NOT NULL,
    travel DECIMAL(10,2) NOT NULL,
    entertainment DECIMAL(10,2) NOT NULL,
    savings DECIMAL(10,2) GENERATED ALWAYS AS (monthly_income - (food + rent + travel + entertainment)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Quiz questions
CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    option1 VARCHAR(255) NOT NULL,
    option2 VARCHAR(255) NOT NULL,
    option3 VARCHAR(255) NOT NULL,
    option4 VARCHAR(255) NOT NULL,
    correct_option INT NOT NULL,
    explanation TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User quiz attempts
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option INT NOT NULL,
    is_correct BOOLEAN NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
);

-- Daily challenges
CREATE TABLE IF NOT EXISTS challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    points INT NOT NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User challenges
CREATE TABLE IF NOT EXISTS user_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    challenge_id INT NOT NULL,
    is_completed BOOLEAN DEFAULT false,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(id) ON DELETE CASCADE
);

-- AI chat history
CREATE TABLE IF NOT EXISTS ai_chat_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_message TEXT NOT NULL,
    ai_response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample quiz questions
INSERT INTO quiz_questions (question, option1, option2, option3, option4, correct_option, explanation) VALUES
('If your monthly income is ₹20,000, how much should you save according to the 50-30-20 rule?', '₹4,000 (20%)', '₹10,000 (50%)', '₹6,000 (30%)', '₹15,000 (75%)', 1, 'The 50-30-20 rule suggests 50% on needs, 30% on wants, and 20% on savings. 20% of ₹20,000 is ₹4,000.'),
('What is the first step in creating a budget?', 'Track your spending', 'Set financial goals', 'Calculate your income', 'Cut all expenses', 3, 'The first step is always to calculate your total income before planning expenses or savings.'),
('Which is an example of a "need" in budgeting?', 'Eating out at restaurants', 'Monthly rent payment', 'Movie tickets', 'New smartphone', 2, 'Rent is a basic necessity, while the others are wants.'),
('What is an emergency fund?', 'Money for vacations', 'Savings for unexpected expenses', 'Investment in stocks', 'Retirement fund', 2, 'An emergency fund is money set aside for unexpected expenses like medical bills or job loss.'),
('How much of your income should go towards needs according to the 50-30-20 rule?', '20%', '30%', '50%', '70%', 3, 'The 50-30-20 rule allocates 50% of income to needs like housing, food, and transportation.');

-- Insert sample challenges
INSERT INTO challenges (title, description, points) VALUES
('Track Expenses', 'Track all your expenses for one week', 20),
('Save ₹100', 'Save ₹100 this week by cutting unnecessary expenses', 15),
('No Spend Day', 'Have a day where you spend only on essentials', 25),
('Meal Planning', 'Plan and cook meals at home for 3 days', 30),
('Review Subscriptions', 'Review and cancel one unused subscription', 10);
