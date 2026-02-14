// Main JavaScript for FinWise Financial Literacy Platform

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Handle module card clicks
    const moduleCards = document.querySelectorAll('.module-card');
    moduleCards.forEach(card => {
        card.addEventListener('click', function() {
            // Add click effect
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Animate progress bars on scroll
    const animateOnScroll = function() {
        const progressBars = document.querySelectorAll('.progress-bar');
        progressBars.forEach(bar => {
            const barPosition = bar.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.3;
            
            if (barPosition < screenPosition) {
                const width = bar.getAttribute('aria-valuenow');
                bar.style.width = width + '%';
            }
        });
    };

    // Run animation on scroll
    window.addEventListener('scroll', animateOnScroll);
    // Run once on page load
    animateOnScroll();

    // Handle form submissions with AJAX
    const forms = document.querySelectorAll('.ajax-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            // Simulate API call (replace with actual fetch/AJAX call)
            setTimeout(() => {
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success mt-3';
                alertDiv.role = 'alert';
                alertDiv.textContent = 'Changes saved successfully!';
                this.appendChild(alertDiv);
                
                // Reset form and button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                // Remove alert after 3 seconds
                setTimeout(() => {
                    alertDiv.remove();
                }, 3000);
                
                // If this is a budget form, update the financial health score
                if (this.id === 'budget-form') {
                    updateFinancialHealthScore();
                }
            }, 1000);
        });
    });

    // Handle challenge completion toggles
    const challengeToggles = document.querySelectorAll('.challenge-toggle');
    challengeToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const challengeId = this.getAttribute('data-challenge-id');
            const isCompleted = this.checked;
            
            // Update UI
            const challengeCard = this.closest('.challenge-card');
            if (isCompleted) {
                challengeCard.classList.add('completed');
                // Add coins and XP (simulated)
                addCoins(5);
                addXp(5);
                
                // Show completion message
                const completionBadge = document.createElement('span');
                completionBadge.className = 'badge bg-success ms-2';
                completionBadge.innerHTML = '<i class="fas fa-check me-1"></i> +5 XP';
                challengeCard.querySelector('.challenge-content h6').appendChild(completionBadge);
                
                // Disable toggle after completion
                this.disabled = true;
            }
            
            // In a real app, you would send this to your backend
            console.log(`Challenge ${challengeId} ${isCompleted ? 'completed' : 'incomplete'}`);
        });
    });

    // Initialize financial health score animation
    const animateFinancialHealthScore = () => {
        const scoreElement = document.querySelector('.financial-health-score .score');
        if (!scoreElement) return;
        
        const targetScore = parseInt(scoreElement.textContent);
        let currentScore = 0;
        const duration = 1500; // Animation duration in ms
        const increment = Math.ceil(targetScore / (duration / 16)); // 60fps
        
        const animate = () => {
            if (currentScore < targetScore) {
                currentScore = Math.min(currentScore + increment, targetScore);
                scoreElement.textContent = currentScore;
                requestAnimationFrame(animate);
            }
        };
        
        animate();
    };
    
    // Run animations when the page loads
    animateFinancialHealthScore();
});

// Function to add coins to user's balance
function addCoins(amount) {
    const coinElement = document.getElementById('coin-count');
    if (!coinElement) return;
    
    let currentCoins = parseInt(coinElement.textContent) || 0;
    const newTotal = currentCoins + amount;
    
    // Animate the coin count
    animateValue(coinElement, currentCoins, newTotal, 1000);
    
    // In a real app, you would update this on the server
    console.log(`Added ${amount} coins. New total: ${newTotal}`);
}

// Function to add XP to user's profile
function addXp(amount) {
    const xpElement = document.querySelector('.xp-count');
    if (!xpElement) return;
    
    let currentXp = parseInt(xpElement.textContent) || 0;
    const newTotal = currentXp + amount;
    
    // Animate the XP count
    animateValue(xpElement, currentXp, newTotal, 1000);
    
    // Check for level up
    checkLevelUp(newTotal);
    
    // In a real app, you would update this on the server
    console.log(`Added ${amount} XP. New total: ${newTotal}`);
}

// Function to check if user has leveled up
function checkLevelUp(xp) {
    // Define level thresholds
    const levels = {
        0: 'Beginner',
        50: 'Smart Saver',
        150: 'Investor',
        300: 'Financial Pro'
    };
    
    // Find current and next level
    let currentLevel = 'Beginner';
    let nextLevel = null;
    let nextLevelXp = 50;
    
    for (const [xpNeeded, level] of Object.entries(levels)) {
        if (xp >= parseInt(xpNeeded)) {
            currentLevel = level;
        } else if (nextLevel === null) {
            nextLevel = level;
            nextLevelXp = parseInt(xpNeeded);
            break;
        }
    }
    
    // Update level display if needed
    const levelElement = document.querySelector('.user-level');
    if (levelElement) {
        levelElement.textContent = currentLevel;
    }
    
    // Check if user leveled up
    const currentXp = xp || 0;
    const previousLevelXp = Object.keys(levels).filter(xp => xp <= currentXp).pop();
    
    if (currentLevel !== levels[previousLevelXp]) {
        // Show level up modal or notification
        showLevelUpModal(currentLevel);
    }
    
    // Update progress bar
    const progressBar = document.querySelector('.level-progress .progress-bar');
    if (progressBar) {
        const progress = ((currentXp - previousLevelXp) / (nextLevelXp - previousLevelXp)) * 100;
        progressBar.style.width = `${Math.min(100, progress)}%`;
        progressBar.setAttribute('aria-valuenow', Math.min(100, progress));
    }
}

// Function to show level up modal
function showLevelUpModal(level) {
    // In a real app, you would show a modal with confetti animation
    console.log(`🎉 Level Up! You are now a ${level}!`);
    
    // Example of how you might show a notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
    notification.role = 'alert';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-trophy fa-2x me-3"></i>
            <div>
                <h5 class="alert-heading mb-1">Level Up! 🎉</h5>
                <p class="mb-0">You've reached the <strong>${level}</strong> level!</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 150);
    }, 5000);
}

// Helper function to animate numeric values
function animateValue(element, start, end, duration) {
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        element.textContent = value.toLocaleString();
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Function to update financial health score
function updateFinancialHealthScore() {
    // In a real app, this would calculate based on user's financial data
    const score = Math.floor(Math.random() * 30) + 70; // Random score between 70-100 for demo
    const scoreElement = document.querySelector('.financial-health-score .score');
    
    if (scoreElement) {
        animateValue(scoreElement, 0, score, 1500);
    }
    
    // Update the score category
    const scoreCategory = document.querySelector('.score-category');
    if (scoreCategory) {
        let category = '';
        let categoryClass = '';
        
        if (score >= 90) {
            category = 'Excellent';
            categoryClass = 'text-success';
        } else if (score >= 70) {
            category = 'Good';
            categoryClass = 'text-info';
        } else if (score >= 50) {
            category = 'Fair';
            categoryClass = 'text-warning';
        } else {
            category = 'Needs Improvement';
            categoryClass = 'text-danger';
        }
        
        scoreCategory.textContent = category;
        scoreCategory.className = `fw-bold ${categoryClass}`;
    }
}

// Function to handle quiz answer submission
function submitQuizAnswer(questionId, selectedOption) {
    // In a real app, this would send the answer to the server
    console.log(`Question ${questionId}: Selected option ${selectedOption}`);
    
    // Show feedback for the selected answer
    const options = document.querySelectorAll(`#question-${questionId} .option`);
    options.forEach((option, index) => {
        if (index === selectedOption) {
            option.classList.add('selected');
            // In a real app, you would check if this is the correct answer
            const isCorrect = Math.random() > 0.5; // 50% chance of being correct for demo
            
            if (isCorrect) {
                option.classList.add('correct');
                addCoins(10);
                showFeedback('correct', 'Great job! You got it right!');
            } else {
                option.classList.add('incorrect');
                showFeedback('incorrect', 'Not quite. Here\'s why...');
            }
            
            // Disable all options after selection
            options.forEach(opt => opt.style.pointerEvents = 'none');
        }
    });
}

// Function to show feedback for quiz answers
function showFeedback(type, message) {
    const feedbackElement = document.getElementById('quiz-feedback');
    if (!feedbackElement) return;
    
    feedbackElement.className = `alert alert-${type === 'correct' ? 'success' : 'danger'}`;
    feedbackElement.textContent = message;
    feedbackElement.style.display = 'block';
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        feedbackElement.style.display = 'none';
    }, 3000);
}

// Function to format currency (Indian Rupees)
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        maximumFractionDigits: 0
    }).format(amount);
}

// Function to handle budget form submission
function handleBudgetSubmit(e) {
    e.preventDefault();
    
    // Get form values
    const income = parseFloat(document.getElementById('income').value) || 0;
    const rent = parseFloat(document.getElementById('rent').value) || 0;
    const food = parseFloat(document.getElementById('food').value) || 0;
    const travel = parseFloat(document.getElementById('travel').value) || 0;
    const entertainment = parseFloat(document.getElementById('entertainment').value) || 0;
    const other = parseFloat(document.getElementById('other').value) || 0;
    
    // Calculate totals
    const totalExpenses = rent + food + travel + entertainment + other;
    const savings = income - totalExpenses;
    const savingsPercentage = income > 0 ? (savings / income) * 100 : 0;
    
    // Update UI with results
    document.getElementById('total-expenses').textContent = formatCurrency(totalExpenses);
    document.getElementById('total-savings').textContent = formatCurrency(savings);
    document.getElementById('savings-percentage').textContent = savingsPercentage.toFixed(1) + '%';
    
    // Show suggestion based on 50/30/20 rule
    const suggestionElement = document.getElementById('suggestion');
    if (savingsPercentage < 20) {
        const suggestedSavings = income * 0.2;
        const additionalNeeded = suggestedSavings - savings;
        suggestionElement.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Tip:</strong> Try to save at least 20% of your income (${formatCurrency(suggestedSavings)}). 
                You need to save ${formatCurrency(additionalNeeded)} more this month.
            </div>
        `;
    } else {
        suggestionElement.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Great job!</strong> You're saving ${savingsPercentage.toFixed(1)}% of your income, 
                which meets the recommended 20% savings goal.
            </div>
        `;
        
        // Award coins for good savings
        addCoins(15);
        addXp(10);
    }
    
    // Show the results section
    document.getElementById('budget-results').classList.remove('d-none');
    
    // Scroll to results
    document.getElementById('budget-results').scrollIntoView({ behavior: 'smooth' });
    
    // In a real app, you would save this data to the server
    console.log('Budget saved:', { income, expenses: { rent, food, travel, entertainment, other }, savings, savingsPercentage });
}

// Initialize event listeners when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listener for budget form submission
    const budgetForm = document.getElementById('budget-form');
    if (budgetForm) {
        budgetForm.addEventListener('submit', handleBudgetSubmit);
    }
    
    // Add event listeners for quiz options
    const quizOptions = document.querySelectorAll('.quiz-option');
    quizOptions.forEach(option => {
        option.addEventListener('click', function() {
            const questionId = this.closest('.question').getAttribute('data-question-id');
            const optionIndex = this.getAttribute('data-option-index');
            submitQuizAnswer(questionId, parseInt(optionIndex));
        });
    });
    
    // Initialize any tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
