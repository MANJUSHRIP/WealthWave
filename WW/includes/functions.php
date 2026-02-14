<?php
/**
 * Calculate user level based on points
 * 
 * @param int $points User's total points
 * @return string User's level
 */
function calculateLevel($points) {
    if ($points >= 300) {
        return 'Finance Pro';
    } elseif ($points >= 100) {
        return 'Smart Saver';
    } else {
        return 'Beginner';
    }
}

/**
 * Sanitize input data
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Format currency in Indian Rupees
 * 
 * @param float $amount Amount to format
 * @return string Formatted currency string
 */
function formatINR($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Add points to user's account
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $points Points to add
 * @return bool True on success, false on failure
 */
function addPoints($pdo, $userId, $points) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $result = $stmt->execute([$points, $userId]);
        
        if ($result) {
            // Update session points
            $_SESSION['user_points'] += $points;
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error adding points: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's current budget if exists
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array|false Budget data or false if not found
 */
function getUserBudget($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM budgets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user budget: " . $e->getMessage());
        return false;
    }
}

/**
 * Save user's budget
 * 
 * @param PDO $pdo Database connection
 * @param array $budgetData Budget data to save
 * @return bool True on success, false on failure
 */
function saveUserBudget($pdo, $budgetData) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO budgets 
            (user_id, monthly_income, food, rent, travel, entertainment)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $budgetData['user_id'],
            $budgetData['monthly_income'],
            $budgetData['food'],
            $budgetData['rent'],
            $budgetData['travel'],
            $budgetData['entertainment']
        ]);
    } catch (PDOException $e) {
        error_log("Error saving budget: " . $e->getMessage());
        return false;
    }
}

/**
 * Get random quiz questions
 * 
 * @param PDO $pdo Database connection
 * @param int $limit Number of questions to fetch
 * @return array Array of quiz questions
 */
function getQuizQuestions($pdo, $limit = 5) {
    try {
        $stmt = $pdo->query("SELECT * FROM quiz_questions ORDER BY RAND() LIMIT $limit");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting quiz questions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get active challenges for user
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array Array of active challenges
 */
function getUserChallenges($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, uc.is_completed, uc.completed_at 
            FROM challenges c
            LEFT JOIN user_challenges uc ON c.id = uc.challenge_id AND uc.user_id = ?
            WHERE c.is_active = 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user challenges: " . $e->getMessage());
        return [];
    }
}

/**
 * Complete a challenge for a user
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param int $challengeId Challenge ID
 * @return bool True on success, false on failure
 */
function completeChallenge($pdo, $userId, $challengeId) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if already completed
        $stmt = $pdo->prepare("
            SELECT id FROM user_challenges 
            WHERE user_id = ? AND challenge_id = ? AND is_completed = 1
        ");
        $stmt->execute([$userId, $challengeId]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->rollBack();
            return false; // Already completed
        }
        
        // Get challenge points
        $stmt = $pdo->prepare("SELECT points FROM challenges WHERE id = ?");
        $stmt->execute([$challengeId]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$challenge) {
            $pdo->rollBack();
            return false; // Invalid challenge
        }
        
        // Add user challenge record
        $stmt = $pdo->prepare("
            INSERT INTO user_challenges (user_id, challenge_id, is_completed, completed_at)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE is_completed = 1, completed_at = NOW()
        ");
        $stmt->execute([$userId, $challengeId]);
        
        // Add points to user
        $points = $challenge['points'];
        $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $userId]);
        
        // Update session points
        $_SESSION['user_points'] += $points;
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error completing challenge: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's financial summary
 * 
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @return array Financial summary data
 */
function getFinancialSummary($pdo, $userId) {
    try {
        $summary = [
            'total_income' => 0,
            'total_expenses' => 0,
            'savings' => 0,
            'savings_rate' => 0,
            'expense_categories' => []
        ];
        
        // Get latest budget
        $budget = getUserBudget($pdo, $userId);
        
        if ($budget) {
            $summary['total_income'] = (float)$budget['monthly_income'];
            $summary['expense_categories'] = [
                'Food' => (float)$budget['food'],
                'Rent' => (float)$budget['rent'],
                'Travel' => (float)$budget['travel'],
                'Entertainment' => (float)$budget['entertainment']
            ];
            
            $totalExpenses = array_sum($summary['expense_categories']);
            $savings = $summary['total_income'] - $totalExpenses;
            $savingsRate = $summary['total_income'] > 0 ? ($savings / $summary['total_income']) * 100 : 0;
            
            $summary['total_expenses'] = $totalExpenses;
            $summary['savings'] = $savings;
            $summary['savings_rate'] = round($savingsRate, 2);
        }
        
        return $summary;
    } catch (Exception $e) {
        error_log("Error getting financial summary: " . $e->getMessage());
        return [];
    }
}
