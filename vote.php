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

// Handle Vote Submission (Per Position)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['election_id']) && isset($_POST['position']) && isset($_POST['candidate_id'])) {
    $election_id = (int) $_POST['election_id'];
    $position = $_POST['position'];
    $candidate_id = (int) $_POST['candidate_id'];
    
    // Check if already voted for this specific position
    if (hasVotedForPosition($pdo, $election_id, $student_id, $position)) {
        $error_msg = "You have already voted for " . htmlspecialchars($position) . "!";
    } else {
        $code = generateTrackingCode();
        try {
            $stmt = $pdo->prepare("INSERT INTO votes (election_id, candidate_id, student_id, position, tracking_code) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$election_id, $candidate_id, $student_id, $position, $code]);
            
            $success_msg = "Vote for " . htmlspecialchars($position) . " cast successfully!";
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
                <div class="success-card">
                    <div class="success-icon">
                        🎉
                    </div>
                    <div class="success-content">
                        <h2>Vote Submitted Successfully!</h2>
                        <p>Thank you for participating in the election.</p>
                        <a href="index.php" class="btn-home">Return to Home</a>
                    </div>
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
                <div class="election-card text-center" style="padding: 3rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📭</div>
                    <h2>No Active Elections</h2>
                    <p class="text-muted">There are no elections currently open for voting. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($activeElections as $election): ?>
                    <div class="election-card">
                        <div class="election-header">
                            <h2 class="election-title"><?php echo htmlspecialchars($election['title']); ?></h2>
                            <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>
                        </div>

                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE election_id = ? ORDER BY field(position, 'President', 'Vice President', 'Secretary')");
                        $stmt->execute([$election['election_id']]);
                        $allCandidates = $stmt->fetchAll();
                        
                        // Group candidates by position
                        $candidatesByPosition = [];
                        foreach ($allCandidates as $cand) {
                            $candidatesByPosition[$cand['position']][] = $cand;
                        }
                        ?>
                        
                        <?php foreach ($candidatesByPosition as $position => $candidates): ?>
                            <?php
                            // Check if already voted for this position
                            $hasVotedForThisPosition = hasVotedForPosition($pdo, $election['election_id'], $student_id, $position);
                            ?>
                            
                            <div class="position-section">
                                <h3 style="margin-top: 2rem; border-bottom: 2px solid var(--accent); padding-bottom: 0.5rem; display: inline-block;">
                                    <?php echo htmlspecialchars($position); ?> Candidates
                                </h3>
                                
                                <?php if ($hasVotedForThisPosition): ?>
                                    <div class="alert alert-success" style="margin-top: 1rem; text-align: center;">
                                        <h4>✅ Vote Recorded for <?php echo htmlspecialchars($position); ?></h4>
                                        <p>You have already voted for this position.</p>
                                    </div>
                                <?php else: ?>
                                    <form action="" method="POST" class="position-form">
                                        <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
                                        <input type="hidden" name="position" value="<?php echo htmlspecialchars($position); ?>">
                                        
                                        <div class="candidate-grid">
                                            <?php foreach ($candidates as $candidate): ?>
                                                <label class="candidate-option">
                                                    <input type="radio" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>" required>
                                                    <div class="candidate-card">
                                                        <div class="check-icon">✓</div>
                                                        <img src="<?php echo $candidate['photo_url'] ? htmlspecialchars($candidate['photo_url']) : 'assets/default_avatar.png'; ?>" 
                                                             alt="Candidate" class="candidate-photo"
                                                             onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($candidate['full_name']); ?>&background=random&color=fff&background=002147'">
                                                        <div class="candidate-info">
                                                            <div class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></div>
                                                            <div class="candidate-details"><?php echo htmlspecialchars($candidate['details']); ?></div>
                                                        </div>
                                                    </div>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <div class="vote-actions">
                                            <button type="submit" class="btn-vote" onclick="return confirm('Are you sure you want to vote for this <?php echo htmlspecialchars($position); ?>? This cannot be undone.');">
                                                Confirm My Vote for <?php echo htmlspecialchars($position); ?>
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.position-form');
            forms.forEach(form => {
                const radios = form.querySelectorAll('input[type="radio"]');
                const submitBtn = form.querySelector('button[type="submit"]');
                
                // Initial State
                if(submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.5';
                    submitBtn.style.cursor = 'not-allowed';

                    radios.forEach(radio => {
                        radio.addEventListener('change', () => {
                            // Enable button when a candidate is selected
                            submitBtn.disabled = false;
                            submitBtn.style.opacity = '1';
                            submitBtn.style.cursor = 'pointer';
                            submitBtn.classList.add('pulse-animation');
                        });
                    });
                }
            });
        });
    </script>

</body>
</html>