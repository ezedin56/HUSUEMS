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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elections | HUSUEMS</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .nav-elections { background: rgba(255, 255, 255, 0.1); border-left-color: var(--accent); color: white; }
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
            <a href="elections.php" class="nav-elections">🗳️ Manage Elections</a>
            <a href="candidates.php">👥 Manage Candidates</a>
            <a href="voters.php">🎓 Manage Voters</a>
            <a href="../index.php" target="_blank">🌐 View Public Site</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="btn-logout" style="text-align: center; display: block;">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-header">
            <h1>Manage Elections</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Create New Election</h3>
            <form action="" method="POST" class="d-flex" style="gap: 1rem; flex-wrap: wrap;">
                <input type="hidden" name="action" value="create">
                <input type="text" name="title" placeholder="Election Title" required style="flex: 2; min-width: 200px;">
                <input type="text" name="description" placeholder="Description (Optional)" style="flex: 3; min-width: 200px;">
                <button type="submit" class="btn-primary" style="flex: 1; min-width: 100px;">Create</button>
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
                                <small class="text-muted"><?php echo htmlspecialchars($election['description']); ?></small>
                            </td>
                            <td>
                                <?php
                                $statusColor = $election['status'] == 'active' ? '#28a745' : ($election['status'] == 'closed' ? '#dc3545' : '#6c757d');
                                ?>
                                <span style="padding: 4px 8px; border-radius: 4px; color: white; background: <?php echo $statusColor; ?>; font-size: 0.85rem;">
                                    <?php echo ucfirst($election['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if ($election['status'] !== 'active'): ?>
                                        <a href="?id=<?php echo $election['election_id']; ?>&status=active" class="btn-primary" style="background: var(--success); text-decoration: none; padding: 0.25rem 0.5rem; font-size: 0.8rem;">Activate</a>
                                    <?php else: ?>
                                        <a href="?id=<?php echo $election['election_id']; ?>&status=closed" class="btn-primary" style="background: var(--danger); text-decoration: none; padding: 0.25rem 0.5rem; font-size: 0.8rem;">Close</a>
                                    <?php endif; ?>

                                    <a href="candidates.php?election_id=<?php echo $election['election_id']; ?>" class="btn-primary" style="text-decoration: none; padding: 0.25rem 0.5rem; font-size: 0.8rem;">Candidates</a>
                                    
                                    <a href="?delete=<?php echo $election['election_id']; ?>" class="btn-primary" style="background: var(--text-light); text-decoration: none; padding: 0.25rem 0.5rem; font-size: 0.8rem;" onclick="return confirm('Delete this election?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

</div>

</body>
</html>