<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$answers = $data['answers'] ?? [];

if (empty($answers)) {
    echo json_encode(['success' => false, 'message' => 'No answers provided']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    $totalQuestions = count($answers);
    $correctAnswers = 0;
    $results = [];
    $questions = [];
    $pointsEarned = 0;
    
    // Get all question details first
    $questionIds = array_keys($answers);
    $placeholders = str_repeat('?,', count($questionIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE id IN ($placeholders)");
    $stmt->execute($questionIds);
    
    while ($question = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $questions[$question['id']] = $question;
    }
    
    // Check each answer
    foreach ($answers as $questionId => $userAnswer) {
        if (!isset($questions[$questionId])) continue;
        
        $question = $questions[$questionId];
        $isCorrect = ($userAnswer == $question['correct_option']);
        
        if ($isCorrect) {
            $correctAnswers++;
        }
        
        // Record the attempt
        $stmt = $pdo->prepare("
            INSERT INTO quiz_attempts (user_id, question_id, selected_option, is_correct)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $questionId,
            $userAnswer,
            $isCorrect ? 1 : 0
        ]);
        
        // Prepare result for this question
        $results[$questionId] = [
            'isCorrect' => $isCorrect,
            'correctAnswer' => $question['option' . $question['correct_option']],
            'explanation' => $question['explanation']
        ];
    }
    
    // Calculate score and award points
    $score = ($correctAnswers / $totalQuestions) * 100;
    $points = $correctAnswers * 10; // 10 points per correct answer
    
    if ($points > 0) {
        // Add points to user
        $stmt = $pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $_SESSION['user_id']]);
        
        // Get updated points and level
        $stmt = $pdo->prepare("SELECT points, level FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $pointsEarned = $points;
        $newPoints = $user['points'];
        $newLevel = $user['level'];
        
        // Check for level up
        $newLevel = calculateLevel($newPoints);
        
        // Update level if changed
        if ($newLevel !== $user['level']) {
            $stmt = $pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
            $stmt->execute([$newLevel, $_SESSION['user_id']]);
        }
    }
    
    $pdo->commit();
    
    // Prepare response
    $response = [
        'success' => true,
        'score' => $score,
        'correctAnswers' => $correctAnswers,
        'totalQuestions' => $totalQuestions,
        'pointsEarned' => $pointsEarned,
        'newPoints' => $newPoints ?? 0,
        'newLevel' => $newLevel ?? 'Beginner',
        'results' => $results,
        'message' => $score >= 70 ? 
            "🎉 Great job! You scored {$correctAnswers}/{$totalQuestions}" : 
            "You scored {$correctAnswers}/{$totalQuestions}. Keep learning!"
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error submitting quiz: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while submitting your quiz. Please try again.'
    ]);
}
