
<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

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
    <title>Vote Now - Haramaya University</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <div class="container">
        <div class="d-flex justify-between align-center mb-3" style="border-bottom: 1px solid #eee; padding-bottom: 1rem;">
            <div>
                <h2>Welcome, <?php echo htmlspecialchars($full_name); ?></h2>
                <small class="text-muted">ID: <?php echo htmlspecialchars($student_id); ?></small>
            </div>
            <a href="index.php" class="btn btn-danger btn-sm">Logout</a>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success text-center">
                <h3>✅ <?php echo $success_msg; ?></h3>
                <p>Your Tracking Code:</p>
                <div class="tracking-code"><?php echo $tracking_code; ?></div>
                <p><small>Save this code to verify your vote later.</small></p>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger">
                <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($activeElections)): ?>
            <p class="text-center" style="color: #666; font-size: 1.2rem; margin-top: 50px;">No active elections at the moment.</p>
        <?php else: ?>
            <?php foreach ($activeElections as $election): ?>
                <div class="card election-card">
                    <h3><?php echo htmlspecialchars($election['title']); ?></h3>
                    <p class="mb-3"><?php echo htmlspecialchars($election['description']); ?></p>

                    <?php
                    $has_voted = hasVoted($pdo, $election['election_id'], $student_id);
                    if ($has_voted):
                        ?>
                        <div class="alert alert-secondary d-inline-block">You have already voted in this election</div>
                    <?php else: ?>
                        <?php
                        // Fetch Candidates for this election
                        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE election_id = ?");
                        $stmt->execute([$election['election_id']]);
                        $candidates = $stmt->fetchAll();
                        ?>

                        <form action="" method="POST">
                            <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
                            <?php foreach ($candidates as $candidate): ?>
                                <label class="candidate-option-label">
                                    <input type="radio" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>" required>
                                    <div>
                                        <strong><?php echo htmlspecialchars($candidate['full_name']); ?></strong>
                                        <?php if ($candidate['details']): ?>
                                            <br><span style="color: #666; font-size: 0.9em;"><?php echo htmlspecialchars($candidate['details']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>

                            <button type="submit" class="btn btn-success mt-3" onclick="return confirm('Confirm your vote?');">Submit Vote</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>

</html>