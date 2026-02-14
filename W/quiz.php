<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user']['name'])) {
    header('Location: dashboard.php');
    exit();
}

$user = $_SESSION['user'];
$page_title = 'Financial Quiz';
$body_class = 'quiz-module';

// Include header
include 'includes/header.php';

// Quiz questions by category
$quiz_data = [
    'budgeting' => [
        'title' => 'Budgeting Basics',
        'icon' => 'wallet',
        'questions' => [
            [
                'question' => 'What is the recommended percentage of income that should go towards needs in the 50/30/20 budget rule?',
                'options' => [
                    '20%',
                    '30%',
                    '50%',
                    '70%'
                ],
                'correct' => 2, // 50%
                'explanation' => 'The 50/30/20 rule suggests allocating 50% of your income to needs (like rent and groceries), 30% to wants, and 20% to savings and debt repayment.'
            ],
            [
                'question' => 'What should you do first when creating a budget?',
                'options' => [
                    'Set financial goals',
                    'Track your income and expenses',
                    'Cut all unnecessary expenses',
                    'Open a savings account'
                ],
                'correct' => 1, // Track your income and expenses
                'explanation' => 'The first step in creating a budget is to track your current income and expenses to understand your spending patterns.'
            ]
        ]
    ],
    'savings' => [
        'title' => 'Savings & Emergency Fund',
        'icon' => 'piggy-bank',
        'questions' => [
            [
                'question' => 'How many months of expenses should an emergency fund typically cover?',
                'options' => [
                    '1-2 months',
                    '3-6 months',
                    '7-9 months',
                    '12+ months'
                ],
                'correct' => 1, // 3-6 months
                'explanation' => 'Financial experts recommend having an emergency fund that covers 3-6 months of living expenses to handle unexpected situations like job loss or medical emergencies.'
            ]
        ]
    ],
    'investment' => [
        'title' => 'Investment Basics',
        'icon' => 'chart-line',
        'questions' => [
            [
                'question' => 'What is the main advantage of starting to invest early?',
                'options' => [
                    'Higher interest rates',
                    'More time for compound interest to work',
                    'Lower risk',
                    'Guaranteed returns'
                ],
                'correct' => 1, // More time for compound interest to work
                'explanation' => 'Starting to invest early gives your money more time to grow through the power of compound interest, where you earn returns on your returns.'
            ]
        ]
    ],
    'credit' => [
        'title' => 'Credit & Loans',
        'icon' => 'credit-card',
        'questions' => [
            [
                'question' => 'What is a good credit score range in India?',
                'options' => [
                    '300-500',
                    '500-650',
                    '650-750',
                    '750-900'
                ],
                'correct' => 2, // 650-750
                'explanation' => 'In India, a credit score of 650-750 is considered good, while 750+ is excellent. Higher scores help you get better interest rates on loans.'
            ]
        ]
    ],
    'behavioral' => [
        'title' => 'Behavioral Finance',
        'icon' => 'brain',
        'questions' => [
            [
                'question' => 'What is the term for the tendency to make decisions based on recent events or information?',
                'options' => [
                    'Anchoring',
                    'Confirmation bias',
                    'Recency bias',
                    'Herd mentality'
                ],
                'correct' => 2, // Recency bias
                'explanation' => 'Recency bias is the tendency to weigh recent events more heavily than earlier events when making decisions, which can lead to poor financial choices.'
            ]
        ]
    ],
    'digital_payments' => [
        'title' => 'Digital Payment Safety',
        'icon' => 'mobile-screen',
        'questions' => [
            [
                'question' => 'What should you do if you receive an OTP that you didn\'t request?',
                'options' => [
                    'Ignore it',
                    'Share it with customer care if they call',
                    'Enter it on the website that requested it',
                    'Never share it with anyone'
                ],
                'correct' => 3, // Never share it with anyone
                'explanation' => 'Never share OTPs with anyone, even if they claim to be from your bank. Legitimate organizations will never ask for your OTP.'
            ]
        ]
    ]
];

// Flatten questions for the quiz
$all_questions = [];
foreach ($quiz_data as $category => $data) {
    foreach ($data['questions'] as $question) {
        $question['category'] = $category;
        $all_questions[] = $question;
    }
}

// Shuffle questions for the quiz
shuffle($all_questions);
?>

<div class="quiz-module">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Financial Quiz</li>
                </ol>
            </nav>
            <h2 class="mb-3">Financial Literacy Quiz</h2>
            <p class="lead">Test your financial knowledge across different categories and earn coins!</p>
        </div>
    </div>

    <!-- Quiz Categories -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-layer-group text-primary me-2"></i>
                        Quiz Categories
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <?php foreach ($quiz_data as $key => $category): ?>
                        <div class="col-md-4">
                            <div class="category-card card h-100" data-category="<?php echo $key; ?>">
                                <div class="card-body text-center">
                                    <div class="category-icon mb-3">
                                        <i class="fas fa-<?php echo $category['icon']; ?> fa-3x text-primary"></i>
                                    </div>
                                    <h5 class="card-title"><?php echo $category['title']; ?></h5>
                                    <p class="text-muted small mb-0"><?php echo count($category['questions']); ?> questions</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quiz Container -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy text-warning me-2"></i>
                        Financial Quiz Challenge
                        <span class="badge bg-success ms-2">+10 XP per correct answer</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="quizContainer">
                        <div class="text-center py-5" id="quizStartScreen">
                            <div class="mb-4">
                                <i class="fas fa-question-circle fa-4x text-primary mb-3"></i>
                                <h3>Ready to Test Your Financial Knowledge?</h3>
                                <p class="lead">Answer questions from different categories and earn coins!</p>
                            </div>
                            
                            <div class="row justify-content-center mb-4">
                                <div class="col-md-8">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h5 class="card-title">Quiz Rules</h5>
                                            <ul class="text-start">
                                                <li>20 seconds per question</li>
                                                <li>+10 coins for each correct answer</li>
                                                <li>+5 bonus coins for 3 correct answers in a row</li>
                                                <li>See explanations for all answers</li>
                                                <li>Track your weak areas for improvement</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-center gap-3">
                                <button class="btn btn-primary btn-lg" id="startFullQuiz">
                                    <i class="fas fa-play me-2"></i> Start Full Quiz
                                </button>
                                <button class="btn btn-outline-primary btn-lg" id="selectCategory">
                                    <i class="fas fa-layer-group me-2"></i> Select Category
                                </button>
                            </div>
                        </div>
                        
                        <div id="quizQuestions" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div>
                                    <span class="badge bg-primary" id="questionCategory">Budgeting</span>
                                    <span class="ms-2 text-muted" id="questionProgress">Question 1/10</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="me-2">Time Left:</span>
                                    <div class="timer-circle">
                                        <svg class="timer-svg" viewBox="0 0 36 36">
                                            <path class="timer-circle-bg"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="#eee"
                                                stroke-width="3"
                                            />
                                            <path class="timer-circle"
                                                d="M18 2.0845
                                                a 15.9155 15.9155 0 0 1 0 31.831
                                                a 15.9155 15.9155 0 0 1 0 -31.831"
                                                fill="none"
                                                stroke="#4CAF50"
                                                stroke-width="3"
                                                stroke-dasharray="100, 100"
                                            />
                                        </svg>
                                        <span class="timer-text" id="timer">20</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="question-card mb-4">
                                <div class="question-text mb-4" id="questionText">
                                    Loading question...
                                </div>
                                
                                <div class="options-container" id="optionsContainer">
                                    <div class="form-check option-item mb-3">
                                        <input class="form-check-input" type="radio" name="quizOption" id="option1" value="0">
                                        <label class="form-check-label w-100" for="option1">
                                            <span class="option-text">Option 1</span>
                                        </label>
                                    </div>
                                    <!-- More options will be added by JavaScript -->
                                </div>
                                
                                <div class="alert mt-3" id="feedbackAlert" style="display: none;">
                                    <div id="feedbackContent"></div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div id="coinsEarned" class="text-success fw-bold"></div>
                                        <button class="btn btn-sm btn-primary" id="nextQuestionBtn">
                                            Next Question <i class="fas fa-arrow-right ms-1"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 0%" 
                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="quizProgressBar"></div>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-4">
                                <span>Score: <span id="currentScore">0</span> points</span>
                                <span>Streak: <span id="streakCount">0</span> correct in a row</span>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-secondary" id="quitQuiz">
                                    <i class="fas fa-sign-out-alt me-1"></i> Quit Quiz
                                </button>
                                <button class="btn btn-primary" id="submitAnswer" disabled>
                                    Submit Answer
                                </button>
                            </div>
                        </div>
                        
                        <div id="quizResults" style="display: none;">
                            <div class="text-center py-4">
                                <div class="result-icon mb-3">
                                    <i class="fas fa-trophy fa-4x text-warning"></i>
                                </div>
                                <h3 class="mb-3">Quiz Complete!</h3>
                                
                                <div class="row justify-content-center mb-4">
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="row text-center">
                                                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                                                        <div class="display-5 fw-bold text-primary" id="finalScore">0</div>
                                                        <div class="text-muted small">SCORE</div>
                                                    </div>
                                                    <div class="col-6 col-md-3 mb-3 mb-md-0">
                                                        <div class="display-5 fw-bold text-success" id="correctAnswers">0</div>
                                                        <div class="text-muted small">CORRECT</div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <div class="display-5 fw-bold text-danger" id="wrongAnswers">0</div>
                                                        <div class="text-muted small">WRONG</div>
                                                    </div>
                                                    <div class="col-6 col-md-3">
                                                        <div class="display-5 fw-bold text-warning" id="coinsEarnedTotal">0</div>
                                                        <div class="text-muted small">COINS EARNED</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row justify-content-center mb-4">
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header">
                                                <h5 class="mb-0">Your Financial Literacy Level</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="text-center mb-3">
                                                    <div class="financial-level mx-auto" id="financialLevelMeter">
                                                        <div class="level-fill" style="width: 0%;"></div>
                                                        <div class="level-text">Beginner</div>
                                                    </div>
                                                </div>
                                                <div class="text-center" id="levelDescription">
                                                    Keep learning to improve your financial knowledge!
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row justify-content-center mb-4" id="weakAreasContainer">
                                    <div class="col-md-8">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h5 class="mb-0">Areas to Improve</h5>
                                            </div>
                                            <div class="card-body">
                                                <div id="weakAreasList">
                                                    <!-- Weak areas will be added by JavaScript -->
                                                    <p class="text-muted mb-0" id="noWeakAreas">Great job! You performed well across all categories.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-center gap-3">
                                    <button class="btn btn-primary" id="reviewAnswersBtn">
                                        <i class="fas fa-redo me-2"></i> Review Answers
                                    </button>
                                    <button class="btn btn-outline-primary" id="tryAgainBtn">
                                        <i class="fas fa-sync-alt me-2"></i> Try Again
                                    </button>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-home me-2"></i> Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quiz Data (hidden) -->
<div id="quizData" 
     data-questions='<?php echo json_encode($all_questions); ?>'
     data-categories='<?php echo json_encode(array_map(function($cat) { return $cat['title']; }, $quiz_data)); ?>'>
</div>

<!-- Confirmation Modal -->
<div class="modal fade" id="confirmQuitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quit Quiz?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to quit the quiz? Your progress will be lost.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmQuit">Quit Quiz</button>
            </div>
        </div>
    </div>
</div>

<!-- Category Selection Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Quiz Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group">
                    <?php foreach ($quiz_data as $key => $category): ?>
                    <a href="#" class="list-group-item list-group-item-action" data-category="<?php echo $key; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">
                                <i class="fas fa-<?php echo $category['icon']; ?> me-2 text-primary"></i>
                                <?php echo $category['title']; ?>
                            </h6>
                            <small class="text-muted"><?php echo count($category['questions']); ?> questions</small>
                        </div>
                        <p class="mb-1 small text-muted"><?php echo $category['description'] ?? ''; ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="streakToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-warning text-dark">
            <strong class="me-auto">Streak Bonus!</strong>
            <small>Just now</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <div class="d-flex align-items-center">
                <i class="fas fa-fire text-warning me-2"></i>
                <span id="streakToastText">You've answered 3 questions in a row correctly! +5 bonus coins</span>
            </div>
        </div>
    </div>
    
    <div id="levelUpToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Level Up!</strong>
            <small>Just now</small>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <div class="d-flex align-items-center">
                <i class="fas fa-trophy text-warning me-2"></i>
                <span>Congratulations! You've reached <strong id="newLevelName">Smart Saver</strong> level!</span>
            </div>
        </div>
    </div>
</div>

<!-- Include JavaScript -->
<script src="assets/js/quiz.js"></script>

<style>
/* Quiz Module Styles */
.quiz-module .category-card {
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
    border: 1px solid rgba(0,0,0,0.05);
}

.quiz-module .category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
}

.quiz-module .category-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(67, 97, 238, 0.1);
    border-radius: 50%;
}

/* Timer Circle */
.timer-circle {
    position: relative;
    width: 40px;
    height: 40px;
}

.timer-svg {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}

.timer-circle-bg {
    fill: none;
}

.timer-circle {
    stroke-dasharray: 100;
    stroke-dashoffset: 0;
    transition: stroke-dashoffset 1s linear;
}

.timer-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: bold;
    font-size: 0.9rem;
}

/* Question Card */
.question-card {
    background-color: #fff;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.question-text {
    font-size: 1.25rem;
    font-weight: 500;
    line-height: 1.4;
}

.option-item {
    padding: 0.75rem 1rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    transition: all 0.2s;
    margin-bottom: 0.75rem;
}

.option-item:hover {
    background-color: #f8f9fa;
}

.option-item.correct {
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.option-item.incorrect {
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.option-item.selected {
    background-color: #e2e9ff;
    border-color: #b8cffc;
}

/* Financial Level Meter */
.financial-level {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background: #f0f0f0;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
}

.level-fill {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 0%;
    background: linear-gradient(to top, #4CAF50, #8BC34A);
    transition: height 1s ease-in-out;
}

.level-text {
    position: relative;
    z-index: 1;
    font-size: 1.25rem;
    font-weight: bold;
    color: #333;
    text-align: center;
    padding: 1rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .quiz-module .category-card {
        margin-bottom: 1rem;
    }
    
    .question-text {
        font-size: 1.1rem;
    }
    
    .financial-level {
        width: 150px;
        height: 150px;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}

/* Streak Indicator */
.streak-indicator {
    display: inline-flex;
    align-items: center;
    background-color: #fff3cd;
    color: #856404;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.streak-indicator .fire-icon {
    color: #ff6b35;
    margin-right: 0.25rem;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

/* Badge for correct answers */
.correct-badge {
    position: absolute;
    top: -10px;
    right: -10px;
    background-color: #28a745;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
</style>

<?php include 'includes/footer.php'; ?>
