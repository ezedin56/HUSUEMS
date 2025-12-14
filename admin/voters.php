<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../includes/functions.php';

$message = '';

// Handle Add Single Voter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $sid = sanitizeInput($_POST['student_id']);
    $name = sanitizeInput($_POST['full_name']);

    if ($sid && $name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO voters (student_id, full_name) VALUES (?, ?)");
            $stmt->execute([$sid, $name]);
            $message = "Voter added successfully!";
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Handle CSV Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $count = 0;
        while (($row = fgetcsv($file)) !== FALSE) {
            if (count($row) >= 2) {
                $sid = sanitizeInput($row[0]);
                $name = sanitizeInput($row[1]);
                if ($sid && $name) {
                    try {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO voters (student_id, full_name) VALUES (?, ?)");
                        $stmt->execute([$sid, $name]);
                        $count++;
                    } catch (Exception $e) { }
                }
            }
        }
        fclose($file);
        $message = "$count voters imported successfully!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM voters WHERE voter_id = ?");
    $stmt->execute([$id]);
    header('Location: voters.php');
    exit;
}

// Fetch Voters
$stmt = $pdo->query("SELECT * FROM voters ORDER BY created_at DESC LIMIT 50");
$voters = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Voters | HUSUEMS</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .nav-voters { background: rgba(255, 255, 255, 0.1); border-left-color: var(--accent); color: white; }
    </style>
</head>
<body>

<div class="admin-layout">
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            🗳️ Election Admin
        </div>
        <nav class="sidebar-nav">
            <a href="index.php">📊 Dashboard</a>
            <a href="elections.php">🗳️ Manage Elections</a>
            <a href="candidates.php">👥 Manage Candidates</a>
            <a href="voters.php" class="nav-voters">🎓 Manage Voters</a>
            <a href="../index.php" target="_blank">🌐 View Public Site</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout" style="text-align: center; display: block;">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <h1>Manage Voters</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Card: Add Voter -->
            <div class="card">
                <h3>Add Single Voter</h3>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="student_id" placeholder="Student ID" required>
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <button type="submit" class="btn-primary">Add Voter</button>
                </form>
            </div>

            <!-- Card: Import CSV -->
            <div class="card">
                <h3>Import CSV</h3>
                <p class="text-muted" style="margin-bottom: 1rem;"><small>Format: StudentID, FullName (No header)</small></p>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <input type="file" name="csv_file" required style="margin-bottom: 1rem; width: 100%;">
                    <button type="submit" class="btn-primary" style="background-color: var(--secondary);">Upload CSV</button>
                </form>
            </div>
        </div>

        <div class="card">
            <h3>Recent Voters</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($voters as $voter): ?>
                            <tr>
                                <td><?php echo $voter['voter_id']; ?></td>
                                <td><?php echo htmlspecialchars($voter['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($voter['full_name']); ?></td>
                                <td>
                                    <a href="?delete=<?php echo $voter['voter_id']; ?>" class="btn-logout" style="background: var(--danger); border: none; padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="return confirm('Delete this voter?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</div>

</body>
</html>