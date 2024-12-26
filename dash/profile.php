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

// Check if the user was found in the database
if (!$user) {
    echo "User not found.";
    exit();
}

// Get the username and email
$username = $user['username'];
$email = $user['email'];

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $new_username = htmlspecialchars($_POST['username']);
    $new_email = htmlspecialchars($_POST['email']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password update logic
    if (!empty($new_password) && $new_password == $confirm_password) {
        // Hash the new password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        // Update the profile in the database
        $stmt = $pdo->prepare("UPDATE Users SET username = :username, email = :email, password = :password WHERE id = :id");
        $stmt->execute(['username' => $new_username, 'email' => $new_email, 'password' => $password_hash, 'id' => $user_id]);
    } else {
        // Only update the username and email if password fields are not changed or invalid
        $stmt = $pdo->prepare("UPDATE Users SET username = :username, email = :email WHERE id = :id");
        $stmt->execute(['username' => $new_username, 'email' => $new_email, 'id' => $user_id]);
    }

    // Reload the page after update
    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayBro - Profile</title>
    <!-- Link to Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/profile.css">
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
                    <li><a href="#" class="active">Profile</a></li>
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

            <!-- Profile Section -->
            <section class="profile-section">
                <div class="profile-header">
                    <h2>@<?php echo htmlspecialchars($username); ?></h2>
                    <p><?php echo htmlspecialchars($email); ?></p>
                </div>

                <form class="profile-form" action="profile.php" method="POST">
                    <!-- Editable Fields -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <!-- Change Password -->
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password">
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm new password">
                    </div>

                    <!-- Save Changes -->
                    <button type="submit" class="cta-button">Save Changes</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
