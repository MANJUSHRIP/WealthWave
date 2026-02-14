// Savings & Emergency Fund Module JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Emergency Fund Calculator
    const emergencyFundForm = document.getElementById('emergencyFundCalculator');
    if (emergencyFundForm) {
        emergencyFundForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const monthlyExpenses = parseFloat(document.getElementById('monthlyExpenses').value) || 0;
            const months = parseInt(document.getElementById('months').value) || 3;
            
            const targetAmount = monthlyExpenses * months;
            const monthlySaving = Math.ceil(targetAmount / 12); // Save over 1 year
            
            document.getElementById('targetAmount').textContent = targetAmount.toLocaleString();
            document.getElementById('monthlySaving').textContent = monthlySaving.toLocaleString();
            document.getElementById('monthsToSave').textContent = months;
            
            document.getElementById('calculationResult').style.display = 'block';
            
            // Animate the result
            const resultElement = document.querySelector('#calculationResult .alert');
            resultElement.classList.remove('fade-in');
            setTimeout(() => resultElement.classList.add('fade-in'), 10);
        });
    }

    // Challenge Questions
    let currentQuestion = 0;
    const questions = document.querySelectorAll('.challenge-question');
    const totalQuestions = questions.length;
    let score = 0;

    // Show first question
    if (questions.length > 0) {
        showQuestion(0);
    }

    // Handle next question button
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('next-question')) {
            const currentQuestionEl = document.querySelector(`[data-question="${currentQuestion}"]`);
            const selectedOption = currentQuestionEl.querySelector('input[type="radio"]:checked');
            
            if (!selectedOption) {
                showToast('Please select an answer before continuing.', 'warning');
                return;
            }

            const isCorrect = selectedOption.dataset.correct === 'true';
            
            // Update score
            if (isCorrect) {
                score++;
                updateScore(score);
            }

            // Show feedback
            const feedbackEl = currentQuestionEl.querySelector('.feedback');
            feedbackEl.style.display = 'block';
            feedbackEl.classList.add('fade-in');
            
            // Show next question or complete challenge
            if (currentQuestion < totalQuestions - 1) {
                setTimeout(() => {
                    showQuestion(currentQuestion + 1);
                }, 1500);
            } else {
                // Challenge complete
                setTimeout(completeChallenge, 1500);
            }
        }
    });

    // Show question by index
    function showQuestion(index) {
        // Hide all questions
        questions.forEach((q, i) => {
            q.style.display = 'none';
            q.classList.remove('active');
        });
        
        // Show current question
        currentQuestion = index;
        const currentQuestionEl = questions[index];
        currentQuestionEl.style.display = 'block';
        
        // Add active class with delay for animation
        setTimeout(() => {
            currentQuestionEl.classList.add('active');
        }, 50);
        
        // Update progress
        updateProgress(index + 1);
    }

    // Update progress bar
    function updateProgress(completed) {
        const progress = (completed / totalQuestions) * 100;
        const progressBar = document.querySelector('.challenge-progress .progress-bar');
        const progressText = document.querySelector('.challenge-progress .progress-text');
        
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute('aria-valuenow', progress);
            progressBar.textContent = `${Math.round(progress)}%`;
        }
        
        if (progressText) {
            progressText.textContent = `Question ${completed} of ${totalQuestions}`;
        }
    }

    // Update score display
    function updateScore(newScore) {
        const scoreElement = document.getElementById('challengeScore');
        if (scoreElement) {
            scoreElement.textContent = newScore;
            scoreElement.classList.add('score-updated');
            setTimeout(() => scoreElement.classList.remove('score-updated'), 1000);
        }
    }

    // Complete challenge
    function completeChallenge() {
        const challengeComplete = document.getElementById('challengeComplete');
        const challengeContainer = document.getElementById('challengeContainer');
        const finalScore = document.getElementById('finalScore');
        
        if (challengeComplete && finalScore) {
            // Calculate score percentage
            const percentage = Math.round((score / totalQuestions) * 100);
            finalScore.textContent = `${score}/${totalQuestions} (${percentage}%)`;
            
            // Show completion message
            challengeContainer.style.display = 'none';
            challengeComplete.style.display = 'block';
            challengeComplete.classList.add('fade-in');
            
            // Award XP and coins
            const xpEarned = Math.ceil(percentage / 10) * 5; // 5 XP per 10%
            const coinsEarned = Math.ceil(percentage / 20) * 2; // 1 coin per 20%
            
            document.getElementById('xpEarned').textContent = xpEarned;
            document.getElementById('coinsEarned').textContent = coinsEarned;
            
            // Update user stats (in a real app, this would be an API call)
            updateUserStats(xpEarned, coinsEarned);
            
            // Check for level up
            checkLevelUp();
        }
    }

    // Update user stats (simulated)
    function updateUserStats(xp, coins) {
        // In a real app, this would be an API call to update the user's stats
        console.log(`User earned ${xp} XP and ${coins} coins`);
        
        // Update the UI
        const xpElement = document.querySelector('.xp-count');
        const coinsElement = document.querySelector('.coins-count');
        
        if (xpElement) {
            const currentXP = parseInt(xpElement.textContent) || 0;
            xpElement.textContent = currentXP + xp;
        }
        
        if (coinsElement) {
            const currentCoins = parseInt(coinsElement.textContent) || 0;
            coinsElement.textContent = currentCoins + coins;
        }
    }

    // Check for level up (simulated)
    function checkLevelUp() {
        // In a real app, this would check the user's XP against level thresholds
        const xpElement = document.querySelector('.xp-count');
        if (!xpElement) return;
        
        const currentXP = parseInt(xpElement.textContent) || 0;
        
        // Example level thresholds
        const levels = [
            { xp: 0, name: 'Beginner' },
            { xp: 100, name: 'Saver' },
            { xp: 300, name: 'Investor' },
            { xp: 600, name: 'Financial Pro' }
        ];
        
        let currentLevel = levels[0];
        let nextLevel = levels[1];
        
        for (let i = 0; i < levels.length - 1; i++) {
            if (currentXP >= levels[i].xp && currentXP < levels[i + 1].xp) {
                currentLevel = levels[i];
                nextLevel = levels[i + 1];
                break;
            }
        }
        
        // Check if user leveled up
        const xpElementBefore = document.querySelector('.xp-count').textContent;
        const xpBefore = parseInt(xpElementBefore) - 100; // Simulate level up if XP increased by 100
        
        for (let i = 0; i < levels.length - 1; i++) {
            if (xpBefore < levels[i + 1].xp && currentXP >= levels[i + 1].xp) {
                // Level up!
                showLevelUpModal(levels[i + 1].name);
                break;
            }
        }
    }

    // Show level up modal
    function showLevelUpModal(levelName) {
        const modal = new bootstrap.Modal(document.getElementById('levelUpModal'));
        const levelNameElement = document.getElementById('levelUpName');
        
        if (levelNameElement) {
            levelNameElement.textContent = levelName;
            modal.show();
        }
    }

    // Show toast notification
    function showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) return;
        
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
        
        toastContainer.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    // Initialize quiz functionality
    const startQuizBtn = document.getElementById('startQuiz');
    if (startQuizBtn) {
        startQuizBtn.addEventListener('click', startQuiz);
    }

    // Start quiz
    function startQuiz() {
        const quizContainer = document.getElementById('quizContainer');
        if (!quizContainer) return;
        
        // In a real app, this would load questions from an API
        const questions = [
            {
                question: 'What is the recommended emergency fund duration?',
                options: [
                    '1-2 months of expenses',
                    '3-6 months of expenses',
                    '6-12 months of expenses',
                    '1-2 years of expenses'
                ],
                correct: 1,
                explanation: 'Financial experts recommend having 3-6 months of living expenses in your emergency fund.'
            },
            // Add more questions here
        ];
        
        // Render first question
        renderQuestion(0, questions);
    }

    // Render quiz question
    function renderQuestion(index, questions) {
        if (index >= questions.length) {
            // Quiz complete
            showQuizResults(questions);
            return;
        }
        
        const question = questions[index];
        const quizContainer = document.getElementById('quizContainer');
        
        if (!quizContainer) return;
        
        // Render question
        quizContainer.innerHTML = `
            <div class="quiz-question">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Question ${index + 1}/${questions.length}</h5>
                    <span class="badge bg-primary">+10 XP per correct answer</span>
                </div>
                <p class="fw-bold">${question.question}</p>
                <div class="options">
                    ${question.options.map((option, i) => `
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" 
                                   name="quizQuestion" 
                                   id="quizOption${i}" 
                                   value="${i}"
                                   data-correct="${i === question.correct}">
                            <label class="form-check-label" for="quizOption${i}">
                                ${option}
                            </label>
                        </div>
                    `).join('')}
                </div>
                <button class="btn btn-primary mt-3" id="submitQuizAnswer">
                    ${index === questions.length - 1 ? 'Finish Quiz' : 'Next Question'}
                </button>
                <div class="quiz-feedback mt-3" style="display: none;">
                    <div class="alert"></div>
                </div>
            </div>
        `;
        
        // Add event listener for answer submission
        document.getElementById('submitQuizAnswer')?.addEventListener('click', function() {
            const selectedOption = document.querySelector('input[name="quizQuestion"]:checked');
            const feedbackEl = document.querySelector('.quiz-feedback');
            const feedbackAlert = feedbackEl?.querySelector('.alert');
            
            if (!selectedOption) {
                showToast('Please select an answer.', 'warning');
                return;
            }
            
            const isCorrect = selectedOption.dataset.correct === 'true';
            
            // Show feedback
            if (feedbackEl && feedbackAlert) {
                feedbackEl.style.display = 'block';
                
                if (isCorrect) {
                    feedbackAlert.className = 'alert alert-success';
                    feedbackAlert.innerHTML = `
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Correct!</strong> ${question.explanation}
                    `;
                    
                    // Award XP (in a real app, this would be handled server-side)
                    updateUserStats(10, 1);
                } else {
                    feedbackAlert.className = 'alert alert-danger';
                    feedbackAlert.innerHTML = `
                        <i class="fas fa-times-circle me-2"></i>
                        <strong>Incorrect.</strong> ${question.explanation}
                    `;
                }
                
                // Scroll to feedback
                feedbackEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                
                // Disable all options
                document.querySelectorAll('input[name="quizQuestion"]').forEach(input => {
                    input.disabled = true;
                });
                
                // Update button for next question
                const submitBtn = document.getElementById('submitQuizAnswer');
                if (submitBtn) {
                    submitBtn.textContent = index === questions.length - 1 ? 'View Results' : 'Next Question';
                    submitBtn.onclick = function() {
                        renderQuestion(index + 1, questions);
                    };
                }
            }
        });
    }
    
    // Show quiz results
    function showQuizResults(questions) {
        const quizContainer = document.getElementById('quizContainer');
        if (!quizContainer) return;
        
        // In a real app, this would calculate the actual score from the user's answers
        const score = Math.floor(Math.random() * questions.length) + 1;
        const percentage = Math.round((score / questions.length) * 100);
        
        // Determine result message
        let resultMessage = '';
        let resultClass = '';
        
        if (percentage >= 80) {
            resultMessage = 'Excellent! You have a strong understanding of savings and emergency funds.';
            resultClass = 'success';
        } else if (percentage >= 50) {
            resultMessage = 'Good job! You have a basic understanding, but there\'s room for improvement.';
            resultClass = 'info';
        } else {
            resultMessage = 'Keep learning! Review the material and try again to improve your score.';
            resultClass = 'warning';
        }
        
        // Render results
        quizContainer.innerHTML = `
            <div class="quiz-results text-center">
                <div class="result-icon mb-3">
                    <i class="fas fa-${percentage >= 50 ? 'trophy' : 'book'}-open fa-4x text-${resultClass}"></i>
                </div>
                <h3 class="mb-3">Quiz Complete!</h3>
                <div class="score-display mb-4">
                    <div class="score-circle mx-auto">
                        <span class="score-value">${percentage}%</span>
                        <span class="score-label">Your Score</span>
                    </div>
                    <p class="mt-3">${score} out of ${questions.length} questions correct</p>
                </div>
                <div class="alert alert-${resultClass}">
                    <i class="fas fa-${percentage >= 50 ? 'check' : 'info'}-circle me-2"></i>
                    ${resultMessage}
                </div>
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                    <button class="btn btn-primary btn-lg px-4 me-sm-3" id="reviewQuiz">
                        <i class="fas fa-redo me-2"></i> Review Answers
                    </button>
                    <button class="btn btn-outline-secondary btn-lg px-4" id="backToDashboard">
                        <i class="fas fa-home me-2"></i> Back to Dashboard
                    </button>
                </div>
            </div>
        `;
        
        // Add event listeners
        document.getElementById('reviewQuiz')?.addEventListener('click', function() {
            // In a real app, this would show the questions with correct/incorrect answers
            alert('In the full version, this would show a review of your answers.');
        });
        
        document.getElementById('backToDashboard')?.addEventListener('click', function() {
            window.location.href = 'dashboard.php';
        });
    }
});
