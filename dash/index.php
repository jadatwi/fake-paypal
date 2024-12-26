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

// Fetch user details from the database
$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch the user's balance (assuming there's a wallet system)
$stmt_balance = $pdo->prepare("SELECT balance FROM Wallets WHERE user_id = :user_id");
$stmt_balance->execute(['user_id' => $user_id]);
$wallet = $stmt_balance->fetch(PDO::FETCH_ASSOC);

// Get the username and balance
$username = $user['username'];
$balance = isset($wallet['balance']) ? $wallet['balance'] : 0.00;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayBro - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
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
                    <li><a href="#" class="active">Dashboard</a></li>
                    <li><a href="transactions.php">Transactions</a></li>
                    <li><a href="wallet.php">Wallet</a></li>
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
                    <span>Welcome, @<?php echo htmlspecialchars($username); ?></span>
                </div>
                <a href="../php/logout.php" class="logout-btn">Logout</a>
            </header>

            <!-- Dashboard Overview -->
            <section class="overview">
                <div class="balance">
                    <h2>Balance</h2>
                    <p>$<?php echo number_format($balance, 2); ?></p>
                </div>
                <section class="quick-actions">
                    <button class="cta-button" onclick="openSendMoneyModal()">Send Money</button>
                    <button class="cta-button" onclick="window.location.href='https://discord.gg/decomposing'">Deposit Money</button>
                </section>
            </section>

            <!-- Send Money Modal -->
            <div id="send-money-modal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal('send-money')">&times;</span>
                    <h2>Send Money</h2>
                    <form id="send-money-form" method="POST">
                        <input type="text" name="recipient_username" placeholder="Recipient Username" required>
                        <input type="number" name="amount" placeholder="Amount" required min="0.01" step="0.01">
                        <button type="submit" class="cta-button">Send</button>
                    </form>
                    <div id="send-money-message"></div> <!-- Error or success message will be displayed here -->
                </div>
            </div>

            <!-- Transaction History -->
            <section class="transaction-history">
                <h2>Recent Transactions</h2>
                <?php
                // Fetch recent transactions
                $stmt_transactions = $pdo->prepare("SELECT * FROM Transactions WHERE user_id = :user_id ORDER BY date DESC LIMIT 3");
                $stmt_transactions->execute(['user_id' => $user_id]);
                $transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);

                foreach ($transactions as $transaction) {
                    $transaction_type = ($transaction['type'] == 'credit') ? 'Payment from' : 'Payment to';
                    $amount = number_format($transaction['amount'], 2);
                    $transaction_class = ($transaction['type'] == 'debit') ? 'negative' : '';
                    $recipient_username = htmlspecialchars($transaction['recipient_username']); // Recipient username

                    echo "
                    <div class='transaction-item $transaction_class'>
                        <span>{$transaction_type} @$recipient_username</span>
                        <span class='$transaction_class'>$" . ($transaction['type'] == 'debit' ? '-' : '') . "$amount</span>
                    </div>
                    ";
                }
                ?>
            </section>
        </main>
    </div>

<script>
// Handle the form submission for Send Money via AJAX
document.getElementById('send-money-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent normal form submission

    const formData = new FormData(this);

    fetch('../php/send_money.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        const messageDiv = document.getElementById('send-money-message');
        
        if (data.status === 'success') {
            messageDiv.innerHTML = `<p style="color:green">${data.message}</p>`;
            setTimeout(() => {
                closeModal('send-money'); // Close the modal after a successful transfer
                location.reload(); // Reload the page to reflect the updated balance and transactions
            }, 2000);
        } else {
            messageDiv.innerHTML = `<p style="color:red">${data.message}</p>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const messageDiv = document.getElementById('send-money-message');
        messageDiv.innerHTML = `<p style="color:red">An error occurred. Please try again.</p>`;
    });
});

// Open Send Money Modal
function openSendMoneyModal() {
    const modal = document.getElementById('send-money-modal');
    modal.style.display = 'flex';
}

// Close Modal
function closeModal(type) {
    const modal = document.getElementById(`${type}-modal`);
    modal.style.display = 'none';
}
</script>

</body>
</html>
