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

// Get candidate vote counts for each election
$candidateVotes = [];
foreach ($activeElections as $election) {
    $stmt = $pdo->prepare("
        SELECT candidate_id, COUNT(*) as vote_count 
        FROM votes 
        WHERE election_id = ? 
        GROUP BY candidate_id
    ");
    $stmt->execute([$election['election_id']]);
    $votes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $candidateVotes[$election['election_id']] = $votes;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote | Haramaya University Student Election</title>
    <link rel="stylesheet" href="vote.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="election-header">
            <div class="university-logo">
                <i class="fas fa-university"></i>
                <h1>Haramaya University</h1>
            </div>
            <div class="election-info">
                <h2>Student Election Voting System</h2>
                <div class="user-info">
                    <span><i class="fas fa-user"></i> Welcome, <strong><?php echo htmlspecialchars($full_name); ?></strong></span>
                    <span class="student-id"><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($student_id); ?></span>
                    <a href="index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </header>

        <main class="election-content">
            <?php if ($success_msg): ?>
                <div class="notification success">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <h3>Vote Submitted Successfully!</h3>
                        <p><?php echo $success_msg; ?></p>
                        <?php if ($tracking_code): ?>
                            <p class="tracking-code"><i class="fas fa-barcode"></i> Tracking Code: <strong><?php echo $tracking_code; ?></strong></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="notification error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h3>Voting Error</h3>
                        <p><?php echo $error_msg; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($activeElections)): ?>
                <div class="no-elections">
                    <div class="no-elections-icon">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h2>No Active Elections</h2>
                    <p>There are no elections currently open for voting. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php foreach ($activeElections as $election): 
                    // Get candidates for this election
                    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE election_id = ? ORDER BY field(position, 'President', 'Vice President', 'Secretary')");
                    $stmt->execute([$election['election_id']]);
                    $allCandidates = $stmt->fetchAll();
                    
                    // Group candidates by position
                    $candidatesByPosition = [];
                    foreach ($allCandidates as $cand) {
                        $candidatesByPosition[$cand['position']][] = $cand;
                    }
                    
                    // Get total votes per position
                    $positionVotes = [];
                    foreach ($candidatesByPosition as $position => $candidates) {
                        $totalVotes = 0;
                        foreach ($candidates as $candidate) {
                            $voteCount = $candidateVotes[$election['election_id']][$candidate['candidate_id']] ?? 0;
                            $totalVotes += $voteCount;
                        }
                        $positionVotes[$position] = $totalVotes;
                    }
                ?>
                <div class="election-section" id="election-<?php echo $election['election_id']; ?>">
                    <div class="election-title-section">
                        <h2><i class="fas fa-vote-yea"></i> <?php echo htmlspecialchars($election['title']); ?></h2>
                        <p class="election-description"><?php echo htmlspecialchars($election['description']); ?></p>
                        <div class="election-meta">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($election['start_date'])); ?> - <?php echo date('F j, Y', strtotime($election['end_date'])); ?></span>
                            <span><i class="fas fa-users"></i> <?php echo count($allCandidates); ?> Candidates</span>
                        </div>
                    </div>

                    <?php foreach ($candidatesByPosition as $position => $candidates): 
                        $hasVotedForThisPosition = hasVotedForPosition($pdo, $election['election_id'], $student_id, $position);
                        $positionTotalVotes = $positionVotes[$position] ?? 0;
                    ?>
                    <div class="position-section" id="<?php echo strtolower(str_replace(' ', '_', $position)); ?>">
                        <div class="position-header">
                            <h3><i class="fas fa-flag"></i> <?php echo htmlspecialchars($position); ?></h3>
                            <p class="position-description">Vote for your candidate for Student <?php echo htmlspecialchars($position); ?></p>
                            <div class="position-stats">
                                <span><i class="fas fa-user-friends"></i> <?php echo count($candidates); ?> Candidates</span>
                                <span><i class="fas fa-vote-yea"></i> <?php echo $positionTotalVotes; ?> Total Votes</span>
                                <?php if ($hasVotedForThisPosition): ?>
                                <span class="voted-badge"><i class="fas fa-check-circle"></i> You have voted</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="candidates-container">
                            <?php foreach ($candidates as $candidate): 
                                $voteCount = $candidateVotes[$election['election_id']][$candidate['candidate_id']] ?? 0;
                                $percentage = $positionTotalVotes > 0 ? round(($voteCount / $positionTotalVotes) * 100, 1) : 0;
                            ?>
                            <div class="candidate-card" data-position="<?php echo htmlspecialchars($position); ?>" data-candidate-id="<?php echo $candidate['candidate_id']; ?>">
                                <div class="candidate-header">
                                    <div class="candidate-image">
                                        <div class="image-placeholder">
                                            <?php 
                                            // Different icons for different positions
                                            if ($position === 'President') {
                                                echo '<i class="fas fa-crown"></i>';
                                            } elseif ($position === 'Vice President') {
                                                echo '<i class="fas fa-user-tie"></i>';
                                            } else {
                                                echo '<i class="fas fa-clipboard-list"></i>';
                                            }
                                            ?>
                                        </div>
                                        <div class="candidate-badge">
                                            <?php echo substr($position, 0, 1); ?>
                                        </div>
                                    </div>
                                    <div class="candidate-basic-info">
                                        <h4 class="candidate-name"><?php echo htmlspecialchars($candidate['full_name']); ?></h4>
                                        <p class="candidate-year"><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($candidate['details']); ?></p>
                                        <div class="candidate-tag">Candidate for <?php echo htmlspecialchars($position); ?></div>
                                        <div class="candidate-id">ID: HU-<?php echo strtoupper(substr($position, 0, 3)); ?>-<?php echo str_pad($candidate['candidate_id'], 3, '0', STR_PAD_LEFT); ?></div>
                                    </div>
                                </div>
                                
                                <div class="candidate-description">
                                    <p><?php echo htmlspecialchars($candidate['platform'] ?? 'No platform description available.'); ?></p>
                                </div>
                                
                                <div class="candidate-votes">
                                    <div class="vote-progress">
                                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="vote-stats">
                                        <span class="vote-count">Votes: <?php echo $voteCount; ?> (<?php echo $percentage; ?>%)</span>
                                        <span class="vote-trend">
                                            <?php if ($voteCount > 0): ?>
                                            <i class="fas fa-chart-line"></i> Active
                                            <?php else: ?>
                                            <i class="fas fa-minus"></i> No votes yet
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="candidate-actions">
                                    <?php if (!$hasVotedForThisPosition): ?>
                                    <form method="POST" class="vote-form" onsubmit="return confirmVote('<?php echo htmlspecialchars($position); ?>', '<?php echo htmlspecialchars($candidate['full_name']); ?>');">
                                        <input type="hidden" name="election_id" value="<?php echo $election['election_id']; ?>">
                                        <input type="hidden" name="position" value="<?php echo htmlspecialchars($position); ?>">
                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['candidate_id']; ?>">
                                        <button type="submit" class="vote-btn">
                                            <i class="fas fa-check-circle"></i> 
                                            Vote for <?php echo htmlspecialchars(explode(' ', $candidate['full_name'])[0]); ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <div class="already-voted">
                                        <i class="fas fa-check-double"></i> Already Voted for this Position
                                    </div>
                                    <?php endif; ?>
                                    <div class="action-buttons">
                                        <button class="platform-btn" onclick="showPlatform('<?php echo htmlspecialchars($candidate['full_name']); ?>', `<?php echo htmlspecialchars($candidate['platform'] ?? 'No platform available.'); ?>`)">
                                            <i class="fas fa-list-alt"></i> Platform
                                        </button>
                                        <button class="detail-btn" onclick="showDetails('<?php echo htmlspecialchars($candidate['full_name']); ?>', '<?php echo htmlspecialchars($candidate['details']); ?>')">
                                            <i class="fas fa-info-circle"></i> Details
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="candidate-selection">
                                    <div class="selection-instruction">
                                        <i class="fas fa-mouse-pointer"></i> Click the vote button to select this candidate
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
                
                <div class="election-instructions">
                    <h4><i class="fas fa-info-circle"></i> Election Instructions</h4>
                    <ul>
                        <li><i class="fas fa-check-square"></i> You can vote for one candidate per position</li>
                        <li><i class="fas fa-exclamation-circle"></i> Once submitted, your vote cannot be changed</li>
                        <li><i class="fas fa-lock"></i> Your vote is confidential and secure</li>
                        <li><i class="fas fa-barcode"></i> Save your tracking code for verification</li>
                        <li><i class="fas fa-clock"></i> Elections close on their specified end dates</li>
                    </ul>
                </div>
            <?php endif; ?>
        </main>
        
        <footer class="election-footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h5>Haramaya University</h5>
                    <p>Student Government Elections</p>
                    <p>Secure Voting System</p>
                </div>
                <div class="footer-section">
                    <h5>Quick Links</h5>
                    <a href="#president"><i class="fas fa-crown"></i> President</a>
                    <a href="#vice_president"><i class="fas fa-user-tie"></i> Vice President</a>
                    <a href="#secretary"><i class="fas fa-clipboard-list"></i> Secretary</a>
                </div>
                <div class="footer-section">
                    <h5>Contact Support</h5>
                    <p><i class="fas fa-envelope"></i> elections@haramaya.edu.et</p>
                    <p><i class="fas fa-phone"></i> +251-22-111-1234</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>Haramaya University Student Election System &copy; <?php echo date('Y'); ?></p>
                <p class="footer-note"><i class="fas fa-shield-alt"></i> Secure Encrypted Voting Platform</p>
            </div>
        </footer>
    </div>
    
    <script>
        function confirmVote(position, candidateName) {
            return confirm(`Are you sure you want to vote for ${candidateName} as ${position}?\n\nThis action cannot be undone.`);
        }
        
        function showPlatform(candidateName, platform) {
            alert(`Platform for ${candidateName}:\n\n${platform}`);
        }
        
        function showDetails(candidateName, details) {
            alert(`Candidate Details for ${candidateName}:\n\n${details}`);
        }
        
        // Smooth scrolling for navigation links
        document.querySelectorAll('.footer-section a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>