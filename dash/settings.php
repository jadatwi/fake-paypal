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

// Fetch user settings from the database
$stmt = $pdo->prepare("SELECT * FROM settings WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if settings were found for the user, if not set default values
if (!$settings) {
    $settings = [
        'notifications' => 'enabled',
        'privacy' => 'public',
        'theme' => 'dark',
        'language' => 'en'
    ];
}

// Handle form submission to update settings
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the form values
    $notifications = $_POST['notifications'];
    $privacy = $_POST['privacy'];
    $theme = $_POST['theme'];
    $language = $_POST['language'];

    // Update the user settings in the database
    $stmt = $pdo->prepare("REPLACE INTO settings (user_id, notifications, privacy, theme, language) VALUES (:user_id, :notifications, :privacy, :theme, :language)");
    $stmt->execute([
        'user_id' => $user_id,
        'notifications' => $notifications,
        'privacy' => $privacy,
        'theme' => $theme,
        'language' => $language
    ]);

    // Reload the page after saving the changes
    header("Location: settings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayBro - Settings</title>
    <!-- Link to Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/settings.css">
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
                    <li><a href="#" class="active">Settings</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="user-info">
                    <span>Welcome, @<?php echo htmlspecialchars($_SESSION['username']); ?> IM LAZY TO DO THIS PART SO YOU JUST GET THE VISUALS AND IT STORING ON THE DATABASE <3 </span>
                </div>
                <a href="../php/logout.php" class="logout-btn">Logout</a>
            </header>

            <!-- Settings Section -->
            <section class="settings-section">
                <h2>Settings</h2>

                <form class="settings-form" action="settings.php" method="POST">
                    <!-- Account Preferences -->
                    <div class="form-group">
                        <label for="notifications">Email Notifications</label>
                        <select id="notifications" name="notifications">
                            <option value="enabled" <?php echo ($settings['notifications'] == 'enabled') ? 'selected' : ''; ?>>Enabled</option>
                            <option value="disabled" <?php echo ($settings['notifications'] == 'disabled') ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="privacy">Privacy Settings</label>
                        <select id="privacy" name="privacy">
                            <option value="public" <?php echo ($settings['privacy'] == 'public') ? 'selected' : ''; ?>>Public</option>
                            <option value="private" <?php echo ($settings['privacy'] == 'private') ? 'selected' : ''; ?>>Private</option>
                        </select>
                    </div>

                    <!-- Theme Settings -->
                    <div class="form-group">
                        <label for="theme">Theme</label>
                        <select id="theme" name="theme">
                            <option value="dark" <?php echo ($settings['theme'] == 'dark') ? 'selected' : ''; ?>>Dark</option>
                            <option value="light" <?php echo ($settings['theme'] == 'light') ? 'selected' : ''; ?>>Light</option>
                        </select>
                    </div>

                    <!-- Language Preferences -->
                    <div class="form-group">
                        <label for="language">Language</label>
                        <select id="language" name="language">
                            <option value="en" <?php echo ($settings['language'] == 'en') ? 'selected' : ''; ?>>English</option>
                            <option value="es" <?php echo ($settings['language'] == 'es') ? 'selected' : ''; ?>>Spanish</option>
                            <option value="fr" <?php echo ($settings['language'] == 'fr') ? 'selected' : ''; ?>>French</option>
                            <option value="de" <?php echo ($settings['language'] == 'de') ? 'selected' : ''; ?>>German</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="submit" class="cta-button">Save Changes</button>
                        <button type="button" class="cta-button reset">Reset</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
