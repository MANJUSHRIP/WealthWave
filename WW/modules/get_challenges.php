<!-- modules/challenges.php -->
<div class="challenges-module">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Daily Challenges</h2>
        <div>
            <span class="badge bg-primary">Level: <?= $user['level'] ?? 'Beginner' ?></span>
            <span class="badge bg-success ms-2">Points: <?= $user['points'] ?? 0 ?></span>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-tasks me-2"></i> Available Challenges
                </div>
                <div class="card-body" id="challenges-list">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading challenges...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sample challenges (replace with API call to get_challenges.php)
    const sampleChallenges = [
        {
            id: 1,
            title: "Track Your Spending",
            description: "Record all your expenses for today in the budget planner.",
            points: 20,
            category: "budgeting",
            completed: false
        },
        {
            id: 2,
            title: "Emergency Fund Check",
            description: "Check if you have at least 3 months of expenses saved.",
            points: 15,
            category: "savings",
            completed: true
        },
        {
            id: 3,
            title: "Financial Quiz",
            description: "Complete the financial literacy quiz with 80% or higher.",
            points: 30,
            category: "education",
            completed: false
        },
        {
            id: 4,
            title: "Review Subscriptions",
            description: "Cancel at least one unused subscription service.",
            points: 25,
            category: "savings",
            completed: false
        }
    ];
    
    // Load challenges
    loadChallenges();
    
    async function loadChallenges() {
        try {
            // In a real app, fetch from the server:
            // const response = await fetch('modules/get_challenges.php');
            // const data = await response.json();
            
            // For now, use sample data
            const data = { success: true, challenges: sampleChallenges };
            
            if (data.success) {
                displayChallenges(data.challenges);
            } else {
                throw new Error(data.message || 'Failed to load challenges');
            }
        } catch (error) {
            document.getElementById('challenges-list').innerHTML = `
                <div class="alert alert-danger m-3">
                    Error loading challenges: ${error.message}
                    <button class="btn btn-sm btn-outline-secondary ms-2" onclick="loadChallenges()">
                        <i class="fas fa-sync-alt me-1"></i> Retry
                    </button>
                </div>
            `;
        }
    }
    
    function displayChallenges(challenges) {
        const container = document.getElementById('challenges-list');
        
        if (!challenges || challenges.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No challenges available</h5>
                    <p>Check back later for new challenges!</p>
                </div>
            `;
            return;
        }
        
        let html = '<div class="list-group list-group-flush">';
        
        challenges.forEach(challenge => {
            const categoryIcons = {
                'budgeting': 'fa-wallet',
                'savings': 'fa-piggy-bank',
                'education': 'fa-graduation-cap',
                'investment': 'fa-chart-line'
            };
            
            const icon = categoryIcons[challenge.category] || 'fa-star';
            const iconColors = {
                'budgeting': 'text-primary',
                'savings': 'text-success',
                'education': 'text-info',
                'investment': 'text-warning'
            };
            const iconColor = iconColors[challenge.category] || 'text-secondary';
            
            html += `
                <div class="list-group-item ${challenge.completed ? 'bg-light' : ''}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="me-3 ${iconColor}">
                                <i class="fas ${icon} fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="mb-1">${challenge.title}</h5>
                                <p class="mb-1">${challenge.description}</p>
                                <span class="badge bg-${challenge.completed ? 'success' : 'secondary'}">
                                    <i class="fas ${challenge.completed ? 'fa-check-circle' : 'fa-lock'} me-1"></i>
                                    ${challenge.completed ? 'Completed' : challenge.points} points
                                </span>
                                ${challenge.completed ? 
                                    `<span class="badge bg-light text-dark ms-2">
                                        <i class="fas fa-calendar-check me-1"></i>
                                        Completed on ${new Date().toLocaleDateString()}
                                    </span>` : ''
                                }
                            </div>
                        </div>
                        ${!challenge.completed ? `
                            <button class="btn btn-primary btn-sm complete-challenge" 
                                    data-id="${challenge.id}">
                                Mark as Complete
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
        
        // Add event listeners to complete buttons
        document.querySelectorAll('.complete-challenge').forEach(button => {
            button.addEventListener('click', function() {
                const challengeId = this.getAttribute('data-id');
                completeChallenge(challengeId, this);
            });
        });
    }
    
    // Complete a challenge
    async function completeChallenge(challengeId, button) {
        try {
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Completing...';
            
            // In a real app, send to server:
            // const response = await fetch('modules/complete_challenge.php', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ challenge_id: challengeId })
            // });
            // const result = await response.json();
            
            // For now, simulate server response
            await new Promise(resolve => setTimeout(resolve, 1000));
            const result = { 
                success: true, 
                pointsEarned: 20, // This would come from the server
                newPoints: (parseInt('<?= $user['points'] ?? 0 ?>') + 20),
                leveledUp: false,
                newLevel: '<?= $user['level'] ?? 'Beginner' ?>'
            };
            
            if (result.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show m-3';
                alert.innerHTML = `
                    <strong>Challenge completed!</strong> 
                    You earned ${result.pointsEarned} points!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.getElementById('challenges-list').prepend(alert);
                
                // Reload challenges
                loadChallenges();
                
                // Update points display
                const pointsBadge = document.querySelector('.badge.bg-success');
                if (pointsBadge) {
                    const currentPoints = parseInt(pointsBadge.textContent.replace