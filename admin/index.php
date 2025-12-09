<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

    <div class="admin-layout">
        <div class="sidebar">
            <h2>Election Admin</h2>
            <a href="index.php" class="active">Dashboard</a>
            <a href="elections.php">Manage Elections</a>
            <a href="candidates.php">Manage Candidates</a>
            <a href="voters.php">Manage Voters</a>
            <a href="../index.php" target="_blank">View Public Site</a>
            <a href="logout.php" style="color: #ff6b6b; margin-top: 20px;">Logout</a>
        </div>

        <div class="main-content">
            <h1>Dashboard</h1>

            <div class="card">
                <h3>System Status</h3>
                <p>Welcome to the Haramaya University Election Administration Panel.</p>
            </div>

            <div class="stat-box blue">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM elections WHERE status = 'active'");
                $activeElections = $stmt->fetchColumn();
                ?>
                <span class="stat-number"><?php echo $activeElections; ?></span>
                <span class="stat-label">Active Elections</span>
            </div>

            <div class="stat-box green">
                <?php
                $stmt = $pdo->query("SELECT COUNT(*) FROM votes");
                $totalVotes = $stmt->fetchColumn();
                ?>
                <span class="stat-number"><?php echo $totalVotes; ?></span>
                <span class="stat-label">Total Votes Cast</span>
            </div>

        </div>
    </div>

</body>

</html>