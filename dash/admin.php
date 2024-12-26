<?php
session_start();
include('../php/config.php');

// Check if user is logged in and has admin permissions
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details to check if the logged-in user is an admin
$stmt = $pdo->prepare("SELECT * FROM Users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['perms'] != 1) {  // Check if the user is an admin
    header('Location: dashboard.php');  // Redirect if not admin    
    exit();
}

// Handle adding or removing balance
if (isset($_POST['update_balance'])) {
    if (isset($_POST['description'])) { // Check if 'description' is set
        $target_user_id = $_POST['target_user_id'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];  // 'deposit' or 'withdrawal'

        // Validate that the description is either 'deposit' or 'withdrawal'
        if (!in_array($description, ['deposit', 'withdrawal'])) {
            echo "Invalid transaction type.";
            exit();
        }

        // Ensure amount is a valid number and greater than 0
        if (is_numeric($amount) && $amount > 0) {
            // Status for the transaction
            $status = "completed";  // For simplicity, assume all transactions are completed

            // If it's a deposit, add balance
            if ($description == 'deposit') {
                $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance + :amount WHERE user_id = :user_id");
                $stmt->execute(['amount' => $amount, 'user_id' => $target_user_id]);
            } 
            // If it's a withdrawal, check balance and subtract
            elseif ($description == 'withdrawal') {
                // Fetch user's wallet balance
                $stmt = $pdo->prepare("SELECT balance FROM Wallets WHERE user_id = :user_id");
                $stmt->execute(['user_id' => $target_user_id]);
                $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($wallet['balance'] >= $amount) {
                    $stmt = $pdo->prepare("UPDATE Wallets SET balance = balance - :amount WHERE user_id = :user_id");
                    $stmt->execute(['amount' => $amount, 'user_id' => $target_user_id]);
                } else {
                    echo "Insufficient balance.";
                    exit();
                }
            }

            // Insert the transaction into wallet_transactions table
            $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, description, status, date) 
                                   VALUES (:user_id, :amount, :description, :status, NOW())");
            $stmt->execute([
                'user_id' => $target_user_id,
                'amount' => $amount,
                'description' => ucfirst($description),  // Capitalize 'deposit' or 'withdrawal'
                'status' => $status
            ]);

            echo ucfirst($description) . " successful!";
        } else {
            echo "Invalid amount.";
        }
    } else {
        echo "Transaction type is missing.";
    }
}

// Handle deleting a user account
if (isset($_POST['delete_user'])) {
    $delete_user_id = $_POST['delete_user_id'];

    // Delete user from the Users table
    $stmt = $pdo->prepare("DELETE FROM Users WHERE id = :id");
    $stmt->execute(['id' => $delete_user_id]);

    // Optionally delete user's wallet and transactions if you want to clean up
    $stmt = $pdo->prepare("DELETE FROM Wallets WHERE user_id = :id");
    $stmt->execute(['id' => $delete_user_id]);

    $stmt = $pdo->prepare("DELETE FROM wallet_transactions WHERE user_id = :id");
    $stmt->execute(['id' => $delete_user_id]);

    echo "User account deleted successfully!";
}

// Fetch all users for admin to manage
$stmt = $pdo->prepare("SELECT * FROM Users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayBro - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
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
                    <li><a href="wallet.php">Wallet</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="#" class="active">Admin Panel</a></li>
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

            <!-- Admin Panel -->
            <section class="admin-panel">
                <h2>Admin Panel</h2>

                <!-- Add/Remove Balance Form -->
                <h3>Update Balance for User</h3>
                <form action="admin.php" method="POST">
                    <div class="form-group">
                        <label for="target_user_id">User ID</label>
                        <input type="text" id="target_user_id" name="target_user_id" required>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" id="amount" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Transaction Type</label>
                        <select name="description" id="description" required>
                            <option value="deposit">Deposit</option>
                            <option value="withdrawal">Withdrawal</option>
                        </select>
                    </div>
                    <button type="submit" name="update_balance" class="cta-button">Update Balance</button>
                </form>

                <!-- Delete User Form -->
                <h3>Delete User Account</h3>
                <form action="admin.php" method="POST">
                    <div class="form-group">
                        <label for="delete_user_id">User ID</label>
                        <input type="text" id="delete_user_id" name="delete_user_id" required>
                    </div>
                    <button type="submit" name="delete_user" class="cta-button">Delete Account</button>
                </form>

                <!-- User Management Table -->
                <h3>All Users</h3>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Permissions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['perms'] == 1 ? 'Admin' : 'User'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
