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
    $name = sanitizeInput($_POST['full_name']);
    $details = sanitizeInput($_POST['details']);

    if ($name) {
        $stmt = $pdo->prepare("INSERT INTO candidates (election_id, full_name, details) VALUES (?, ?, ?)");
        $stmt->execute([$election_id, $name, $details]);
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
    <title>Manage Candidates</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body>

    <div class="admin-layout">
        <div class="sidebar">
            <h2>Election Admin</h2>
            <a href="index.php">Dashboard</a>
            <a href="elections.php">Manage Elections</a>
            <a href="candidates.php" class="active">Manage Candidates</a>
            <a href="voters.php">Manage Voters</a>
            <a href="../index.php" target="_blank">View Public Site</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="content main-content">

            <?php if (!$election_id): ?>
                <h1>Select an Election</h1>
                <div class="card">
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($allElections as $e): ?>
                            <li style="padding: 10px; border-bottom: 1px solid #eee;">
                                <a href="?election_id=<?php echo $e['election_id']; ?>" style="font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($e['title']); ?>
                                </a>
                                <span style="color: #666; font-size: 0.9em; float: right;">
                                    <?php echo ucfirst($e['status']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>

                <h1>Candidates for: <?php echo htmlspecialchars($election['title']); ?></h1>
                <p><a href="candidates.php">&larr; Back to Election List</a></p>

                <div class="card">
                    <h3>Add Candidate</h3>
                    <form action="" method="POST" class="d-flex gap-2">
                        <input type="hidden" name="action" value="add">
                        <input type="text" name="full_name" placeholder="Candidate Name" required style="margin-bottom: 0;">
                        <input type="text" name="details" placeholder="Details/Party (Optional)" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary">Add Candidate</button>
                    </form>
                </div>

                <h3>Candidate List</h3>
                <div class="card table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Details</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidates as $candidate): ?>
                                <tr>
                                    <td><?php echo $candidate['candidate_id']; ?></td>
                                    <td><?php echo htmlspecialchars($candidate['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($candidate['details']); ?></td>
                                    <td>
                                        <a href="?election_id=<?php echo $election_id; ?>&delete=<?php echo $candidate['candidate_id']; ?>"
                                            class="btn btn-danger btn-sm"
                                            onclick="return confirm('Delete this candidate?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </div>
    </div>

</body>

</html>