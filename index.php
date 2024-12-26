<?php
// Display errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'php/config.php'; // Include database configuration

// Handle login logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($username) || empty($password)) {
        $login_error = "Both fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dash/index.php");
            exit();
        } else {
            $login_error = "Invalid username or password.";
        }
    }
}

// Handle registration logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $register_error = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = :username OR email = :email");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->rowCount() > 0) {
            $register_error = "Username or email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare("INSERT INTO Users (username, email, password) VALUES (:username, :email, :password)");
                $stmt->execute(['username' => $username, 'email' => $email, 'password' => $hashed_password]);

                $user_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO Wallets (user_id, balance) VALUES (:user_id, 0)");
                $stmt->execute(['user_id' => $user_id]);

                $pdo->commit();
                $register_success = "Registration successful. Please log in.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $register_error = "An error occurred. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayBro - Home</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar">
        <div class="logo">
            <h1><span style="color:white">Pay</span>Bro</h1>
        </div>
        <div class="navbar-btns">
            <button class="navbar-btn login" onclick="openModal('login')">Login</button>
            <button class="navbar-btn register" onclick="openModal('register')">Register</button>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h2>👋 <span style="color:white">Pay</span>Bro</h2>
            <p>PayBro is your fast, secure, and easy way to send money online.</p>
            <button class="cta-button">Get Started</button>
        </div>
    </section>

    <footer class="footer">
        <p>© 2024 PayBro, All rights reserved.</p>
    </footer>

    <!-- Modals -->
    <div id="login-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('login')">&times;</span>
            <h2>Login</h2>
            <?php if (isset($login_error)): ?>
                <p style="color: red;"><?= $login_error ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="hidden" name="login" value="1">
                <p>Don't have an account? <a onclick="closeModal('login'); openModal('register')">Register</a></p><br>
                <button type="submit" class="cta-button" style="color: white; background-color: #1c1c1c;">Login</button>
            </form>                
        </div>
    </div>

    <div id="register-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('register')">&times;</span>
            <h2>Register</h2>
            <?php if (isset($register_error)): ?>
                <p style="color: red;"><?= $register_error ?></p>
            <?php elseif (isset($register_success)): ?>
                <p style="color: green;"><?= $register_success ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="hidden" name="register" value="1">
                <p>Already have an account? <a onclick="closeModal('register'); openModal('login')">Login</a></p><br>
                <button type="submit" class="cta-button" style="color: white; background-color: #1c1c1c;">Register</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(type) {
            const modal = document.getElementById(`${type}-modal`);
            modal.style.display = 'flex';

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal(type);
                }
            });
        }

        function closeModal(type) {
            const modal = document.getElementById(`${type}-modal`);
            modal.style.display = 'none';
        }
    </script>
</body>
</html>
