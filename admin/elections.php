<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../includes/functions.php';

$message = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $status = 'inactive';

    if ($title) {
        $stmt = $pdo->prepare("INSERT INTO elections (title, description, status) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $status]);
        $message = "Election created successfully!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM elections WHERE election_id = ?");
    $stmt->execute([$id]);
    header('Location: elections.php');
    exit;
}

// Handle Status Change
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $status = $_GET['status']; // active, inactive, closed
    $stmt = $pdo->prepare("UPDATE elections SET status = ? WHERE election_id = ?");
    $stmt->execute([$status, $id]);
    header('Location: elections.php');
    exit;
}

// Fetch All Elections
$stmt = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC");
$elections = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Elections</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

    <div class="admin-layout">
        <div class="sidebar">
            <h2>Election Admin</h2>
            <a href="index.php">Dashboard</a>
            <a href="elections.php" class="active">Manage Elections</a>
            <a href="candidates.php">Manage Candidates</a>
            <a href="voters.php">Manage Voters</a>
            <a href="../index.php" target="_blank">View Public Site</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="main-content">
            <h1>Manage Elections</h1>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="card">
                <h3>Create New Election</h3>
                <form action="" method="POST" class="d-flex gap-2">
                    <input type="hidden" name="action" value="create">
                    <input type="text" name="title" placeholder="Election Title" required
                        style="flex: 2; margin-bottom: 0;">
                    <input type="text" name="description" placeholder="Description (Optional)"
                        style="flex: 3; margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Create</button>
                </form>
            </div>

            <h3>Existing Elections</h3>
            <div class="card table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($elections as $election): ?>
                            <tr>
                                <td><?php echo $election['election_id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($election['title']); ?></strong><br>
                                    <small
                                        class="text-muted"><?php echo htmlspecialchars($election['description']); ?></small>
                                </td>
                                <td>
                                    <?php
                                    $statusColor = $election['status'] == 'active' ? '#28a745' : ($election['status'] == 'closed' ? '#dc3545' : '#6c757d');
                                    ?>
                                    <span
                                        style="padding: 4px 8px; border-radius: 4px; color: white; background: <?php echo $statusColor; ?>">
                                        <?php echo ucfirst($election['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($election['status'] !== 'active'): ?>
                                        <a href="?id=<?php echo $election['election_id']; ?>&status=active"
                                            class="btn btn-success btn-sm">Activate</a>
                                    <?php else: ?>
                                        <a href="?id=<?php echo $election['election_id']; ?>&status=closed"
                                            class="btn btn-danger btn-sm">Close</a>
                                    <?php endif; ?>

                                    <a href="candidates.php?election_id=<?php echo $election['election_id']; ?>"
                                        class="btn btn-primary btn-sm">Candidates</a>
                                    <a href="?delete=<?php echo $election['election_id']; ?>"
                                        class="btn btn-secondary btn-sm"
                                        onclick="return confirm('Are you sure?');">Delete</a>
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