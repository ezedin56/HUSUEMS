<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

// Auth Check
if (!isset($_SESSION['voter_verified']) || $_SESSION['voter_verified'] !== true) {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$full_name = $_SESSION['full_name'];
$success_msg = '';
$error_msg = '';
$tracking_code = '';

// Handle Vote Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_id']) && isset($_POST['candidate_id'])) {
    $election_id = (int) $_POST['election_id'];
    $candidate_id = (int) $_POST['candidate_id'];

    if (hasVoted($pdo, $election_id, $student_id)) {
        $error_msg = "You have already voted in this election!";
    } else {
        $code = generateTrackingCode();
        try {
            $stmt = $pdo->prepare("INSERT INTO votes (election_id, candidate_id, student_id, tracking_code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$election_id, $candidate_id, $student_id, $code]);
            $success_msg = "Vote Cast Successfully!";
            $tracking_code = $code;
        } catch (Exception $e) {
            $error_msg = "Error casting vote: " . $e->getMessage();
        }
    }
}

// Fetch Active Elections
$stmt = $pdo->query("SELECT * FROM elections WHERE status = 'active'");
$activeElections = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote | Haramaya University</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="#" class="brand">
                🏛️ HUSUEMS
            </a>
            <div class="user-info">
                <span>Welcome, <strong><?php echo htmlspecialchars($full_name); ?></strong> (<?php echo htmlspecialchars($student_id); ?>)</span>
                <a href="index.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">

            <!-- Success Message -->
            <?php if ($success_msg): ?>
                <div class="tracking-box">
                    <h2 style="color: var(--success)">✅ Vote Submitted!</h2>
                    <p>Thank you for participating. Please save your tracking code:</p>
                    <div class="code-display"><?php echo $tracking_code; ?></div>
                    <p class="text-muted">You can use this code to verify your vote later.</p>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error_msg): ?>
                <div class="alert alert-danger">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- Active Elections -->
            <?php if (empty($activeElections)): ?>
                <div class="election-card text-center">
                    <h2>No Active Elections</h2>
                    <p>There are no elections currently open for voting. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($activeElections as $election): ?>
                    <div class="election-card">
                        <h2 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h2>
                        <p class="text-muted"><?php echo htmlspecialchars($election['description']); ?></p>

                        <?php
                        $has_voted = hasVoted($pdo, $election['election_id'], $student_id);
                        if ($has_voted):
                        ?>
                            <div class="alert alert-success" style="margin-top: 1rem;">
                                ✅ You have already participated in this election.
                            </div>
                        <?php else: ?>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM candidates WHERE election_id = ?");
                            $stmt->execute([$election['election_id']]);
                            $candidates = $stmt->fetchAll();
                            ?>
                            
                            <form action="" method="POST">
                                <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
                                
                                <div class="candidate-grid">
                                    <?php foreach ($candidates as $candidate): ?>
                                        <label class="candidate-option">
                                            <input type="radio" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>" required>
                                            <div class="candidate-card">
                                                <img src="<?php echo $candidate['photo_url'] ? htmlspecialchars($candidate['photo_url']) : 'assets/default_avatar.png'; ?>" 
                                                     alt="Candidate" class="candidate-photo"
                                                     onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($candidate['full_name']); ?>&background=random'">
                                                <div class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                                                <div class="candidate-details"><?php echo htmlspecialchars($candidate['details']); ?></div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <button type="submit" class="btn-vote" onclick="return confirm('Are you sure you want to cast your vote for this candidate?');">
                                    Confirm Vote
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

</body>
</html>