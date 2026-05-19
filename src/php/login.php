<?php
require_once __DIR__ . '/auth.php';

$BASE_URL = dirname($_SERVER['SCRIPT_NAME'], 3);
if ($BASE_URL === DIRECTORY_SEPARATOR) {
    $BASE_URL = '';
}

if (is_logged_in()) {
        header('Location: dashboard.php');
        exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (validate_credentials($username, $password)) {
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="<?php echo $BASE_URL; ?>/static/style.css">
</head>
<body>
    <main class="page-enter">
        <div class="container narrow glass">
            <h1>Admin Login</h1>
            <p class="subtitle">Please sign in with the admin account to manage uploads and the contract product catalog.</p>

            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="login-form card">
                <label>
                    Username
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit" class="button icon-btn"><span>🔐</span> Sign In</button>
            </form>
        </div>
    </main>
</body>
</html>
