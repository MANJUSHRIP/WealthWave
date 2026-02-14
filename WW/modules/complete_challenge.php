<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$challengeId = filter_input(INPUT_POST, 'challenge_id', FILTER_SANITIZE_STRING);

if (empty($challengeId)) {
    echo json_encode(['success' => false, 'message' => 'No challenge specified']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Check if challenge exists and get points
    $stmt = $pdo->prepare("
        SELECT id, points, title, description 
        FROM challenges 
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$challengeId]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$challenge) {
        // Check if it's a dynamic challenge
        if (strpos($challengeId, 'dynamic_') === 0) {
            $points = 0;
            $title = 'Custom Challenge';
            $description = 'Personal achievement';
            
            // Set points based on challenge type
            if (strpos($challengeId, 'food') !== false) {
                $points = 30;
                $title = 'Meal Prep Master';
                $description = 'Prepared meals at home for 3 days';
            } elseif (strpos($challengeId, 'ent') !== false) {
                $points = 25;
                $title = 'Weekend Saver';
                $description = 'Completed a no-spend weekend';
            } elseif (strpos($challengeId, 'goal') !== false) {
                $points = 20;
                $title = 'Goal Setter';
                $description = 'Set a new savings goal';
            }
            
            $challenge = [
                'id' => $challengeId,
                'points' => $points,
                'title' => $title,
                'description' => $description,
                'is_dynamic' => true
            ];
        } else {
            throw new Exception('Challenge not found or inactive');
        }
    }
    
    // Check if already completed
    $stmt = $pdo->prepare("
        SELECT id, completed_at 
        FROM user_challenges 
        WHERE user_id = ? AND challenge_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $challengeId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Challenge already completed
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'alreadyCompleted' => true,
            'message' => 'You\'ve already completed this challenge!',
            'points' => 0,
            'challenge' => [
                'title' => $challenge['title'],
                'description' => $challenge['description']
            ]
        ]);
        exit();
    }
    
    // Record challenge completion
    $stmt = $pdo->prepare("
        INSERT INTO user_challenges (user_id, challenge_id, completed_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$_SESSION['user_id'], $challengeId]);
    
    // Add points to user
    $points = $challenge['points'];
    $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $stmt->execute([$points, $_SESSION['user_id']]);
    
    // Get updated points and level
    $stmt = $pdo->prepare("SELECT points, level FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check for level up
    $newLevel = calculateLevel($user['points']);
    $leveledUp = ($newLevel !== $user['level']);
    
    if ($leveledUp) {
        // Update user level
        $stmt = $pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
        $stmt->execute([$newLevel, $_SESSION['user_id']]);
    }
    
    // Check for badge unlocks
    $badgesUnlocked = checkBadgeUnlocks($pdo, $_SESSION['user_id']);
    
    $pdo->commit();
    
    // Prepare response
    $response = [
        'success' => true,
        'pointsEarned' => $points,
        'newPoints' => $user['points'] + $points,
        'newLevel' => $newLevel,
        'leveledUp' => $leveledUp,
        'badgesUnlocked' => $badgesUnlocked,
        'challenge' => [
            'title' => $challenge['title'],
            'description' => $challenge['description']
        ],
        'message' => '🎉 Challenge completed! You earned ' . $points . ' points.'
    ];
    
    if ($leveledUp) {
        $response['message'] .= ' Level up! You are now a ' . $newLevel . '!';
    }
    
    if (!empty($badgesUnlocked)) {
        $badgeNames = array_column($badgesUnlocked, 'name');
        $response['message'] .= ' Unlocked badge: ' . implode(', ', $badgeNames) . '!';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error completing challenge: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to complete challenge. Please try again.'
    ]);
}

/**
 * Check if user has unlocked any new badges
 */
function checkBadgeUnlocks($pdo, $userId) {
    $unlockedBadges = [];
    
    // Get user's current badges
    $stmt = $pdo->prepare("
        SELECT b.id, b.name, b.description, b.icon, b.color
        FROM user_badges ub
        JOIN badges b ON ub.badge_id = b.id
        WHERE ub.user_id = ?
    ");
    $stmt->execute([$userId]);
    $userBadges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userBadgeIds = array_column($userBadges, 'id');
    
    // Get user stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT uc.challenge_id) as completed_challenges,
            SUM(c.points) as total_points_earned,
            COUNT(DISTINCT qa.id) as quiz_attempts,
            SUM(CASE WHEN qa.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
            COUNT(DISTINCT DATE(uc2.completed_at)) as active_days
        FROM users u
        LEFT JOIN user_challenges uc ON u.id = uc.user_id
        LEFT JOIN challenges c ON uc.challenge_id = c.id
        LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
        LEFT JOIN user_challenges uc2 ON u.id = uc2.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Define badge criteria
    $badgeCriteria = [
        // Challenge badges
        [
            'id' => 'challenge_starter',
            'condition' => $stats['completed_challenges'] >= 1,
            'name' => 'Challenge Starter',
            'description' => 'Complete your first challenge',
            'icon' => 'trophy',
            'color' => 'warning'
        ],
        [
            'id' => 'challenge_champion',
            'condition' => $stats['completed_challenges'] >= 5,
            'name' => 'Challenge Champion',
            'description' => 'Complete 5 challenges',
            'icon' => 'trophy',
            'color' => 'success'
        ],
        
        // Quiz badges
        [
            'id' => 'quiz_whiz',
            'condition' => $stats['correct_answers'] >= 10,
            'name' => 'Quiz Whiz',
            'description' => 'Answer 10 quiz questions correctly',
            'icon' => 'award',
            'color' => 'info'
        ],
        
        // Consistency badges
        [
            'id' => 'dedicated_learner',
            'condition' => $stats['active_days'] >= 7,
            'name' => 'Dedicated Learner',
            'description' => 'Be active for 7 days',
            'icon' => 'calendar-check',
            'color' => 'primary'
        ],
        
        // Points badges
        [
            'id' => 'points_collector',
            'condition' => $stats['total_points_earned'] >= 100,
            'name' => 'Points Collector',
            'description' => 'Earn 100 points',
            'icon' => 'star',
            'color' => 'warning'
        ]
    ];
    
    // Check each badge criterion
    foreach ($badgeCriteria as $badge) {
        if ($badge['condition'] && !in_array($badge['id'], $userBadgeIds)) {
            // Award badge
            $stmt = $pdo->prepare("
                INSERT INTO user_badges (user_id, badge_id, earned_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$userId, $badge['id']]);
            
            $unlockedBadges[] = [
                'id' => $badge['id'],
                'name' => $badge['name'],
                'description' => $badge['description'],
                'icon' => $badge['icon'],
                'color' => $badge['color']
            ];
        }
    }
    
    return $unlockedBadges;
}
