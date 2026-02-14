<?php
// ai_coach.php
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die('Please log in to access the AI Coach');
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, points, level FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Sample conversation if none exists
if (!isset($_SESSION['ai_conversation'])) {
    $_SESSION['ai_conversation'] = [
        [
            'role' => 'assistant',
            'content' => "Hi " . htmlspecialchars($user['username']) . "! I'm your AI Financial Coach. How can I assist you with your finances today?",
            'timestamp' => time()
        ]
    ];
}

// Handle new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $user_message = trim($_POST['message']);
    
    if (!empty($user_message)) {
        // Add user message to conversation
        $_SESSION['ai_conversation'][] = [
            'role' => 'user',
            'content' => $user_message,
            'timestamp' => time()
        ];
        
        // Generate AI response (in a real app, this would call an AI API)
        $ai_response = generateAIResponse($user_message, $user);
        
        // Add AI response to conversation
        $_SESSION['ai_conversation'][] = [
            'role' => 'assistant',
            'content' => $ai_response,
            'timestamp' => time()
        ];
        
        // Return the updated conversation as JSON
        header('Content-Type: application/json');
        echo json_encode(['conversation' => $_SESSION['ai_conversation']]);
        exit;
    }
}

// Function to generate AI response (simplified for demo)
function generateAIResponse($message, $user) {
    $message = strtolower(trim($message));
    
    // Sample responses (in a real app, this would be handled by an AI model)
    $responses = [
        'hello|hi|hey' => "Hello " . htmlspecialchars($user['username']) . "! How can I help you with your finances today?",
        'how are you' => "I'm just a computer program, but I'm here and ready to help you with your financial questions!",
        'budget|save money|saving' => "I can help you create a budget! A good starting point is the 50/30/20 rule: 50% needs, 30% wants, and 20% savings. Would you like me to help you set up a budget?",
        'invest|investment' => "Investing is a great way to grow your wealth over time. Some options include stocks, bonds, mutual funds, and real estate. How can I help you get started?",
        'debt|loan|credit card' => "Managing debt is crucial for financial health. I can help you create a debt repayment plan. Would you like to discuss strategies for paying down your debt?",
        'thank|thanks' => "You're welcome! Is there anything else you'd like to know?",
        'bye|goodbye' => "Goodbye! Don't hesitate to come back if you have more questions about your finances."
    ];
    
    // Check for matching patterns
    foreach ($responses as $pattern => $response) {
        if (preg_match("/$pattern/", $message)) {
            return $response;
        }
    }
    
    // Default response if no pattern matches
    return "I'm here to help with your financial questions! Could you please rephrase your question or ask about budgeting, saving, investing, or managing debt?";
}
?>

<div class="container my-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-robot me-2"></i>AI Financial Coach</h3>
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-bolt me-1"></i> Level <?= htmlspecialchars($user['level']) ?>
                    </span>
                </div>
                <div class="card-body p-0">
                    <div id="chat-container" class="p-3" style="height: 500px; overflow-y: auto; background-color: #f8f9fa;">
                        <div id="chat-messages">
                            <?php foreach ($_SESSION['ai_conversation'] as $message): ?>
                                <div class="message mb-3 <?= $message['role'] === 'user' ? 'text-end' : 'text-start' ?>">
                                    <div class="d-flex <?= $message['role'] === 'user' ? 'justify-content-end' : 'justify-content-start' ?>">
                                        <div class="message-bubble <?= $message['role'] === 'user' ? 'bg-primary text-white' : 'bg-white' ?> p-3 rounded-3 shadow-sm" 
                                             style="max-width: 80%;">
                                            <?= nl2br(htmlspecialchars($message['content'])) ?>
                                            <div class="text-muted small mt-1" style="opacity: 0.7;">
                                                <?= date('h:i A', $message['timestamp']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div id="typing-indicator" class="text-muted small mb-3" style="display: none;">
                                <i class="fas fa-ellipsis-h"></i> AI Coach is typing...
                            </div>
                        </div>
                    </div>
                    <div class="p-3 border-top">
                        <form id="chat-form" class="d-flex">
                            <input type="text" id="user-input" class="form-control me-2" 
                                   placeholder="Ask me anything about personal finance..." autocomplete="off">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                        <div class="suggestions mt-2 d-flex flex-wrap gap-2">
                            <button class="btn btn-sm btn-outline-secondary suggestion-btn">Budgeting tips</button>
                            <button class="btn btn-sm btn-outline-secondary suggestion-btn">Saving strategies</button>
                            <button class="btn btn-sm btn-outline-secondary suggestion-btn">Debt management</button>
                            <button class="btn btn-sm btn-outline-secondary suggestion-btn">Investment advice</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="fas fa-lightbulb me-2 text-warning"></i>Financial Tips</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-piggy-bank text-primary me-2"></i>Emergency Fund</h5>
                                    <p class="card-text small">Aim to save 3-6 months' worth of living expenses in an easily accessible account for unexpected events.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-credit-card text-success me-2"></i>Credit Score</h5>
                                    <p class="card-text small">Pay your bills on time and keep credit card balances low to maintain a good credit score.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-chart-line text-info me-2"></i>Invest Early</h5>
                                    <p class="card-text small">Start investing as early as possible to take advantage of compound interest over time.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-file-invoice-dollar text-danger me-2"></i>Track Spending</h5>
                                    <p class="card-text small">Monitor your expenses to identify areas where you can cut back and save more.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.message-bubble {
    position: relative;
    border-radius: 1rem;
    word-wrap: break-word;
}

.message-bubble:after {
    content: '';
    position: absolute;
    bottom: 0;
    width: 0;
    height: 0;
    border: 10px solid transparent;
}

.message-bubble.user:after {
    right: -10px;
    border-left-color: #0d6efd;
    border-right: 0;
    margin-left: -10px;
    margin-right: -10px;
}

.message-bubble.assistant:after {
    left: -10px;
    border-right-color: #fff;
    border-left: 0;
    margin-right: -10px;
    margin-left: -10px;
}

/* Custom scrollbar */
#chat-container::-webkit-scrollbar {
    width: 8px;
}

#chat-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

#chat-container::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}

#chat-container::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Typing animation */
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.typing-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #6c757d;
    margin: 0 2px;
    animation: blink 1.4s infinite both;
}

.typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    const chatMessages = document.getElementById('chat-messages');
    const userInput = document.getElementById('user-input');
    const chatContainer = document.getElementById('chat-container');
    const typingIndicator = document.getElementById('typing-indicator');
    
    // Scroll to bottom of chat
    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    // Add message to chat
    function addMessage(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message mb-3 ${role === 'user' ? 'text-end' : 'text-start'}`;
        
        const messageBubble = document.createElement('div');
        messageBubble.className = `d-flex ${role === 'user' ? 'justify-content-end' : 'justify-content-start'}`;
        
        const bubbleContent = `
            <div class="message-bubble ${role === 'user' ? 'bg-primary text-white' : 'bg-white'} p-3 rounded-3 shadow-sm" style="max-width: 80%;">
                ${content.replace(/\n/g, '<br>')}
                <div class="text-muted small mt-1" style="opacity: 0.7;">
                    ${new Date().toLocaleTimeString([], {hour: '2-digit', 'minute': '2-digit'})}
                </div>
            </div>
        `;
        
        messageBubble.innerHTML = bubbleContent;
        messageDiv.appendChild(messageBubble);
        chatMessages.insertBefore(messageDiv, typingIndicator);
        scrollToBottom();
    }
    
    // Handle form submission
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = userInput.value.trim();
        if (!message) return;
        
        // Add user message to chat
        addMessage('user', message);
        userInput.value = '';
        
        // Show typing indicator
        typingIndicator.style.display = 'block';
        scrollToBottom();
        
        try {
            // Send message to server
            const response = await fetch('modules/ai_coach.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `message=${encodeURIComponent(message)}`
            });
            
            const data = await response.json();
            
            // Update conversation in session
            if (data.conversation) {
                // The response is already added by the PHP script
                // Just update the UI with the latest message
                const lastMessage = data.conversation[data.conversation.length - 1];
                if (lastMessage.role === 'assistant') {
                    // Remove typing indicator and add AI response
                    typingIndicator.style.display = 'none';
                    addMessage('assistant', lastMessage.content);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            typingIndicator.style.display = 'none';
            addMessage('assistant', 'Sorry, I encountered an error. Please try again.');
        }
    });
    
    // Handle suggestion clicks
    document.querySelectorAll('.suggestion-btn').forEach(button => {
        button.addEventListener('click', function() {
            userInput.value = this.textContent;
            userInput.focus();
        });
    });
    
    // Auto-focus input
    userInput.focus();
    
    // Initial scroll to bottom
    scrollToBottom();
});
</script>
