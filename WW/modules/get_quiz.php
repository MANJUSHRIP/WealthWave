<?php
// get_quiz.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please log in to access the quiz']);
    exit;
}

// Sample quiz questions (in a real app, these would come from a database)
$questions = [
    [
        'id' => 1,
        'question' => 'What is the recommended percentage of your income to save?',
        'options' => [
            '5-10%',
            '10-15%',
            '15-20%',
            '20% or more'
        ],
        'correctAnswer' => 2, // 0-based index of correct answer
        'explanation' => 'Financial experts typically recommend saving at least 15-20% of your income for long-term financial health.',
        'points' => 10
    ],
    [
        'id' => 2,
        'question' => 'What is an emergency fund?',
        'options' => [
            'Money set aside for vacations',
            'Savings for unexpected expenses',
            'Investment in stocks',
            'Retirement savings'
        ],
        'correctAnswer' => 1,
        'explanation' => 'An emergency fund is money set aside to cover unexpected expenses like medical bills or car repairs, typically 3-6 months of living expenses.',
        'points' => 10
    ],
    [
        'id' => 3,
        'question' => 'What is compound interest?',
        'options' => [
            'Interest on the principal only',
            'Interest that decreases over time',
            'Interest on both principal and accumulated interest',
            'A type of loan interest'
        ],
        'correctAnswer' => 2,
        'explanation' => 'Compound interest is interest calculated on the initial principal and also on the accumulated interest of previous periods.',
        'points' => 15
    ],
    [
        'id' => 4,
        'question' => 'What is the 50/30/20 budget rule?',
        'options' => [
            '50% savings, 30% investments, 20% expenses',
            '50% needs, 30% wants, 20% savings',
            '50% housing, 30% food, 20% transportation',
            '50% bills, 30% entertainment, 20% savings'
        ],
        'correctAnswer' => 1,
        'explanation' => 'The 50/30/20 rule suggests spending 50% on needs, 30% on wants, and saving 20% of your income.',
        'points' => 15
    ],
    [
        'id' => 5,
        'question' => 'What is a credit score used for?',
        'options' => [
            'To determine loan eligibility and interest rates',
            'To calculate taxes',
            'To track spending habits',
            'To measure income level'
        ],
        'correctAnswer' => 0,
        'explanation' => 'A credit score is a number that lenders use to determine the risk of lending money to a borrower.',
        'points' => 10
    ]
];

// Shuffle questions (optional)
shuffle($questions);

// Return questions as JSON
echo json_encode($questions);