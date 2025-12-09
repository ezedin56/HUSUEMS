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
            // Assume CSV format: Student ID, Full Name
            if (count($row) >= 2) {
                $sid = sanitizeInput($row[0]);
                $name = sanitizeInput($row[1]);
                if ($sid && $name) {
                    try {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO voters (student_id, full_name) VALUES (?, ?)");
                        $stmt->execute([$sid, $name]);
                        $count++;
                    } catch (Exception $e) {
                        // Ignore duplicates or errors
                    }
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

// Fetch Voters (Limit 50 for display)
$stmt = $pdo->query("SELECT * FROM voters ORDER BY created_at DESC LIMIT 50");
$voters = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Voters</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

    <div class="admin-layout">
        <div class="sidebar">
            <h2>Election Admin</h2>
            <a href="index.php">Dashboard</a>
            <a href="elections.php">Manage Elections</a>
            <a href="candidates.php">Manage Candidates</a>
            <a href="voters.php" class="active">Manage Voters</a>
            <a href="../index.php" target="_blank">View Public Site</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="main-content">
            <h1>Manage Voters</h1>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="d-flex gap-2 mb-3" style="flex-wrap: wrap;">
                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3>Add Single Voter</h3>
                    <form action="" method="POST">
                        <input type="hidden" name="action" value="add">
                        <input type="text" name="student_id" placeholder="Student ID" required>
                        <input type="text" name="full_name" placeholder="Full Name" required>
                        <button type="submit" class="btn btn-primary btn-block">Add Voter</button>
                    </form>
                </div>

                <div class="card" style="flex: 1; min-width: 300px;">
                    <h3>Import CSV</h3>
                    <p class="text-muted"><small>Format: StudentID, FullName (No header)</small></p>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload">
                        <input type="file" name="csv_file" required style="margin-bottom: 10px;">
                        <br>
                        <button type="submit" class="btn btn-secondary btn-block">Upload CSV</button>
                    </form>
                </div>
            </div>

            <h3>Recent Voters (Showing last 50)</h3>
            <div class="card table-responsive">
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
                                    <a href="?delete=<?php echo $voter['voter_id']; ?>" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this voter?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>