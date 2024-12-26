<?php
session_start();

// Include the database connection
include('../php/config.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in.']);
    exit();
}

// Get the logged-in user ID and username
$user_id = $_SESSION['user_id'];
$sender_username = $_SESSION['username']; // Get the sender's username from the session

// Get recipient username and amount from POST request
$recipient_username = $_POST['recipient_username'];
$amount = $_POST['amount'];

// Validate the inputs
if (empty($recipient_username) || empty($amount) || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid recipient or amount.']);
    exit();
}

// Fetch sender's balance from the database
$stmt_balance = $pdo->prepare("SELECT balance FROM Wallets WHERE user_id = :user_id");
$stmt_balance->execute(['user_id' => $user_id]);
$wallet = $stmt_balance->fetch(PDO::FETCH_ASSOC);

if (!$wallet || $wallet['balance'] < $amount) {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient balance.']);
    exit();
}

// Fetch recipient's user ID from the username
$stmt_recipient = $pdo->prepare("SELECT id FROM Users WHERE username = :username");
$stmt_recipient->execute(['username' => $recipient_username]);
$recipient = $stmt_recipient->fetch(PDO::FETCH_ASSOC);

if (!$recipient) {
    echo json_encode(['status' => 'error', 'message' => 'Recipient not found.']);
    exit();
}

$recipient_id = $recipient['id'];

// Start transaction
try {
    // Deduct from sender's balance
    $pdo->beginTransaction();
    $stmt_sender = $pdo->prepare("UPDATE Wallets SET balance = balance - :amount WHERE user_id = :user_id");
    $stmt_sender->execute(['amount' => $amount, 'user_id' => $user_id]);

    // Add to recipient's balance
    $stmt_recipient_balance = $pdo->prepare("UPDATE Wallets SET balance = balance + :amount WHERE user_id = :user_id");
    $stmt_recipient_balance->execute(['amount' => $amount, 'user_id' => $recipient_id]);

    // Insert transactions for both sender and recipient
    $stmt_transaction_sender = $pdo->prepare("INSERT INTO Transactions (user_id, send_username, recipient_username, amount, type) 
        VALUES (:user_id, :send_username, :recipient_username, :amount, 'debit')");
    $stmt_transaction_sender->execute([
        'user_id' => $user_id,
        'send_username' => $sender_username,
        'recipient_username' => $recipient_username,
        'amount' => $amount
    ]);

    $stmt_transaction_recipient = $pdo->prepare("INSERT INTO Transactions (user_id, send_username, recipient_username, amount, type) 
        VALUES (:user_id, :send_username, :recipient_username, :amount, 'credit')");
    $stmt_transaction_recipient->execute([
        'user_id' => $recipient_id,
        'send_username' => $sender_username,
        'recipient_username' => $recipient_username,
        'amount' => $amount
    ]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode(['status' => 'success', 'message' => 'Transaction completed successfully!']);
} catch (Exception $e) {
    // Rollback transaction in case of an error
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
}
?>
