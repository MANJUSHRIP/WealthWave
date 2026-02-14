<?php
// quiz.php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in to access the quiz');
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT points, level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0"><i class="fas fa-question-circle me-2"></i>Financial Literacy Quiz</h2>
                </div>
                <div class="card-body">
                    <div id="quiz-container">
                        <!-- Quiz content will be loaded here via JavaScript -->
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3">Loading quiz questions...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .quiz-question {
        display: none;
    }
    .quiz-question.active {
        display: block;
    }
    .option-btn {
        text-align: left;
        margin-bottom: 10px;
        transition: all 0.2s;
    }
    .option-btn:hover {
        transform: translateX(5px);
    }
    .progress {
        height: 10px;
        margin: 20px 0;
    }
    .feedback {
        display: none;
        margin-top: 20px;
        padding: 15px;
        border-radius: 5px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load quiz questions
    loadQuiz();
    
    async function loadQuiz() {
        try {
            const response = await fetch('modules/get_quiz.php');
            if (!response.ok) {
                throw new Error('Failed to load quiz');
            }
            const questions = await response.json();
            renderQuiz(questions);
        } catch (error) {
            document.getElementById('quiz-container').innerHTML = `
                <div class="alert alert-danger">
                    Error loading quiz: ${error.message}
                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="window.location.reload()">Retry</button>
                </div>
            `;
        }
    }

    function renderQuiz(questions) {
        const container = document.getElementById('quiz-container');
        let currentQuestion = 0;
        let score = 0;
        let userAnswers = [];

        function showQuestion(index) {
            if (index >= questions.length) {
                showResults();
                return;
            }

            const question = questions[index];
            const optionsHtml = question.options.map((option, i) => `
                <button class="btn btn-outline-primary w-100 option-btn" 
                        data-answer="${i}">
                    ${option}
                </button>
            `).join('');

            container.innerHTML = `
                <div class="quiz-question active">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="text-muted">
                            Question ${index + 1} of ${questions.length}
                        </div>
                        <div>
                            <span class="badge bg-primary">${question.points} points</span>
                        </div>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" 
                             role="progressbar" 
                             style="width: ${((index) / questions.length) * 100}%">
                        </div>
                    </div>
                    <h4 class="mb-4">${question.question}</h4>
                    <div class="options-container">
                        ${optionsHtml}
                    </div>
                    <div class="feedback mt-3" id="feedback"></div>
                    <div class="d-flex justify-content-between mt-4">
                        <button class="btn btn-outline-secondary" 
                                id="prev-btn" 
                                style="visibility: ${index === 0 ? 'hidden' : 'visible'}"
                                onclick="showQuestion(${index - 1})">
                            <i class="fas fa-arrow-left me-1"></i> Previous
                        </button>
                        <button class="btn btn-primary ms-auto" 
                                id="next-btn" 
                                style="display: none;"
                                onclick="showQuestion(${index + 1})">
                            Next <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            `;

            // Add event listeners to options
            document.querySelectorAll('.option-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const selectedAnswer = parseInt(this.dataset.answer);
                    const isCorrect = selectedAnswer === question.correctAnswer;
                    
                    // Disable all options
                    document.querySelectorAll('.option-btn').forEach(b => {
                        b.disabled = true;
                        b.classList.remove('btn-outline-primary');
                        
                        if (parseInt(b.dataset.answer) === question.correctAnswer) {
                            b.classList.add('btn-success');
                        } else if (parseInt(b.dataset.answer) === selectedAnswer && !isCorrect) {
                            b.classList.add('btn-danger');
                        } else {
                            b.classList.add('btn-outline-secondary');
                        }
                    });

                    // Show feedback
                    const feedback = document.getElementById('feedback');
                    feedback.innerHTML = `
                        <div class="alert ${isCorrect ? 'alert-success' : 'alert-danger'}">
                            <strong>${isCorrect ? 'Correct!' : 'Incorrect.'}</strong>
                            ${question.explanation || ''}
                        </div>
                    `;
                    feedback.style.display = 'block';

                    // Update score
                    if (isCorrect) {
                        score += question.points;
                    }

                    // Show next button
                    document.getElementById('next-btn').style.display = 'block';
                });
            });
        }

        function showResults() {
            const percentage = Math.round((score / questions.reduce((a, q) => a + q.points, 0)) * 100);
            const passed = percentage >= 70;

            container.innerHTML = `
                <div class="text-center py-4">
                    <div class="display-4 ${passed ? 'text-success' : 'text-danger'} mb-3">
                        <i class="fas ${passed ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                    </div>
                    <h2>${passed ? 'Quiz Complete!' : 'Try Again'}</h2>
                    <p class="lead">Your score: ${score} points (${percentage}%)</p>
                    
                    ${!passed ? `
                        <p class="text-muted">You need 70% to pass. Would you like to try again?</p>
                        <button class="btn btn-primary" onclick="window.location.reload()">
                            <i class="fas fa-redo me-2"></i>Retry Quiz
                        </button>
                    ` : `
                        <div class="alert alert-success">
                            <i class="fas fa-trophy me-2"></i>
                            Congratulations! You've earned ${score} points!
                        </div>
                        <button class="btn btn-outline-primary" onclick="window.location.reload()">
                            <i class="fas fa-home me-2"></i>Back to Dashboard
                        </button>
                    `}
                </div>
            `;

            // Submit results to server
            if (passed) {
                submitQuizResults(score);
            }
        }

        // Start the quiz
        showQuestion(0);
    }

    async function submitQuizResults(score) {
        try {
            const response = await fetch('modules/submit_quiz.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ score: score })
            });
            
            if (!response.ok) {
                throw new Error('Failed to save quiz results');
            }
            
            const result = await response.json();
            console.log('Quiz results saved:', result);
            
            // Update points display if on the page
            const pointsBadge = document.querySelector('.navbar .badge.bg-success');
            if (pointsBadge && result.newPoints) {
                pointsBadge.textContent = `${result.newPoints} Points`;
            }
            
        } catch (error) {
            console.error('Error saving quiz results:', error);
        }
    }
});
</script>
