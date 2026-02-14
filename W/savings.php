<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user']['name'])) {
    header('Location: quiz.php');
    exit();
}

$user = $_SESSION['user'];
$page_title = 'Savings & Emergency Fund';
$body_class = 'savings-module';

// Include header
include 'includes/header.php';

// Sample questions and content
$module = [
    'title' => 'Savings & Emergency Fund',
    'description' => 'Learn how to build and manage your emergency fund and develop smart saving habits.',
    'sections' => [
        'emergency_fund' => [
            'title' => 'Emergency Fund Basics',
            'content' => 'An emergency fund is money set aside to cover unexpected expenses or financial emergencies, like medical bills or job loss. Experts recommend saving 3-6 months of living expenses.',
            'image' => 'emergency-fund.svg'
        ],
        'saving_habits' => [
            'title' => 'Smart Saving Habits',
            'content' => 'Developing good saving habits is crucial for financial stability. Start by saving at least 20% of your income and gradually increase it.',
            'tips' => [
                'Pay yourself first',
                'Automate your savings',
                'Cut unnecessary expenses',
                'Set specific savings goals'
            ]
        ],
        'sip' => [
            'title' => 'Systematic Investment Plan (SIP)',
            'content' => 'SIP is a method of investing a fixed amount regularly in mutual funds. It helps in rupee cost averaging and compounding returns.',
            'example' => 'Investing ₹5,000 monthly in an SIP with 12% annual return can grow to ~₹11.5 lakhs in 10 years.'
        ],
        'fd_vs_rd' => [
            'title' => 'FD vs RD',
            'content' => [
                'Fixed Deposit (FD)' => 'Lump sum investment for a fixed tenure with fixed returns.',
                'Recurring Deposit (RD)' => 'Regular fixed monthly investments with fixed returns.'
            ],
            'comparison' => [
                'Returns' => 'FD typically offers slightly higher interest rates than RD',
                'Liquidity' => 'FD has a lock-in period, while RD allows monthly withdrawals',
                'Flexibility' => 'RD is better for regular savers, FD for lump sum investors'
            ]
        ],
        'inflation' => [
            'title' => 'Understanding Inflation',
            'content' => 'Inflation reduces the purchasing power of money over time. Your investments should ideally beat inflation to grow your wealth in real terms.',
            'example' => 'With 6% inflation, what costs ₹100 today will cost ~₹179 in 10 years.'
        ]
    ],
    'challenge' => [
        'title' => 'Emergency Fund Challenge',
        'scenario' => 'Build a 3-month emergency fund for someone who spends ₹8,000 per month.',
        'goal' => 24000, // 3 * 8000
        'questions' => [
            [
                'question' => 'How much should be the target emergency fund amount?',
                'options' => [
                    '₹8,000',
                    '₹16,000',
                    '₹24,000',
                    '₹32,000'
                ],
                'correct' => 2,
                'explanation' => 'For a 3-month emergency fund, multiply monthly expenses by 3: ₹8,000 × 3 = ₹24,000.'
            ],
            [
                'question' => 'Where is the best place to keep an emergency fund?',
                'options' => [
                    'Stocks',
                    'Savings account or liquid fund',
                    'Real estate',
                    'Cryptocurrency'
                ],
                'correct' => 1,
                'explanation' => 'Emergency funds should be easily accessible. Savings accounts or liquid funds are ideal as they provide liquidity and safety.'
            ]
        ]
    ],
    'quiz' => [
        'title' => 'Test Your Knowledge',
        'questions' => [
            [
                'question' => 'What is the recommended emergency fund duration?',
                'options' => [
                    '1-2 months of expenses',
                    '3-6 months of expenses',
                    '6-12 months of expenses',
                    '1-2 years of expenses'
                ],
                'correct' => 1,
                'explanation' => 'Financial experts recommend having 3-6 months of living expenses in your emergency fund.',
                'category' => 'emergency_fund'
            ],
            // Add more questions here...
        ]
    ]
];
?>

<div class="savings-module">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo $module['title']; ?></li>
                </ol>
            </nav>
            <h2 class="mb-3"><?php echo $module['title']; ?></h2>
            <p class="lead"><?php echo $module['description']; ?></p>
        </div>
    </div>

    <!-- Module Sections -->
    <div class="row g-4">
        <?php foreach ($module['sections'] as $key => $section): ?>
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-<?php 
                            echo match($key) {
                                'emergency_fund' => 'shield-alt',
                                'saving_habits' => 'piggy-bank',
                                'sip' => 'chart-line',
                                'fd_vs_rd' => 'balance-scale',
                                'inflation' => 'rupee-sign',
                                default => 'info-circle'
                            }; 
                        ?> me-2 text-primary"></i>
                        <?php echo $section['title']; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (is_array($section['content'])): ?>
                        <?php foreach ($section['content'] as $subtitle => $content): ?>
                            <h6><?php echo $subtitle; ?></h6>
                            <p><?php echo $content; ?></p>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p><?php echo $section['content']; ?></p>
                    <?php endif; ?>

                    <?php if (isset($section['tips'])): ?>
                        <h6 class="mt-4">Tips:</h6>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($section['tips'] as $tip): ?>
                                <li class="list-group-item">
                                    <i class="fas fa-check-circle text-success me-2"></i> <?php echo $tip; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (isset($section['example'])): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-lightbulb me-2"></i> 
                            <strong>Example:</strong> <?php echo $section['example']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Emergency Fund Challenge -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-trophy me-2 text-warning"></i>
                        <?php echo $module['challenge']['title']; ?>
                        <span class="badge bg-success ms-2">+20 XP</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><?php echo $module['challenge']['scenario']; ?></h5>
                            <p>Complete this challenge to earn 20 XP and a Savings Star badge!</p>
                            
                            <div class="challenge-progress mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Progress</span>
                                    <span>0/<?php echo count($module['challenge']['questions']); ?> completed</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%" 
                                         aria-valuenow="0" aria-valuemin="0" 
                                         aria-valuemax="100">0%</div>
                                </div>
                            </div>

                            <div id="challengeQuestions">
                                <?php foreach ($module['challenge']['questions'] as $index => $question): ?>
                                <div class="question mb-4" data-question="<?php echo $index; ?>" 
                                     style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>">
                                    <h6>Question <?php echo $index + 1; ?></h6>
                                    <p class="fw-bold"><?php echo $question['question']; ?></p>
                                    <div class="options">
                                        <?php foreach ($question['options'] as $i => $option): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" 
                                                   name="q<?php echo $index; ?>" 
                                                   id="q<?php echo $index; ?>_<?php echo $i; ?>"
                                                   value="<?php echo $i; ?>">
                                            <label class="form-check-label" for="q<?php echo $index; ?>_<?php echo $i; ?>">
                                                <?php echo $option; ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="feedback mt-2" style="display: none;">
                                        <div class="alert"></div>
                                        <button class="btn btn-sm btn-primary next-question" 
                                                style="display: none;">
                                            Next Question
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                                <div id="challengeComplete" style="display: none;">
                                    <div class="alert alert-success">
                                        <h5><i class="fas fa-check-circle me-2"></i> Challenge Complete!</h5>
                                        <p>Congratulations! You've earned 20 XP and a Savings Star badge!</p>
                                    </div>
                                    <button class="btn btn-primary" id="claimReward">
                                        <i class="fas fa-gift me-2"></i> Claim Your Reward
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 bg-light">
                                <div class="card-body text-center">
                                    <div class="py-4">
                                        <i class="fas fa-piggy-bank fa-4x text-primary mb-3"></i>
                                        <h5>Emergency Fund Calculator</h5>
                                        <p class="text-muted">Calculate how much you need for your emergency fund</p>
                                        
                                        <form id="emergencyFundCalculator">
                                            <div class="mb-3">
                                                <label for="monthlyExpenses" class="form-label">Monthly Expenses (₹)</label>
                                                <input type="number" class="form-control" id="monthlyExpenses" 
                                                       value="8000" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="months" class="form-label">Months to Save For</label>
                                                <select class="form-select" id="months">
                                                    <option value="3">3 months</option>
                                                    <option value="6">6 months</option>
                                                    <option value="9">9 months</option>
                                                    <option value="12">12 months</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                Calculate
                                            </button>
                                        </form>
                                        
                                        <div id="calculationResult" class="mt-4" style="display: none;">
                                            <div class="alert alert-info">
                                                <h6>Your Emergency Fund Target:</h6>
                                                <h3 class="text-primary my-2">₹<span id="targetAmount">0</span></h3>
                                                <p class="mb-0 small">
                                                    Save ₹<span id="monthlySaving">0</span>/month for 
                                                    <span id="monthsToSave">0</span> months to reach your goal.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Knowledge Quiz -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle me-2 text-primary"></i>
                        Test Your Knowledge
                        <span class="badge bg-success ms-2">+10 XP per correct answer</span>
                    </h5>
                </div>
                <div class="card-body
                <div id="quizContainer">
                    <div class="text-center py-4">
                        <h5>Ready to test your knowledge?</h5>
                        <p>Answer 5 questions about savings and emergency funds to earn coins and XP!</p>
                        <button class="btn btn-primary" id="startQuiz">
                            <i class="fas fa-play me-2"></i> Start Quiz
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AI Feedback Modal -->
<div class="modal fade" id="aiFeedbackModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-robot text-primary me-2"></i> Financial Coach
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="aiFeedbackContent">
                    <!-- AI feedback will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="nextQuestionBtn">Next Question</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Emergency Fund Calculator
    const emergencyFundForm = document.getElementById('emergencyFundCalculator');
    const calculationResult = document.getElementById('calculationResult');
    const targetAmountEl = document.getElementById('targetAmount');
    const monthlySavingEl = document.getElementById('monthlySaving');
    const monthsToSaveEl = document.getElementById('monthsToSave');

    if (emergencyFundForm) {
        emergencyFundForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const monthlyExpenses = parseFloat(document.getElementById('monthlyExpenses').value) || 0;
            const months = parseInt(document.getElementById('months').value) || 3;
            
            const targetAmount = monthlyExpenses * months;
            const monthlySaving = Math.ceil(targetAmount / 12); // Save over 1 year
            
            targetAmountEl.textContent = targetAmount.toLocaleString();
            monthlySavingEl.textContent = monthlySaving.toLocaleString();
            monthsToSaveEl.textContent = months;
            
            calculationResult.style.display = 'block';
        });
    }

    // Challenge Questions
    const questions = <?php echo json_encode($module['challenge']['questions']); ?>;
    let currentQuestion = 0;
    const totalQuestions = questions.length;
    let score = 0;

    // Handle challenge question submission
    document.addEventListener('change', function(e) {
        if (e.target.matches('input[type="radio"]')) {
            const questionIndex = parseInt(e.target.closest('.question').dataset.question);
            const selectedOption = parseInt(e.target.value);
            const correctAnswer = questions[questionIndex].correct;
            const feedbackEl = e.target.closest('.question').querySelector('.feedback');
            const feedbackAlert = feedbackEl.querySelector('.alert');
            const nextBtn = feedbackEl.querySelector('.next-question');
            
            // Check answer
            const isCorrect = selectedOption === correctAnswer;
            
            // Update score
            if (isCorrect) {
                score++;
                feedbackAlert.className = 'alert alert-success';
                feedbackAlert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i> 
                    <strong>Correct!</strong> ${questions[questionIndex].explanation}
                `;
            } else {
                feedbackAlert.className = 'alert alert-danger';
                feedbackAlert.innerHTML = `
                    <i class="fas fa-times-circle me-2"></i> 
                    <strong>Incorrect.</strong> ${questions[questionIndex].explanation}
                `;
            }
            
            // Show feedback
            feedbackEl.style.display = 'block';
            
            // Show next button or complete challenge
            if (questionIndex < totalQuestions - 1) {
                nextBtn.style.display = 'block';
                nextBtn.onclick = function() {
                    showQuestion(questionIndex + 1);
                };
            } else {
                // Last question
                document.getElementById('challengeComplete').style.display = 'block';
                updateProgress(totalQuestions, totalQuestions);
            }
            
            // Update progress
            updateProgress(questionIndex + 1, totalQuestions);
        }
    });
    
    // Show question by index
    function showQuestion(index) {
        document.querySelectorAll('.question').forEach((q, i) => {
            q.style.display = i === index ? 'block' : 'none';
        });
        currentQuestion = index;
    }
    
    // Update progress bar
    function updateProgress(completed, total) {
        const progress = (completed / total) * 100;
        const progressBar = document.querySelector('.challenge-progress .progress-bar');
        const progressText = document.querySelector('.challenge-progress .progress-bar');
        const progressCount = document.querySelector('.challenge-progress span:last-child');
        
        progressBar.style.width = `${progress}%`;
        progressBar.setAttribute('aria-valuenow', progress);
        progressBar.textContent = `${Math.round(progress)}%`;
        progressCount.textContent = `${completed}/${total} completed`;
    }
    
    // Claim reward
    document.getElementById('claimReward')?.addEventListener('click', function() {
        // In a real app, this would update the user's XP and badges on the server
        showToast('🎉 You earned 20 XP and a Savings Star badge!', 'success');
        
        // Update UI
        const xpCount = document.querySelector('.xp-count');
        if (xpCount) {
            const currentXP = parseInt(xpCount.textContent) || 0;
            xpCount.textContent = currentXP + 20;
        }
        
        // Show badge in profile
        // This would be handled by the backend in a real app
        
        // Close challenge
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-check me-2"></i> Reward Claimed';
    });
    
    // Quiz functionality
    document.getElementById('startQuiz')?.addEventListener('click', function() {
        // In a real app, this would load questions from the server
        const quizContainer = document.getElementById('quizContainer');
        quizContainer.innerHTML = `
            <div class="quiz-question">
                <h5>Question 1/5</h5>
                <p class="fw-bold">What is the recommended emergency fund duration?</p>
                <div class="options">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="quizQuestion1" id="q1_1" value="0">
                        <label class="form-check-label" for="q1_1">1-2 months of expenses</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="quizQuestion1" id="q1_2" value="1">
                        <label class="form-check-label" for="q1_2">3-6 months of expenses</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="quizQuestion1" id="q1_3" value="2">
                        <label class="form-check-label" for="q1_3">6-12 months of expenses</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="quizQuestion1" id="q1_4" value="3">
                        <label class="form-check-label" for="q1_4">1-2 years of expenses</label>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" id="submitQuizAnswer">Submit Answer</button>
            </div>
        `;
        
        // Add event listener for quiz submission
        document.getElementById('submitQuizAnswer')?.addEventListener('click', function() {
            // In a real app, this would validate the answer and show feedback
            showAIFeedback(
                'You selected 3-6 months of expenses',
                'Correct! Financial experts recommend having 3-6 months of living expenses in your emergency fund.',
                'For example, if your monthly expenses are ₹20,000, aim to save between ₹60,000 to ₹1,20,000 in your emergency fund.',
                'Consider starting with a smaller goal (like ₹10,000) and gradually building up to 3-6 months of expenses.',
                'Great job! You\'re on your way to financial security!',
                true
            );
        });
    });
    
    // Show AI feedback
    function showAIFeedback(userAnswer, correctConcept, example, suggestion, motivation, isCorrect) {
        const modal = new bootstrap.Modal(document.getElementById('aiFeedbackModal'));
        const feedbackContent = document.getElementById('aiFeedbackContent');
        
        feedbackContent.innerHTML = `
            <div class="ai-feedback">
                <div class="alert ${isCorrect ? 'alert-success' : 'alert-danger'}">
                    <h6><i class="fas fa-${isCorrect ? 'check' : 'times'}-circle me-2"></i> 
                        ${isCorrect ? 'Correct!' : 'Not quite right'}
                    </h6>
                    <p class="mb-0">${userAnswer}</p>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h6><i class="fas fa-lightbulb text-warning me-2"></i> The Correct Concept</h6>
                        <p class="mb-0">${correctConcept}</p>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h6><i class="fas fa-rupee-sign text-success me-2"></i> Real-Life Example</h6>
                        <p class="mb-0">${example}</p>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h6><i class="fas fa-bullseye text-primary me-2"></i> Suggestion</h6>
                        <p class="mb-0">${suggestion}</p>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-heart me-2"></i>
                    <strong>Remember:</strong> ${motivation}
                </div>
            </div>
        `;
        
        // Show the modal
        modal.show();
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast show align-items-center text-white bg-${type} border-0`;
        toast.role = 'alert';
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        const toastContainer = document.querySelector('.toast-container');
        toastContainer.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
});
</script>

<style>
.savings-module .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    margin-bottom: 1.5rem;
}

.savings-module .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.savings-module .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    font-weight: 600;
}

.savings-module .progress {
    height: 10px;
    border-radius: 5px;
}

.savings-module .progress-bar {
    background-color: #4361ee;
    transition: width 0.6s ease;
}

.savings-module .form-check-input:checked {
    background-color: #4361ee;
    border-color: #4361ee;
}

.savings-module .btn-primary {
    background-color: #4361ee;
    border-color: #4361ee;
}

.savings-module .btn-primary:hover {
    background-color: #3a56d4;
    border-color: #3a56d4;
}

/* Animation for correct/incorrect answers */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.fade-in {
    animation: fadeIn 0.3s ease forwards;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .savings-module .card {
        margin-bottom: 1rem;
    }
    
    .savings-module h2 {
        font-size: 1.75rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
