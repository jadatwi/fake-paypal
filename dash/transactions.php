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

// Get the username
$username = $user['username'];

// Handle search and filter
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build the SQL query for transactions based on filters
$query = "SELECT * FROM Transactions WHERE user_id = :user_id AND (send_username LIKE :search OR recipient_username LIKE :search)";
if ($filter != 'all') {
    $transaction_type = $filter == 'income' ? 'credit' : 'debit';
    $query .= " AND type = :transaction_type";
}
$query .= " ORDER BY date DESC";

$stmt_transactions = $pdo->prepare($query);
$stmt_transactions->execute([
    'user_id' => $user_id,
    'search' => $search,
    'transaction_type' => $filter != 'all' ? $transaction_type : null
]);

$transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayBro - Transactions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/transactions.css">
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
                    <li><a href="#" class="active">Transactions</a></li>
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

            <!-- Search and Filter Section -->
            <section class="search-filter">
                <form method="GET" action="transactions.php">
                    <input type="text" name="search" class="search-input" placeholder="Search Transactions" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <select name="filter" class="filter-dropdown">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Transactions</option>
                        <option value="income" <?php echo $filter == 'income' ? 'selected' : ''; ?>>Income</option>
                        <option value="expense" <?php echo $filter == 'expense' ? 'selected' : ''; ?>>Expenses</option>
                    </select>
                    <button type="submit" class="filter-btn">Apply</button>
                </form>
            </section>

            <!-- Transactions Table -->
            <section class="transaction-table">
                <h2>Transactions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (empty($transactions)) {
                            echo "<tr><td colspan='4'>No transactions found.</td></tr>";
                        } else {
                            foreach ($transactions as $transaction) {
                                $transaction_type = ($transaction['type'] == 'credit') ? 'Payment from' : 'Payment to';
                                $amount = number_format($transaction['amount'], 2);
                                $transaction_class = ($transaction['type'] == 'debit') ? 'negative' : 'positive';
                                $status_class = ($transaction['status'] == 'pending') ? 'pending' : 'completed';
                                $description = $transaction['type'] == 'credit' ? "@{$transaction['send_username']}" : "@{$transaction['recipient_username']}";

                                echo "
                                    <tr>
                                        <td>{$transaction['date']}</td>
                                        <td>{$transaction_type} {$description}</td>
                                        <td class='$transaction_class'>$" . ($transaction['type'] == 'debit' ? '-' : '') . "$amount</td>
                                        <td class='$status_class'>" . ucfirst($transaction['status']) . "</td>
                                    </tr>
                                ";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
