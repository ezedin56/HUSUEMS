<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT admin_id, password_hash FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['admin_id'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Election System</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

    <div class="auth-container">
        <div class="auth-box">
            <h2 class="mb-3">Admin Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <label for="username" style="display: block; text-align: left; margin-bottom: 5px;">Username</label>
                <input type="text" name="username" id="username" required>

                <label for="password" style="display: block; text-align: left; margin-bottom: 5px;">Password</label>
                <input type="password" name="password" id="password" required>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            <p class="mt-3"><a href="../index.php">&larr; Back to Home</a></p>
        </div>
    </div>

</body>

</html>