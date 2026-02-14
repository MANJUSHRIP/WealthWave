<?php
// Disable error display to users (but log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ai_coach.php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user']) || empty($_SESSION['user']['name'])) {
    header('Location: dashboard.php');
    exit();
}

$user = $_SESSION['user'];
$page_title = 'AI Financial Coach';
$body_class = 'ai-coach-module';

// Include header
include 'includes/header.php';

// Initialize chat history if not exists
if (!isset($_SESSION['ai_coach_chat'])) {
    $_SESSION['ai_coach_chat'] = [
        'messages' => [
            [
                'sender' => 'ai',
                'text' => "👋 Hi " . htmlspecialchars($user['name']) . "! I'm your AI Financial Coach. How can I help you today?",
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ],
        'last_advice_topic' => null,
        'suggestions' => []
    ];
}

// Handle incoming messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $userMessage = trim($_POST['message']);
    
    if (!empty($userMessage)) {
        // Add user message to chat
        $_SESSION['ai_coach_chat']['messages'][] = [
            'sender' => 'user',
            'text' => $userMessage,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Process the message and generate AI response
        $aiResponse = generateAIResponse($userMessage, $user);
        
        // Add AI response to chat
        $_SESSION['ai_coach_chat']['messages'][] = [
            'sender' => 'ai',
            'text' => $aiResponse['text'],
            'suggestions' => $aiResponse['suggestions'] ?? [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Update last advice topic
        if (!empty($aiResponse['topic'])) {
            $_SESSION['ai_coach_chat']['last_advice_topic'] = $aiResponse['topic'];
        }
        
        // Add suggestions for future reference
        if (!empty($aiResponse['suggestions'])) {
            $_SESSION['ai_coach_chat']['suggestions'] = array_merge(
                $_SESSION['ai_coach_chat']['suggestions'] ?? [],
                $aiResponse['suggestions']
            );
        }
        
        // Return success response for AJAX
        if (isset($_POST['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'response' => end($_SESSION['ai_coach_chat']['messages'])
            ]);
            exit();
        }
    }
}

// Clear chat history
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $_SESSION['ai_coach_chat'] = [
        'messages' => [
            [
                'sender' => 'ai',
                'text' => "👋 Hi " . htmlspecialchars($user['name']) . "! I've cleared our chat history. How can I help you today?",
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ],
        'last_advice_topic' => null,
        'suggestions' => []
    ];
    header('Location: ai_coach.php');
    exit();
}

// Function to generate AI response
function generateAIResponse($message, $user) {
    $message = strtolower($message);
    $response = [
        'text' => '',
        'suggestions' => [],
        'topic' => null
    ];
    
    // Extract amount if mentioned
    preg_match('/₹\s*([0-9,]+)/', $message, $amountMatches);
    $amount = !empty($amountMatches) ? (int)str_replace(',', '', $amountMatches[1]) : null;
    
    // Check for keywords and generate appropriate response
    if (preg_match('/(income|earn|salary|wage)/', $message) && $amount) {
        // Income management advice
        $response['topic'] = 'income_management';
        $savings = $amount * 0.2; // 20% savings
        $needs = $amount * 0.5;    // 50% needs
        $wants = $amount * 0.3;    // 30% wants
        
        $response['text'] = "💰 <strong>Income Management Advice for ₹" . number_format($amount) . " Monthly Income</strong>\n\n";
        $response['text'] .= "Here's a simple way to manage your income:\n\n";
        $response['text'] .= "1. <strong>Needs (50% - ₹" . number_format($needs) . "):</strong> Essentials like rent, groceries, utilities, and transportation.\n";
        $response['text'] .= "2. <strong>Savings (20% - ₹" . number_format($savings) . "):</strong> Build your emergency fund and investments.\n";
        $response['text'] .= "3. <strong>Wants (30% - ₹" . number_format($wants) . "):</strong> Entertainment, dining out, and non-essentials.\n\n";
        $response['text'] .= "💡 <em>Tip:</em> Set up automatic transfers to your savings account on payday to make saving effortless!";
        
        $response['suggestions'] = [
            "How to save more from my salary?",
            "Best investment options for my income level",
            "How to reduce my monthly expenses?"
        ];
    }
    elseif (preg_match('/(loan|emi|debt|credit)/', $message) && $amount) {
        // Loan/EMI advice
        $response['topic'] = 'debt_management';
        $tenure = 12; // default 12 months
        $interestRate = 12; // 12% annual interest
        
        // Extract tenure if mentioned
        if (preg_match('/(\d+)\s*(month|year)/', $message, $tenureMatch)) {
            $tenure = (int)$tenureMatch[1];
            if (isset($tenureMatch[2]) && $tenureMatch[2] === 'year') {
                $tenure *= 12; // convert years to months
            }
        }
        
        $monthlyRate = ($interestRate / 12) / 100;
        $emi = $amount * $monthlyRate * pow(1 + $monthlyRate, $tenure) / (pow(1 + $monthlyRate, $tenure) - 1);
        $totalPayment = $emi * $tenure;
        $totalInterest = $totalPayment - $amount;
        
        $response['text'] = "💳 <strong>Loan/EMI Advice for ₹" . number_format($amount) . " Loan</strong>\n\n";
        $response['text'] .= "For a ₹" . number_format($amount) . " loan at " . $interestRate . "% interest for " . $tenure . " months:\n\n";
        $response['text'] .= "• <strong>Monthly EMI:</strong> ₹" . number_format(round($emi, 2)) . "\n";
        $response['text'] .= "• <strong>Total Payment:</strong> ₹" . number_format(round($totalPayment)) . "\n";
        $response['text'] .= "• <strong>Total Interest:</strong> ₹" . number_format(round($totalInterest)) . "\n\n";
        $response['text'] .= "💡 <em>Tip:</em> Try to keep your total EMIs below 40% of your monthly income for better financial health!";
        
        $response['suggestions'] = [
            "How to pay off my loan faster?",
            "What's better: shorter tenure or lower EMI?",
            "How to reduce my loan interest?"
        ];
    }
    elseif (preg_match('/(save|saving|investment|invest)/', $message)) {
        // Savings/Investment advice
        $response['topic'] = 'savings_investment';
        $monthlySavings = $amount ?: 5000; // Default to ₹5000 if no amount mentioned
        $annualReturn = 10; // 10% annual return
        
        $year1 = $monthlySavings * 12 * (1 + $annualReturn/100);
        $year5 = $monthlySavings * 12 * (pow(1 + $annualReturn/100, 5) - 1) / ($annualReturn/100) * (1 + $annualReturn/100);
        $year10 = $monthlySavings * 12 * (pow(1 + $annualReturn/100, 10) - 1) / ($annualReturn/100) * (1 + $annualReturn/100);
        
        $response['text'] = "📈 <strong>Savings & Investment Advice</strong>\n\n";
        $response['text'] .= "If you save ₹" . number_format($monthlySavings) . " per month at an average return of " . $annualReturn . "% annually:\n\n";
        $response['text'] .= "• After 1 year: ₹" . number_format(round($year1)) . "\n";
        $response['text'] .= "• After 5 years: ₹" . number_format(round($year5)) . "\n";
        $response['text'] .= "• After 10 years: ₹" . number_format(round($year10)) . "\n\n";
        $response['text'] .= "💡 <em>Tip:</em> Start a SIP in mutual funds to automate your investments and benefit from rupee cost averaging!";
        
        $response['suggestions'] = [
            "Best investment options for beginners",
            "How to start a SIP?",
            "Difference between FD and mutual funds"
        ];
    }
    elseif (preg_match('/(emergency|rainy day|safety net)/', $message)) {
        // Emergency fund advice
        $response['topic'] = 'emergency_fund';
        $monthlyExpenses = $amount ?: 15000; // Default to ₹15,000 if no amount mentioned
        $targetMonths = 6; // 6 months of expenses
        
        $emergencyFund = $monthlyExpenses * $targetMonths;
        $monthlySavings = $emergencyFund / 12; // 1-year target
        
        $response['text'] = "🛡️ <strong>Emergency Fund Advice</strong>\n\n";
        $response['text'] .= "For monthly expenses of ₹" . number_format($monthlyExpenses) . ":\n\n";
        $response['text'] .= "• <strong>3-month emergency fund:</strong> ₹" . number_format($monthlyExpenses * 3) . "\n";
        $response['text'] .= "• <strong>6-month emergency fund (recommended):</strong> ₹" . number_format($emergencyFund) . "\n";
        $response['text'] .= "• <strong>Save monthly to reach goal in 1 year:</strong> ₹" . number_format(round($monthlySavings)) . "\n\n";
        $response['text'] .= "💡 <em>Tip:</em> Keep your emergency fund in a liquid fund or high-interest savings account for easy access!";
        
        $response['suggestions'] = [
            "Where should I keep my emergency fund?",
            "How to build an emergency fund quickly?",
            "Best high-interest savings accounts"
        ];
    }
    elseif (preg_match('/(scam|fraud|phishing|secure)/', $message)) {
        // Security/fraud prevention advice
        $response['topic'] = 'fraud_prevention';
       $response['text'] = "🔒 <strong>Financial Safety Tips</strong>\n\n";
$response['text'] .= "1. <strong>Never share OTPs or passwords</strong> with anyone, even if they claim to be from your bank.\n";
$response['text'] .= "2. <strong>Verify UPI IDs</strong> carefully before sending money.\n";
$response['text'] .= "3. <strong>Beware of too-good-to-be-true offers</strong> promising high returns with no risk.\n";
$response['text'] .= "4. <strong>Use strong, unique passwords</strong> for all financial accounts.\n";
$response['text'] .= "5. <strong>Enable 2FA</strong> (two-factor authentication) wherever possible.\n\n";
$response['text'] .= "💡 <em>Tip:</em> If you suspect fraud, immediately call your bank's 24/7 helpline to block your cards and accounts!";
        
        $response['suggestions'] = [
            "What to do if I've been scammed?",
            "How to report financial fraud?",
            "Best practices for secure online banking"
        ];
    }
    else {
        // Default response for unrecognized queries
        $response['text'] = "🤔 I'm here to help with your financial questions! You can ask me about:\n\n";
        $response['text'] .= "• Managing your income and expenses\n";
        $response['text'] .= "• Saving and investing money\n";
        $response['text'] .= "• Loans, EMIs, and debt management\n";
        $response['text'] .= "• Building an emergency fund\n";
        $response['text'] .= "• Protecting yourself from financial fraud\n\n";
        $response['text'] .= "Just type your question in simple terms, like:\n";
        $response['text'] .= "\"How to save money from my salary?\" or \"Best investment for beginners?\"";
        
        $response['suggestions'] = [
            "How to save money from my salary?",
            "Best investment options for beginners",
            "How to reduce my monthly expenses?",
            "What is an emergency fund?"
        ];
    }
    
    return $response;
}
?>

<div class="ai-coach-module">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">AI Financial Coach</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">AI Financial Coach</h2>
                <a href="?clear=1" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-trash-alt me-1"></i> Clear Chat
                </a>
            </div>
            <p class="lead">Get personalized financial advice anytime, anywhere. Ask me anything about money!</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="chat-container" id="chatContainer">
                        <?php foreach ($_SESSION['ai_coach_chat']['messages'] as $message): ?>
                            <div class="chat-message <?php echo $message['sender']; ?>">
                                <div class="message-bubble">
                                    <?php echo nl2br(htmlspecialchars($message['text'])); ?>
                                    <div class="message-time">
                                        <?php echo date('h:i A', strtotime($message['timestamp'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($message['suggestions'])): ?>
                                <div class="suggestions">
                                    <?php foreach ($message['suggestions'] as $suggestion): ?>
                                        <button class="btn btn-sm btn-outline-primary suggestion-btn" data-suggestion="<?php echo htmlspecialchars($suggestion); ?>">
                                            <?php echo htmlspecialchars($suggestion); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="chat-input p-3 border-top">
                        <form id="chatForm" class="d-flex">
                            <input type="text" 
                                   id="userMessage" 
                                   class="form-control me-2" 
                                   placeholder="Type your financial question here..." 
                                   autocomplete="off"
                                   required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Quick Tips
                    </h5>
                </div>
                <div class="card-body">
                    <div class="tip-card mb-3">
                        <div class="tip-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <div class="tip-content">
                            <h6>Save First, Spend Later</h6>
                            <p class="small text-muted mb-0">Aim to save at least 20% of your income before spending on wants.</p>
                        </div>
                    </div>
                    
                    <div class="tip-card mb-3">
                        <div class="tip-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="tip-content">
                            <h6>Invest Early</h6>
                            <p class="small text-muted mb-0">Starting early can significantly grow your wealth due to compound interest.</p>
                        </div>
                    </div>
                    
                    <div class="tip-card">
                        <div class="tip-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="tip-content">
                            <h6>Emergency Fund</h6>
                            <p class="small text-muted mb-0">Build a 6-month emergency fund for financial security.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history text-info me-2"></i>
                        Recent Advice
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($_SESSION['ai_coach_chat']['last_advice_topic']): ?>
                        <p class="small text-muted">Last topic discussed:</p>
                        <p class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['ai_coach_chat']['last_advice_topic'])); ?></p>
                        
                        <?php if (!empty($_SESSION['ai_coach_chat']['suggestions'])): ?>
                            <p class="small text-muted mt-3">Suggested actions:</p>
                            <ul class="list-unstyled">
                                <?php foreach (array_slice($_SESSION['ai_coach_chat']['suggestions'], 0, 3) as $suggestion): ?>
                                    <li class="mb-2">
                                        <button class="btn btn-sm btn-outline-secondary w-100 text-start suggestion-btn" data-suggestion="<?php echo htmlspecialchars($suggestion); ?>">
                                            <i class="fas fa-arrow-right me-1 small"></i> <?php echo htmlspecialchars($suggestion); ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted small">No recent advice. Start chatting to get personalized financial guidance!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Chat container styles */
.chat-container {
    height: 500px;
    overflow-y: auto;
    padding: 1.5rem;
    background-color: #f8f9fa;
}

/* Chat message bubbles */
.chat-message {
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
}

.chat-message.user {
    align-items: flex-end;
}

.chat-message.ai {
    align-items: flex-start;
}

.message-bubble {
    max-width: 80%;
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    position: relative;
    word-wrap: break-word;
}

.chat-message.user .message-bubble {
    background-color: #4361ee;
    color: white;
    border-bottom-right-radius: 0.25rem;
}

.chat-message.ai .message-bubble {
    background-color: #f1f3f5;
    color: #212529;
    border-bottom-left-radius: 0.25rem;
}

/* Message time */
.message-time {
    font-size: 0.7rem;
    opacity: 0.8;
    margin-top: 0.25rem;
    text-align: right;
}

/* Suggestions */
.suggestions {
    margin: 0.5rem 0 1.5rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.suggestion-btn {
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

/* Chat input */
.chat-input {
    background-color: white;
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}

/* Tip cards */
.tip-card {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding: 0.75rem;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
    transition: all 0.2s;
}

.tip-card:hover {
    background-color: #e9ecef;
    transform: translateX(5px);
}

.tip-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
    flex-shrink: 0;
}

.tip-content h6 {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

/* Scrollbar styling */
.chat-container::-webkit-scrollbar {
    width: 6px;
}

.chat-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.chat-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.chat-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .chat-container {
        height: 400px;
    }
    
    .message-bubble {
        max-width: 90%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chatForm');
    const userMessage = document.getElementById('userMessage');
    const chatContainer = document.getElementById('chatContainer');
    
    // Scroll to bottom of chat
    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    // Handle form submission
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const message = userMessage.value.trim();
        if (!message) return;
        
        // Add user message to chat
        addMessage('user', message);
        userMessage.value = '';
        
        // Show typing indicator
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'chat-message ai';
        typingIndicator.id = 'typingIndicator';
        typingIndicator.innerHTML = `
            <div class="message-bubble">
                <div class="typing">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        chatContainer.appendChild(typingIndicator);
        scrollToBottom();
        
        // Send message to server
        fetch('ai_coach.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'message=' + encodeURIComponent(message) + '&ajax=1'
        })
        .then(response => response.json())
        .then(data => {
            // Remove typing indicator
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
            
            if (data.success) {
                // Add AI response to chat
                addMessage('ai', data.response.text, data.response.suggestions);
            }
        })
        .catch(error => {
    console.error('Error:', error);
    const indicator = document.getElementById('typingIndicator');
    if (indicator) indicator.remove();
    
    // Show detailed error message
    console.log('Error details:', {
        name: error.name,
        message: error.message,
        stack: error.stack,
        response: error.response
    });
    
    addMessage('ai', "I'm having trouble connecting. Please check your internet connection and try again. If the problem persists, please contact support.");
});
    });
    
    // Add message to chat
    function addMessage(sender, text, suggestions = []) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message ${sender}`;
        
        let html = `
            <div class="message-bubble">
                ${text}
                <div class="message-time">${formatTime()}</div>
            </div>
        `;
        
        // Add suggestions if any
        if (suggestions && suggestions.length > 0) {
            html += `<div class="suggestions">`;
            suggestions.forEach(suggestion => {
                html += `<button class="btn btn-sm btn-outline-primary suggestion-btn" data-suggestion="${suggestion.replace(/"/g, '&quot;')}">
                    ${suggestion}
                </button>`;
            });
            html += `</div>`;
        }
        
        messageDiv.innerHTML = html;
        chatContainer.appendChild(messageDiv);
        scrollToBottom();
        
        // Add event listeners to suggestion buttons
        messageDiv.querySelectorAll('.suggestion-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                userMessage.value = this.dataset.suggestion;
                userMessage.focus();
                // Trigger form submission
                chatForm.dispatchEvent(new Event('submit'));
            });
        });
    }
    
    // Format current time
    function formatTime() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12; // the hour '0' should be '12'
        return `${hours}:${minutes} ${ampm}`;
    }
    
    // Handle suggestion button clicks
    document.addEventListener('click', function(e) {
        if (e.target && e.target.matches('.suggestion-btn')) {
            e.preventDefault();
            userMessage.value = e.target.dataset.suggestion;
            userMessage.focus();
        }
    });
    
    // Auto-focus input on page load
    userMessage.focus();
    
    // Scroll to bottom on page load
    scrollToBottom();
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>