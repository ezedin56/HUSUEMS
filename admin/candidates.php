<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../config/db.php';
require_once '../includes/functions.php';

// Get Current Election
$election_id = isset($_GET['election_id']) ? (int) $_GET['election_id'] : 0;
$election = null;

if ($election_id) {
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE election_id = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch();
}

// Handle Add Candidate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add' && $election_id) {
    echo "Processing Add Candidate"; // Debug output
    $name = sanitizeInput($_POST['full_name']);
    $details = sanitizeInput($_POST['details']);
    $position = sanitizeInput($_POST['position']);

    if ($name && $position) {
        $stmt = $pdo->prepare("INSERT INTO candidates (election_id, full_name, position, details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$election_id, $name, $position, $details]);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $cand_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM candidates WHERE candidate_id = ?");
    $stmt->execute([$cand_id]);
    header("Location: candidates.php?election_id=$election_id");
    exit;
}

// Fetch Candidates
$candidates = [];
if ($election_id) {
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE election_id = ?");
    $stmt->execute([$election_id]);
    $candidates = $stmt->fetchAll();
} else {
    // If no election selected, show list of elections to choose from
    $stmt = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC");
    $allElections = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates | HUSUEMS</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .nav-candidates { background: rgba(255, 255, 255, 0.1); border-left-color: var(--accent); color: white; }
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
            <a href="candidates.php" class="nav-candidates">👥 Manage Candidates</a>
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
            <h1>Manage Candidates</h1>
        </div>

        <?php if (!$election_id): ?>
            <div class="card">
                <h3>Select an Election</h3>
                <p class="text-muted">Choose an election to manage its candidates.</p>
                <div class="table-responsive">
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($allElections as $e): ?>
                            <li style="padding: 1rem; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                <a href="?election_id=<?php echo $e['election_id']; ?>" style="font-size: 1.1rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($e['title']); ?>
                                </a>
                                <?php
                                $statusColor = $e['status'] == 'active' ? '#28a745' : ($e['status'] == 'closed' ? '#dc3545' : '#6c757d');
                                ?>
                                <span style="padding: 4px 8px; border-radius: 4px; color: white; background: <?php echo $statusColor; ?>; font-size: 0.8rem;">
                                    <?php echo ucfirst($e['status']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php else: ?>

            <div class="d-flex justify-between align-center mb-3">
                <h2 style="margin: 0; font-size: 1.25rem;">Candidates for: <span style="color: var(--primary);"><?php echo htmlspecialchars($election['title']); ?></span></h2>
                <a href="candidates.php" class="btn-primary" style="background: var(--text-light); text-decoration: none;">&larr; Back to List</a>
            </div>

            <div class="card">
                <h3>Add New Candidate</h3>
                <form action="" method="POST" class="d-flex" style="gap: 1rem; flex-wrap: wrap;">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="full_name" placeholder="Candidate Name" required style="flex: 2; min-width: 200px; margin-bottom: 0;">
                    
                    <select name="position" required style="flex: 1; min-width: 150px; padding: 0.75rem 1rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; margin-bottom: 0;">
                        <option value="President">President</option>
                        <option value="Vice President">Vice President</option>
                        <option value="Secretary">Secretary</option>
                    </select>

                    <input type="text" name="details" placeholder="Details/Party (Optional)" style="flex: 2; min-width: 200px; margin-bottom: 0;">
                    <button type="submit" class="btn-primary" style="flex: 1; min-width: 150px;">Add</button>
                </form>
            </div>

            <div class="card table-responsive">
                <h3>Candidate List</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidates)): ?>
                            <tr><td colspan="4" class="text-center text-muted">No candidates yet. Add one above.</td></tr>
                        <?php else: ?>
                            <?php foreach ($candidates as $candidate): ?>
                                <tr>
                                    <td><?php echo $candidate['candidate_id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($candidate['full_name']); ?></strong></td>
                                    <td><span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem;"><?php echo htmlspecialchars($candidate['position']); ?></span></td>
                                    <td><?php echo htmlspecialchars($candidate['details']); ?></td>
                                    <td>
                                        <a href="?election_id=<?php echo $election_id; ?>&delete=<?php echo $candidate['candidate_id']; ?>"
                                            class="btn-primary"
                                            style="background: var(--danger); text-decoration: none; padding: 0.25rem 0.5rem; font-size: 0.8rem;"
                                            onclick="return confirm('Delete this candidate?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </main>

</div>

</body>
</html>