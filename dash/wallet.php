<?php
session_start();

// Include the database connection
include('../php/config.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get the logged-in user ID
$user_id = $_SESSION['user_id'];

// Fetch user details (for username)
$stmt_user = $pdo->prepare("SELECT * FROM Users WHERE id = :id");
$stmt_user->execute(['id' => $user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Fetch the user's wallet balance
$stmt_balance = $pdo->prepare("SELECT balance FROM Wallets WHERE user_id = :user_id");
$stmt_balance->execute(['user_id' => $user_id]);
$wallet = $stmt_balance->fetch(PDO::FETCH_ASSOC);

// Get the balance, defaulting to 0.00 if no wallet is found
$balance = isset($wallet['balance']) ? number_format($wallet['balance'], 2) : '0.00';

// Fetch recent wallet transactions (assuming you have a Wallet_Transactions table)
$stmt_transactions = $pdo->prepare("SELECT * FROM Wallet_Transactions WHERE user_id = :user_id ORDER BY date DESC LIMIT 3");
$stmt_transactions->execute(['user_id' => $user_id]);
$transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayBro - Wallet</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/wallet.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h1><span style="color:white">Pay</span>Bro</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="transactions.php">Transactions</a></li>
                    <li><a href="#" class="active">Wallet</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="settings.php">Settings</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="user-info">
                    <span>Welcome, @<?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <a href="../php/logout.php" class="logout-btn">Logout</a>
            </header>

            <!-- Wallet Overview -->
            <section class="wallet-overview">
                <h2>Wallet Balance</h2>
                <div class="wallet-balance">$<?php echo $balance; ?></div>
            </section>

            <!-- Wallet Actions -->
            <section class="wallet-actions">
                <button class="cta-button" onclick="window.location.href='https://discord.gg/decomposing'">Add Funds</button>
                <button class="cta-button" onclick="window.location.href='https://discord.gg/decomposing'">Withdraw Funds</button>
                <button class="cta-button" onclick="window.location.href='transactions.php'">View Activity</button>
            </section>

            <!-- Wallet Transactions -->
            <section class="wallet-transactions">
                <h2>Recent Wallet Transactions</h2>
                <?php
                if (empty($transactions)) {
                    echo "<div class='transaction-item'>No transactions found.</div>";
                } else {
                    foreach ($transactions as $transaction) {
                        $transaction_class = $transaction['amount'] < 0 ? 'negative' : 'positive';
                        $amount = number_format($transaction['amount'], 2);
                        $description = htmlspecialchars($transaction['description']);
                        $status = $transaction['status'] == 'completed' ? 'Completed' : 'Pending';

                        echo "
                        <div class='transaction-item'>
                            <span>$description</span>
                            <span class='$transaction_class'>$" . ($transaction['amount'] < 0 ? '-' : '') . "$amount</span>
                        </div>";
                    }
                }
                ?>
            </section>
        </main>
    </div>
</body>
</html>
