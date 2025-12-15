<?php
session_start();
// If this is a simple GET to the root, send users to the modern login page.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: /login.html');
    exit;
}
require_once 'includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = sanitizeInput($_POST['student_id']);
    $full_name = sanitizeInput($_POST['full_name']);

    // Load DB only when verification is requested. Wrap in try/catch so
    // missing PDO drivers or misconfiguration don't break the login page.
    try {
        require_once 'config/db.php';

        if (verifyVoter($pdo, $student_id, $full_name)) {
            $_SESSION['voter_verified'] = true;
            $_SESSION['student_id'] = $student_id;
            $_SESSION['full_name'] = $full_name;
            header('Location: vote.php');
            exit;
        } else {
            $error = "Verification failed. Please check your details.";
        }
    } catch (Exception $e) {
        // Do not expose internals. Provide a friendly message and allow the
        // login form to render so users can still see the page.
        $error = "Server configuration error. Please contact the election administrator.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Verification - Haramaya University</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">🗳️</div>
            <h2 class="auth-title">Public Election System</h2>
            <p class="auth-subtitle">Haramaya University</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <input type="text" name="student_id" placeholder="Enter Student ID" required class="form-control">
                <input type="text" name="full_name" placeholder="Enter Full Name" required class="form-control">
                <button type="submit" class="btn-primary">Verify Identity</button>
            </form>
        </div>
    </div>

</body>

</html>